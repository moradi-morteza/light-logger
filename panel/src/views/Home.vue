<script setup>
import { useRouter } from 'vue-router'

const router = useRouter()

// Redirect to dashboard if installed
router.push({ name: 'dashboard' })
</script>

<template>
  <div class="min-h-screen flex items-center justify-center">
    <div class="text-center">
      <div class="animate-spin w-8 h-8 border-4 border-primary-500 border-t-transparent rounded-full mx-auto"></div>
      <p class="text-slate-400 mt-4">Loading...</p>
    </div>
  </div>
</template>
