package com.example.sportsnews

import android.app.Activity
import android.app.AlertDialog
import android.os.Bundle
import android.os.Handler
import android.os.Looper
import android.view.Gravity
import android.view.View
import android.widget.FrameLayout
import android.widget.LinearLayout
import android.widget.ListView
import android.widget.ProgressBar
import android.widget.TextView
import android.widget.Toast
import com.example.sportsnews.network.ApiClient
import com.example.sportsnews.network.ApiException
import com.example.sportsnews.network.SessionStore
import com.example.sportsnews.ui.RegistrationAdapter
import com.example.sportsnews.ui.displayTime
import com.example.sportsnews.ui.registrationStatusLabel
import com.example.sportsnews.model.Registration
import com.example.sportsnews.ui.dp
import com.example.sportsnews.ui.screen
import java.util.concurrent.Executors

class MyRegistrationsActivity : Activity() {
    private val executor = Executors.newSingleThreadExecutor()
    private val main = Handler(Looper.getMainLooper())
    private val registrationAdapter by lazy { RegistrationAdapter(this, ::showDetail, ::confirmCancel) }
    private lateinit var progress: ProgressBar
    private lateinit var status: TextView

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        if (!SessionStore.isLoggedIn(this)) { finish(); return }
        val root = screen("我的報名紀錄")
        val frame = FrameLayout(this)
        val list = ListView(this).apply {
            adapter = registrationAdapter; dividerHeight = dp(12); setPadding(dp(14), dp(14), dp(14), dp(20)); clipToPadding = false
        }
        status = TextView(this).apply { gravity = Gravity.CENTER }
        progress = ProgressBar(this)
        frame.addView(list, FrameLayout.LayoutParams(-1, -1)); frame.addView(status, FrameLayout.LayoutParams(-1, -1)); frame.addView(progress, FrameLayout.LayoutParams(dp(48), dp(48), Gravity.CENTER))
        root.addView(frame, LinearLayout.LayoutParams(-1, 0, 1f)); setContentView(root); load()
    }

    private fun showDetail(item: Registration) {
        val detail = listOf(
            "報名編號：${item.registrationNo}",
            "狀態：${registrationStatusLabel(item.status)}",
            "賽事：${item.event.title}",
            "項目：${item.items.joinToString("、") { "${it.name}（NT$ ${String.format("%,d", it.unitPrice)}）" }}",
            "總額：NT$ ${String.format("%,d", item.totalAmount)}",
            "單位：${item.organization.orEmpty()}",
            "聯絡電話：${item.contactPhone}",
            "緊急聯絡人：${item.emergencyContactName.orEmpty()}",
            "緊急聯絡電話：${item.emergencyContactPhone.orEmpty()}",
            "報名時間：${displayTime(item.registeredAt)}",
            item.notes?.takeIf { it.isNotBlank() }?.let { "備註：$it" },
        ).filterNotNull().joinToString("\n")
        AlertDialog.Builder(this).setTitle("報名詳細資料").setMessage(detail).setPositiveButton("關閉", null).show()
    }

    private fun confirmCancel(item: Registration) {
        AlertDialog.Builder(this).setTitle("取消報名")
            .setMessage("確定要取消「${item.event.title}」的報名嗎？")
            .setNegativeButton("保留報名", null)
            .setPositiveButton("確定取消") { _, _ -> cancel(item.id) }
            .show()
    }

    private fun load() {
        progress.visibility = View.VISIBLE; status.visibility = View.GONE
        val token = SessionStore.token(this) ?: return
        executor.execute { runCatching { ApiClient.fetchRegistrations(token) }
            .onSuccess { items -> main.post { progress.visibility = View.GONE; registrationAdapter.submit(items); status.text = if (items.isEmpty()) "尚無報名紀錄。" else ""; status.visibility = if (items.isEmpty()) View.VISIBLE else View.GONE } }
            .onFailure { error -> main.post { progress.visibility = View.GONE; if ((error as? ApiException)?.statusCode == 401) { SessionStore.clear(this); finish() } else { status.text = ApiClient.userMessage(error, "無法取得報名紀錄，請稍後再試。"); status.visibility = View.VISIBLE } } } }
    }

    private fun cancel(id: Long) {
        val token = SessionStore.token(this) ?: return
        executor.execute { runCatching { ApiClient.cancelRegistration(token, id) }
            .onSuccess { main.post { Toast.makeText(this, "已取消報名", Toast.LENGTH_SHORT).show(); load() } }
            .onFailure { error -> main.post { Toast.makeText(this, ApiClient.userMessage(error, "取消失敗，請稍後再試。"), Toast.LENGTH_LONG).show() } } }
    }

    override fun onDestroy() { executor.shutdownNow(); super.onDestroy() }
}
