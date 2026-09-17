package com.example.sportsnews.model

data class RegistrationItem(val id: Long, val name: String, val unitPrice: Long)

data class Registration(val id: Long, val registrationNo: String, val status: String, val contactPhone: String, val organization: String?, val totalAmount: Long, val emergencyContactName: String?, val emergencyContactPhone: String?, val notes: String?, val registeredAt: String, val items: List<RegistrationItem>, val event: Event)
