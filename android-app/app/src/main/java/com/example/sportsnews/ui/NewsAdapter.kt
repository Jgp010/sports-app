package com.example.sportsnews.ui

import android.content.Context
import android.graphics.Color
import android.view.View
import android.view.ViewGroup
import android.widget.BaseAdapter
import android.widget.ImageView
import android.widget.LinearLayout
import android.widget.TextView
import com.example.sportsnews.model.NewsPost
import com.example.sportsnews.network.ImageLoader

class NewsAdapter(private val context: Context) : BaseAdapter() {
    private val items = mutableListOf<NewsPost>()

    fun submit(newItems: List<NewsPost>) {
        items.clear()
        items.addAll(newItems)
        notifyDataSetChanged()
    }

    override fun getCount() = items.size
    override fun getItem(position: Int) = items[position]
    override fun getItemId(position: Int) = items[position].id

    override fun getView(position: Int, convertView: View?, parent: ViewGroup?): View {
        val holder: Holder
        val root = if (convertView == null) {
            val image = ImageView(context).apply {
                layoutParams = LinearLayout.LayoutParams(context.dp(96), context.dp(96)).apply { marginEnd = context.dp(14) }
                scaleType = ImageView.ScaleType.CENTER_CROP
                background = roundedBackground(Color.rgb(232, 238, 245), context.dp(10).toFloat())
            }
            val category = TextView(context).apply { textSize = 12f; setTextColor(Color.rgb(46, 117, 182)) }
            val title = TextView(context).apply { asHeading(18f); maxLines = 2 }
            val summary = TextView(context).apply { textSize = 14f; setTextColor(Color.rgb(102, 112, 133)); maxLines = 2 }
            val text = LinearLayout(context).apply {
                orientation = LinearLayout.VERTICAL
                addView(category)
                addView(title)
                addView(summary)
            }
            LinearLayout(context).apply {
                orientation = LinearLayout.HORIZONTAL
                setPaddingDp(context, 14)
                background = roundedBackground(Color.WHITE, context.dp(12).toFloat(), Color.rgb(217, 225, 234))
                addView(image)
                addView(text, LinearLayout.LayoutParams(0, ViewGroup.LayoutParams.WRAP_CONTENT, 1f))
                tag = Holder(image, category, title, summary)
            }.also { holder = it.tag as Holder }
        } else {
            convertView.also { holder = it.tag as Holder }
        }
        val item = getItem(position)
        holder.category.text = item.sport.name
        holder.title.text = item.title
        holder.summary.text = item.summary
        ImageLoader.load(item.coverUrl, holder.image)
        root.layoutParams = android.widget.AbsListView.LayoutParams(ViewGroup.LayoutParams.MATCH_PARENT, ViewGroup.LayoutParams.WRAP_CONTENT)
        return root
    }

    private data class Holder(val image: ImageView, val category: TextView, val title: TextView, val summary: TextView)
}
