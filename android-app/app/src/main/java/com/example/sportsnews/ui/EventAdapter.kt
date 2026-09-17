package com.example.sportsnews.ui

import android.content.Context
import android.graphics.Color
import android.view.View
import android.view.ViewGroup
import android.widget.BaseAdapter
import android.widget.LinearLayout
import android.widget.TextView
import com.example.sportsnews.model.Event
import com.example.sportsnews.R

class EventAdapter(private val context: Context) : BaseAdapter() {
    private val items = mutableListOf<Event>()
    fun submit(values: List<Event>) { items.clear(); items.addAll(values); notifyDataSetChanged() }
    override fun getCount() = items.size
    override fun getItem(position: Int) = items[position]
    override fun getItemId(position: Int) = items[position].id

    override fun getView(position: Int, convertView: View?, parent: ViewGroup?): View {
        val item = getItem(position)
        return LinearLayout(context).apply {
            orientation = LinearLayout.VERTICAL
            setPaddingDp(context, 16)
            background = roundedBackground(Color.WHITE, context.dp(12).toFloat(), Color.rgb(217, 225, 234))
            addView(TextView(context).apply { text = item.sport.name; textSize = 13f; setTextColor(Color.rgb(46, 117, 182)) })
            addView(TextView(context).apply { text = item.title; asHeading(19f) })
            addView(TextView(context).apply { text = context.getString(R.string.event_time_venue, displayTime(item.eventStartAt), item.venue); textSize = 14f })
            addView(TextView(context).apply {
                text = if (item.registrationOpen) "開放報名中｜${item.registeredCount}/${item.capacity ?: "不限"}" else "目前未開放報名"
                setTextColor(if (item.registrationOpen) Color.rgb(46, 125, 50) else Color.rgb(102, 112, 133))
            })
            layoutParams = android.widget.AbsListView.LayoutParams(ViewGroup.LayoutParams.MATCH_PARENT, ViewGroup.LayoutParams.WRAP_CONTENT)
        }
    }
}
