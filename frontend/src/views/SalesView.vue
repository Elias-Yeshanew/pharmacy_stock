<template>
  <div class="space-y-5">
    <div class="flex flex-wrap items-center justify-between gap-3">
      <div class="flex flex-wrap items-center gap-2">
        <input v-model="search" type="text" class="input w-64" placeholder="Search by invoice or customer…" />
        <input v-model="dateFrom" type="date" class="input w-40" />
        <input v-model="dateTo" type="date" class="input w-40" />
      </div>
      <router-link to="/sales/new" class="btn-primary">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        New Sale
      </router-link>
    </div>

    <div class="card overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full">
          <thead class="bg-gray-50 border-b border-gray-100">
            <tr>
              <th class="table-header">Invoice</th>
              <th class="table-header">Customer</th>
              <th class="table-header">Items</th>
              <th class="table-header">Total</th>
              <th class="table-header">Payment</th>
              <th class="table-header">Status</th>
              <th class="table-header">By</th>
              <th class="table-header">Date</th>
              <th class="table-header">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-50">
            <tr v-if="loading"><td colspan="9" class="py-12 text-center text-gray-400 text-sm">Loading…</td></tr>
            <tr v-else-if="sales.length === 0"><td colspan="9" class="py-12 text-center text-gray-400 text-sm">No sales found</td></tr>
            <tr v-for="sale in sales" :key="sale.id" class="hover:bg-gray-50">
              <td class="table-cell font-mono text-xs font-semibold text-primary-700">{{ sale.invoice_number }}</td>
              <td class="table-cell">{{ sale.customer_name || '—' }}<br><span class="text-xs text-gray-400">{{ sale.customer_phone }}</span></td>
              <td class="table-cell text-center font-semibold">{{ sale.items?.length }}</td>
              <td class="table-cell font-semibold text-gray-900">${{ sale.total }}</td>
              <td class="table-cell"><span class="badge-blue capitalize">{{ sale.payment_method?.replace('_',' ') }}</span></td>
              <td class="table-cell"><span :class="sale.status==='completed'?'badge-green':'badge-yellow'">{{ sale.status }}</span></td>
              <td class="table-cell text-gray-500">{{ sale.user?.name }}</td>
              <td class="table-cell text-xs text-gray-400">{{ formatDate(sale.created_at) }}</td>
              <td class="table-cell">
                <button @click="viewSale(sale)" class="text-primary-600 hover:text-primary-800 text-xs font-medium">View</button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      <div class="flex items-center justify-between px-5 py-3 border-t border-gray-100" v-if="pagination.last_page > 1">
        <p class="text-xs text-gray-500">Page {{ pagination.current_page }} of {{ pagination.last_page }} ({{ pagination.total }} total)</p>
        <div class="flex gap-1">
          <button @click="page--" :disabled="page===1" class="btn btn-secondary btn-sm">Prev</button>
          <button @click="page++" :disabled="page===pagination.last_page" class="btn btn-secondary btn-sm">Next</button>
        </div>
      </div>
    </div>

    <!-- Sale Detail Modal -->
    <Modal v-model="showDetail" title="Sale Details" size="lg">
      <div v-if="selectedSale" class="space-y-4">
        <div class="grid grid-cols-2 gap-3 text-sm">
          <div><p class="text-gray-500">Invoice</p><p class="font-mono font-semibold text-primary-700">{{ selectedSale.invoice_number }}</p></div>
          <div><p class="text-gray-500">Date</p><p class="font-medium">{{ formatDate(selectedSale.created_at) }}</p></div>
          <div><p class="text-gray-500">Customer</p><p class="font-medium">{{ selectedSale.customer_name || 'Walk-in' }}</p></div>
          <div><p class="text-gray-500">Payment</p><p class="font-medium capitalize">{{ selectedSale.payment_method?.replace('_',' ') }}</p></div>
        </div>
        <table class="w-full text-sm border border-gray-100 rounded-lg overflow-hidden">
          <thead class="bg-gray-50"><tr>
            <th class="table-header">Medicine</th><th class="table-header text-right">Qty</th>
            <th class="table-header text-right">Unit Price</th><th class="table-header text-right">Total</th>
          </tr></thead>
          <tbody class="divide-y divide-gray-100">
            <tr v-for="item in selectedSale.items" :key="item.id">
              <td class="table-cell">{{ item.medicine?.name }}</td>
              <td class="table-cell text-right font-mono">{{ item.quantity }}</td>
              <td class="table-cell text-right">${{ item.unit_price }}</td>
              <td class="table-cell text-right font-semibold">${{ item.total_price }}</td>
            </tr>
          </tbody>
        </table>
        <div class="flex justify-end text-sm">
          <div class="space-y-1 w-48">
            <div class="flex justify-between"><span class="text-gray-500">Subtotal</span><span>${{ selectedSale.subtotal }}</span></div>
            <div class="flex justify-between" v-if="selectedSale.discount > 0"><span class="text-gray-500">Discount</span><span class="text-red-600">-${{ selectedSale.discount }}</span></div>
            <div class="flex justify-between font-bold border-t pt-1"><span>Total</span><span class="text-primary-700">${{ selectedSale.total }}</span></div>
          </div>
        </div>
      </div>
    </Modal>
  </div>
</template>
<script setup>
import { ref, watch, onMounted } from 'vue'
import api from '@/composables/useApi'
import Modal from '@/components/Modal.vue'
import { format, parseISO } from 'date-fns'

const sales = ref([]); const loading = ref(true)
const page = ref(1); const search = ref(''); const dateFrom = ref(''); const dateTo = ref('')
const pagination = ref({ current_page:1, last_page:1, total:0 })
const showDetail = ref(false); const selectedSale = ref(null)

onMounted(fetchSales)
watch([search, dateFrom, dateTo], () => { page.value=1; fetchSales() })
watch(page, fetchSales)

async function fetchSales() {
  loading.value = true
  try {
    const { data } = await api.get('/sales', { params:{ page:page.value, search:search.value, date_from:dateFrom.value, date_to:dateTo.value, per_page:20 }})
    sales.value = data.data
    pagination.value = { current_page:data.current_page, last_page:data.last_page, total:data.total }
  } finally { loading.value = false }
}

function formatDate(d) { return d ? format(parseISO(d), 'dd MMM yyyy HH:mm') : '—' }
function viewSale(sale) { selectedSale.value = sale; showDetail.value = true }
</script>
