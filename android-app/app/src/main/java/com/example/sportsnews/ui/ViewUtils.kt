package com.example.sportsnews.ui

import android.content.Context
import android.graphics.Color
import android.graphics.Typeface
import android.graphics.drawable.GradientDrawable
import android.view.View
import android.widget.TextView

fun Context.dp(value: Int): Int = (value * resources.displayMetrics.density).toInt()

fun roundedBackground(color: Int, radius: Float, strokeColor: Int? = null): GradientDrawable =
    GradientDrawable().apply {
        setColor(color)
        cornerRadius = radius
        strokeColor?.let { setStroke(1, it) }
    }

fun TextView.asHeading(size: Float = 22f) {
    textSize = size
    setTextColor(Color.rgb(23, 54, 93))
    setTypeface(typeface, Typeface.BOLD)
}

fun View.setPaddingDp(context: Context, all: Int) {
    setPadding(context.dp(all), context.dp(all), context.dp(all), context.dp(all))
}
