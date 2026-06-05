<template>
  <div class="space-y-5">
    <div class="flex items-center justify-between">
      <div class="flex gap-2">
        <input v-model="search" type="text" class="input w-56" placeholder="Search order number…" />
        <select v-model="filterStatus" class="input w-40">
          <option value="">All Status</option>
          <option value="pending">Pending</option>
          <option value="ordered">Ordered</option>
          <option value="received">Received</option>
          <option value="cancelled">Cancelled</option>
        </select>
      </div>
      <router-link v-if="['admin', 'supply_chain_manager'].includes(authStore.user?.role)" to="/purchase-orders/new" class="btn-primary">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        New Order
      </router-link>
    </div>

    <div class="card overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full">
          <thead class="bg-gray-50 border-b border-gray-100">
            <tr>
              <th class="table-header">Order #</th>
              <th class="table-header">Supplier</th>
              <th class="table-header">Order Date</th>
              <th class="table-header">Expected</th>
              <th class="table-header">Total</th>
              <th class="table-header">Status</th>
              <th class="table-header">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-50">
            <tr v-if="loading"><td colspan="7" class="py-12 text-center text-gray-400 text-sm">Loading…</td></tr>
            <tr v-else-if="orders.length===0"><td colspan="7" class="py-12 text-center text-gray-400 text-sm">No purchase orders found</td></tr>
            <tr v-for="o in orders" :key="o.id" class="hover:bg-gray-50">
              <td class="table-cell font-mono text-xs font-semibold text-primary-700">{{ o.order_number }}</td>
              <td class="table-cell font-medium">{{ o.supplier?.name }}</td>
              <td class="table-cell text-sm">{{ formatDate(o.order_date) }}</td>
              <td class="table-cell text-sm text-gray-500">{{ formatDate(o.expected_date) || '—' }}</td>
              <td class="table-cell font-semibold">${{ o.total_amount }}</td>
              <td class="table-cell"><span :class="statusBadge(o.status)">{{ o.status }}</span></td>
              <td class="table-cell">
                <div class="flex gap-2">
                  <button @click="viewOrder(o)" class="text-primary-600 text-xs font-medium">View</button>
                  <button v-if="['admin', 'supply_chain_manager'].includes(authStore.user?.role) && o.status !== 'received' && o.status !== 'cancelled'" @click="openReceive(o)" class="text-green-600 text-xs font-medium">Receive</button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      <div class="flex items-center justify-between px-5 py-3 border-t border-gray-100" v-if="pagination.last_page > 1">
        <p class="text-xs text-gray-500">Page {{ pagination.current_page }} of {{ pagination.last_page }}</p>
        <div class="flex gap-1">
          <button @click="page--" :disabled="page===1" class="btn btn-secondary btn-sm">Prev</button>
          <button @click="page++" :disabled="page===pagination.last_page" class="btn btn-secondary btn-sm">Next</button>
        </div>
      </div>
    </div>

    <!-- View Modal -->
    <Modal v-model="showDetail" title="Purchase Order Details" size="lg">
      <div v-if="selectedOrder" class="space-y-4">
        <div class="grid grid-cols-2 gap-3 text-sm">
          <div><p class="text-gray-500">Order Number</p><p class="font-mono font-semibold text-primary-700">{{ selectedOrder.order_number }}</p></div>
          <div><p class="text-gray-500">Supplier</p><p class="font-medium">{{ selectedOrder.supplier?.name }}</p></div>
          <div><p class="text-gray-500">Status</p><span :class="statusBadge(selectedOrder.status)">{{ selectedOrder.status }}</span></div>
          <div><p class="text-gray-500">Total</p><p class="font-bold text-primary-700">${{ selectedOrder.total_amount }}</p></div>
        </div>
        <table class="w-full text-sm border border-gray-100 rounded-lg overflow-hidden">
          <thead class="bg-gray-50"><tr>
            <th class="table-header">Medicine</th>
            <th class="table-header text-right">Ordered</th>
            <th class="table-header text-right">Received</th>
            <th class="table-header text-right">Unit Price</th>
            <th class="table-header text-right">Total</th>
          </tr></thead>
          <tbody class="divide-y divide-gray-100">
            <tr v-for="item in selectedOrder.items" :key="item.id">
              <td class="table-cell">{{ item.medicine?.name }}</td>
              <td class="table-cell text-right font-mono">{{ item.quantity_ordered }}</td>
              <td class="table-cell text-right font-mono">{{ item.quantity_received }}</td>
              <td class="table-cell text-right">${{ item.unit_price }}</td>
              <td class="table-cell text-right font-semibold">${{ item.total_price }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </Modal>

    <!-- Receive Modal -->
    <Modal v-model="showReceive" title="Receive Order" size="lg">
      <div v-if="selectedOrder" class="space-y-4">
        <p class="text-sm text-gray-500">Enter the quantity received for each item. Stock will be updated automatically.</p>
        <div v-for="item in receiveForm" :key="item.id" class="flex items-center gap-4 p-3 bg-gray-50 rounded-lg">
          <div class="flex-1">
            <p class="text-sm font-medium">{{ item.medicine_name }}</p>
            <p class="text-xs text-gray-500">Ordered: {{ item.quantity_ordered }}</p>
          </div>
          <div>
            <label class="label text-xs">Qty Received</label>
            <input v-model.number="item.quantity_received" type="number" min="0" :max="item.quantity_ordered" class="input w-24 text-center" />
          </div>
        </div>
        <div v-if="receiveError" class="text-red-600 text-sm">{{ receiveError }}</div>
      </div>
      <template #footer>
        <div class="flex justify-end gap-2" v-if="selectedOrder">
          <button type="button" @click="showReceive=false" class="btn-secondary">Cancel</button>
          <button @click="submitReceive" class="btn-primary" :disabled="receiveLoading">{{ receiveLoading?'Processing…':'Confirm Receipt' }}</button>
        </div>
      </template>
    </Modal>
  </div>
</template>
<script setup>
import { ref, watch, onMounted } from 'vue'
import api from '@/composables/useApi'
import Modal from '@/components/Modal.vue'
import { format, parseISO } from 'date-fns'
import { useAuthStore } from '@/stores/auth'

const authStore = useAuthStore()
const orders = ref([]); const loading = ref(true)
const page = ref(1); const search = ref(''); const filterStatus = ref('')
const pagination = ref({ current_page:1, last_page:1, total:0 })
const showDetail = ref(false); const showReceive = ref(false)
const selectedOrder = ref(null); const receiveForm = ref([])
const receiveLoading = ref(false); const receiveError = ref('')

onMounted(fetchOrders)
watch([search, filterStatus, () => authStore.activeBranchId], () => { page.value=1; fetchOrders() })
watch(page, fetchOrders)

async function fetchOrders() {
  loading.value=true
  try {
    const { data } = await api.get('/purchase-orders', { params:{ page:page.value, search:search.value, status:filterStatus.value }})
    orders.value = data.data; pagination.value = { current_page:data.current_page, last_page:data.last_page, total:data.total }
  } finally { loading.value=false }
}

function formatDate(d) { return d ? format(parseISO(d), 'dd MMM yyyy') : null }
function statusBadge(s) { return { pending:'badge-yellow', ordered:'badge-blue', received:'badge-green', cancelled:'badge-gray' }[s]||'badge-gray' }

async function viewOrder(o) {
  const { data } = await api.get(`/purchase-orders/${o.id}`)
  selectedOrder.value = data; showDetail.value = true
}

async function openReceive(o) {
  const { data } = await api.get(`/purchase-orders/${o.id}`)
  selectedOrder.value = data
  receiveForm.value = data.items.map(i => ({ id:i.id, medicine_name:i.medicine?.name, quantity_ordered:i.quantity_ordered, quantity_received:i.quantity_ordered }))
  receiveError.value = ''; showReceive.value = true
}

async function submitReceive() {
  receiveLoading.value=true; receiveError.value=''
  try {
    await api.post(`/purchase-orders/${selectedOrder.value.id}/receive`, { items: receiveForm.value })
    showReceive.value=false; await fetchOrders()
  } catch(e) { receiveError.value = e.response?.data?.message || 'Failed to receive order' }
  finally { receiveLoading.value=false }
}
</script>
