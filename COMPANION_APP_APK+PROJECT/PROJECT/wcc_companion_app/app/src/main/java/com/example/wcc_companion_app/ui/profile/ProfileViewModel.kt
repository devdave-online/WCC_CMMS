package com.example.wcc_companion_app.ui.profile

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.example.wcc_companion_app.data.remote.WccApiService
import com.example.wcc_companion_app.data.remote.models.AchievementsDataDto
import com.example.wcc_companion_app.data.repository.AuthRepository
import dagger.hilt.android.lifecycle.HiltViewModel
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.launch
import javax.inject.Inject

@HiltViewModel
class ProfileViewModel @Inject constructor(
    private val apiService: WccApiService,
    private val authRepository: AuthRepository,
) : ViewModel() {

    private val _userProfile = MutableStateFlow<Map<String, Any>?>(null)
    val userProfile: StateFlow<Map<String, Any>?> = _userProfile.asStateFlow()

    private val _activeWorkOrders = MutableStateFlow<List<Map<String, Any>>>(emptyList())
    val activeWorkOrders: StateFlow<List<Map<String, Any>>> = _activeWorkOrders.asStateFlow()

    private val _achievements = MutableStateFlow<AchievementsDataDto?>(null)
    val achievements: StateFlow<AchievementsDataDto?> = _achievements.asStateFlow()

    private val _achievementsError = MutableStateFlow<String?>(null)
    val achievementsError: StateFlow<String?> = _achievementsError.asStateFlow()

    private val _isLoading = MutableStateFlow(true)
    val isLoading: StateFlow<Boolean> = _isLoading.asStateFlow()

    private val _loadError = MutableStateFlow<String?>(null)
    val loadError: StateFlow<String?> = _loadError.asStateFlow()

    private val _hapticsEnabled = MutableStateFlow(authRepository.isHapticsEnabled())
    val hapticsEnabled: StateFlow<Boolean> = _hapticsEnabled.asStateFlow()

    /** Device-local only — never synced to plant DB. */
    private val _biometricLockEnabled =
        MutableStateFlow(authRepository.isBiometricLockEnabled())
    val biometricLockEnabled: StateFlow<Boolean> = _biometricLockEnabled.asStateFlow()

    init {
        // Offline identity fallback for My Shift before /me returns.
        seedProfileFromCache()
        refresh()
    }

    fun setHapticsEnabled(enabled: Boolean) {
        authRepository.setHapticsEnabled(enabled)
        _hapticsEnabled.value = enabled
    }

    fun setBiometricLockEnabled(enabled: Boolean) {
        authRepository.setBiometricLockEnabled(enabled)
        _biometricLockEnabled.value = enabled
    }

    private fun seedProfileFromCache() {
        if (_userProfile.value != null) return
        val uid = authRepository.getCachedUserId()
        val user = authRepository.getCachedUsername()
        val name = authRepository.getCachedFullName()
        if (uid == null && user == null && name == null) return
        val map = mutableMapOf<String, Any>()
        if (uid != null) map["user_id"] = uid
        if (user != null) map["username"] = user
        if (name != null) map["name"] = name
        _userProfile.value = map
    }

    /**
     * Loads /me + achievements. Seeds nothing — empty states are honest.
     */
    fun refresh() {
        viewModelScope.launch {
            _isLoading.value = true
            _loadError.value = null
            _achievementsError.value = null
            try {
                val response = apiService.getCurrentUser()
                if (response.isSuccessful) {
                    val serverData = response.body()?.data
                    if (!serverData.isNullOrEmpty()) {
                        _userProfile.value = serverData
                        cacheIdentity(serverData)
                        @Suppress("UNCHECKED_CAST")
                        val wos = serverData["active_work_orders"]
                        _activeWorkOrders.value = if (wos is List<*>) {
                            wos.filterIsInstance<Map<String, Any>>()
                        } else emptyList()
                    } else {
                        _loadError.value = "Profile returned no data."
                    }
                } else {
                    _loadError.value = "Could not load profile (HTTP ${response.code()})."
                }
            } catch (e: Exception) {
                if (_userProfile.value == null) {
                    _loadError.value = "Offline — profile unavailable."
                }
            }

            try {
                val ach = apiService.getProfileAchievements()
                val body = ach.body()
                if (ach.isSuccessful && body?.status == "success" && body.data != null) {
                    _achievements.value = body.data
                } else {
                    _achievements.value = null
                    _achievementsError.value = body?.message ?: "Achievements unavailable."
                }
            } catch (e: Exception) {
                // Malformed JSON / Gson cast must never crash profile open.
                _achievements.value = null
                _achievementsError.value = "Offline — achievements unavailable."
            } finally {
                _isLoading.value = false
            }
        }
    }

    private fun cacheIdentity(data: Map<String, Any>) {
        val userId = (data["user_id"] as? Number)?.toInt()
            ?: (data["id"] as? Number)?.toInt()
        val username = data["username"]?.toString()?.takeIf { it.isNotBlank() }
        val fullName = data["name"]?.toString()?.takeIf { it.isNotBlank() }
            ?: data["full_name"]?.toString()?.takeIf { it.isNotBlank() }
        authRepository.cacheUserIdentity(userId, username, fullName)
    }
}
