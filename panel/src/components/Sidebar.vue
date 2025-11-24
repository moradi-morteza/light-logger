<script setup>
import { ref } from 'vue'
import { RouterLink, useRoute } from 'vue-router'

const route = useRoute()
const isCollapsed = ref(false)

const menuItems = [
  { name: 'Dashboard', path: '/dashboard', icon: 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6' },
  { name: 'Projects', path: '/projects', icon: 'M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z' },
  { name: 'Logs', path: '/logs', icon: 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z' },
]

const isActive = (path) => {
  return route.path === path
}

const toggleSidebar = () => {
  isCollapsed.value = !isCollapsed.value
}
</script>

<template>
  <aside
    class="bg-dark-300 border-r border-slate-800 flex flex-col transition-all duration-300"
    :class="isCollapsed ? 'w-16' : 'w-56'"
  >
    <!-- Logo & Collapse Button -->
    <div class="h-14 flex items-center justify-between px-4 border-b border-slate-800">
      <div v-if="!isCollapsed" class="flex items-center gap-2">
        <div class="w-8 h-8 bg-gradient-to-br from-primary-600 to-primary-800 flex items-center justify-center">
          <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
          </svg>
        </div>
        <span class="font-semibold text-white text-sm">Light Logger</span>
      </div>
      <button
        @click="toggleSidebar"
        class="p-1.5 hover:bg-dark-100 transition-colors text-slate-400 hover:text-white"
        :class="isCollapsed ? 'mx-auto' : ''"
      >
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            :d="isCollapsed ? 'M13 5l7 7-7 7M5 5l7 7-7 7' : 'M11 19l-7-7 7-7m8 14l-7-7 7-7'"
          ></path>
        </svg>
      </button>
    </div>

    <!-- Navigation -->
    <nav class="flex-1 py-4 overflow-y-auto">
      <RouterLink
        v-for="item in menuItems"
        :key="item.path"
        :to="item.path"
        class="flex items-center gap-3 px-4 py-3 text-slate-400 hover:bg-dark-200 hover:text-white transition-colors border-l-2"
        :class="isActive(item.path) ? 'border-primary-600 bg-dark-200 text-white' : 'border-transparent'"
        :title="isCollapsed ? item.name : ''"
      >
        <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="item.icon"></path>
        </svg>
        <span v-if="!isCollapsed" class="text-sm font-medium">{{ item.name }}</span>
      </RouterLink>
    </nav>
  </aside>
</template>
