<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'

const router = useRouter()
const authStore = useAuthStore()

const username = ref('')
const password = ref('')
const error = ref(null)
const loading = ref(false)

async function handleLogin() {
  if (!username.value || !password.value) {
    error.value = 'Please enter username and password'
    return
  }

  loading.value = true
  error.value = null

  const result = await authStore.login(username.value, password.value)

  if (result.success) {
    router.push({ name: 'dashboard' })
  } else {
    error.value = result.message || 'Login failed'
  }

  loading.value = false
}
</script>

<template>
  <div class="min-h-screen bg-dark-400 flex items-center justify-center p-4">
    <div class="w-full max-w-md">
      <!-- Header -->
      <div class="text-center mb-8">
        <h1 class="text-4xl font-bold bg-gradient-to-r from-primary-400 to-purple-400 bg-clip-text text-transparent">
          Light Logger
        </h1>
        <p class="text-slate-400 mt-2">Sign in to your account</p>
      </div>

      <!-- Login Card -->
      <div class="card">
        <form @submit.prevent="handleLogin" class="space-y-6">
          <!-- Error Message -->
          <div v-if="error" class="bg-red-500/10 border border-red-500/30 p-3">
            <p class="text-red-400 text-sm">{{ error }}</p>
          </div>

          <!-- Username Field -->
          <div>
            <label class="label">Username</label>
            <input
              v-model="username"
              type="text"
              class="input"
              placeholder="Enter your username"
              autocomplete="username"
              :disabled="loading"
              autofocus
            />
          </div>

          <!-- Password Field -->
          <div>
            <label class="label">Password</label>
            <input
              v-model="password"
              type="password"
              class="input"
              placeholder="Enter your password"
              autocomplete="current-password"
              :disabled="loading"
              @keyup.enter="handleLogin"
            />
          </div>

          <!-- Submit Button -->
          <button
            type="submit"
            class="btn btn-primary w-full"
            :disabled="loading || !username || !password"
          >
            {{ loading ? 'Signing in...' : 'Sign In' }}
          </button>
        </form>
      </div>

      <!-- Footer -->
      <div class="text-center mt-6 text-slate-500 text-sm">
        <p>Light Logger v1.0</p>
      </div>
    </div>
  </div>
</template>
