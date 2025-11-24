import { defineStore } from 'pinia'
import { ref, reactive } from 'vue'

export const useInstallStore = defineStore('install', () => {
  const installed = ref(false)
  const checked = ref(false)
  const loading = ref(false)
  const error = ref(null)

  const defaults = reactive({
    app: {},
    database: {},
    redis: {},
    elasticsearch: {},
  })

  async function checkStatus() {
    loading.value = true
    error.value = null

    try {
      const response = await fetch('/api/install/status')
      const data = await response.json()

      if (data.success) {
        installed.value = data.data.status.installed
        Object.assign(defaults, data.data.defaults)
      }

      checked.value = true
    } catch (e) {
      error.value = 'Failed to check installation status'
      console.error(e)
    } finally {
      loading.value = false
    }
  }

  async function testDatabase(config) {
    loading.value = true
    error.value = null

    try {
      const response = await fetch('/api/install/test-database', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(config),
      })

      const data = await response.json()
      return data
    } catch (e) {
      error.value = 'Failed to test database connection'
      return { success: false, message: error.value }
    } finally {
      loading.value = false
    }
  }

  async function testRedis(config) {
    loading.value = true
    error.value = null

    try {
      const response = await fetch('/api/install/test-redis', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(config),
      })

      const data = await response.json()
      return data
    } catch (e) {
      error.value = 'Failed to test Redis connection'
      return { success: false, message: error.value }
    } finally {
      loading.value = false
    }
  }

  async function completeInstallation(config) {
    loading.value = true
    error.value = null

    try {
      const response = await fetch('/api/install/complete', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(config),
      })

      const data = await response.json()

      if (data.success) {
        installed.value = true
      }

      return data
    } catch (e) {
      error.value = 'Installation failed'
      return { success: false, message: error.value }
    } finally {
      loading.value = false
    }
  }

  return {
    installed,
    checked,
    loading,
    error,
    defaults,
    checkStatus,
    testDatabase,
    testRedis,
    completeInstallation,
  }
})
