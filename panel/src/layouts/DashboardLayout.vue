<script setup>
import Sidebar from '../components/Sidebar.vue'
import Toolbar from '../components/Toolbar.vue'
</script>

<template>
  <div class="min-h-screen bg-dark-400 flex">
    <!-- Sidebar -->
    <Sidebar />

    <!-- Main Content -->
    <div class="flex-1 flex flex-col min-w-0">
      <!-- Toolbar -->
      <Toolbar />

      <!-- Page Content -->
      <main class="flex-1 overflow-y-auto bg-dark-400">
        <RouterView />
      </main>
    </div>
  </div>
</template>
