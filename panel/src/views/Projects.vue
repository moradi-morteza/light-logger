<script setup>
import { ref, onMounted } from 'vue'
import { useProjectsStore } from '../stores/projects'

const projectsStore = useProjectsStore()

const showCreateModal = ref(false)
const showDeleteModal = ref(false)
const showTokenModal = ref(false)

const newProjectName = ref('')
const createdProject = ref(null)
const projectToDelete = ref(null)
const deleteConfirmName = ref('')

onMounted(() => {
  projectsStore.fetchProjects()
})

function openCreateModal() {
  newProjectName.value = ''
  showCreateModal.value = true
}

async function createProject() {
  if (!newProjectName.value.trim()) return

  const result = await projectsStore.createProject(newProjectName.value)

  if (result.success) {
    showCreateModal.value = false
    createdProject.value = result.project
    showTokenModal.value = true
  }
}

function openDeleteModal(project) {
  projectToDelete.value = project
  deleteConfirmName.value = ''
  showDeleteModal.value = true
}

async function confirmDelete() {
  if (deleteConfirmName.value !== projectToDelete.value.name) return

  const result = await projectsStore.deleteProject(projectToDelete.value.id)

  if (result.success) {
    showDeleteModal.value = false
    projectToDelete.value = null
  }
}

function copyToken(token) {
  navigator.clipboard.writeText(token)
  // Could add a toast notification here
}

function maskToken(token) {
  if (token.length <= 12) return token
  return token.substring(0, 6) + '...' + token.substring(token.length - 6)
}

function formatDate(dateString) {
  return new Date(dateString).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  })
}
</script>

<template>
  <div class="p-6">
    <!-- Page Header -->
    <div class="mb-6 flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-bold text-white">Projects</h1>
        <p class="text-slate-400 text-sm mt-1">Manage your logging projects and tokens</p>
      </div>
      <button
        @click="openCreateModal"
        class="btn btn-primary flex items-center gap-2"
      >
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
        </svg>
        New Project
      </button>
    </div>

    <!-- Projects List -->
    <div v-if="projectsStore.loading && projectsStore.projects.length === 0" class="card text-center py-12">
      <div class="animate-spin w-8 h-8 border-4 border-primary-500 border-t-transparent mx-auto"></div>
      <p class="text-slate-400 mt-4">Loading projects...</p>
    </div>

    <div v-else-if="projectsStore.projects.length === 0" class="card text-center py-12">
      <svg class="w-16 h-16 text-slate-600 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"></path>
      </svg>
      <p class="text-slate-400 mb-4">No projects yet</p>
      <button @click="openCreateModal" class="btn btn-primary">Create Your First Project</button>
    </div>

    <div v-else class="grid grid-cols-1 gap-4">
      <div
        v-for="project in projectsStore.projects"
        :key="project.id"
        class="card flex items-center justify-between hover:border-slate-600 transition-colors"
      >
        <div class="flex-1">
          <h3 class="text-lg font-semibold text-white">{{ project.name }}</h3>
          <div class="flex items-center gap-4 mt-2 text-sm text-slate-400">
            <div class="flex items-center gap-2">
              <span>Token:</span>
              <code class="px-2 py-0.5 bg-dark-400 border border-slate-800 text-slate-300">{{ maskToken(project.token) }}</code>
              <button
                @click="copyToken(project.token)"
                class="p-1 hover:bg-dark-100 transition-colors"
                title="Copy token"
              >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                </svg>
              </button>
            </div>
            <span>•</span>
            <span>Created {{ formatDate(project.created_at) }}</span>
          </div>
        </div>
        <button
          @click="openDeleteModal(project)"
          class="p-2 text-red-400 hover:bg-red-500/10 transition-colors"
          title="Delete project"
        >
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
          </svg>
        </button>
      </div>
    </div>

    <!-- Create Project Modal -->
    <div v-if="showCreateModal" class="fixed inset-0 bg-black/50 backdrop-blur-sm flex items-center justify-center z-50" @click.self="showCreateModal = false">
      <div class="card max-w-md w-full mx-4">
        <h2 class="text-xl font-bold text-white mb-4">Create New Project</h2>
        <div class="mb-4">
          <label class="label">Project Name</label>
          <input
            v-model="newProjectName"
            type="text"
            class="input"
            placeholder="Enter project name"
            @keyup.enter="createProject"
            autofocus
          />
        </div>
        <div class="flex justify-end gap-3">
          <button @click="showCreateModal = false" class="btn btn-secondary">Cancel</button>
          <button
            @click="createProject"
            :disabled="!newProjectName.trim() || projectsStore.loading"
            class="btn btn-primary"
          >
            {{ projectsStore.loading ? 'Creating...' : 'Create Project' }}
          </button>
        </div>
      </div>
    </div>

    <!-- Token Display Modal -->
    <div v-if="showTokenModal && createdProject" class="fixed inset-0 bg-black/50 backdrop-blur-sm flex items-center justify-center z-50" @click.self="showTokenModal = false">
      <div class="card max-w-lg w-full mx-4">
        <div class="flex items-center gap-3 mb-4">
          <div class="w-12 h-12 bg-emerald-500/20 flex items-center justify-center">
            <svg class="w-6 h-6 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
            </svg>
          </div>
          <div>
            <h2 class="text-xl font-bold text-white">Project Created!</h2>
            <p class="text-slate-400 text-sm">{{ createdProject.name }}</p>
          </div>
        </div>

        <div class="bg-dark-400 border border-slate-800 p-4 mb-4">
          <div class="flex items-center justify-between mb-2">
            <span class="text-sm text-slate-400">Project Token</span>
            <button
              @click="copyToken(createdProject.token)"
              class="text-sm text-primary-400 hover:text-primary-300 flex items-center gap-1"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
              </svg>
              Copy
            </button>
          </div>
          <code class="block text-sm text-slate-300 break-all font-mono">{{ createdProject.token }}</code>
        </div>

        <div class="bg-blue-500/10 border border-blue-500/30 p-4 mb-4">
          <p class="text-blue-400 text-sm">
            <strong>Important:</strong> Save this token securely. You'll need it to send logs to this project.
          </p>
        </div>

        <div class="flex justify-end">
          <button @click="showTokenModal = false; createdProject = null" class="btn btn-primary">
            Got it
          </button>
        </div>
      </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div v-if="showDeleteModal && projectToDelete" class="fixed inset-0 bg-black/50 backdrop-blur-sm flex items-center justify-center z-50" @click.self="showDeleteModal = false">
      <div class="card max-w-md w-full mx-4">
        <div class="flex items-center gap-3 mb-4">
          <div class="w-12 h-12 bg-red-500/20 flex items-center justify-center">
            <svg class="w-6 h-6 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
            </svg>
          </div>
          <div>
            <h2 class="text-xl font-bold text-white">Delete Project?</h2>
            <p class="text-slate-400 text-sm">This action cannot be undone</p>
          </div>
        </div>

        <div class="bg-dark-400 border border-slate-800 p-4 mb-4">
          <p class="text-slate-300 text-sm mb-2">You are about to delete:</p>
          <p class="text-white font-semibold">{{ projectToDelete.name }}</p>
        </div>

        <div class="mb-4">
          <label class="label">Type project name to confirm:</label>
          <input
            v-model="deleteConfirmName"
            type="text"
            class="input"
            :placeholder="projectToDelete.name"
          />
        </div>

        <div class="flex justify-end gap-3">
          <button @click="showDeleteModal = false" class="btn btn-secondary">Cancel</button>
          <button
            @click="confirmDelete"
            :disabled="deleteConfirmName !== projectToDelete.name || projectsStore.loading"
            class="bg-red-600 hover:bg-red-700 disabled:opacity-50 disabled:cursor-not-allowed text-white px-4 py-2 border border-red-700 font-medium transition-colors"
          >
            {{ projectsStore.loading ? 'Deleting...' : 'Delete Project' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>
