import { defineStore } from 'pinia'
import { ref } from 'vue'
import { api } from '../utils/api'

export const useProjectsStore = defineStore('projects', () => {
  const projects = ref([])
  const loading = ref(false)
  const error = ref(null)

  async function fetchProjects() {
    loading.value = true
    error.value = null

    try {
      const response = await api.get('/api/projects')
      const data = await response.json()

      if (data.success) {
        projects.value = data.data
      } else {
        error.value = data.message
      }
    } catch (e) {
      error.value = 'Failed to fetch projects'
      console.error(e)
    } finally {
      loading.value = false
    }
  }

  async function createProject(name) {
    loading.value = true
    error.value = null

    try {
      const response = await api.post('/api/projects', { name })
      const data = await response.json()

      if (data.success) {
        projects.value.unshift(data.data)
        return { success: true, project: data.data }
      } else {
        error.value = data.message
        return { success: false, message: data.message }
      }
    } catch (e) {
      error.value = 'Failed to create project'
      return { success: false, message: error.value }
    } finally {
      loading.value = false
    }
  }

  async function deleteProject(id) {
    loading.value = true
    error.value = null

    try {
      const response = await api.delete(`/api/projects/${id}`)
      const data = await response.json()

      if (data.success) {
        projects.value = projects.value.filter(p => p.id !== id)
        return { success: true }
      } else {
        error.value = data.message
        return { success: false, message: data.message }
      }
    } catch (e) {
      error.value = 'Failed to delete project'
      return { success: false, message: error.value }
    } finally {
      loading.value = false
    }
  }

  return {
    projects,
    loading,
    error,
    fetchProjects,
    createProject,
    deleteProject,
  }
})
