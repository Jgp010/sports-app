package com.example.sportsnews.ui

import android.app.Activity
import android.graphics.Color
import android.text.InputType
import android.view.Gravity
import android.widget.Button
import android.widget.EditText
import android.widget.LinearLayout
import android.widget.TextView
import android.content.Intent
import com.example.sportsnews.EventsActivity
import com.example.sportsnews.MainActivity
import com.example.sportsnews.ProfileActivity
import java.time.OffsetDateTime
import java.time.ZoneId
import java.time.format.DateTimeFormatter

fun Activity.screen(title: String, showBack: Boolean = true): LinearLayout {
    val root = LinearLayout(this).apply { orientation = LinearLayout.VERTICAL; setBackgroundColor(Color.rgb(244, 247, 251)) }
    val header = LinearLayout(this).apply { gravity = Gravity.CENTER_VERTICAL; setPadding(dp(12), dp(10), dp(16), dp(10)); setBackgroundColor(Color.rgb(23, 54, 93)) }
    if (showBack) header.addView(Button(this).apply { text = "返回"; setOnClickListener { finish() } })
    header.addView(TextView(this).apply { text = title; textSize = 22f; setTextColor(Color.WHITE); setTypeface(typeface, android.graphics.Typeface.BOLD); setPadding(dp(12), 0, 0, 0) })
    root.addView(header)
    return root
}

fun Activity.field(hintText: String, type: Int = InputType.TYPE_CLASS_TEXT): EditText = EditText(this).apply {
    hint = hintText
    inputType = type
    setPadding(dp(12), dp(12), dp(12), dp(12))
    background = roundedBackground(Color.WHITE, dp(8).toFloat(), Color.rgb(184, 197, 211))
}

fun displayTime(value: String?): String {
    if (value.isNullOrBlank()) return "—"
    return runCatching {
        OffsetDateTime.parse(value).atZoneSameInstant(ZoneId.of("Asia/Taipei"))
            .format(DateTimeFormatter.ofPattern("yyyy-MM-dd HH:mm"))
    }.getOrElse {
        value.replace("T", " ").substringBefore("+").substringBefore("Z").take(16)
    }
}

enum class NavTab { NEWS, EVENTS, PROFILE }

fun Activity.bottomNavigation(current: NavTab): LinearLayout = LinearLayout(this).apply {
    orientation = LinearLayout.HORIZONTAL
    gravity = Gravity.CENTER
    setPadding(dp(8), dp(6), dp(8), dp(6))
    setBackgroundColor(Color.WHITE)
    listOf(
        Triple(NavTab.NEWS, "最新消息", android.R.drawable.ic_menu_recent_history),
        Triple(NavTab.EVENTS, "賽事報名", android.R.drawable.ic_menu_my_calendar),
        Triple(NavTab.PROFILE, "會員中心", android.R.drawable.ic_menu_myplaces),
    ).forEach { (tab, label, icon) ->
        addView(Button(this@bottomNavigation).apply {
            text = label
            setCompoundDrawablesWithIntrinsicBounds(null, resources.getDrawable(icon, theme), null, null)
            compoundDrawablePadding = dp(2)
            isAllCaps = false
            alpha = if (tab == current) 1f else .62f
            isEnabled = tab != current
            setOnClickListener {
                val target = when (tab) { NavTab.NEWS -> MainActivity::class.java; NavTab.EVENTS -> EventsActivity::class.java; NavTab.PROFILE -> ProfileActivity::class.java }
                startActivity(Intent(this@bottomNavigation, target).addFlags(Intent.FLAG_ACTIVITY_REORDER_TO_FRONT))
            }
        }, LinearLayout.LayoutParams(0, LinearLayout.LayoutParams.WRAP_CONTENT, 1f))
    }
}

fun registrationStatusLabel(status: String): String = when (status) {
    "registered" -> "已報名"
    "cancelled" -> "已取消"
    "attended" -> "已出席"
    else -> status
}
