package com.example.sportsnews.network

import android.graphics.Bitmap
import android.graphics.BitmapFactory
import android.os.Handler
import android.os.Looper
import android.util.LruCache
import android.widget.ImageView
import java.net.URI
import java.util.concurrent.Executors

object ImageLoader {
    private val executor = Executors.newFixedThreadPool(3)
    private val main = Handler(Looper.getMainLooper())
    private val cache = LruCache<String, Bitmap>(12 * 1024 * 1024)

    fun load(url: String?, imageView: ImageView) {
        imageView.tag = url
        imageView.setImageDrawable(null)
        if (url.isNullOrBlank()) return
        cache.get(url)?.let { imageView.setImageBitmap(it); return }
        executor.execute {
            runCatching {
                URI(url).toURL().openStream().use(BitmapFactory::decodeStream)
            }.getOrNull()?.let { bitmap ->
                cache.put(url, bitmap)
                main.post { if (imageView.tag == url) imageView.setImageBitmap(bitmap) }
            }
        }
    }
}
