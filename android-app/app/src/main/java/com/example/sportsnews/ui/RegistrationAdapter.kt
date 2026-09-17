package com.example.sportsnews.ui

import android.content.Context
import android.graphics.Color
import android.view.View
import android.view.ViewGroup
import android.widget.BaseAdapter
import android.widget.LinearLayout
import android.widget.TextView
import android.widget.Button
import com.example.sportsnews.model.Registration
import com.example.sportsnews.R

class RegistrationAdapter(
    private val context: Context,
    private val onDetail: (Registration) -> Unit,
    private val onCancel: (Registration) -> Unit,
) : BaseAdapter() {
    private val items = mutableListOf<Registration>()
    fun submit(values: List<Registration>) { items.clear(); items.addAll(values); notifyDataSetChanged() }
    override fun getCount() = items.size
    override fun getItem(position: Int) = items[position]
    override fun getItemId(position: Int) = items[position].id
    override fun getView(position: Int, convertView: View?, parent: ViewGroup?): View {
        val item = getItem(position)
        return LinearLayout(context).apply {
            orientation = LinearLayout.VERTICAL; setPaddingDp(context, 16)
            background = roundedBackground(Color.WHITE, context.dp(12).toFloat(), Color.rgb(217, 225, 234))
            addView(TextView(context).apply { text = item.event.title; asHeading(18f) })
            addView(TextView(context).apply { text = context.getString(R.string.registration_summary, item.registrationNo, registrationStatusLabel(item.status)) })
            addView(TextView(context).apply { text = "項目：${item.items.joinToString("、") { it.name }}｜總額：NT$ ${String.format("%,d", item.totalAmount)}" })
            addView(TextView(context).apply { text = context.getString(R.string.event_time, displayTime(item.event.eventStartAt)) })
            addView(TextView(context).apply { text = context.getString(R.string.registration_time, displayTime(item.registeredAt)); setTextColor(Color.rgb(102, 112, 133)) })
            if (item.status == "registered") addView(Button(context).apply {
                text = "取消報名"
                isFocusable = false
                setOnClickListener { onCancel(item) }
            })
            setOnClickListener { onDetail(item) }
            layoutParams = android.widget.AbsListView.LayoutParams(ViewGroup.LayoutParams.MATCH_PARENT, ViewGroup.LayoutParams.WRAP_CONTENT)
        }
    }
}
