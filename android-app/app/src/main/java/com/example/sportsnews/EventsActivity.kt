package com.example.sportsnews

import android.app.Activity
import android.content.Intent
import android.graphics.Color
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
import com.example.sportsnews.network.ApiClient
import com.example.sportsnews.ui.EventAdapter
import com.example.sportsnews.ui.dp
import com.example.sportsnews.ui.screen
import com.example.sportsnews.ui.bottomNavigation
import com.example.sportsnews.ui.NavTab
import java.util.concurrent.Executors

class EventsActivity : Activity() {
    private val executor = Executors.newSingleThreadExecutor()
    private val main = Handler(Looper.getMainLooper())
    private val eventAdapter by lazy { EventAdapter(this) }
    private lateinit var progress: ProgressBar
    private lateinit var status: TextView

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        val root = screen("賽事報名", showBack = false)
        val frame = FrameLayout(this)
        val list = ListView(this).apply {
            adapter = eventAdapter; divider = android.graphics.drawable.ColorDrawable(Color.TRANSPARENT); dividerHeight = dp(12)
            setPadding(dp(14), dp(14), dp(14), dp(20)); clipToPadding = false
            setOnItemClickListener { _, _, position, _ -> startActivity(Intent(this@EventsActivity, EventDetailActivity::class.java).putExtra(EventDetailActivity.EXTRA_SLUG, eventAdapter.getItem(position).slug)) }
        }
        status = TextView(this).apply { gravity = Gravity.CENTER; textSize = 16f }
        progress = ProgressBar(this)
        frame.addView(list, FrameLayout.LayoutParams(-1, -1)); frame.addView(status, FrameLayout.LayoutParams(-1, -1)); frame.addView(progress, FrameLayout.LayoutParams(dp(48), dp(48), Gravity.CENTER))
        root.addView(frame, LinearLayout.LayoutParams(-1, 0, 1f)); root.addView(bottomNavigation(NavTab.EVENTS)); setContentView(root)
    }

    private fun load() {
        progress.visibility = View.VISIBLE; status.visibility = View.GONE
        executor.execute { runCatching { ApiClient.fetchEvents() }
            .onSuccess { items -> main.post { progress.visibility = View.GONE; eventAdapter.submit(items); status.text = if (items.isEmpty()) "目前沒有已發布的賽事。" else ""; status.visibility = if (items.isEmpty()) View.VISIBLE else View.GONE } }
            .onFailure { error -> main.post { progress.visibility = View.GONE; status.text = ApiClient.userMessage(error, "無法取得賽事，請稍後再試。"); status.visibility = View.VISIBLE } } }
    }

    override fun onResume() { super.onResume(); if (::progress.isInitialized) load() }
    override fun onDestroy() { executor.shutdownNow(); super.onDestroy() }
}
