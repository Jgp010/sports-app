package com.example.sportsnews.model

data class Sport(
    val id: Long,
    val name: String,
    val slug: String,
)

data class NewsPost(
    val id: Long,
    val slug: String,
    val title: String,
    val summary: String,
    val content: String?,
    val coverUrl: String?,
    val sport: Sport,
    val eventStartAt: String?,
    val venue: String?,
    val publishedAt: String?,
)
