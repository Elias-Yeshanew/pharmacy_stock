<template>
  <div class="space-y-5">
    <div class="flex items-center justify-between">
      <h2 class="text-sm text-gray-500">{{ branches.length }} branches configured</h2>
      <button @click="openCreate" class="btn-primary">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        Add Branch
      </button>
    </div>
    
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
      <div v-for="b in branches" :key="b.id" class="card p-5 flex flex-col justify-between">
        <div>
          <div class="flex items-start justify-between">
            <div class="w-10 h-10 bg-primary-100 rounded-xl flex items-center justify-center text-primary-700 font-bold text-lg mb-3">
              <svg class="w-5 h-5 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
              </svg>
            </div>
            <span :class="b.is_active ? 'badge-green' : 'badge-red'">
              {{ b.is_active ? 'Active' : 'Inactive' }}
            </span>
          </div>
          
          <h3 class="font-semibold text-gray-900 text-base">{{ b.name }}</h3>
          <p class="text-xs text-gray-500 mt-2 flex items-center gap-1.5">
            <svg class="w-3.5 h-3.5 flex-shrink-0 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
            {{ b.address || 'No address' }}
          </p>
          <p class="text-xs text-gray-500 mt-1 flex items-center gap-1.5">
            <svg class="w-3.5 h-3.5 flex-shrink-0 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.94.725l.548 2.2a1 1 0 01-.321.988l-1.305.98a10.582 10.582 0 004.872 4.872l.98-1.305a1 1 0 01.988-.321l2.2.548a1 1 0 01.725.94V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
            {{ b.phone || 'No phone' }}
          </p>
          <p class="text-xs text-gray-500 mt-1 flex items-center gap-1.5">
            <svg class="w-3.5 h-3.5 flex-shrink-0 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
            {{ b.email || 'No email' }}
          </p>
        </div>

        <div class="flex items-center justify-between border-t pt-4 mt-5">
          <span class="text-xs text-gray-400 font-medium">{{ b.users_count || 0 }} staff assigned</span>
          <div class="flex gap-2">
            <button @click="openEdit(b)" class="text-xs text-primary-600 font-medium hover:text-primary-800">Edit</button>
            <button @click="deleteBranch(b)" class="text-xs text-red-500 font-medium hover:text-red-700">Delete</button>
          </div>
        </div>
      </div>
    </div>

    <Modal v-model="showModal" :title="editingId ? 'Edit Branch' : 'Add Branch'" size="sm">
      <form id="branch-form" @submit.prevent="submitForm" class="space-y-4">
        <div>
          <label class="label">Name *</label>
          <input v-model="form.name" type="text" class="input" required placeholder="e.g. Bole Branch" />
        </div>
        <div>
          <label class="label">Address</label>
          <input v-model="form.address" type="text" class="input" placeholder="e.g. Bole Road, Addis Ababa" />
        </div>
        <div>
          <label class="label">Phone</label>
          <input v-model="form.phone" type="text" class="input" placeholder="e.g. +251911000111" />
        </div>
        <div>
          <label class="label">Email</label>
          <input v-model="form.email" type="email" class="input" placeholder="e.g. bole@pharmacy.com" />
        </div>
        <div class="flex items-center gap-2 pt-2">
          <input v-model="form.is_active" type="checkbox" id="is_active" class="rounded text-primary-600 border-gray-300 focus:ring-primary-500" />
          <label for="is_active" class="text-sm font-medium text-gray-700">Active / Enabled</label>
        </div>
        <div v-if="error" class="text-red-600 text-sm">{{ error }}</div>
      </form>
      <template #footer>
        <div class="flex justify-end gap-2">
          <button type="button" @click="showModal=false" class="btn-secondary">Cancel</button>
          <button type="submit" form="branch-form" class="btn-primary" :disabled="loading">{{ loading ? 'Saving…' : 'Save' }}</button>
        </div>
      </template>
    </Modal>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import api from '@/composables/useApi'
import Modal from '@/components/Modal.vue'
import { useAuthStore } from '@/stores/auth'

const authStore = useAuthStore()
const branches = ref([])
const showModal = ref(false)
const editingId = ref(null)
const loading = ref(false)
const error = ref('')

const form = ref({
  name: '',
  address: '',
  phone: '',
  email: '',
  is_active: true
})

onMounted(fetch)

async function fetch() {
  try {
    const { data } = await api.get('/branches')
    branches.value = data
  } catch (e) {
    alert('Failed to load branches')
  }
}

function openCreate() {
  editingId.value = null
  form.value = {
    name: '',
    address: '',
    phone: '',
    email: '',
    is_active: true
  }
  error.value = ''
  showModal.value = true
}

function openEdit(b) {
  editingId.value = b.id
  form.value = {
    name: b.name,
    address: b.address || '',
    phone: b.phone || '',
    email: b.email || '',
    is_active: !!b.is_active
  }
  error.value = ''
  showModal.value = true
}

async function submitForm() {
  loading.value = true
  error.value = ''
  try {
    if (editingId.value) {
      await api.put(`/branches/${editingId.value}`, form.value)
    } else {
      await api.post('/branches', form.value)
    }
    showModal.value = false
    await fetch()
    
    if (authStore.user?.role === 'admin') {
      await authStore.fetchBranches()
    }
  } catch (e) {
    error.value = e.response?.data?.message || 'Failed to save branch'
  } finally {
    loading.value = false
  }
}

async function deleteBranch(b) {
  if (!confirm(`Delete ${b.name}?`)) return
  try {
    await api.delete(`/branches/${b.id}`)
    await fetch()
    if (authStore.user?.role === 'admin') {
      await authStore.fetchBranches()
    }
  } catch (e) {
    alert(e.response?.data?.message || 'Cannot delete branch')
  }
}
</script>
