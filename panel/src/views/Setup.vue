<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useInstallStore } from '../stores/install'

const router = useRouter()
const installStore = useInstallStore()

const currentStep = ref(1)
const totalSteps = 5

const config = reactive({
  app: {
    debug: 'true',
    host: '0.0.0.0',
    port: '9501',
    env: 'local',
  },
  database: {
    driver: 'mariadb',
    host: 'mariadb',
    port: '3306',
    database: 'light_logger',
    username: 'light_logger',
    password: 'secret',
  },
  redis: {
    host: 'redis',
    port: '6379',
    password: '',
  },
  elasticsearch: {
    host: 'elasticsearch',
    port: '9200',
  },
  user: {
    username: '',
    email: '',
    password: '',
    confirmPassword: '',
  },
})

const testResults = reactive({
  database: null,
  redis: null,
})

const testing = reactive({
  database: false,
  redis: false,
})

const installing = ref(false)
const installError = ref(null)
const installSuccess = ref(false)

// Update port based on driver selection
const updateDatabasePort = () => {
  if (config.database.driver === 'postgres') {
    config.database.port = '5432'
  } else {
    config.database.port = '3306'
  }
}

// Load defaults on mount (but keep password fields from local defaults)
onMounted(async () => {
  await installStore.checkStatus()
  if (installStore.defaults.database) {
    const savedPassword = config.database.password
    Object.assign(config.database, installStore.defaults.database)
    // Keep local password if API returned empty (for security, API doesn't expose passwords)
    if (!config.database.password) {
      config.database.password = savedPassword
    }
  }
  if (installStore.defaults.redis) {
    const savedPassword = config.redis.password
    Object.assign(config.redis, installStore.defaults.redis)
    if (!config.redis.password) {
      config.redis.password = savedPassword
    }
  }
  if (installStore.defaults.app) {
    Object.assign(config.app, installStore.defaults.app)
  }
})

const canProceed = computed(() => {
  switch (currentStep.value) {
    case 1:
      return true
    case 2:
      return config.database.host && config.database.database && config.database.username
    case 3:
      return config.redis.host && config.redis.port
    case 4:
      return config.user.username && config.user.email && config.user.password &&
             config.user.password === config.user.confirmPassword
    case 5:
      return testResults.database?.success
    default:
      return false
  }
})

async function testDatabaseConnection() {
  testing.database = true
  testResults.database = null

  const result = await installStore.testDatabase(config.database)
  testResults.database = result
  testing.database = false
}

async function testRedisConnection() {
  testing.redis = true
  testResults.redis = null

  const result = await installStore.testRedis(config.redis)
  testResults.redis = result
  testing.redis = false
}

function nextStep() {
  if (currentStep.value < totalSteps && canProceed.value) {
    currentStep.value++
  }
}

function prevStep() {
  if (currentStep.value > 1) {
    currentStep.value--
  }
}

async function completeInstallation() {
  installing.value = true
  installError.value = null

  const result = await installStore.completeInstallation(config)

  if (result.success) {
    installSuccess.value = true
  } else {
    installError.value = result.message
  }

  installing.value = false
}

function goToDashboard() {
  router.push({ name: 'dashboard' })
}
</script>

<template>
  <div class="min-h-screen flex items-center justify-center p-4">
    <div class="w-full max-w-2xl">
      <!-- Header -->
      <div class="text-center mb-8">
        <h1 class="text-4xl font-bold bg-gradient-to-r from-primary-400 to-purple-400 bg-clip-text text-transparent">
          Light Logger
        </h1>
        <p class="text-slate-400 mt-2">Setup Wizard</p>
      </div>

      <!-- Progress Steps -->
      <div class="flex items-center justify-center mb-8">
        <template v-for="step in totalSteps" :key="step">
          <div
            class="w-10 h-10 rounded-full flex items-center justify-center font-medium transition-colors"
            :class="[
              step === currentStep ? 'bg-primary-600 text-white' :
              step < currentStep ? 'bg-emerald-600 text-white' :
              'bg-slate-700 text-slate-400'
            ]"
          >
            <span v-if="step < currentStep">&#10003;</span>
            <span v-else>{{ step }}</span>
          </div>
          <div
            v-if="step < totalSteps"
            class="w-16 h-1 mx-2 rounded transition-colors"
            :class="step < currentStep ? 'bg-emerald-600' : 'bg-slate-700'"
          ></div>
        </template>
      </div>

      <!-- Card Container -->
      <div class="card">
        <!-- Success State -->
        <div v-if="installSuccess" class="text-center py-8">
          <div class="w-20 h-20 bg-emerald-600 rounded-full flex items-center justify-center mx-auto mb-4">
            <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
            </svg>
          </div>
          <h2 class="text-2xl font-bold text-white mb-2">Installation Complete!</h2>
          <p class="text-slate-400 mb-6">
            Light Logger has been configured successfully.<br>
            Please restart the server to apply changes.
          </p>
          <button @click="goToDashboard" class="btn btn-primary">
            Go to Dashboard
          </button>
        </div>

        <!-- Step 1: Welcome -->
        <div v-else-if="currentStep === 1" class="space-y-4">
          <div class="text-center">
            <h2 class="text-2xl font-bold text-white mb-2">Welcome to Light Logger</h2>
            <p class="text-slate-400 text-sm">
              Let's configure your logging server. This wizard will guide you through
              setting up database and Redis connections.
            </p>
          </div>

          <div class="grid grid-cols-2 gap-3">
            <div class="bg-slate-700/50 p-3">
              <h3 class="font-medium text-white mb-2 text-sm">What you'll need:</h3>
              <ul class="text-slate-400 text-xs space-y-1">
                <li>&#8226; Database credentials (MariaDB or PostgreSQL)</li>
                <li>&#8226; Redis server connection details</li>
                <li>&#8226; Admin account details (username, email, password)</li>
              </ul>
            </div>

            <div class="bg-blue-500/10 border border-blue-500/30 p-3">
              <h3 class="font-medium text-blue-400 mb-2 text-sm">Docker Users:</h3>
              <p class="text-blue-400 text-xs">
                Default values are pre-configured for Docker Compose setup.
                You can proceed with defaults if using the provided docker-compose.yml.
              </p>
            </div>
          </div>
        </div>

        <!-- Step 2: Database Configuration -->
        <div v-else-if="currentStep === 2" class="space-y-4">
          <div>
            <h2 class="text-xl font-bold text-white mb-1">Database Configuration</h2>
            <p class="text-slate-400 text-sm">Configure your database connection.</p>
          </div>

          <div class="grid grid-cols-3 gap-3">
            <div>
              <label class="label">Driver</label>
              <select v-model="config.database.driver" @change="updateDatabasePort" class="select">
                <option value="mariadb">MariaDB</option>
                <option value="postgres">PostgreSQL</option>
              </select>
            </div>

            <div>
              <label class="label">Host</label>
              <input v-model="config.database.host" type="text" class="input" placeholder="localhost" />
            </div>

            <div>
              <label class="label">Port</label>
              <input v-model="config.database.port" type="text" class="input" placeholder="3306" />
            </div>

            <div>
              <label class="label">Database Name</label>
              <input v-model="config.database.database" type="text" class="input" placeholder="light_logger" />
            </div>

            <div>
              <label class="label">Username</label>
              <input v-model="config.database.username" type="text" class="input" placeholder="root" />
            </div>

            <div>
              <label class="label">Password</label>
              <input v-model="config.database.password" type="password" class="input" placeholder="••••••••" />
            </div>
          </div>

          <!-- Test Connection -->
          <div class="flex items-center gap-3">
            <button
              @click="testDatabaseConnection"
              :disabled="testing.database"
              class="btn btn-secondary"
            >
              {{ testing.database ? 'Testing...' : 'Test Connection' }}
            </button>

            <div v-if="testResults.database" class="flex items-center gap-2">
              <span
                class="w-2 h-2"
                :class="testResults.database.success ? 'bg-emerald-500' : 'bg-red-500'"
              ></span>
              <span class="text-sm" :class="testResults.database.success ? 'text-emerald-400' : 'text-red-400'">
                {{ testResults.database.message }}
              </span>
            </div>
          </div>
        </div>

        <!-- Step 3: Redis Configuration -->
        <div v-else-if="currentStep === 3" class="space-y-4">
          <div>
            <h2 class="text-xl font-bold text-white mb-1">Redis Configuration</h2>
            <p class="text-slate-400 text-sm">Configure your Redis connection for caching and pub/sub.</p>
          </div>

          <div class="grid grid-cols-3 gap-3">
            <div>
              <label class="label">Host</label>
              <input v-model="config.redis.host" type="text" class="input" placeholder="localhost" />
            </div>

            <div>
              <label class="label">Port</label>
              <input v-model="config.redis.port" type="text" class="input" placeholder="6379" />
            </div>

            <div>
              <label class="label">Password (optional)</label>
              <input v-model="config.redis.password" type="password" class="input" placeholder="Leave empty if none" />
            </div>
          </div>

          <!-- Test Connection -->
          <div class="flex items-center gap-3">
            <button
              @click="testRedisConnection"
              :disabled="testing.redis"
              class="btn btn-secondary"
            >
              {{ testing.redis ? 'Testing...' : 'Test Connection' }}
            </button>

            <div v-if="testResults.redis" class="flex items-center gap-2">
              <span
                class="w-2 h-2"
                :class="testResults.redis.success ? 'bg-emerald-500' : 'bg-red-500'"
              ></span>
              <span class="text-sm" :class="testResults.redis.success ? 'text-emerald-400' : 'text-red-400'">
                {{ testResults.redis.message }}
              </span>
            </div>
          </div>
        </div>

        <!-- Step 4: Create Admin Account -->
        <div v-else-if="currentStep === 4" class="space-y-4">
          <div>
            <h2 class="text-xl font-bold text-white mb-1">Create Admin Account</h2>
            <p class="text-slate-400 text-sm">Create your administrator account to access the panel.</p>
          </div>

          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="label">Username</label>
              <input
                v-model="config.user.username"
                type="text"
                class="input"
                placeholder="admin"
                autocomplete="username"
              />
            </div>

            <div>
              <label class="label">Email</label>
              <input
                v-model="config.user.email"
                type="email"
                class="input"
                placeholder="admin@example.com"
                autocomplete="email"
              />
            </div>

            <div>
              <label class="label">Password</label>
              <input
                v-model="config.user.password"
                type="password"
                class="input"
                placeholder="Min 8 characters"
                autocomplete="new-password"
              />
            </div>

            <div>
              <label class="label">Confirm Password</label>
              <input
                v-model="config.user.confirmPassword"
                type="password"
                class="input"
                placeholder="Confirm password"
                autocomplete="new-password"
              />
            </div>
          </div>

          <div v-if="config.user.password && config.user.confirmPassword && config.user.password !== config.user.confirmPassword" class="bg-red-500/10 border border-red-500/30 p-2">
            <p class="text-red-400 text-sm">Passwords do not match</p>
          </div>

          <div v-if="config.user.password && config.user.password.length < 8" class="bg-yellow-500/10 border border-yellow-500/30 p-2">
            <p class="text-yellow-400 text-sm">Password should be at least 8 characters</p>
          </div>
        </div>

        <!-- Step 5: Review & Install -->
        <div v-else-if="currentStep === 5" class="space-y-4">
          <div>
            <h2 class="text-xl font-bold text-white mb-1">Review Configuration</h2>
            <p class="text-slate-400 text-sm">Review your settings before completing the installation.</p>
          </div>

          <!-- Summary in 2 columns -->
          <div class="grid grid-cols-2 gap-3">
            <div class="bg-slate-700/50 p-3">
              <h3 class="font-medium text-white mb-2 text-sm">Database</h3>
              <div class="text-slate-400 text-xs space-y-1">
                <p><span class="text-slate-500">Driver:</span> {{ config.database.driver }}</p>
                <p><span class="text-slate-500">Host:</span> {{ config.database.host }}:{{ config.database.port }}</p>
                <p><span class="text-slate-500">Database:</span> {{ config.database.database }}</p>
                <p><span class="text-slate-500">Username:</span> {{ config.database.username }}</p>
              </div>
              <div class="mt-2 flex items-center gap-2">
                <span
                  class="w-2 h-2"
                  :class="testResults.database?.success ? 'bg-emerald-500' : 'bg-yellow-500'"
                ></span>
                <span class="text-xs" :class="testResults.database?.success ? 'text-emerald-400' : 'text-yellow-400'">
                  {{ testResults.database?.success ? 'Connected' : 'Not tested' }}
                </span>
              </div>
            </div>

            <div class="bg-slate-700/50 p-3">
              <h3 class="font-medium text-white mb-2 text-sm">Redis</h3>
              <div class="text-slate-400 text-xs space-y-1">
                <p><span class="text-slate-500">Host:</span> {{ config.redis.host }}:{{ config.redis.port }}</p>
                <p><span class="text-slate-500">Password:</span> {{ config.redis.password ? '••••••••' : 'None' }}</p>
              </div>
              <div class="mt-2 flex items-center gap-2">
                <span
                  class="w-2 h-2"
                  :class="testResults.redis?.success ? 'bg-emerald-500' : 'bg-yellow-500'"
                ></span>
                <span class="text-xs" :class="testResults.redis?.success ? 'text-emerald-400' : 'text-yellow-400'">
                  {{ testResults.redis?.success ? 'Connected' : 'Not tested' }}
                </span>
              </div>
            </div>

            <div class="bg-slate-700/50 p-3 col-span-2">
              <h3 class="font-medium text-white mb-2 text-sm">Admin Account</h3>
              <div class="text-slate-400 text-xs space-y-1">
                <p><span class="text-slate-500">Username:</span> {{ config.user.username }}</p>
                <p><span class="text-slate-500">Email:</span> {{ config.user.email }}</p>
              </div>
            </div>
          </div>

          <!-- Warning if database not tested -->
          <div v-if="!testResults.database?.success" class="bg-yellow-500/10 border border-yellow-500/30 p-2">
            <p class="text-yellow-400 text-sm">
              <strong>Warning:</strong> Database connection has not been tested successfully.
            </p>
          </div>

          <!-- Install Error -->
          <div v-if="installError" class="bg-red-500/10 border border-red-500/30 p-2">
            <p class="text-red-400 text-sm">{{ installError }}</p>
          </div>
        </div>

        <!-- Navigation Buttons -->
        <div v-if="!installSuccess" class="flex justify-between mt-6 pt-4 border-t border-slate-700">
          <button
            v-if="currentStep > 1"
            @click="prevStep"
            class="btn btn-secondary"
          >
            Back
          </button>
          <div v-else></div>

          <button
            v-if="currentStep < totalSteps"
            @click="nextStep"
            :disabled="!canProceed"
            class="btn btn-primary"
          >
            Continue
          </button>

          <button
            v-else
            @click="completeInstallation"
            :disabled="installing || !testResults.database?.success"
            class="btn btn-success"
          >
            {{ installing ? 'Installing...' : 'Complete Installation' }}
          </button>
        </div>
      </div>

      <!-- Footer -->
      <p class="text-center text-slate-500 text-sm mt-6">
        Light Logger v0.1.0
      </p>
    </div>
  </div>
</template>
