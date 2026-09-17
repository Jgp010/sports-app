package com.example.sportsnews.model

data class Member(val id: Long, val name: String, val email: String, val phone: String?, val birthDate: String?, val gender: String?, val address: String?, val ssoProvider: String?, val hasSsoCredential: Boolean)
