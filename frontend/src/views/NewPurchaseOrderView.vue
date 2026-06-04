<template>
  <div class="max-w-4xl space-y-5">
    <div class="flex items-center gap-3 mb-2">
      <router-link to="/purchase-orders" class="text-gray-400 hover:text-gray-600">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
      </router-link>
      <h2 class="text-lg font-semibold text-gray-900">New Purchase Order</h2>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
      <!-- Left: Items -->
      <div class="lg:col-span-2 space-y-4">
        <div class="card p-5">
          <h3 class="font-medium text-sm text-gray-900 mb-3">Order Items</h3>
          <div class="flex gap-2 mb-4">
            <input v-model="medicineSearch" type="text" class="input flex-1" placeholder="Search medicine to add…" />
          </div>
          <div v-if="searchResults.length > 0" class="border border-gray-200 rounded-lg overflow-hidden mb-4">
            <div v-for="med in searchResults" :key="med.id" @click="addItem(med)"
              class="flex items-center justify-between px-4 py-3 hover:bg-primary-50 cursor-pointer border-b last:border-0">
              <div>
                <p class="text-sm font-medium">{{ med.name }}</p>
                <p class="text-xs text-gray-500">{{ med.dosage_form }} {{ med.strength }} · Stock: {{ med.stock_quantity }}</p>
              </div>
              <span class="text-xs text-primary-600 font-medium">Add →</span>
            </div>
          </div>
          <div v-if="orderItems.length === 0" class="text-center py-8 text-gray-400 text-sm border-2 border-dashed border-gray-200 rounded-lg">
            Search and add medicines above
          </div>
          <div v-else class="space-y-2">
            <div v-for="(item, idx) in orderItems" :key="idx" class="grid grid-cols-12 gap-2 items-center p-3 bg-gray-50 rounded-lg">
              <div class="col-span-4">
                <p class="text-sm font-medium text-gray-900">{{ item.medicine.name }}</p>
                <p class="text-xs text-gray-500">{{ item.medicine.strength }}</p>
              </div>
              <div class="col-span-2">
                <label class="text-xs text-gray-500 mb-0.5 block">Qty</label>
                <input v-model.number="item.quantity_ordered" type="number" min="1" class="input text-center text-sm" @change="recalc" />
              </div>
              <div class="col-span-3">
                <label class="text-xs text-gray-500 mb-0.5 block">Unit Price ($)</label>
                <input v-model.number="item.unit_price" type="number" step="0.01" min="0" class="input text-sm" @change="recalc" />
              </div>
              <div class="col-span-2 text-right">
                <label class="text-xs text-gray-500 mb-0.5 block">Total</label>
                <p class="text-sm font-semibold text-gray-900">${{ (item.quantity_ordered * item.unit_price).toFixed(2) }}</p>
              </div>
              <div class="col-span-1 flex justify-end">
                <button @click="removeItem(idx)" class="text-red-400 hover:text-red-600">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Right: Order info -->
      <div class="space-y-4">
        <div class="card p-5 space-y-4">
          <h3 class="font-medium text-sm text-gray-900">Order Information</h3>
          <div>
            <label class="label">Supplier *</label>
            <select v-model="form.supplier_id" class="input" required>
              <option value="">Select supplier</option>
              <option v-for="s in suppliers" :key="s.id" :value="s.id">{{ s.name }}</option>
            </select>
          </div>
          <div>
            <label class="label">Order Date *</label>
            <input v-model="form.order_date" type="date" class="input" required />
          </div>
          <div>
            <label class="label">Expected Delivery</label>
            <input v-model="form.expected_date" type="date" class="input" />
          </div>
          <div>
            <label class="label">Notes</label>
            <textarea v-model="form.notes" class="input" rows="2"></textarea>
          </div>
        </div>

        <div class="card p-5 space-y-3">
          <h3 class="font-medium text-sm text-gray-900">Summary</h3>
          <div class="flex justify-between text-sm"><span class="text-gray-500">Items</span><span class="font-medium">{{ orderItems.length }}</span></div>
          <div class="flex justify-between text-sm"><span class="text-gray-500">Total Qty</span><span class="font-medium">{{ totalQty }}</span></div>
          <div class="flex justify-between font-bold border-t pt-2"><span>Total Amount</span><span class="text-primary-700">${{ totalAmount.toFixed(2) }}</span></div>
          <div v-if="error" class="bg-red-50 text-red-700 text-xs p-2 rounded">{{ error }}</div>
          <button @click="submitOrder" class="btn-primary w-full justify-center" :disabled="orderItems.length===0 || !form.supplier_id || submitting">
            {{ submitting ? 'Creating…' : 'Create Purchase Order' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>
<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import api from '@/composables/useApi'
import { useDebounceFn } from '@vueuse/core'
import { format } from 'date-fns'
import { useAuthStore } from '@/stores/auth'

const router = useRouter()
const authStore = useAuthStore()
const suppliers = ref([]); const medicineSearch = ref(''); const searchResults = ref([])
const orderItems = ref([]); const submitting = ref(false); const error = ref('')
const form = ref({ supplier_id:'', order_date: format(new Date(),'yyyy-MM-dd'), expected_date:'', notes:'' })

const totalAmount = computed(() => orderItems.value.reduce((s,i) => s + i.quantity_ordered*i.unit_price, 0))
const totalQty = computed(() => orderItems.value.reduce((s,i) => s + i.quantity_ordered, 0))

onMounted(async () => { const { data } = await api.get('/suppliers'); suppliers.value = data.data || data })

const doSearch = useDebounceFn(async () => {
  if (!medicineSearch.value.trim()) { searchResults.value=[]; return }
  const { data } = await api.get('/medicines', { params:{ search:medicineSearch.value, per_page:8 }})
  searchResults.value = data.data
}, 300)

watch(medicineSearch, doSearch)

// Clear items if active branch changes to prevent branch stock mismatch
watch(() => authStore.activeBranchId, () => {
  orderItems.value = []
  searchResults.value = []
  medicineSearch.value = ''
})

function addItem(med) {
  if (orderItems.value.find(i => i.medicine.id === med.id)) return
  orderItems.value.push({ medicine:med, quantity_ordered:1, unit_price:parseFloat(med.purchase_price), expiry_date:'', batch_number:'' })
  medicineSearch.value=''; searchResults.value=[]
}
function removeItem(idx) { orderItems.value.splice(idx,1) }
function recalc() {}

async function submitOrder() {
  submitting.value=true; error.value=''
  try {
    await api.post('/purchase-orders', {
      ...form.value,
      items: orderItems.value.map(i => ({ medicine_id:i.medicine.id, quantity_ordered:i.quantity_ordered, unit_price:i.unit_price, expiry_date:i.expiry_date||null, batch_number:i.batch_number||null }))
    })
    router.push('/purchase-orders')
  } catch(e) { error.value = e.response?.data?.message || 'Failed to create order' }
  finally { submitting.value=false }
}
</script>
