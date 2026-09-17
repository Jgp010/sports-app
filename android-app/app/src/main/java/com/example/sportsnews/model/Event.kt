package com.example.sportsnews.model

data class EventItem(
    val id: Long,
    val name: String,
    val description: String?,
    val registrationFee: Long,
    val earlyBirdFee: Long?,
    val earlyBirdEndsAt: String?,
    val currentFee: Long,
    val earlyBirdActive: Boolean,
)

data class Event(val id: Long, val slug: String, val title: String, val description: String, val venue: String, val sport: Sport, val eventStartAt: String, val eventEndAt: String?, val registrationOpenAt: String, val registrationCloseAt: String, val capacity: Int?, val registeredCount: Int, val registrationOpen: Boolean, val items: List<EventItem>)
