<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useInstallStore } from '../stores/install'

const router = useRouter()
const installStore = useInstallStore()

const currentStep = ref(1)
const totalSteps = 4

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
        <div v-else-if="currentStep === 1" class="space-y-6">
          <div class="text-center">
            <h2 class="text-2xl font-bold text-white mb-2">Welcome to Light Logger</h2>
            <p class="text-slate-400">
              Let's configure your logging server. This wizard will guide you through
              setting up database and Redis connections.
            </p>
          </div>

          <div class="bg-slate-700/50 rounded-lg p-4">
            <h3 class="font-medium text-white mb-2">What you'll need:</h3>
            <ul class="text-slate-400 text-sm space-y-1">
              <li>&#8226; Database credentials (MariaDB or PostgreSQL)</li>
              <li>&#8226; Redis server connection details</li>
              <li>&#8226; Optional: Elasticsearch configuration</li>
            </ul>
          </div>

          <div class="bg-blue-500/10 border border-blue-500/30 rounded-lg p-4">
            <p class="text-blue-400 text-sm">
              <strong>Docker Users:</strong> Default values are pre-configured for Docker Compose setup.
              You can proceed with defaults if using the provided docker-compose.yml.
            </p>
          </div>
        </div>

        <!-- Step 2: Database Configuration -->
        <div v-else-if="currentStep === 2" class="space-y-6">
          <div>
            <h2 class="text-2xl font-bold text-white mb-2">Database Configuration</h2>
            <p class="text-slate-400">Configure your database connection.</p>
          </div>

          <div class="grid grid-cols-2 gap-4">
            <div class="col-span-2">
              <label class="label">Database Driver</label>
              <select v-model="config.database.driver" @change="updateDatabasePort" class="select">
                <option value="mariadb">MariaDB / MySQL</option>
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

            <div class="col-span-2">
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
          <div class="flex items-center gap-4">
            <button
              @click="testDatabaseConnection"
              :disabled="testing.database"
              class="btn btn-secondary"
            >
              {{ testing.database ? 'Testing...' : 'Test Connection' }}
            </button>

            <div v-if="testResults.database" class="flex items-center gap-2">
              <span
                class="w-3 h-3 rounded-full"
                :class="testResults.database.success ? 'bg-emerald-500' : 'bg-red-500'"
              ></span>
              <span :class="testResults.database.success ? 'text-emerald-400' : 'text-red-400'">
                {{ testResults.database.message }}
              </span>
            </div>
          </div>
        </div>

        <!-- Step 3: Redis Configuration -->
        <div v-else-if="currentStep === 3" class="space-y-6">
          <div>
            <h2 class="text-2xl font-bold text-white mb-2">Redis Configuration</h2>
            <p class="text-slate-400">Configure your Redis connection for caching and pub/sub.</p>
          </div>

          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="label">Host</label>
              <input v-model="config.redis.host" type="text" class="input" placeholder="localhost" />
            </div>

            <div>
              <label class="label">Port</label>
              <input v-model="config.redis.port" type="text" class="input" placeholder="6379" />
            </div>

            <div class="col-span-2">
              <label class="label">Password (optional)</label>
              <input v-model="config.redis.password" type="password" class="input" placeholder="Leave empty if no password" />
            </div>
          </div>

          <!-- Test Connection -->
          <div class="flex items-center gap-4">
            <button
              @click="testRedisConnection"
              :disabled="testing.redis"
              class="btn btn-secondary"
            >
              {{ testing.redis ? 'Testing...' : 'Test Connection' }}
            </button>

            <div v-if="testResults.redis" class="flex items-center gap-2">
              <span
                class="w-3 h-3 rounded-full"
                :class="testResults.redis.success ? 'bg-emerald-500' : 'bg-red-500'"
              ></span>
              <span :class="testResults.redis.success ? 'text-emerald-400' : 'text-red-400'">
                {{ testResults.redis.message }}
              </span>
            </div>
          </div>
        </div>

        <!-- Step 4: Review & Install -->
        <div v-else-if="currentStep === 4" class="space-y-6">
          <div>
            <h2 class="text-2xl font-bold text-white mb-2">Review Configuration</h2>
            <p class="text-slate-400">Review your settings before completing the installation.</p>
          </div>

          <!-- Summary -->
          <div class="space-y-4">
            <div class="bg-slate-700/50 rounded-lg p-4">
              <h3 class="font-medium text-white mb-2">Database</h3>
              <div class="text-slate-400 text-sm space-y-1">
                <p><span class="text-slate-500">Driver:</span> {{ config.database.driver }}</p>
                <p><span class="text-slate-500">Host:</span> {{ config.database.host }}:{{ config.database.port }}</p>
                <p><span class="text-slate-500">Database:</span> {{ config.database.database }}</p>
                <p><span class="text-slate-500">Username:</span> {{ config.database.username }}</p>
              </div>
              <div class="mt-2 flex items-center gap-2">
                <span
                  class="w-2 h-2 rounded-full"
                  :class="testResults.database?.success ? 'bg-emerald-500' : 'bg-yellow-500'"
                ></span>
                <span class="text-xs" :class="testResults.database?.success ? 'text-emerald-400' : 'text-yellow-400'">
                  {{ testResults.database?.success ? 'Connected' : 'Not tested' }}
                </span>
              </div>
            </div>

            <div class="bg-slate-700/50 rounded-lg p-4">
              <h3 class="font-medium text-white mb-2">Redis</h3>
              <div class="text-slate-400 text-sm space-y-1">
                <p><span class="text-slate-500">Host:</span> {{ config.redis.host }}:{{ config.redis.port }}</p>
                <p><span class="text-slate-500">Password:</span> {{ config.redis.password ? '••••••••' : 'None' }}</p>
              </div>
              <div class="mt-2 flex items-center gap-2">
                <span
                  class="w-2 h-2 rounded-full"
                  :class="testResults.redis?.success ? 'bg-emerald-500' : 'bg-yellow-500'"
                ></span>
                <span class="text-xs" :class="testResults.redis?.success ? 'text-emerald-400' : 'text-yellow-400'">
                  {{ testResults.redis?.success ? 'Connected' : 'Not tested' }}
                </span>
              </div>
            </div>
          </div>

          <!-- Warning if database not tested -->
          <div v-if="!testResults.database?.success" class="bg-yellow-500/10 border border-yellow-500/30 rounded-lg p-4">
            <p class="text-yellow-400 text-sm">
              <strong>Warning:</strong> Database connection has not been tested successfully.
              Please go back and test the connection before proceeding.
            </p>
          </div>

          <!-- Install Error -->
          <div v-if="installError" class="bg-red-500/10 border border-red-500/30 rounded-lg p-4">
            <p class="text-red-400 text-sm">{{ installError }}</p>
          </div>
        </div>

        <!-- Navigation Buttons -->
        <div v-if="!installSuccess" class="flex justify-between mt-8 pt-6 border-t border-slate-700">
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
