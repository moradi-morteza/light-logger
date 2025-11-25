<script setup>
import { ref, onMounted, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useProjectsStore } from '../stores/projects'

const route = useRoute()
const router = useRouter()
const projectsStore = useProjectsStore()

const projectId = computed(() => parseInt(route.params.id))
const project = ref(null)
const loading = ref(true)
const saving = ref(false)
const error = ref(null)

const fields = ref([])

const fieldTypes = [
  { value: 'string', label: 'String (Text)' },
  { value: 'number', label: 'Number' },
  { value: 'boolean', label: 'Boolean (true/false)' },
  { value: 'array', label: 'Array (List)' },
  { value: 'object', label: 'Object (JSON)' },
  { value: 'datetime', label: 'DateTime' }
]

// Computed property for log structure preview
const logStructurePreview = computed(() => {
  const dataFields = {}

  fields.value.forEach(field => {
    if (field.name && field.name.trim()) {
      let exampleValue

      switch (field.type) {
        case 'string':
          exampleValue = field.validation?.enum?.[0] || 'example_value'
          break
        case 'number':
          exampleValue = field.validation?.min || 123
          break
        case 'boolean':
          exampleValue = true
          break
        case 'array':
          exampleValue = ['item1', 'item2']
          break
        case 'object':
          exampleValue = { key: 'value' }
          break
        case 'datetime':
          exampleValue = '2025-11-25T10:30:45.123Z'
          break
        default:
          exampleValue = 'value'
      }

      dataFields[field.name] = exampleValue
    }
  })

  return {
    timestamp: '2025-11-25T10:30:45.123Z',
    level: 'info',
    title: 'Event description',
    data: dataFields
  }
})

onMounted(async () => {
  await loadProject()
})

async function loadProject() {
  loading.value = true
  error.value = null

  try {
    // Fetch project details
    const result = await projectsStore.fetchProject(projectId.value)

    if (result.success) {
      project.value = result.project

      // Load existing schema if available
      if (project.value.schema && project.value.schema.fields) {
        fields.value = project.value.schema.fields.map(f => ({
          ...f,
          validation: f.validation || {},
          showValidation: false
        }))
      }

      // If no fields, add one empty field to start
      if (fields.value.length === 0) {
        addField()
      }
    } else {
      error.value = 'Project not found'
    }
  } catch (err) {
    error.value = 'Failed to load project'
  } finally {
    loading.value = false
  }
}

function addField() {
  fields.value.push({
    name: '',
    type: 'string',
    indexed: true,
    required: false,
    description: '',
    validation: {},
    showValidation: false
  })
}

function removeField(index) {
  fields.value.splice(index, 1)
}

function toggleValidation(index) {
  fields.value[index].showValidation = !fields.value[index].showValidation
}

function addEnumValue(field) {
  if (!field.validation.enum) {
    field.validation.enum = []
  }
  field.validation.enum.push('')
}

function removeEnumValue(field, enumIndex) {
  field.validation.enum.splice(enumIndex, 1)
}

function validateFields() {
  const errors = []

  fields.value.forEach((field, index) => {
    if (!field.name || !field.name.trim()) {
      errors.push(`Field #${index + 1}: Name is required`)
    } else if (!/^[a-zA-Z_][a-zA-Z0-9_]*$/.test(field.name)) {
      errors.push(`Field #${index + 1}: Name must start with letter or underscore, and contain only letters, numbers, and underscores`)
    }

    if (!field.type) {
      errors.push(`Field #${index + 1}: Type is required`)
    }
  })

  // Check for duplicate names
  const names = fields.value.map(f => f.name.trim().toLowerCase()).filter(n => n)
  const duplicates = names.filter((name, index) => names.indexOf(name) !== index)

  if (duplicates.length > 0) {
    errors.push(`Duplicate field names: ${[...new Set(duplicates)].join(', ')}`)
  }

  return errors
}

async function saveSchema() {
  const validationErrors = validateFields()

  if (validationErrors.length > 0) {
    error.value = validationErrors.join('; ')
    return
  }

  saving.value = true
  error.value = null

  try {
    // Clean up fields before saving
    const cleanedFields = fields.value.map(field => {
      const cleaned = {
        name: field.name.trim(),
        type: field.type,
        indexed: field.indexed,
        required: field.required
      }

      if (field.description && field.description.trim()) {
        cleaned.description = field.description.trim()
      }

      // Add validation rules if present
      const validation = {}

      if (field.type === 'string') {
        if (field.validation.min_length) validation.min_length = parseInt(field.validation.min_length)
        if (field.validation.max_length) validation.max_length = parseInt(field.validation.max_length)
        if (field.validation.pattern) validation.pattern = field.validation.pattern
        if (field.validation.enum && field.validation.enum.length > 0) {
          validation.enum = field.validation.enum.filter(v => v.trim())
        }
      }

      if (field.type === 'number') {
        if (field.validation.min !== undefined && field.validation.min !== '') {
          validation.min = parseFloat(field.validation.min)
        }
        if (field.validation.max !== undefined && field.validation.max !== '') {
          validation.max = parseFloat(field.validation.max)
        }
      }

      if (Object.keys(validation).length > 0) {
        cleaned.validation = validation
      }

      return cleaned
    })

    const result = await projectsStore.updateSchema(projectId.value, {
      fields: cleanedFields
    })

    if (result.success) {
      router.push({ name: 'projects' })
    } else {
      error.value = result.message || 'Failed to save schema'
    }
  } catch (err) {
    error.value = 'Failed to save schema'
  } finally {
    saving.value = false
  }
}
</script>

<template>
  <div class="p-6">
    <!-- Loading State -->
    <div v-if="loading" class="text-center py-12">
      <div class="animate-spin w-8 h-8 border-4 border-primary-500 border-t-transparent mx-auto"></div>
      <p class="text-slate-400 mt-4">Loading project...</p>
    </div>

    <!-- Content -->
    <div v-else-if="project">
      <!-- Header -->
      <div class="mb-6">
        <button
          @click="router.push({ name: 'projects' })"
          class="text-slate-400 hover:text-slate-300 mb-4 flex items-center gap-2"
        >
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
          </svg>
          Back to Projects
        </button>

        <h1 class="text-2xl font-bold text-white">Define Log Fields</h1>
        <p class="text-slate-400 text-sm mt-1">{{ project.name }}</p>
      </div>

      <!-- Error Message -->
      <div v-if="error" class="bg-red-500/10 border border-red-500/30 p-4 mb-6 flex items-start gap-3">
        <svg class="w-5 h-5 text-red-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
        </svg>
        <div class="flex-1">
          <p class="text-red-400 font-medium">{{ error }}</p>
        </div>
        <button @click="error = null" class="text-red-400 hover:text-red-300">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
          </svg>
        </button>
      </div>

      <!-- Info Box -->
      <div class="bg-blue-500/10 border border-blue-500/30 p-4 mb-6">
        <p class="text-blue-400 text-sm">
          Define custom fields for your logs. These fields will be validated when you send logs to this project.
          Core fields (timestamp, level, title) are always required.
        </p>
      </div>

      <!-- Two Column Layout -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        <!-- Left Column: Fields List -->
        <div class="space-y-4">
          <h2 class="text-lg font-semibold text-white mb-4">Custom Fields</h2>
      <div class="space-y-4 mb-6">
        <div
          v-for="(field, index) in fields"
          :key="index"
          class="card"
        >
          <div class="flex items-start justify-between mb-4">
            <h3 class="text-lg font-semibold text-white">Field #{{ index + 1 }}</h3>
            <button
              @click="removeField(index)"
              class="text-red-400 hover:text-red-300 p-1"
              title="Remove field"
            >
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
              </svg>
            </button>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
            <div>
              <label class="label">Field Name *</label>
              <input
                v-model="field.name"
                type="text"
                class="input"
                placeholder="e.g., user_id"
              />
              <p class="text-xs text-slate-500 mt-1">Must start with letter or underscore</p>
            </div>

            <div>
              <label class="label">Type *</label>
              <select v-model="field.type" class="input">
                <option v-for="type in fieldTypes" :key="type.value" :value="type.value">
                  {{ type.label }}
                </option>
              </select>
            </div>
          </div>

          <div class="mb-4">
            <label class="label">Description</label>
            <input
              v-model="field.description"
              type="text"
              class="input"
              placeholder="Optional description"
            />
          </div>

          <div class="flex gap-6 mb-4">
            <label class="flex items-center gap-2 cursor-pointer">
              <input
                v-model="field.indexed"
                type="checkbox"
                class="w-4 h-4 bg-dark-300 border-slate-700 text-primary-500 focus:ring-primary-500"
              />
              <span class="text-slate-300 text-sm">Indexed (searchable/filterable)</span>
            </label>

            <label class="flex items-center gap-2 cursor-pointer">
              <input
                v-model="field.required"
                type="checkbox"
                class="w-4 h-4 bg-dark-300 border-slate-700 text-primary-500 focus:ring-primary-500"
              />
              <span class="text-slate-300 text-sm">Required</span>
            </label>
          </div>

          <!-- Validation Rules Toggle -->
          <button
            @click="toggleValidation(index)"
            class="text-sm text-primary-400 hover:text-primary-300 flex items-center gap-2 mb-3"
          >
            <svg
              class="w-4 h-4 transition-transform"
              :class="{ 'rotate-90': field.showValidation }"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
            </svg>
            {{ field.showValidation ? 'Hide' : 'Show' }} Validation Rules
          </button>

          <!-- Validation Rules -->
          <div v-if="field.showValidation" class="bg-dark-400 border border-slate-800 p-4">
            <!-- String Validations -->
            <div v-if="field.type === 'string'" class="space-y-3">
              <div class="grid grid-cols-2 gap-3">
                <div>
                  <label class="label text-xs">Min Length</label>
                  <input
                    v-model="field.validation.min_length"
                    type="number"
                    class="input text-sm"
                    placeholder="e.g., 3"
                  />
                </div>
                <div>
                  <label class="label text-xs">Max Length</label>
                  <input
                    v-model="field.validation.max_length"
                    type="number"
                    class="input text-sm"
                    placeholder="e.g., 100"
                  />
                </div>
              </div>

              <div>
                <label class="label text-xs">Pattern (Regex)</label>
                <input
                  v-model="field.validation.pattern"
                  type="text"
                  class="input text-sm font-mono"
                  placeholder="e.g., ^[A-Z]{2,4}-[0-9]+$"
                />
              </div>

              <div>
                <label class="label text-xs">Allowed Values (Enum)</label>
                <div class="space-y-2">
                  <div
                    v-for="(enumValue, enumIndex) in field.validation.enum"
                    :key="enumIndex"
                    class="flex gap-2"
                  >
                    <input
                      v-model="field.validation.enum[enumIndex]"
                      type="text"
                      class="input text-sm flex-1"
                      placeholder="Value"
                    />
                    <button
                      @click="removeEnumValue(field, enumIndex)"
                      class="text-red-400 hover:text-red-300"
                    >
                      <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                      </svg>
                    </button>
                  </div>
                  <button
                    @click="addEnumValue(field)"
                    class="text-sm text-primary-400 hover:text-primary-300 flex items-center gap-1"
                  >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                    </svg>
                    Add Value
                  </button>
                </div>
              </div>
            </div>

            <!-- Number Validations -->
            <div v-if="field.type === 'number'" class="grid grid-cols-2 gap-3">
              <div>
                <label class="label text-xs">Minimum Value</label>
                <input
                  v-model="field.validation.min"
                  type="number"
                  step="any"
                  class="input text-sm"
                  placeholder="e.g., 0"
                />
              </div>
              <div>
                <label class="label text-xs">Maximum Value</label>
                <input
                  v-model="field.validation.max"
                  type="number"
                  step="any"
                  class="input text-sm"
                  placeholder="e.g., 1000"
                />
              </div>
            </div>

            <!-- Other types -->
            <div v-if="!['string', 'number'].includes(field.type)" class="text-sm text-slate-400">
              No validation rules available for this type.
            </div>
          </div>
        </div>
      </div>

      <!-- Add Field Button -->
      <button
        @click="addField"
        class="btn btn-secondary w-full flex items-center justify-center gap-2"
      >
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
        </svg>
        Add Field
      </button>
        </div>

        <!-- Right Column: Log Structure Preview -->
        <div class="sticky top-6 self-start">
          <h2 class="text-lg font-semibold text-white mb-4">Log Structure Preview</h2>

          <div class="card">
            <div class="flex items-center justify-between mb-3">
              <h3 class="text-sm font-semibold text-slate-300">Example Log Structure</h3>
              <span class="text-xs text-slate-500">JSON Format</span>
            </div>

            <div class="bg-dark-400 border border-slate-800 p-4 overflow-x-auto">
              <pre class="text-xs text-slate-300 font-mono leading-relaxed">{{ JSON.stringify(logStructurePreview, null, 2) }}</pre>
            </div>

            <div class="mt-4 pt-4 border-t border-slate-800">
              <h4 class="text-sm font-semibold text-slate-300 mb-3">Field Descriptions</h4>

              <div class="space-y-3 text-sm">
                <!-- Core Fields -->
                <div class="bg-emerald-500/10 border border-emerald-500/30 p-3">
                  <p class="text-emerald-400 font-semibold mb-2">Core Fields (Always Required)</p>
                  <div class="space-y-2 text-xs text-slate-400">
                    <div>
                      <code class="text-emerald-400">timestamp</code> - ISO 8601 format (YYYY-MM-DDTHH:mm:ss.sssZ)
                    </div>
                    <div>
                      <code class="text-emerald-400">level</code> - Log level: debug, info, warning, error, critical
                    </div>
                    <div>
                      <code class="text-emerald-400">title</code> - Short description of the event
                    </div>
                  </div>
                </div>

                <!-- Custom Fields -->
                <div class="bg-blue-500/10 border border-blue-500/30 p-3">
                  <p class="text-blue-400 font-semibold mb-2">Custom Fields (Your Schema)</p>
                  <div v-if="fields.length === 0 || fields.every(f => !f.name.trim())" class="text-xs text-slate-500 italic">
                    No custom fields defined yet. Add fields to see them here.
                  </div>
                  <div v-else class="space-y-2 text-xs text-slate-400">
                    <div v-for="(field, index) in fields" :key="index">
                      <div v-if="field.name && field.name.trim()" class="flex items-start gap-2">
                        <code class="text-blue-400">data.{{ field.name }}</code>
                        <span class="text-slate-500">-</span>
                        <span class="flex-1">
                          <span class="text-slate-300">{{ field.type }}</span>
                          <span v-if="field.required" class="text-amber-400 ml-1">(required)</span>
                          <span v-if="field.indexed" class="text-emerald-400 ml-1">(indexed)</span>
                          <div v-if="field.description" class="text-slate-500 mt-0.5">{{ field.description }}</div>
                        </span>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- API Endpoint Hint -->
              <div class="mt-4 pt-4 border-t border-slate-800">
                <h4 class="text-xs font-semibold text-slate-400 mb-2">Sending Logs</h4>
                <div class="bg-dark-400 border border-slate-800 p-2 text-xs">
                  <div class="text-slate-500 mb-1">POST /api/v1/logs</div>
                  <div class="text-slate-500">Authorization: Bearer YOUR_TOKEN</div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Actions -->
      <div class="flex justify-end gap-3">
        <button
          @click="router.push({ name: 'projects' })"
          class="btn btn-secondary"
        >
          Cancel
        </button>
        <button
          @click="saveSchema"
          :disabled="saving || fields.length === 0"
          class="btn btn-primary"
        >
          {{ saving ? 'Saving...' : 'Save Schema' }}
        </button>
      </div>
    </div>

    <!-- Error State -->
    <div v-else class="card text-center py-12">
      <svg class="w-16 h-16 text-red-500 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
      </svg>
      <p class="text-slate-400 mb-4">{{ error || 'Project not found' }}</p>
      <button @click="router.push({ name: 'projects' })" class="btn btn-primary">
        Back to Projects
      </button>
    </div>
  </div>
</template>
