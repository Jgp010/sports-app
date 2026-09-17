package com.example.sportsnews

import android.app.Activity
import android.app.DatePickerDialog
import android.content.Intent
import android.os.Bundle
import android.os.Handler
import android.os.Looper
import android.text.InputType
import android.widget.Button
import android.widget.LinearLayout
import android.widget.ProgressBar
import android.widget.ScrollView
import android.widget.TextView
import android.widget.Toast
import android.widget.ArrayAdapter
import android.widget.Spinner
import com.example.sportsnews.model.Member
import com.example.sportsnews.network.ApiClient
import com.example.sportsnews.network.ApiException
import com.example.sportsnews.network.SessionStore
import com.example.sportsnews.ui.dp
import com.example.sportsnews.ui.field
import com.example.sportsnews.ui.screen
import com.example.sportsnews.ui.bottomNavigation
import com.example.sportsnews.ui.NavTab
import java.time.LocalDate
import java.util.concurrent.Executors

class ProfileActivity : Activity() {
    private val executor = Executors.newSingleThreadExecutor()
    private val main = Handler(Looper.getMainLooper())
    private lateinit var body: LinearLayout

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        val root = screen("會員中心", showBack = false)
        body = LinearLayout(this).apply { orientation = LinearLayout.VERTICAL; setPadding(dp(22), dp(22), dp(22), dp(32)) }
        root.addView(ScrollView(this).apply { addView(body) }, LinearLayout.LayoutParams(-1, 0, 1f)); root.addView(bottomNavigation(NavTab.PROFILE)); setContentView(root)
        ensureLogin()
    }

    private fun ensureLogin() {
        if (!SessionStore.isLoggedIn(this)) {
            body.removeAllViews(); body.addView(TextView(this).apply { text = "請先登入會員。" })
            startActivityForResult(Intent(this, LoginActivity::class.java), LOGIN_REQUEST)
        } else load()
    }

    private fun load() {
        body.removeAllViews(); body.addView(ProgressBar(this))
        val token = SessionStore.token(this) ?: return
        executor.execute { runCatching { ApiClient.fetchMe(token) }
            .onSuccess { member -> main.post { render(member) } }
            .onFailure { error -> main.post { if ((error as? ApiException)?.statusCode == 401) { SessionStore.clear(this); ensureLogin() } else { body.removeAllViews(); body.addView(TextView(this).apply { text = ApiClient.userMessage(error, "無法取得會員資料，請稍後再試。") }) } } } }
    }

    private fun render(member: Member) {
        body.removeAllViews()
        val name = field("姓名").apply { setText(member.name) }
        val email = field("Email", InputType.TYPE_CLASS_TEXT or InputType.TYPE_TEXT_VARIATION_EMAIL_ADDRESS).apply { setText(member.email) }
        val phone = field("電話", InputType.TYPE_CLASS_PHONE).apply { setText(member.phone.orEmpty()) }
        val birthDate = field("生日（點擊選擇）").apply {
            setText(member.birthDate.orEmpty()); isFocusable = false; isClickable = true
            setOnClickListener {
                val initial = runCatching { LocalDate.parse(text.toString()) }.getOrElse { LocalDate.of(2000, 1, 1) }
                DatePickerDialog(this@ProfileActivity, { _, year, month, day -> setText("%04d-%02d-%02d".format(year, month + 1, day)) }, initial.year, initial.monthValue - 1, initial.dayOfMonth).show()
            }
        }
        val genderCodes = listOf("", "male", "female", "other", "undisclosed")
        val gender = Spinner(this).apply {
            adapter = ArrayAdapter(this@ProfileActivity, android.R.layout.simple_spinner_dropdown_item, listOf("請選擇性別", "男性", "女性", "其他", "不透露"))
            setSelection(genderCodes.indexOf(member.gender).coerceAtLeast(0))
        }
        val address = field("地址").apply { setText(member.address.orEmpty()) }
        listOf(name, email, phone, birthDate, gender, address).forEach { body.addView(it, LinearLayout.LayoutParams(-1, -2).apply { bottomMargin = dp(10) }) }
        val save = Button(this).apply { text = "儲存基本資料" }
        val records = Button(this).apply { text = "我的報名紀錄"; setOnClickListener { startActivity(Intent(this@ProfileActivity, MyRegistrationsActivity::class.java)) } }
        val logout = Button(this).apply { text = "登出" }
        body.addView(save); body.addView(records); body.addView(logout)
        save.setOnClickListener {
            if (name.text.isBlank() || email.text.isBlank()) { Toast.makeText(this, "姓名與 Email 為必填", Toast.LENGTH_SHORT).show(); return@setOnClickListener }
            save.isEnabled = false
            val edited = member.copy(name = name.text.toString(), email = email.text.toString(), phone = phone.text.toString().ifBlank { null }, birthDate = birthDate.text.toString().ifBlank { null }, gender = genderCodes[gender.selectedItemPosition].ifBlank { null }, address = address.text.toString().ifBlank { null })
            executor.execute { runCatching { ApiClient.updateMe(SessionStore.token(this)!!, edited) }
                .onSuccess { updated -> main.post { Toast.makeText(this, "個人資料已更新", Toast.LENGTH_SHORT).show(); render(updated) } }
                .onFailure { error -> main.post { save.isEnabled = true; Toast.makeText(this, ApiClient.userMessage(error, "更新失敗，請稍後再試。"), Toast.LENGTH_LONG).show() } } }
        }
        logout.setOnClickListener {
            logout.isEnabled = false
            val token = SessionStore.token(this)
            executor.execute {
                token?.let { runCatching { ApiClient.logout(it) } }
                main.post { SessionStore.clear(this); finish() }
            }
        }
    }

    override fun onActivityResult(requestCode: Int, resultCode: Int, data: Intent?) {
        super.onActivityResult(requestCode, resultCode, data)
        if (requestCode == LOGIN_REQUEST) { if (resultCode == RESULT_OK) load() else finish() }
    }

    override fun onDestroy() { executor.shutdownNow(); super.onDestroy() }
    companion object { private const val LOGIN_REQUEST = 301 }
}
