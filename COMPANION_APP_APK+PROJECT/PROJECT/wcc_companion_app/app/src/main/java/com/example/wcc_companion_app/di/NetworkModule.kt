package com.example.wcc_companion_app.di

import com.example.wcc_companion_app.BuildConfig
import com.example.wcc_companion_app.data.remote.WccApiService
import com.example.wcc_companion_app.data.repository.AuthRepository
import dagger.Module
import dagger.Provides
import dagger.hilt.InstallIn
import dagger.hilt.components.SingletonComponent
import okhttp3.Credentials
import okhttp3.HttpUrl.Companion.toHttpUrlOrNull
import okhttp3.Cookie
import okhttp3.CookieJar
import okhttp3.HttpUrl
import okhttp3.logging.HttpLoggingInterceptor
import okhttp3.Interceptor
import okhttp3.OkHttpClient
import retrofit2.Retrofit
import retrofit2.converter.gson.GsonConverterFactory
import java.util.concurrent.TimeUnit
import javax.inject.Named
import javax.inject.Singleton

@Module
@InstallIn(SingletonComponent::class)
object NetworkModule {

    @Provides
    @Singleton
    @Named("AuthInterceptor")
    fun provideAuthInterceptor(authRepository: AuthRepository): Interceptor {
        return Interceptor { chain ->
            val original = chain.request()
            val requestBuilder = original.newBuilder()
            
            // 1. Try API Key
            authRepository.getApiKey()?.let {
                requestBuilder.addHeader("X-API-Key", it)
            }
            
            // 2. Try Basic Auth (for initial login or if no API key)
            authRepository.getCredentials()?.let { (user, pass) ->
                requestBuilder.addHeader("Authorization", Credentials.basic(user, pass))
            }
            
            chain.proceed(requestBuilder.build())
        }
    }

    @Provides
    @Singleton
    @Named("DynamicBaseUrlInterceptor")
    fun provideDynamicBaseUrlInterceptor(authRepository: AuthRepository): Interceptor {
        return Interceptor { chain ->
            var request = chain.request()
            val newBaseUrl = authRepository.getServerUrl()?.toHttpUrlOrNull()
            
            if (newBaseUrl != null) {
                val newUrlBuilder = request.url.newBuilder()
                    .scheme(newBaseUrl.scheme)
                    .host(newBaseUrl.host)
                    .port(newBaseUrl.port)
                if (request.header("DynamicBaseUrl-Skip") != null) {
                    // Bypass prepending api/v1/resources
                    request = request.newBuilder()
                        .removeHeader("DynamicBaseUrl-Skip")
                        .url(newUrlBuilder.build())
                        .build()
                } else {
                    // Prepend base path segments (e.g., "api/v1")
                    val baseSegments = newBaseUrl.pathSegments.filter { it.isNotEmpty() }
                val originalPath = request.url.encodedPath.trimStart('/')
                val fullPath = if (baseSegments.isEmpty()) {
                    originalPath
                } else {
                    baseSegments.joinToString("/") + "/" + originalPath
                }
                
                    newUrlBuilder.encodedPath("/$fullPath")
                    request = request.newBuilder().url(newUrlBuilder.build()).build()
                }
            }
            
            chain.proceed(request)
        }
    }

    @Provides
    @Singleton
    fun provideCookieJar(): CookieJar {
        return object : CookieJar {
            private val cookies = mutableMapOf<String, List<Cookie>>()
            
            override fun saveFromResponse(url: HttpUrl, cookies: List<Cookie>) {
                this.cookies[url.host] = cookies
            }
            
            override fun loadForRequest(url: HttpUrl): List<Cookie> {
                return cookies[url.host] ?: emptyList()
            }
        }
    }

    @Provides
    @Singleton
    fun provideOkHttpClient(
        @Named("AuthInterceptor") authInterceptor: Interceptor,
        @Named("DynamicBaseUrlInterceptor") dynamicBaseUrlInterceptor: Interceptor,
        cookieJar: CookieJar
    ): OkHttpClient {
        // Open Beta: never log request/response bodies in release (credentials / forms).
        val logging = HttpLoggingInterceptor().apply {
            level = if (BuildConfig.DEBUG) {
                HttpLoggingInterceptor.Level.BASIC
            } else {
                HttpLoggingInterceptor.Level.NONE
            }
        }
        return OkHttpClient.Builder()
            .connectTimeout(15, TimeUnit.SECONDS)
            .readTimeout(15, TimeUnit.SECONDS)
            .writeTimeout(15, TimeUnit.SECONDS)
            .cookieJar(cookieJar)
            .addInterceptor(logging)
            .addInterceptor(dynamicBaseUrlInterceptor)
            .addInterceptor(authInterceptor)
            .build()
    }

    @Provides
    @Singleton
    fun provideWccApiService(
        okHttpClient: OkHttpClient
    ): WccApiService {
        return Retrofit.Builder()
            .baseUrl("http://localhost/") 
            .client(okHttpClient)
            .addConverterFactory(GsonConverterFactory.create())
            .build()
            .create(WccApiService::class.java)
    }
}
