package com.example.sportsnews

import android.app.Activity
import android.os.Bundle
import android.os.Handler
import android.os.Looper
import android.text.InputType
import android.view.View
import android.widget.Button
import android.widget.LinearLayout
import android.widget.ScrollView
import android.widget.TextView
import com.example.sportsnews.network.ApiClient
import com.example.sportsnews.network.SessionStore
import com.example.sportsnews.ui.dp
import com.example.sportsnews.ui.field
import com.example.sportsnews.ui.screen
import java.util.concurrent.Executors

class LoginActivity : Activity() {
    private val executor = Executors.newSingleThreadExecutor()
    private val main = Handler(Looper.getMainLooper())
    private var registerMode = false

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        val root = screen("會員登入")
        val body = LinearLayout(this).apply { orientation = LinearLayout.VERTICAL; setPadding(dp(24), dp(24), dp(24), dp(32)) }
        val name = field("姓名").apply { visibility = View.GONE }
        val email = field("Email", InputType.TYPE_CLASS_TEXT or InputType.TYPE_TEXT_VARIATION_EMAIL_ADDRESS)
        val phone = field("電話").apply { visibility = View.GONE; inputType = InputType.TYPE_CLASS_PHONE }
        val password = field("密碼（至少 8 碼）", InputType.TYPE_CLASS_TEXT or InputType.TYPE_TEXT_VARIATION_PASSWORD)
        val status = TextView(this)
        val submit = Button(this).apply { text = "登入" }
        val switchMode = Button(this).apply { text = "建立新會員" }
        val fields = listOf(name, email, phone, password)
        fields.forEach { body.addView(it, LinearLayout.LayoutParams(-1, -2).apply { bottomMargin = dp(12) }) }
        body.addView(status)
        body.addView(submit)
        body.addView(switchMode)
        root.addView(ScrollView(this).apply { addView(body) }, LinearLayout.LayoutParams(-1, 0, 1f))
        setContentView(root)

        switchMode.setOnClickListener {
            registerMode = !registerMode
            name.visibility = if (registerMode) View.VISIBLE else View.GONE
            phone.visibility = if (registerMode) View.VISIBLE else View.GONE
            submit.text = if (registerMode) "註冊並登入" else "登入"
            switchMode.text = if (registerMode) "返回會員登入" else "建立新會員"
        }
        submit.setOnClickListener {
            if (email.text.isBlank() || password.text.length < 8 || (registerMode && name.text.isBlank())) {
                status.text = "請完整填寫資料，密碼至少 8 碼。"
                return@setOnClickListener
            }
            submit.isEnabled = false
            status.text = "處理中…"
            executor.execute {
                runCatching {
                    if (registerMode) ApiClient.register(name.text.toString(), email.text.toString(), password.text.toString(), phone.text.toString())
                    else ApiClient.login(email.text.toString(), password.text.toString())
                }.onSuccess { token -> main.post {
                    SessionStore.save(this, token)
                    setResult(RESULT_OK)
                    finish()
                }}.onFailure { error -> main.post { submit.isEnabled = true; status.text = ApiClient.userMessage(error, "登入失敗，請稍後再試。") } }
            }
        }
    }

    override fun onDestroy() { executor.shutdownNow(); super.onDestroy() }
}
