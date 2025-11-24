import { createRouter, createWebHistory } from 'vue-router'
import { useInstallStore } from '../stores/install'
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
    meta: { requiresInstall: false }
  },
  {
    path: '/',
    component: DashboardLayout,
    meta: { requiresInstall: true },
    children: [
      {
        path: 'dashboard',
        name: 'dashboard',
        component: () => import('../views/Dashboard.vue'),
      },
      {
        path: 'projects',
        name: 'projects',
        component: () => import('../views/Projects.vue'),
      },
      {
        path: 'logs',
        name: 'logs',
        component: () => import('../views/Dashboard.vue'), // Placeholder
      },
      {
        path: 'settings',
        name: 'settings',
        component: () => import('../views/Dashboard.vue'), // Placeholder
      },
    ]
  },
]

const router = createRouter({
  history: createWebHistory(),
  routes,
})

// Navigation guard to check installation status
router.beforeEach(async (to, from, next) => {
  const installStore = useInstallStore()

  // Always check installation status from backend on first navigation
  if (!installStore.checked) {
    await installStore.checkStatus()
  }

  // Handle root path
  if (to.path === '/') {
    if (installStore.installed) {
      next({ name: 'dashboard', replace: true })
    } else {
      next({ name: 'setup', replace: true })
    }
    return
  }

  // If not installed and trying to access protected route, redirect to setup
  if (to.meta.requiresInstall && !installStore.installed) {
    next({ name: 'setup', replace: true })
    return
  }

  // If installed and trying to access setup, redirect to dashboard
  if (to.name === 'setup' && installStore.installed) {
    next({ name: 'dashboard', replace: true })
    return
  }

  next()
})

export default router
