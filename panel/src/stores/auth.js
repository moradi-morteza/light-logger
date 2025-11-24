import { defineStore } from 'pinia'
import { ref } from 'vue'

const API_BASE = 'http://localhost:9501'

export const useAuthStore = defineStore('auth', () => {
  const user = ref(null)
  const token = ref(localStorage.getItem('auth_token') || null)
  const isAuthenticated = ref(false)
  const loading = ref(false)
  const error = ref(null)
  const checked = ref(false)

  /**
   * Login user
   */
  async function login(username, password) {
    loading.value = true
    error.value = null

    try {
      const response = await fetch(`${API_BASE}/api/auth/login`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({ username, password }),
      })

      const data = await response.json()

      if (response.ok && data.success) {
        user.value = data.user
        token.value = data.token
        isAuthenticated.value = true

        // Store token in localStorage
        localStorage.setItem('auth_token', data.token)

        return { success: true }
      } else {
        error.value = data.message || 'Login failed'
        return { success: false, message: error.value }
      }
    } catch (err) {
      error.value = 'Network error. Please try again.'
      return { success: false, message: error.value }
    } finally {
      loading.value = false
    }
  }

  /**
   * Logout user
   */
  async function logout() {
    loading.value = true

    try {
      if (token.value) {
        await fetch(`${API_BASE}/api/auth/logout`, {
          method: 'POST',
          headers: {
            'Authorization': `Bearer ${token.value}`,
            'Content-Type': 'application/json',
          },
        })
      }
    } catch (err) {
      console.error('Logout error:', err)
    } finally {
      // Clear auth state
      clearAuth()
      loading.value = false
    }
  }

  /**
   * Check authentication status
   */
  async function checkAuth() {
    if (!token.value) {
      isAuthenticated.value = false
      checked.value = true
      return
    }

    loading.value = true

    try {
      const response = await fetch(`${API_BASE}/api/auth/me`, {
        headers: {
          'Authorization': `Bearer ${token.value}`,
        },
      })

      const data = await response.json()

      if (response.ok && data.success) {
        user.value = data.user
        isAuthenticated.value = true
      } else {
        // Token is invalid, clear it
        clearAuth()
      }
    } catch (err) {
      console.error('Auth check error:', err)
      clearAuth()
    } finally {
      loading.value = false
      checked.value = true
    }
  }

  /**
   * Clear authentication state
   */
  function clearAuth() {
    user.value = null
    token.value = null
    isAuthenticated.value = false
    localStorage.removeItem('auth_token')
  }

  /**
   * Set token manually (for testing or external auth)
   */
  function setToken(newToken) {
    token.value = newToken
    localStorage.setItem('auth_token', newToken)
  }

  return {
    user,
    token,
    isAuthenticated,
    loading,
    error,
    checked,
    login,
    logout,
    checkAuth,
    clearAuth,
    setToken,
  }
})
