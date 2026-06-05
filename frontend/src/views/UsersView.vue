<template>
  <div class="space-y-5">
    <!-- Header -->
    <div class="flex items-center justify-between">
      <div>
        <h2 class="text-xl font-bold text-gray-900">User Management</h2>
        <p class="text-xs text-gray-500 mt-0.5">Register, update roles, and manage system access.</p>
      </div>
      <button @click="openAddModal" class="btn-primary flex items-center gap-2">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        Add User
      </button>
    </div>

    <!-- Users Table -->
    <div class="card overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full">
          <thead class="bg-gray-50 border-b border-gray-100">
            <tr>
              <th class="table-header">Name</th>
              <th class="table-header">Email</th>
              <th class="table-header">Role</th>
              <th class="table-header">Assigned Branch</th>
              <th class="table-header text-right">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-50">
            <tr v-if="loading"><td colspan="5" class="py-12 text-center text-gray-400 text-sm">Loading users…</td></tr>
            <tr v-else-if="users.length === 0"><td colspan="5" class="py-12 text-center text-gray-400 text-sm">No users found</td></tr>
            <tr v-for="u in users" :key="u.id" class="hover:bg-gray-50 transition-colors">
              <td class="table-cell">
                <div class="flex items-center gap-3">
                  <div class="w-8 h-8 rounded-full bg-primary-100 text-primary-700 flex items-center justify-center font-bold text-sm">
                    {{ u.name[0].toUpperCase() }}
                  </div>
                  <div>
                    <span class="font-medium text-gray-900">{{ u.name }}</span>
                    <span v-if="authStore.user?.id === u.id" class="ml-2 text-xs bg-gray-100 text-gray-600 px-1.5 py-0.5 rounded-full font-medium">You</span>
                  </div>
                </div>
              </td>
              <td class="table-cell text-gray-600">{{ u.email }}</td>
              <td class="table-cell">
                <span :class="roleBadgeClass(u.role)">
                  {{ roleLabel(u.role) }}
                </span>
              </td>
              <td class="table-cell">
                <span v-if="u.branch" class="text-gray-900 font-medium">{{ u.branch.name }}</span>
                <span v-else class="text-gray-400 italic text-sm">Global (All Branches)</span>
              </td>
              <td class="table-cell text-right">
                <div class="flex items-center justify-end gap-3">
                  <button @click="openEditModal(u)" class="text-primary-600 hover:text-primary-800 text-xs font-semibold transition-colors">Edit</button>
                  <button 
                    @click="deleteUser(u)" 
                    class="text-red-500 hover:text-red-700 text-xs font-semibold transition-colors"
                    :disabled="authStore.user?.id === u.id"
                    :class="authStore.user?.id === u.id ? 'opacity-40 cursor-not-allowed' : ''"
                  >
                    Delete
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- User Form Modal (Add / Edit) -->
    <Modal v-model="showModal" :title="isEdit ? 'Edit User Details' : 'Register New User'">
      <form id="user-form" @submit.prevent="submitForm" class="space-y-4">
        <div>
          <label class="label">Full Name</label>
          <input v-model="form.name" type="text" class="input" placeholder="e.g. Abebe Kebede" required />
        </div>
        <div>
          <label class="label">Email Address</label>
          <input v-model="form.email" type="email" class="input" placeholder="e.g. abebe@pharmacy.com" required />
        </div>
        <div>
          <label class="label">Password <span v-if="isEdit" class="text-gray-400 font-normal text-xs">(leave blank to keep current)</span></label>
          <input v-model="form.password" type="password" class="input" placeholder="Min. 8 characters" :required="!isEdit" />
        </div>
        <div>
          <label class="label">System Role</label>
          <select v-model="form.role" class="input" required>
            <option value="admin">Administrator</option>
            <option value="dispenser">Dispenser / Pharmacist</option>
            <option value="supply_chain_manager">Supply Chain Manager</option>
            <option value="sales_manager">Sales Manager</option>
            <option value="owner">Company Owner</option>
            <option value="ceo">CEO</option>
          </select>
        </div>
        
        <!-- Branch selection (Only visible if role is dispenser/pharmacist/cashier) -->
        <div v-if="isBranchLockedRole">
          <label class="label">Assigned Branch</label>
          <select v-model="form.branch_id" class="input" :required="isBranchLockedRole">
            <option value="" disabled>Select a branch</option>
            <option v-for="b in branches" :key="b.id" :value="b.id">{{ b.name }}</option>
          </select>
          <p class="text-xs text-gray-500 mt-1">Dispenser operations and records will be strictly scoped to this branch.</p>
        </div>
        
        <div v-if="errorMsg" class="bg-red-50 text-red-700 text-xs rounded-lg p-3 border border-red-200">
          {{ errorMsg }}
        </div>
      </form>
      <template #footer>
        <div class="flex justify-end gap-2">
          <button type="button" @click="showModal = false" class="btn-secondary">Cancel</button>
          <button type="submit" form="user-form" class="btn-primary" :disabled="submitting">
            {{ submitting ? 'Saving…' : 'Save User' }}
          </button>
        </div>
      </template>
    </Modal>
  </div>
</template>

<script setup>
import { ref, onMounted, computed, watch } from 'vue'
import api from '@/composables/useApi'
import Modal from '@/components/Modal.vue'
import { useAuthStore } from '@/stores/auth'

const authStore = useAuthStore()

const users = ref([])
const branches = ref([])
const loading = ref(true)
const submitting = ref(false)
const showModal = ref(false)
const isEdit = ref(false)
const selectedUserId = ref(null)
const errorMsg = ref('')

const form = ref({
  name: '',
  email: '',
  password: '',
  role: 'dispenser',
  branch_id: ''
})

const isBranchLockedRole = computed(() => {
  return ['dispenser', 'pharmacist', 'cashier'].includes(form.value.role)
})

// Automatically handle branch assignment rules on role changes
watch(() => form.value.role, (newRole) => {
  if (!['dispenser', 'pharmacist', 'cashier'].includes(newRole)) {
    form.value.branch_id = null
  } else if (!form.value.branch_id && branches.value.length > 0) {
    form.value.branch_id = branches.value[0].id
  }
})

onMounted(async () => {
  await Promise.all([fetchUsers(), fetchBranches()])
})

async function fetchUsers() {
  loading.value = true
  try {
    const { data } = await api.get('/users')
    users.value = data
  } catch (e) {
    console.error('Failed to load users', e)
  } finally {
    loading.value = false
  }
}

async function fetchBranches() {
  try {
    const { data } = await api.get('/branches')
    branches.value = data
  } catch (e) {
    console.error('Failed to load branches', e)
  }
}

function openAddModal() {
  isEdit.value = false
  selectedUserId.value = null
  errorMsg.value = ''
  form.value = {
    name: '',
    email: '',
    password: '',
    role: 'dispenser',
    branch_id: branches.value[0]?.id || ''
  }
  showModal.value = true
}

function openEditModal(user) {
  isEdit.value = true
  selectedUserId.value = user.id
  errorMsg.value = ''
  form.value = {
    name: user.name,
    email: user.email,
    password: '',
    role: user.role,
    branch_id: user.branch_id || ''
  }
  showModal.value = true
}

async function submitForm() {
  submitting.value = true
  errorMsg.value = ''
  try {
    const payload = { ...form.value }
    if (isEdit.value) {
      if (!payload.password) delete payload.password
      await api.put(`/users/${selectedUserId.value}`, payload)
    } else {
      await api.post('/users', payload)
    }
    showModal.value = false
    await fetchUsers()
  } catch (e) {
    errorMsg.value = e.response?.data?.message || 'An error occurred while saving the user.'
  } finally {
    submitting.value = false
  }
}

async function deleteUser(user) {
  if (user.id === authStore.user?.id) return
  if (!confirm(`Are you sure you want to delete user ${user.name}? This cannot be undone.`)) return

  try {
    await api.delete(`/users/${user.id}`)
    await fetchUsers()
  } catch (e) {
    alert(e.response?.data?.message || 'Failed to delete user.')
  }
}

function roleBadgeClass(role) {
  const base = 'inline-flex items-center text-xs font-semibold px-2 py-0.5 rounded-full capitalize '
  switch (role) {
    case 'admin':
      return base + 'bg-purple-100 text-purple-800'
    case 'ceo':
      return base + 'bg-emerald-100 text-emerald-800'
    case 'owner':
      return base + 'bg-teal-100 text-teal-800'
    case 'supply_chain_manager':
      return base + 'bg-orange-100 text-orange-800'
    case 'sales_manager':
      return base + 'bg-blue-100 text-blue-800'
    case 'dispenser':
    case 'pharmacist':
      return base + 'bg-indigo-100 text-indigo-800'
    default:
      return base + 'bg-gray-100 text-gray-800'
  }
}

function roleLabel(role) {
  return {
    admin: 'Administrator',
    ceo: 'CEO',
    owner: 'Owner',
    supply_chain_manager: 'Supply Chain Manager',
    sales_manager: 'Sales Manager',
    dispenser: 'Dispenser',
    pharmacist: 'Dispenser (Legacy)',
    cashier: 'Cashier'
  }[role] || role
}
</script>
