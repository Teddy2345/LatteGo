package com.example.lattego

interface Platform {
    val name: String
}

expect fun getPlatform(): Platform