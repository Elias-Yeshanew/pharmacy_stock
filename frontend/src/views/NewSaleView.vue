<template>
  <div class="max-w-4xl space-y-5">
    <div class="flex items-center gap-3 mb-2">
      <router-link to="/sales" class="text-gray-400 hover:text-gray-600">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
      </router-link>
      <h2 class="text-lg font-semibold text-gray-900">New Sale / Dispense</h2>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
      <!-- Left: Item picker -->
      <div class="lg:col-span-2 space-y-4">
        <div class="card p-5">
          <h3 class="font-medium text-sm text-gray-900 mb-3">Add Medicines</h3>
          <div class="flex gap-2 mb-4">
            <input v-model="medicineSearch" type="text" class="input flex-1" placeholder="Search medicine by name or barcode…" />
          </div>
          <div v-if="searchResults.length > 0" class="border border-gray-200 rounded-lg overflow-hidden mb-4">
            <div v-for="med in searchResults" :key="med.id"
              @click="addItem(med)"
              class="flex items-center justify-between px-4 py-3 hover:bg-primary-50 cursor-pointer border-b last:border-0 transition-colors">
              <div>
                <p class="text-sm font-medium text-gray-900">{{ med.name }}</p>
                <p class="text-xs text-gray-500">{{ med.dosage_form }} {{ med.strength }} · Stock: {{ med.stock_quantity }}</p>
              </div>
              <div class="text-right">
                <p class="text-sm font-semibold text-primary-700">${{ med.selling_price }}</p>
                <span :class="med.stock_quantity > 0 ? 'badge-green' : 'badge-red'" class="text-xs">{{ med.stock_quantity > 0 ? 'Available' : 'Out of stock' }}</span>
              </div>
            </div>
          </div>

          <!-- Cart items -->
          <div v-if="cartItems.length === 0" class="text-center py-8 text-gray-400 text-sm border-2 border-dashed border-gray-200 rounded-lg">
            Search and add medicines above
          </div>
          <div v-else class="space-y-2">
            <div v-for="(item, idx) in cartItems" :key="idx" class="flex items-center gap-3 p-3 bg-gray-50 rounded-lg">
              <div class="flex-1">
                <p class="text-sm font-medium text-gray-900">{{ item.medicine.name }}</p>
                <p class="text-xs text-gray-500">{{ item.medicine.dosage_form }} {{ item.medicine.strength }}</p>
              </div>
              <input v-model.number="item.quantity" type="number" min="1" :max="item.medicine.stock_quantity" class="input w-20 text-center text-sm" @change="updateItem(idx)" />
              <div class="text-sm font-semibold text-gray-900 w-20 text-right">${{ (item.unit_price * item.quantity).toFixed(2) }}</div>
              <button @click="removeItem(idx)" class="text-red-400 hover:text-red-600">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Right: Summary & customer -->
      <div class="space-y-4">
        <div class="card p-5 space-y-4">
          <h3 class="font-medium text-sm text-gray-900">Customer Info</h3>
          <div>
            <label class="label">Customer Name</label>
            <input v-model="saleForm.customer_name" type="text" class="input" placeholder="Walk-in customer" />
          </div>
          <div>
            <label class="label">Phone</label>
            <input v-model="saleForm.customer_phone" type="text" class="input" />
          </div>
          <div>
            <label class="label">Payment Method *</label>
            <select v-model="saleForm.payment_method" class="input">
              <option value="cash">Cash</option>
              <option value="card">Card</option>
              <option value="mobile_money">Mobile Money</option>
            </select>
          </div>
          <div class="flex items-center gap-2">
            <input v-model="saleForm.prescription_required" type="checkbox" id="rx" class="w-4 h-4 rounded" />
            <label for="rx" class="text-sm text-gray-700">Prescription required</label>
          </div>
          <div v-if="saleForm.prescription_required">
            <label class="label">Prescription No.</label>
            <input v-model="saleForm.prescription_number" type="text" class="input" />
          </div>
        </div>

        <div class="card p-5 space-y-3">
          <h3 class="font-medium text-sm text-gray-900">Order Summary</h3>
          <div class="space-y-2 text-sm">
            <div class="flex justify-between text-gray-600"><span>Subtotal</span><span>${{ subtotal.toFixed(2) }}</span></div>
            <div class="flex items-center justify-between gap-2">
              <span class="text-gray-600 text-sm">Discount ($)</span>
              <input v-model.number="saleForm.discount" type="number" min="0" class="input w-24 text-right" />
            </div>
            <div class="flex justify-between font-bold text-gray-900 border-t pt-2 text-base"><span>Total</span><span class="text-primary-700">${{ total.toFixed(2) }}</span></div>
          </div>
          <div v-if="error" class="bg-red-50 text-red-700 text-xs p-2 rounded">{{ error }}</div>
          <button @click="submitSale" class="btn-primary w-full justify-center" :disabled="cartItems.length === 0 || submitting">
            {{ submitting ? 'Processing…' : 'Complete Sale' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>
<script setup>
import { ref, computed, watch } from 'vue'
import { useRouter } from 'vue-router'
import api from '@/composables/useApi'
import { useDebounceFn } from '@vueuse/core'
import { useAuthStore } from '@/stores/auth'

const router = useRouter()
const authStore = useAuthStore()
const medicineSearch = ref(''); const searchResults = ref([])
const cartItems = ref([])
const submitting = ref(false); const error = ref('')
const saleForm = ref({ customer_name:'', customer_phone:'', payment_method:'cash', discount:0, prescription_required:false, prescription_number:'' })

const subtotal = computed(() => cartItems.value.reduce((s,i) => s + i.unit_price * i.quantity, 0))
const total = computed(() => Math.max(0, subtotal.value - (saleForm.value.discount || 0)))

const doSearch = useDebounceFn(async () => {
  if (!medicineSearch.value.trim()) { searchResults.value = []; return }
  const { data } = await api.get('/medicines', { params:{ search:medicineSearch.value, per_page:8 }})
  searchResults.value = data.data
}, 300)

watch(medicineSearch, doSearch)

// Clear cart if active branch changes to prevent branch stock mismatch
watch(() => authStore.activeBranchId, () => {
  cartItems.value = []
  searchResults.value = []
  medicineSearch.value = ''
})

function addItem(med) {
  if (med.stock_quantity <= 0) return
  const existing = cartItems.value.find(i => i.medicine.id === med.id)
  if (existing) { existing.quantity++; return }
  cartItems.value.push({ medicine: med, quantity:1, unit_price: parseFloat(med.selling_price) })
  medicineSearch.value = ''; searchResults.value = []
}

function removeItem(idx) { cartItems.value.splice(idx, 1) }
function updateItem(idx) { if (cartItems.value[idx].quantity < 1) cartItems.value[idx].quantity = 1 }

async function submitSale() {
  submitting.value = true; error.value = ''
  try {
    await api.post('/sales', {
      ...saleForm.value,
      items: cartItems.value.map(i => ({ medicine_id:i.medicine.id, quantity:i.quantity, unit_price:i.unit_price, discount:0 }))
    })
    router.push('/sales')
  } catch(e) { error.value = e.response?.data?.message || 'Failed to complete sale' }
  finally { submitting.value = false }
}
</script>
