/**
 * API utility for making authenticated requests
 */

const API_BASE = 'http://localhost:9501'

/**
 * Get auth token from localStorage
 */
function getAuthToken() {
  return localStorage.getItem('auth_token')
}

/**
 * Make an authenticated API request
 */
export async function apiRequest(endpoint, options = {}) {
  const token = getAuthToken()

  const headers = {
    'Content-Type': 'application/json',
    ...options.headers,
  }

  // Add Authorization header if token exists
  if (token) {
    headers['Authorization'] = `Bearer ${token}`
  }

  const url = endpoint.startsWith('http') ? endpoint : `${API_BASE}${endpoint}`

  const response = await fetch(url, {
    ...options,
    headers,
  })

  return response
}

/**
 * Helper methods for common HTTP verbs
 */
export const api = {
  get: (endpoint, options = {}) => {
    return apiRequest(endpoint, { ...options, method: 'GET' })
  },

  post: (endpoint, data, options = {}) => {
    return apiRequest(endpoint, {
      ...options,
      method: 'POST',
      body: JSON.stringify(data),
    })
  },

  put: (endpoint, data, options = {}) => {
    return apiRequest(endpoint, {
      ...options,
      method: 'PUT',
      body: JSON.stringify(data),
    })
  },

  delete: (endpoint, options = {}) => {
    return apiRequest(endpoint, { ...options, method: 'DELETE' })
  },
}
