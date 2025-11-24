<script setup>
import { ref, onMounted } from 'vue'
import { useProjectsStore } from '../stores/projects'

const projectsStore = useProjectsStore()

const stats = ref({
  logs_today: 0,
  storage_used: '0 MB',
})

const recentLogs = ref([])

onMounted(async () => {
  await projectsStore.fetchProjects()
})
</script>

<template>
  <div class="p-6">
    <!-- Page Header -->
    <div class="mb-6">
      <h1 class="text-2xl font-bold text-white">Dashboard</h1>
      <p class="text-slate-400 text-sm mt-1">Overview of your logging activity</p>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
      <div class="card">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-slate-400 text-sm">Logs Today</p>
            <p class="text-3xl font-bold text-white mt-1">{{ stats.logs_today }}</p>
          </div>
          <div class="w-12 h-12 bg-primary-600/20 flex items-center justify-center">
            <svg class="w-6 h-6 text-primary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
            </svg>
          </div>
        </div>
      </div>

      <div class="card">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-slate-400 text-sm">Projects</p>
            <p class="text-3xl font-bold text-white mt-1">{{ projectsStore.projects.length }}</p>
          </div>
          <div class="w-12 h-12 bg-emerald-600/20 flex items-center justify-center">
            <svg class="w-6 h-6 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"></path>
            </svg>
          </div>
        </div>
      </div>

      <div class="card">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-slate-400 text-sm">Storage Used</p>
            <p class="text-3xl font-bold text-white mt-1">{{ stats.storage_used }}</p>
          </div>
          <div class="w-12 h-12 bg-purple-600/20 flex items-center justify-center">
            <svg class="w-6 h-6 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4"></path>
            </svg>
          </div>
        </div>
      </div>
    </div>

    <!-- Recent Logs -->
    <div class="card">
      <h2 class="text-lg font-semibold text-white mb-4">Recent Logs</h2>

      <div v-if="recentLogs.length === 0" class="text-center py-12">
        <svg class="w-16 h-16 text-slate-600 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
        </svg>
        <p class="text-slate-400">No logs yet</p>
        <p class="text-slate-500 text-sm mt-1">Logs will appear here once your applications start sending them.</p>
      </div>

      <div v-else class="space-y-2">
        <div
          v-for="log in recentLogs"
          :key="log.id"
          class="flex items-start gap-3 p-3 bg-dark-300 border border-slate-800"
        >
          <span
            class="px-2 py-0.5 text-xs font-medium"
            :class="{
              'bg-red-500/20 text-red-400': log.level === 'error',
              'bg-yellow-500/20 text-yellow-400': log.level === 'warning',
              'bg-blue-500/20 text-blue-400': log.level === 'info',
              'bg-slate-500/20 text-slate-400': log.level === 'debug',
            }"
          >
            {{ log.level }}
          </span>
          <div class="flex-1 min-w-0">
            <p class="text-white truncate">{{ log.message }}</p>
            <p class="text-slate-500 text-xs mt-1">{{ log.timestamp }}</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
