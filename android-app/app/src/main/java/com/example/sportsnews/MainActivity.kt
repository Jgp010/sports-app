package com.example.sportsnews

import android.app.Activity
import android.content.Intent
import android.graphics.Color
import android.os.Bundle
import android.os.Handler
import android.os.Looper
import android.view.Gravity
import android.view.View
import android.widget.Button
import android.widget.FrameLayout
import android.widget.LinearLayout
import android.widget.ListView
import android.widget.ProgressBar
import android.widget.TextView
import com.example.sportsnews.network.ApiClient
import com.example.sportsnews.ui.NewsAdapter
import com.example.sportsnews.ui.asHeading
import com.example.sportsnews.ui.dp
import com.example.sportsnews.ui.bottomNavigation
import com.example.sportsnews.ui.NavTab
import java.util.concurrent.Executors

class MainActivity : Activity() {
    private val executor = Executors.newSingleThreadExecutor()
    private val main = Handler(Looper.getMainLooper())
    private lateinit var adapter: NewsAdapter
    private lateinit var list: ListView
    private lateinit var status: TextView
    private lateinit var progress: ProgressBar

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        adapter = NewsAdapter(this)
        setContentView(createContent())
        loadNews()
    }

    private fun createContent(): View {
        val root = LinearLayout(this).apply {
            orientation = LinearLayout.VERTICAL
            setBackgroundColor(Color.rgb(244, 247, 251))
        }
        val header = LinearLayout(this).apply {
            gravity = Gravity.CENTER_VERTICAL
            setPadding(dp(20), dp(18), dp(12), dp(14))
            setBackgroundColor(Color.rgb(23, 54, 93))
        }
        val title = TextView(this).apply {
            text = getString(R.string.latest_news)
            textSize = 24f
            setTextColor(Color.WHITE)
            setTypeface(typeface, android.graphics.Typeface.BOLD)
        }
        val refresh = Button(this).apply { text = getString(R.string.refresh); setOnClickListener { loadNews() } }
        header.addView(title, LinearLayout.LayoutParams(0, LinearLayout.LayoutParams.WRAP_CONTENT, 1f))
        header.addView(refresh)
        root.addView(header)

        val content = FrameLayout(this)
        list = ListView(this).apply {
            adapter = this@MainActivity.adapter
            divider = android.graphics.drawable.ColorDrawable(Color.TRANSPARENT)
            dividerHeight = dp(12)
            setPadding(dp(14), dp(14), dp(14), dp(20))
            clipToPadding = false
            setOnItemClickListener { _, _, position, _ ->
                startActivity(Intent(this@MainActivity, DetailActivity::class.java).putExtra(DetailActivity.EXTRA_SLUG, this@MainActivity.adapter.getItem(position).slug))
            }
        }
        status = TextView(this).apply {
            gravity = Gravity.CENTER
            textSize = 16f
            setTextColor(Color.rgb(102, 112, 133))
            setPadding(dp(28), dp(28), dp(28), dp(28))
        }
        progress = ProgressBar(this)
        content.addView(list, FrameLayout.LayoutParams(-1, -1))
        content.addView(status, FrameLayout.LayoutParams(-1, -1))
        content.addView(progress, FrameLayout.LayoutParams(dp(48), dp(48), Gravity.CENTER))
        root.addView(content, LinearLayout.LayoutParams(-1, 0, 1f))
        root.addView(bottomNavigation(NavTab.NEWS))
        return root
    }

    private fun loadNews() {
        progress.visibility = View.VISIBLE
        status.visibility = View.GONE
        executor.execute {
            runCatching { ApiClient.fetchNews() }
                .onSuccess { items -> main.post {
                    progress.visibility = View.GONE
                    adapter.submit(items)
                    status.text = if (items.isEmpty()) getString(R.string.empty_news) else ""
                    status.visibility = if (items.isEmpty()) View.VISIBLE else View.GONE
                }}
                .onFailure { error -> main.post {
                    progress.visibility = View.GONE
                    adapter.submit(emptyList())
                    status.text = getString(
                        R.string.list_error,
                        ApiClient.userMessage(error, getString(R.string.network_retry)),
                    )
                    status.visibility = View.VISIBLE
                }}
        }
    }

    override fun onDestroy() {
        executor.shutdownNow()
        super.onDestroy()
    }
}
