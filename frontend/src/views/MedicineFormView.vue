<template>
  <div class="max-w-3xl">
    <div class="flex items-center gap-3 mb-6">
      <router-link to="/medicines" class="text-gray-400 hover:text-gray-600">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
      </router-link>
      <h2 class="text-lg font-semibold text-gray-900">{{ isEdit ? 'Edit Medicine' : 'Add New Medicine' }}</h2>
    </div>

    <form @submit.prevent="submitForm" class="space-y-6">
      <div class="card p-6 space-y-5">
        <h3 class="font-medium text-gray-900 text-sm border-b pb-2">Basic Information</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="label">Medicine Name *</label>
            <input v-model="form.name" type="text" class="input" required />
          </div>
          <div>
            <label class="label">Generic Name</label>
            <input v-model="form.generic_name" type="text" class="input" />
          </div>
          <div>
            <label class="label">Category *</label>
            <select v-model="form.category_id" class="input" required>
              <option value="">Select category</option>
              <option v-for="c in categories" :key="c.id" :value="c.id">{{ c.name }}</option>
            </select>
          </div>
          <div>
            <label class="label">Supplier *</label>
            <select v-model="form.supplier_id" class="input" required>
              <option value="">Select supplier</option>
              <option v-for="s in suppliers" :key="s.id" :value="s.id">{{ s.name }}</option>
            </select>
          </div>
          <div>
            <label class="label">Dosage Form *</label>
            <select v-model="form.dosage_form" class="input" required>
              <option>Tablet</option><option>Capsule</option><option>Syrup</option>
              <option>Injection</option><option>Cream</option><option>Drops</option>
              <option>Sachet</option><option>Suppository</option><option>Inhaler</option>
            </select>
          </div>
          <div>
            <label class="label">Strength</label>
            <input v-model="form.strength" type="text" class="input" placeholder="e.g. 500mg, 10mg/5ml" />
          </div>
          <div>
            <label class="label">Unit *</label>
            <select v-model="form.unit" class="input" required>
              <option>Pcs</option><option>Bottle</option><option>Box</option><option>Pack</option><option>Vial</option>
            </select>
          </div>
          <div>
            <label class="label">Barcode</label>
            <input v-model="form.barcode" type="text" class="input" />
          </div>
        </div>
      </div>

      <div class="card p-6 space-y-5">
        <h3 class="font-medium text-gray-900 text-sm border-b pb-2">Pricing & Stock</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="label">Purchase Price ($) *</label>
            <input v-model.number="form.purchase_price" type="number" step="0.01" min="0" class="input" required />
          </div>
          <div>
            <label class="label">Selling Price ($) *</label>
            <input v-model.number="form.selling_price" type="number" step="0.01" min="0" class="input" required />
          </div>
          <div v-if="!isEdit">
            <label class="label">Initial Stock Quantity *</label>
            <input v-model.number="form.stock_quantity" type="number" min="0" class="input" required />
          </div>
          <div>
            <label class="label">Reorder Level *</label>
            <input v-model.number="form.reorder_level" type="number" min="0" class="input" required />
          </div>
          <div>
            <label class="label">Expiry Date</label>
            <input v-model="form.expiry_date" type="date" class="input" />
          </div>
          <div>
            <label class="label">Storage Conditions</label>
            <input v-model="form.storage_conditions" type="text" class="input" placeholder="e.g. Store below 25°C" />
          </div>
        </div>
        <div class="flex items-center gap-3">
          <input v-model="form.requires_prescription" type="checkbox" id="rx" class="w-4 h-4 rounded text-primary-600" />
          <label for="rx" class="text-sm text-gray-700">Requires Prescription</label>
        </div>
      </div>

      <div class="card p-6 space-y-3">
        <h3 class="font-medium text-gray-900 text-sm border-b pb-2">Additional Notes</h3>
        <textarea v-model="form.description" class="input" rows="3" placeholder="Optional description or notes…"></textarea>
      </div>

      <div v-if="error" class="bg-red-50 border border-red-200 rounded-lg p-3 text-red-700 text-sm">{{ error }}</div>

      <div class="flex items-center justify-end gap-3">
        <router-link to="/medicines" class="btn-secondary">Cancel</router-link>
        <button type="submit" class="btn-primary" :disabled="loading">
          {{ loading ? 'Saving…' : (isEdit ? 'Update Medicine' : 'Add Medicine') }}
        </button>
      </div>
    </form>
  </div>
</template>
<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import api from '@/composables/useApi'

const route = useRoute(); const router = useRouter()
const isEdit = computed(() => !!route.params.id)
const loading = ref(false); const error = ref('')
const categories = ref([]); const suppliers = ref([])

const form = ref({
  name:'', generic_name:'', category_id:'', supplier_id:'', dosage_form:'Tablet',
  strength:'', unit:'Pcs', barcode:'', purchase_price:0, selling_price:0,
  stock_quantity:0, reorder_level:10, expiry_date:'', storage_conditions:'',
  requires_prescription:false, description:''
})

onMounted(async () => {
  const [cats, sups] = await Promise.all([api.get('/categories'), api.get('/suppliers')])
  categories.value = cats.data
  suppliers.value = sups.data.data || sups.data
  if (isEdit.value) {
    const { data } = await api.get(`/medicines/${route.params.id}`)
    Object.assign(form.value, data)
    form.value.expiry_date = data.expiry_date ? data.expiry_date.substring(0,10) : ''
  }
})

async function submitForm() {
  loading.value = true; error.value = ''
  try {
    if (isEdit.value) await api.put(`/medicines/${route.params.id}`, form.value)
    else await api.post('/medicines', form.value)
    router.push('/medicines')
  } catch(e) {
    const errs = e.response?.data?.errors
    error.value = errs ? Object.values(errs).flat().join(', ') : (e.response?.data?.message || 'Failed to save')
  } finally { loading.value = false }
}
</script>
