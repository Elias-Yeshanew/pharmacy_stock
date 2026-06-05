<template>
  <div class="space-y-5">
    <div class="flex items-center justify-between">
      <h2 class="text-sm text-gray-500">{{ categories.length }} categories</h2>
      <button v-if="['admin', 'supply_chain_manager'].includes(authStore.user?.role)" @click="openCreate" class="btn-primary">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        Add Category
      </button>
    </div>
    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
      <div v-for="c in categories" :key="c.id" class="card p-5">
        <div class="w-10 h-10 bg-indigo-100 rounded-xl flex items-center justify-center text-indigo-700 font-bold text-lg mb-3">{{ c.name[0] }}</div>
        <h3 class="font-semibold text-gray-900">{{ c.name }}</h3>
        <p class="text-xs text-gray-500 mt-1 mb-3">{{ c.description || 'No description' }}</p>
        <div class="flex items-center justify-between border-t pt-3">
          <span class="text-xs text-gray-400">{{ c.medicines_count }} medicines</span>
          <div v-if="['admin', 'supply_chain_manager'].includes(authStore.user?.role)" class="flex gap-2">
            <button @click="openEdit(c)" class="text-xs text-primary-600 font-medium">Edit</button>
            <button @click="deleteCategory(c)" class="text-xs text-red-500 font-medium">Delete</button>
          </div>
        </div>
      </div>
    </div>

    <Modal v-model="showModal" :title="editingId ? 'Edit Category' : 'Add Category'" size="sm">
      <form id="category-form" @submit.prevent="submitForm" class="space-y-4">
        <div><label class="label">Name *</label><input v-model="form.name" type="text" class="input" required /></div>
        <div><label class="label">Description</label><textarea v-model="form.description" class="input" rows="2"></textarea></div>
        <div v-if="error" class="text-red-600 text-sm">{{ error }}</div>
      </form>
      <template #footer>
        <div class="flex justify-end gap-2">
          <button type="button" @click="showModal=false" class="btn-secondary">Cancel</button>
          <button type="submit" form="category-form" class="btn-primary" :disabled="loading">{{ loading?'Saving…':'Save' }}</button>
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

const categories = ref([]); const showModal = ref(false); const editingId = ref(null)
const loading = ref(false); const error = ref('')
const form = ref({ name:'', description:'' })

onMounted(fetch)
async function fetch() { const { data } = await api.get('/categories'); categories.value = data }
function openCreate() { editingId.value=null; form.value={name:'',description:''}; error.value=''; showModal.value=true }
function openEdit(c) { editingId.value=c.id; form.value={name:c.name,description:c.description||''}; error.value=''; showModal.value=true }
async function submitForm() {
  loading.value=true; error.value=''
  try {
    if (editingId.value) await api.put(`/categories/${editingId.value}`, form.value)
    else await api.post('/categories', form.value)
    showModal.value=false; await fetch()
  } catch(e) { error.value = e.response?.data?.message || 'Failed to save' }
  finally { loading.value=false }
}
async function deleteCategory(c) {
  if (!confirm(`Delete ${c.name}?`)) return
  try { await api.delete(`/categories/${c.id}`); await fetch() }
  catch(e) { alert(e.response?.data?.message || 'Cannot delete') }
}
</script>
