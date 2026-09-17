package com.example.sportsnews

import android.app.Activity
import android.graphics.Color
import android.os.Bundle
import android.os.Handler
import android.os.Looper
import android.view.Gravity
import android.view.View
import android.widget.ImageButton
import android.widget.ImageView
import android.widget.LinearLayout
import android.widget.ProgressBar
import android.widget.ScrollView
import android.widget.TextView
import com.example.sportsnews.model.NewsPost
import com.example.sportsnews.network.ApiClient
import com.example.sportsnews.network.ImageLoader
import com.example.sportsnews.ui.asHeading
import com.example.sportsnews.ui.dp
import com.example.sportsnews.ui.displayTime
import java.util.concurrent.Executors

class DetailActivity : Activity() {
    private val executor = Executors.newSingleThreadExecutor()
    private val main = Handler(Looper.getMainLooper())
    private lateinit var body: LinearLayout
    private lateinit var progress: ProgressBar
    private lateinit var error: TextView

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        setContentView(createContent())
        val slug = intent.getStringExtra(EXTRA_SLUG)
        if (slug.isNullOrBlank()) showError("消息識別碼不存在") else load(slug)
    }

    private fun createContent(): View {
        val root = LinearLayout(this).apply { orientation = LinearLayout.VERTICAL; setBackgroundColor(Color.rgb(244, 247, 251)) }
        val bar = LinearLayout(this).apply { gravity = Gravity.CENTER_VERTICAL; setPadding(dp(8), dp(8), dp(16), dp(8)); setBackgroundColor(Color.rgb(23, 54, 93)) }
        val back = ImageButton(this).apply { setImageResource(android.R.drawable.ic_media_previous); contentDescription = "返回"; setBackgroundColor(Color.TRANSPARENT); setColorFilter(Color.WHITE); setOnClickListener { finish() } }
        val barTitle = TextView(this).apply { text = "最新消息詳情"; asHeading(20f); setTextColor(Color.WHITE) }
        bar.addView(back, LinearLayout.LayoutParams(dp(48), dp(48)))
        bar.addView(barTitle)
        root.addView(bar)
        val frame = android.widget.FrameLayout(this)
        body = LinearLayout(this).apply { orientation = LinearLayout.VERTICAL; setPadding(dp(20), dp(20), dp(20), dp(36)) }
        frame.addView(ScrollView(this).apply { addView(body) }, android.widget.FrameLayout.LayoutParams(-1, -1))
        progress = ProgressBar(this)
        frame.addView(progress, android.widget.FrameLayout.LayoutParams(dp(48), dp(48), Gravity.CENTER))
        error = TextView(this).apply { gravity = Gravity.CENTER; textSize = 16f; setTextColor(Color.rgb(102,112,133)); visibility = View.GONE }
        frame.addView(error, android.widget.FrameLayout.LayoutParams(-1, -1))
        root.addView(frame, LinearLayout.LayoutParams(-1, 0, 1f))
        return root
    }

    private fun load(slug: String) {
        executor.execute {
            runCatching { ApiClient.fetchNewsDetail(slug) }
                .onSuccess { post -> main.post { render(post) } }
                .onFailure { failure -> main.post { showError(ApiClient.userMessage(failure, "無法取得消息，請稍後再試。")) } }
        }
    }

    private fun render(post: NewsPost) {
        progress.visibility = View.GONE
        body.removeAllViews()
        val category = TextView(this).apply { text = post.sport.name; textSize = 14f; setTextColor(Color.rgb(46,117,182)) }
        val title = TextView(this).apply { text = post.title; asHeading(28f); setPadding(0, dp(6), 0, dp(8)) }
        val metaText = listOfNotNull(post.eventStartAt?.let { "賽事：${displayTime(it)}" }, post.venue?.let { "地點：$it" }, post.publishedAt?.let { "發布：${displayTime(it)}" }).joinToString("\n")
        val meta = TextView(this).apply { text = metaText; textSize = 14f; setTextColor(Color.rgb(102,112,133)); setPadding(0,0,0,dp(16)) }
        val image = ImageView(this).apply { scaleType = ImageView.ScaleType.CENTER_CROP; adjustViewBounds = true; minimumHeight = dp(180) }
        val summary = TextView(this).apply { text = post.summary; textSize = 18f; setTextColor(Color.rgb(31,41,55)); setPadding(0,dp(18),0,dp(18)) }
        val content = TextView(this).apply { text = post.content.orEmpty(); textSize = 17f; setTextColor(Color.rgb(31,41,55)); setLineSpacing(0f,1.35f) }
        listOf(category,title,meta,image,summary,content).forEach(body::addView)
        ImageLoader.load(post.coverUrl, image)
    }

    private fun showError(message: String) {
        progress.visibility = View.GONE
        error.text = getString(R.string.detail_error, message)
        error.visibility = View.VISIBLE
    }

    override fun onDestroy() { executor.shutdownNow(); super.onDestroy() }

    companion object { const val EXTRA_SLUG = "news_slug" }
}
