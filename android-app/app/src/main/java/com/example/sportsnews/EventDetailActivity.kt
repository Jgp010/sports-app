package com.example.sportsnews

import android.app.Activity
import android.content.Intent
import android.os.Bundle
import android.os.Handler
import android.os.Looper
import android.text.InputType
import android.view.View
import android.widget.Button
import android.widget.CheckBox
import android.widget.LinearLayout
import android.widget.ProgressBar
import android.widget.ScrollView
import android.widget.TextView
import android.widget.Toast
import com.example.sportsnews.model.Event
import com.example.sportsnews.network.ApiClient
import com.example.sportsnews.network.SessionStore
import com.example.sportsnews.ui.asHeading
import com.example.sportsnews.ui.displayTime
import com.example.sportsnews.ui.dp
import com.example.sportsnews.ui.field
import com.example.sportsnews.ui.screen
import java.util.concurrent.Executors

class EventDetailActivity : Activity() {
    private val executor = Executors.newSingleThreadExecutor()
    private val main = Handler(Looper.getMainLooper())
    private lateinit var body: LinearLayout
    private var event: Event? = null

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        val root = screen("賽事詳情")
        body = LinearLayout(this).apply { orientation = LinearLayout.VERTICAL; setPadding(dp(20), dp(20), dp(20), dp(36)); addView(ProgressBar(this@EventDetailActivity)) }
        root.addView(ScrollView(this).apply { addView(body) }, LinearLayout.LayoutParams(-1, 0, 1f)); setContentView(root)
        load(intent.getStringExtra(EXTRA_SLUG).orEmpty())
    }

    private fun load(slug: String) {
        executor.execute { runCatching { ApiClient.fetchEvent(slug) }
            .onSuccess { loaded -> main.post { event = loaded; render(loaded) } }
            .onFailure { error -> main.post { body.removeAllViews(); body.addView(TextView(this).apply { text = ApiClient.userMessage(error, "無法取得賽事，請稍後再試。") }) } } }
    }

    private fun render(item: Event) {
        body.removeAllViews()
        body.addView(TextView(this).apply { text = item.sport.name; textSize = 14f })
        body.addView(TextView(this).apply { text = item.title; asHeading(28f) })
        body.addView(TextView(this).apply { text = getString(R.string.event_detail_meta, displayTime(item.eventStartAt), item.venue, displayTime(item.registrationOpenAt), displayTime(item.registrationCloseAt), item.registeredCount, item.capacity?.toString() ?: "不限"); setPadding(0, dp(10), 0, dp(16)) })
        body.addView(TextView(this).apply { text = item.description; textSize = 17f; setPadding(0, 0, 0, dp(20)) })
        if (!item.registrationOpen) {
            body.addView(TextView(this).apply { text = "目前不在報名期間或名額已滿。" })
            return
        }
        val phone = field("聯絡電話（必填）", InputType.TYPE_CLASS_PHONE)
        val organization = field("單位（必填）")
        val emergencyName = field("緊急聯絡人（必填）")
        val emergencyPhone = field("緊急聯絡電話（必填）", InputType.TYPE_CLASS_PHONE)
        val notes = field("備註").apply { minLines = 3; gravity = android.view.Gravity.TOP }
        body.addView(TextView(this).apply { text = "選擇賽事項目（至少一項）"; asHeading(19f); setPadding(0, dp(4), 0, dp(8)) })
        val selected = linkedMapOf<Long, CheckBox>()
        item.items.forEach { eventItem ->
            val price = "NT$ ${String.format("%,d", eventItem.currentFee)}"
            val earlyBird = if (eventItem.earlyBirdActive) "（早鳥價，至 ${displayTime(eventItem.earlyBirdEndsAt)}）" else ""
            CheckBox(this).apply {
                text = "${eventItem.name}｜$price$earlyBird${eventItem.description?.let { "\n$it" }.orEmpty()}"
                setPadding(0, dp(5), 0, dp(5))
                selected[eventItem.id] = this
                body.addView(this)
            }
        }
        val submit = Button(this).apply { text = if (SessionStore.isLoggedIn(this@EventDetailActivity)) "確認報名" else "登入後報名" }
        listOf(phone, organization, emergencyName, emergencyPhone, notes, submit).forEach { body.addView(it, LinearLayout.LayoutParams(-1, -2).apply { bottomMargin = dp(10) }) }
        submit.setOnClickListener {
            if (!SessionStore.isLoggedIn(this)) {
                startActivityForResult(Intent(this, LoginActivity::class.java), LOGIN_REQUEST)
                return@setOnClickListener
            }
            if (phone.text.isBlank() || organization.text.isBlank() || emergencyName.text.isBlank() || emergencyPhone.text.isBlank()) { Toast.makeText(this, "請完整填寫聯絡電話、單位及緊急聯絡資料", Toast.LENGTH_SHORT).show(); return@setOnClickListener }
            val selectedIds = selected.filterValues { it.isChecked }.keys.toList()
            if (selectedIds.isEmpty()) { Toast.makeText(this, "請至少選擇一個賽事項目", Toast.LENGTH_SHORT).show(); return@setOnClickListener }
            submit.isEnabled = false
            executor.execute { runCatching { ApiClient.registerEvent(SessionStore.token(this)!!, item.slug, phone.text.toString(), organization.text.toString(), emergencyName.text.toString(), emergencyPhone.text.toString(), selectedIds, notes.text.toString()) }
                .onSuccess { registration -> main.post { Toast.makeText(this, "報名成功：${registration.registrationNo}", Toast.LENGTH_LONG).show(); startActivity(Intent(this, MyRegistrationsActivity::class.java)); finish() } }
                .onFailure { error -> main.post { submit.isEnabled = true; if ((error as? com.example.sportsnews.network.ApiException)?.statusCode == 401) SessionStore.clear(this); Toast.makeText(this, ApiClient.userMessage(error, "報名失敗，請稍後再試。"), Toast.LENGTH_LONG).show() } } }
        }
    }

    override fun onActivityResult(requestCode: Int, resultCode: Int, data: Intent?) {
        super.onActivityResult(requestCode, resultCode, data)
        if (requestCode == LOGIN_REQUEST && resultCode == RESULT_OK) { event?.let(::render); Toast.makeText(this, "登入成功，請確認資料後報名", Toast.LENGTH_SHORT).show() }
    }

    override fun onDestroy() { executor.shutdownNow(); super.onDestroy() }
    companion object { const val EXTRA_SLUG = "event_slug"; private const val LOGIN_REQUEST = 201 }
}
