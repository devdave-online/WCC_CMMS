package com.example.wcc_companion_app.ui.auth

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.example.wcc_companion_app.data.remote.WccApiService
import com.example.wcc_companion_app.data.repository.AuthRepository
import com.example.wcc_companion_app.data.sync.NetworkMonitor
import dagger.hilt.android.lifecycle.HiltViewModel
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.launch
import java.net.SocketTimeoutException
import java.net.UnknownHostException
import javax.inject.Inject

@HiltViewModel
class LoginViewModel @Inject constructor(
    private val authRepository: AuthRepository,
    private val apiService: WccApiService,
    private val networkMonitor: NetworkMonitor,
) : ViewModel() {

    private val _isLoading = MutableStateFlow(false)
    val isLoading: StateFlow<Boolean> = _isLoading.asStateFlow()

    private val _loginSuccess = MutableStateFlow(false)
    val loginSuccess: StateFlow<Boolean> = _loginSuccess.asStateFlow()

    private val _errorMessage = MutableStateFlow<String?>(null)
    val errorMessage: StateFlow<String?> = _errorMessage.asStateFlow()

    fun login(serverUrl: String, username: String, pass: String) {
        viewModelScope.launch {
            _isLoading.value = true
            _errorMessage.value = null
            
            // 1. Normalize and Save URL
            var normalizedUrl = serverUrl.trim()
            if (!normalizedUrl.startsWith("http://") && !normalizedUrl.startsWith("https://")) {
                normalizedUrl = "http://$normalizedUrl"
            }
            if (!normalizedUrl.contains("/api/v1")) {
                normalizedUrl = if (normalizedUrl.endsWith("/")) "${normalizedUrl}api/v1/" else "$normalizedUrl/api/v1/"
            }
            if (!normalizedUrl.endsWith("/")) {
                normalizedUrl = "$normalizedUrl/"
            }
            
            authRepository.saveServerUrl(normalizedUrl)
            // Re-probe plant host immediately with the new URL (watchdog).
            networkMonitor.refreshNow()

            // 2. Set credentials for Basic Auth
            authRepository.setCredentials(username, pass)
            
            try {
                // First, login to get the PHP session cookie (for legacy endpoints)
                try {
                    apiService.loginForm(username, pass)
                } catch (e: Exception) {
                    // Ignore strictly, even if it fails we still try v1 auth
                }

                val response = apiService.getCurrentUser()
                if (response.isSuccessful) {
                    authRepository.saveApiKey(username) 
                    _loginSuccess.value = true
                } else {
                    val code = response.code()
                    _errorMessage.value = when (code) {
                        404 -> "Not Found. Check your IP or try adding 'index.php/'."
                        401 -> "Invalid username or password."
                        else -> "Login failed (Code $code): ${response.message()}"
                    }
                    authRepository.logout()
                }
            } catch (e: Exception) {
                _errorMessage.value = when (e) {
                    is UnknownHostException -> "Server not found. Check IP/Address."
                    is SocketTimeoutException -> "Connection timeout. Server took too long."
                    else -> "Connection error: ${e.localizedMessage}"
                }
                authRepository.logout()
            } finally {
                _isLoading.value = false
            }
        }
    }

    fun clearError() {
        _errorMessage.value = null
    }
}
