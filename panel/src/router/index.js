import { createRouter, createWebHistory } from 'vue-router'
import { useInstallStore } from '../stores/install'
import { useAuthStore } from '../stores/auth'
import DashboardLayout from '../layouts/DashboardLayout.vue'

const routes = [
  {
    path: '/',
    name: 'home',
    redirect: () => {
      // This will be intercepted by beforeEach guard
      return { name: 'dashboard' }
    }
  },
  {
    path: '/setup',
    name: 'setup',
    component: () => import('../views/Setup.vue'),
    meta: { requiresInstall: false, requiresAuth: false }
  },
  {
    path: '/login',
    name: 'login',
    component: () => import('../views/Login.vue'),
    meta: { requiresInstall: true, requiresAuth: false }
  },
  {
    path: '/',
    component: DashboardLayout,
    meta: { requiresInstall: true, requiresAuth: true },
    children: [
      {
        path: 'dashboard',
        name: 'dashboard',
        component: () => import('../views/Dashboard.vue'),
        meta: { requiresAuth: true }
      },
      {
        path: 'projects',
        name: 'projects',
        component: () => import('../views/Projects.vue'),
        meta: { requiresAuth: true }
      },
      {
        path: 'projects/:id/schema',
        name: 'project-schema',
        component: () => import('../views/ProjectSchema.vue'),
        meta: { requiresAuth: true }
      },
      {
        path: 'logs',
        name: 'logs',
        component: () => import('../views/Dashboard.vue'), // Placeholder
        meta: { requiresAuth: true }
      },
    ]
  },
]

const router = createRouter({
  history: createWebHistory(),
  routes,
})

// Navigation guard to check installation and authentication
router.beforeEach(async (to, from, next) => {
  const installStore = useInstallStore()
  const authStore = useAuthStore()

  // Step 1: Check installation status
  if (!installStore.checked) {
    await installStore.checkStatus()
  }

  // If not installed, redirect to setup (except setup page itself)
  if (!installStore.installed && to.name !== 'setup') {
    next({ name: 'setup', replace: true })
    return
  }

  // If installed and trying to access setup, redirect to login or dashboard
  if (to.name === 'setup' && installStore.installed) {
    next({ name: 'login', replace: true })
    return
  }

  // Step 2: Check authentication for installed app
  if (installStore.installed) {
    // Check auth status if not checked yet
    if (!authStore.checked) {
      await authStore.checkAuth()
    }

    // Handle root path
    if (to.path === '/') {
      if (authStore.isAuthenticated) {
        next({ name: 'dashboard', replace: true })
      } else {
        next({ name: 'login', replace: true })
      }
      return
    }

    // If route requires auth and user is not authenticated
    if (to.meta.requiresAuth && !authStore.isAuthenticated) {
      next({ name: 'login', replace: true })
      return
    }

    // If authenticated and trying to access login, redirect to dashboard
    if (to.name === 'login' && authStore.isAuthenticated) {
      next({ name: 'dashboard', replace: true })
      return
    }
  }

  next()
})

export default router
