package com.example.sportsnews.network

import android.content.Context

object SessionStore {
    private const val FILE = "member_session"
    private const val TOKEN = "api_token"
    fun token(context: Context): String? = context.getSharedPreferences(FILE, Context.MODE_PRIVATE).getString(TOKEN, null)
    fun save(context: Context, token: String) { context.getSharedPreferences(FILE, Context.MODE_PRIVATE).edit().putString(TOKEN, token).apply() }
    fun clear(context: Context) { context.getSharedPreferences(FILE, Context.MODE_PRIVATE).edit().clear().apply() }
    fun isLoggedIn(context: Context): Boolean = !token(context).isNullOrBlank()
}
