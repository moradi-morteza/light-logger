<script setup>
import { ref } from 'vue'

const searchQuery = ref('')
</script>

<template>
  <header class="h-14 bg-dark-300 border-b border-slate-800 flex items-center justify-between px-6">
    <!-- Search -->
    <div class="flex-1 max-w-md">
      <div class="relative">
        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
          <svg class="w-4 h-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
          </svg>
        </div>
        <input
          v-model="searchQuery"
          type="text"
          placeholder="Search..."
          class="w-full pl-10 pr-4 py-1.5 bg-dark-200 border border-slate-800 text-sm text-slate-100 placeholder-slate-600 focus:outline-none focus:border-primary-600"
        />
      </div>
    </div>

    <!-- Right Side -->
    <div class="flex items-center gap-2">
      <!-- User Info -->
      <div class="flex items-center gap-2 px-3 py-1.5 bg-dark-200 border border-slate-800">
        <div class="w-6 h-6 bg-gradient-to-br from-primary-600 to-primary-800 flex items-center justify-center">
          <span class="text-white text-xs font-medium">A</span>
        </div>
        <span class="text-sm text-slate-300">Admin</span>
      </div>
    </div>
  </header>
</template>
