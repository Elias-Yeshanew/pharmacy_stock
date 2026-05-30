<template>
  <div class="space-y-5">
    <div class="flex items-center justify-between">
      <input v-model="search" type="text" class="input w-64" placeholder="Search suppliers…" />
      <button @click="openCreate" class="btn-primary">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        Add Supplier
      </button>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
      <div v-for="s in suppliers" :key="s.id" class="card p-5">
        <div class="flex items-start justify-between mb-3">
          <div class="w-10 h-10 bg-primary-100 rounded-xl flex items-center justify-center text-primary-700 font-bold text-sm">{{ s.name[0] }}</div>
          <span :class="s.is_active ? 'badge-green' : 'badge-gray'">{{ s.is_active ? 'Active' : 'Inactive' }}</span>
        </div>
        <h3 class="font-semibold text-gray-900">{{ s.name }}</h3>
        <p class="text-sm text-gray-500 mb-3">{{ s.contact_person }}</p>
        <div class="space-y-1 text-xs text-gray-500">
          <p v-if="s.phone">📞 {{ s.phone }}</p>
          <p v-if="s.email">✉️ {{ s.email }}</p>
          <p v-if="s.address">📍 {{ s.address }}</p>
        </div>
        <div class="flex items-center justify-between mt-4 pt-3 border-t border-gray-100">
          <span class="text-xs text-gray-400">{{ s.medicines_count }} medicines</span>
          <div class="flex gap-2">
            <button @click="openEdit(s)" class="text-xs text-primary-600 hover:text-primary-800 font-medium">Edit</button>
            <button @click="deleteSupplier(s)" class="text-xs text-red-500 hover:text-red-700 font-medium">Delete</button>
          </div>
        </div>
      </div>
    </div>

    <Modal v-model="showModal" :title="editingId ? 'Edit Supplier' : 'Add Supplier'">
      <form id="supplier-form" @submit.prevent="submitForm" class="space-y-4">
        <div><label class="label">Name *</label><input v-model="form.name" type="text" class="input" required /></div>
        <div><label class="label">Contact Person</label><input v-model="form.contact_person" type="text" class="input" /></div>
        <div class="grid grid-cols-2 gap-3">
          <div><label class="label">Phone</label><input v-model="form.phone" type="text" class="input" /></div>
          <div><label class="label">Email</label><input v-model="form.email" type="email" class="input" /></div>
        </div>
        <div><label class="label">Address</label><textarea v-model="form.address" class="input" rows="2"></textarea></div>
        <div class="flex items-center gap-2"><input v-model="form.is_active" type="checkbox" id="active" class="w-4 h-4"/><label for="active" class="text-sm text-gray-700">Active</label></div>
        <div v-if="error" class="text-red-600 text-sm">{{ error }}</div>
      </form>
      <template #footer>
        <div class="flex justify-end gap-2">
          <button type="button" @click="showModal=false" class="btn-secondary">Cancel</button>
          <button type="submit" form="supplier-form" class="btn-primary" :disabled="loading">{{ loading?'Saving…':'Save' }}</button>
        </div>
      </template>
    </Modal>
  </div>
</template>
<script setup>
import { ref, watch, onMounted } from 'vue'
import api from '@/composables/useApi'
import Modal from '@/components/Modal.vue'

const suppliers = ref([]); const search = ref('')
const showModal = ref(false); const editingId = ref(null)
const loading = ref(false); const error = ref('')
const form = ref({ name:'', contact_person:'', phone:'', email:'', address:'', is_active:true })

onMounted(fetch)
watch(search, fetch)

async function fetch() {
  const { data } = await api.get('/suppliers', { params:{ search:search.value, per_page:100 }})
  suppliers.value = data.data || data
}
function openCreate() { editingId.value=null; form.value={ name:'',contact_person:'',phone:'',email:'',address:'',is_active:true }; error.value=''; showModal.value=true }
function openEdit(s) { editingId.value=s.id; form.value={ name:s.name,contact_person:s.contact_person||'',phone:s.phone||'',email:s.email||'',address:s.address||'',is_active:s.is_active }; error.value=''; showModal.value=true }
async function submitForm() {
  loading.value=true; error.value=''
  try {
    if (editingId.value) await api.put(`/suppliers/${editingId.value}`, form.value)
    else await api.post('/suppliers', form.value)
    showModal.value=false; await fetch()
  } catch(e) { error.value = e.response?.data?.message || 'Failed to save' }
  finally { loading.value=false }
}
async function deleteSupplier(s) {
  if (!confirm(`Delete ${s.name}?`)) return
  try { await api.delete(`/suppliers/${s.id}`); await fetch() }
  catch(e) { alert(e.response?.data?.message || 'Cannot delete') }
}
</script>
