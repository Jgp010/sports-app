package com.example.sportsnews.network

import com.example.sportsnews.BuildConfig
import com.example.sportsnews.model.Event
import com.example.sportsnews.model.EventItem
import com.example.sportsnews.model.Member
import com.example.sportsnews.model.NewsPost
import com.example.sportsnews.model.Registration
import com.example.sportsnews.model.RegistrationItem
import com.example.sportsnews.model.Sport
import org.json.JSONArray
import org.json.JSONObject
import java.net.HttpURLConnection
import java.net.URI
import java.net.URLEncoder
import java.net.SocketTimeoutException
import java.io.IOException

class ApiException(val statusCode: Int, message: String) : RuntimeException(message)

object ApiClient {
    fun userMessage(error: Throwable, fallback: String): String =
        (error as? ApiException)?.message?.takeIf { it.isNotBlank() } ?: fallback

    fun fetchNews(): List<NewsPost> = array(request("news?per_page=50").getJSONArray("data"), ::parseNews)
    fun fetchNewsDetail(slug: String): NewsPost = parseNews(request("news/${encoded(slug)}").getJSONObject("data"))
    fun fetchEvents(): List<Event> = array(request("events?per_page=50").getJSONArray("data"), ::parseEvent)
    fun fetchEvent(slug: String): Event = parseEvent(request("events/${encoded(slug)}").getJSONObject("data"))

    fun login(email: String, password: String): String = request(
        "auth/login", "POST", JSONObject().put("email", email).put("password", password)
    ).getJSONObject("data").getString("token")

    fun register(name: String, email: String, password: String, phone: String): String = request(
        "auth/register", "POST", JSONObject().put("name", name).put("email", email).put("password", password).put("phone", phone)
    ).getJSONObject("data").getString("token")

    fun fetchMe(token: String): Member = parseMember(request("me", token = token).getJSONObject("data"))

    fun updateMe(token: String, member: Member): Member = parseMember(request(
        "me", "PUT", JSONObject().put("name", member.name).put("email", member.email)
            .putNullable("phone", member.phone).putNullable("birth_date", member.birthDate)
            .putNullable("gender", member.gender).putNullable("address", member.address), token
    ).getJSONObject("data"))

    fun registerEvent(token: String, slug: String, phone: String, organization: String, emergencyName: String, emergencyPhone: String, itemIds: List<Long>, notes: String): Registration =
        parseRegistration(request(
            "events/${encoded(slug)}/registrations", "POST",
            JSONObject().put("contact_phone", phone).put("organization", organization).put("emergency_contact_name", emergencyName)
                .put("emergency_contact_phone", emergencyPhone).put("item_ids", JSONArray(itemIds)).put("notes", notes), token
        ).getJSONObject("data"))

    fun fetchRegistrations(token: String): List<Registration> = array(request("me/registrations", token = token).getJSONArray("data"), ::parseRegistration)
    fun cancelRegistration(token: String, id: Long) { request("registrations/$id", "DELETE", token = token) }
    fun logout(token: String) { request("auth/logout", "POST", JSONObject(), token) }

    private fun request(path: String, method: String = "GET", json: JSONObject? = null, token: String? = null): JSONObject {
        val connection = URI(BuildConfig.API_BASE_URL + path).toURL().openConnection() as HttpURLConnection
        return try {
            connection.connectTimeout = 10_000
            connection.readTimeout = 15_000
            connection.requestMethod = method
            connection.setRequestProperty("Accept", "application/json")
            connection.setRequestProperty("Content-Type", "application/json; charset=utf-8")
            connection.setRequestProperty("X-App-Platform", "android")
            token?.let { connection.setRequestProperty("Authorization", "Bearer $it") }
            if (json != null) {
                connection.doOutput = true
                connection.outputStream.use { it.write(json.toString().toByteArray(Charsets.UTF_8)) }
            }
            val code = connection.responseCode
            val stream = if (code in 200..299) connection.inputStream else connection.errorStream
            val body = stream?.bufferedReader()?.use { it.readText() }.orEmpty()
            val root = body.takeIf { it.isNotBlank() }?.let { runCatching { JSONObject(it) }.getOrNull() }
            if (code !in 200..299) {
                val message = root?.optJSONObject("error")?.optString("message")?.takeIf { it.isNotBlank() }
                    ?: root?.let(::validationMessage)
                    ?: when (code) {
                        401 -> "登入狀態已失效，請重新登入。"
                        404 -> if (root == null) "服務目前無法使用，請稍後再試。" else "找不到指定資料。"
                        409, 422 -> "資料無法送出，請檢查後再試。"
                        in 500..599 -> "系統忙碌中，請稍後再試。"
                        else -> "目前無法完成操作，請稍後再試。"
                    }
                throw ApiException(code, message)
            }
            root ?: throw ApiException(code, "服務回應異常，請稍後再試。")
        } catch (error: ApiException) {
            throw error
        } catch (_: SocketTimeoutException) {
            throw ApiException(0, "連線逾時，請檢查網路後再試。")
        } catch (_: IOException) {
            throw ApiException(0, "目前無法連線，請檢查網路後再試。")
        } catch (_: Exception) {
            throw ApiException(0, "服務回應異常，請稍後再試。")
        } finally {
            connection.disconnect()
        }
    }

    private fun validationMessage(root: JSONObject): String? {
        val errors = root.optJSONObject("errors") ?: return null
        val keys = errors.keys()
        if (!keys.hasNext()) return null
        return errors.optJSONArray(keys.next())?.optString(0)
    }

    private fun parseMember(json: JSONObject) = Member(
        json.getLong("id"), json.getString("name"), json.getString("email"), json.nullable("phone"),
        json.nullable("birth_date"), json.nullable("gender"), json.nullable("address"),
        json.nullable("sso_provider"), json.optBoolean("has_sso_credential")
    )

    private fun parseEvent(json: JSONObject): Event {
        val sport = json.getJSONObject("sport")
        return Event(
            json.getLong("id"), json.getString("slug"), json.getString("title"), json.getString("description"),
            json.getString("venue"), Sport(sport.getLong("id"), sport.getString("name"), sport.getString("slug")),
            json.getString("event_start_at"), json.nullable("event_end_at"), json.getString("registration_open_at"),
            json.getString("registration_close_at"), if (json.isNull("capacity")) null else json.getInt("capacity"),
            json.getInt("registered_count"), json.getBoolean("registration_open"), array(json.optJSONArray("items") ?: JSONArray()) { item ->
                EventItem(item.getLong("id"), item.getString("name"), item.nullable("description"), item.getLong("registration_fee"),
                    item.nullableLong("early_bird_fee"), item.nullable("early_bird_ends_at"), item.getLong("current_fee"), item.optBoolean("early_bird_active"))
            }
        )
    }

    private fun parseRegistration(json: JSONObject) = Registration(
        json.getLong("id"), json.getString("registration_no"), json.getString("status"), json.getString("contact_phone"),
        json.nullable("organization"), json.getLong("total_amount"), json.nullable("emergency_contact_name"),
        json.nullable("emergency_contact_phone"), json.nullable("notes"), json.getString("registered_at"),
        array(json.optJSONArray("items") ?: JSONArray()) { item -> RegistrationItem(item.getLong("id"), item.getString("name"), item.getLong("unit_price")) },
        parseEvent(json.getJSONObject("event"))
    )

    private fun parseNews(json: JSONObject): NewsPost {
        val sport = json.getJSONObject("sport")
        return NewsPost(
            json.getLong("id"), json.getString("slug"), json.getString("title"), json.getString("summary"),
            json.nullable("content"), json.nullable("cover_url"),
            Sport(sport.getLong("id"), sport.getString("name"), sport.getString("slug")),
            json.nullable("event_start_at"), json.nullable("venue"), json.nullable("published_at")
        )
    }

    private fun <T> array(json: JSONArray, mapper: (JSONObject) -> T): List<T> = buildList {
        for (index in 0 until json.length()) add(mapper(json.getJSONObject(index)))
    }
    private fun encoded(value: String) = URLEncoder.encode(value, Charsets.UTF_8.name())
    private fun JSONObject.nullable(key: String): String? = if (has(key) && !isNull(key)) getString(key) else null
    private fun JSONObject.nullableLong(key: String): Long? = if (has(key) && !isNull(key)) getLong(key) else null
    private fun JSONObject.putNullable(key: String, value: String?): JSONObject = put(key, value ?: JSONObject.NULL)
}
