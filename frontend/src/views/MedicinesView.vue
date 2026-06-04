<template>
  <div class="space-y-5">
    <!-- Header -->
    <div class="flex flex-wrap items-center justify-between gap-3">
      <div class="flex flex-wrap items-center gap-2">
        <input v-model="search" type="text" class="input w-64" placeholder="Search medicines…" />
        <select v-model="filterCategory" class="input w-44">
          <option value="">All Categories</option>
          <option v-for="c in categories" :key="c.id" :value="c.id">{{ c.name }}</option>
        </select>
        <select v-model="filterStatus" class="input w-44">
          <option value="">All Stock Status</option>
          <option value="low_stock">Low Stock</option>
          <option value="out_of_stock">Out of Stock</option>
          <option value="expiring_soon">Expiring Soon</option>
          <option value="expired">Expired</option>
        </select>
      </div>
      <router-link to="/medicines/new" class="btn-primary">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        Add Medicine
      </router-link>
    </div>

    <!-- Table -->
    <div class="card overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full">
          <thead class="bg-gray-50 border-b border-gray-100">
            <tr>
              <th class="table-header">Medicine</th>
              <th class="table-header">Category</th>
              <th class="table-header">Dosage</th>
              <th class="table-header">Stock</th>
              <th class="table-header">Price</th>
              <th class="table-header">Expiry</th>
              <th class="table-header">Status</th>
              <th class="table-header">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-50">
            <tr v-if="loading"><td colspan="8" class="py-12 text-center text-gray-400 text-sm">Loading…</td></tr>
            <tr v-else-if="medicines.length === 0"><td colspan="8" class="py-12 text-center text-gray-400 text-sm">No medicines found</td></tr>
            <tr v-for="med in medicines" :key="med.id" class="hover:bg-gray-50 transition-colors">
              <td class="table-cell">
                <div class="font-medium text-gray-900">{{ med.name }}</div>
                <div class="text-xs text-gray-400">{{ med.generic_name }} · {{ med.sku }}</div>
              </td>
              <td class="table-cell text-gray-600">{{ med.category?.name }}</td>
              <td class="table-cell text-gray-600">{{ med.dosage_form }} {{ med.strength }}</td>
              <td class="table-cell font-mono font-semibold">{{ med.stock_quantity }} <span class="text-gray-400 font-sans font-normal text-xs">{{ med.unit }}</span></td>
              <td class="table-cell">
                <div class="text-gray-900">${{ med.selling_price }}</div>
                <div class="text-xs text-gray-400">Cost: ${{ med.purchase_price }}</div>
              </td>
              <td class="table-cell text-sm">
                <span v-if="med.expiry_date">{{ formatDate(med.expiry_date) }}</span>
                <span v-else class="text-gray-400">—</span>
              </td>
              <td class="table-cell">
                <span :class="statusBadge(med.stock_status)">{{ statusLabel(med.stock_status) }}</span>
              </td>
              <td class="table-cell">
                <div class="flex items-center gap-2">
                  <button @click="openAdjust(med)" class="text-primary-600 hover:text-primary-800 text-xs font-medium">Adjust</button>
                  <router-link :to="`/medicines/${med.id}/edit`" class="text-gray-500 hover:text-gray-700 text-xs font-medium">Edit</router-link>
                  <button @click="deleteMedicine(med)" class="text-red-500 hover:text-red-700 text-xs font-medium">Delete</button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      <!-- Pagination -->
      <div class="flex items-center justify-between px-5 py-3 border-t border-gray-100" v-if="pagination.last_page > 1">
        <p class="text-xs text-gray-500">Page {{ pagination.current_page }} of {{ pagination.last_page }} ({{ pagination.total }} total)</p>
        <div class="flex gap-1">
          <button @click="page--" :disabled="page === 1" class="btn btn-secondary btn-sm">Prev</button>
          <button @click="page++" :disabled="page === pagination.last_page" class="btn btn-secondary btn-sm">Next</button>
        </div>
      </div>
    </div>

    <!-- Adjust Stock Modal -->
    <Modal v-model="showAdjust" title="Adjust Stock">
      <form id="adjust-form" @submit.prevent="submitAdjust" class="space-y-4">
        <div class="bg-gray-50 rounded-lg p-3 text-sm">
          <p class="font-medium text-gray-900">{{ selectedMed?.name }}</p>
          <p class="text-gray-500">Current stock: <span class="font-semibold text-gray-900">{{ selectedMed?.stock_quantity }} {{ selectedMed?.unit }}</span></p>
        </div>
        <div>
          <label class="label">Movement Type</label>
          <select v-model="adjustForm.type" class="input" required>
            <option value="in">Stock In (Received)</option>
            <option value="out">Stock Out (Dispensed)</option>
            <option value="adjustment">Manual Adjustment</option>
            <option value="return">Return to Supplier</option>
            <option value="expired">Mark as Expired</option>
          </select>
        </div>
        <div>
          <label class="label">Quantity</label>
          <input v-model.number="adjustForm.quantity" type="number" min="1" class="input" required />
        </div>
        <div>
          <label class="label">Batch Number <span class="text-gray-400">(optional)</span></label>
          <input v-model="adjustForm.batch_number" type="text" class="input" />
        </div>
        <div>
          <label class="label">Notes <span class="text-gray-400">(optional)</span></label>
          <textarea v-model="adjustForm.notes" class="input" rows="2"></textarea>
        </div>
        <div v-if="adjustError" class="text-red-600 text-sm">{{ adjustError }}</div>
      </form>
      <template #footer>
        <div class="flex justify-end gap-2">
          <button type="button" @click="showAdjust = false" class="btn-secondary">Cancel</button>
          <button type="submit" form="adjust-form" class="btn-primary" :disabled="adjustLoading">{{ adjustLoading ? 'Saving…' : 'Save Movement' }}</button>
        </div>
      </template>
    </Modal>
  </div>
</template>
<script setup>
import { ref, watch, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import api from '@/composables/useApi'
import Modal from '@/components/Modal.vue'
import { format, parseISO } from 'date-fns'
import { useAuthStore } from '@/stores/auth'

const authStore = useAuthStore()
const route = useRoute()
const medicines = ref([])
const categories = ref([])
const loading = ref(true)
const page = ref(1)
const search = ref('')
const filterCategory = ref('')
const filterStatus = ref(route.query.stock_status || '')
const pagination = ref({ current_page:1, last_page:1, total:0 })

const showAdjust = ref(false)
const selectedMed = ref(null)
const adjustForm = ref({ type:'in', quantity:1, notes:'', batch_number:'' })
const adjustLoading = ref(false)
const adjustError = ref('')

onMounted(async () => {
  const { data } = await api.get('/categories')
  categories.value = data
  await fetchMedicines()
})

async function fetchMedicines() {
  loading.value = true
  try {
    const { data } = await api.get('/medicines', { params: { page: page.value, search: search.value, category_id: filterCategory.value, stock_status: filterStatus.value, per_page: 15 } })
    medicines.value = data.data
    pagination.value = { current_page: data.current_page, last_page: data.last_page, total: data.total }
  } finally { loading.value = false }
}

watch([search, filterCategory, filterStatus, () => authStore.activeBranchId], () => { page.value = 1; fetchMedicines() })
watch(page, fetchMedicines)

function formatDate(d) { return d ? format(parseISO(d), 'dd MMM yyyy') : '—' }
function statusBadge(s) { return { in_stock:'badge-green', low_stock:'badge-yellow', out_of_stock:'badge-red' }[s] || 'badge-gray' }
function statusLabel(s) { return { in_stock:'In Stock', low_stock:'Low Stock', out_of_stock:'Out of Stock' }[s] || s }

function openAdjust(med) { selectedMed.value = med; adjustForm.value = { type:'in', quantity:1, notes:'', batch_number:'' }; adjustError.value = ''; showAdjust.value = true }

async function submitAdjust() {
  adjustLoading.value = true; adjustError.value = ''
  try {
    await api.post(`/medicines/${selectedMed.value.id}/adjust-stock`, adjustForm.value)
    showAdjust.value = false
    await fetchMedicines()
  } catch(e) { adjustError.value = e.response?.data?.message || 'Failed to adjust stock' }
  finally { adjustLoading.value = false }
}

async function deleteMedicine(med) {
  if (!confirm(`Delete ${med.name}?`)) return
  await api.delete(`/medicines/${med.id}`)
  await fetchMedicines()
}
</script>
