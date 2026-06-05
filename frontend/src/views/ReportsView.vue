<template>
  <div class="space-y-6">
    <!-- Report tabs and Branch Filter -->
    <div class="flex items-center justify-between border-b border-gray-200">
      <div class="flex gap-2">
        <button v-for="tab in tabs" :key="tab.id" @click="activeTab=tab.id"
          class="px-4 py-2.5 text-sm font-medium border-b-2 transition-colors -mb-px"
          :class="activeTab===tab.id ? 'border-primary-600 text-primary-700' : 'border-transparent text-gray-500 hover:text-gray-700'">
          {{ tab.label }}
        </button>
      </div>
      <!-- Branch Filter for Global Roles -->
      <div v-if="['admin', 'owner', 'ceo', 'sales_manager'].includes(authStore.user?.role)" class="flex items-center gap-2 pb-1">
        <label class="text-xs text-gray-500 font-medium">Report Branch:</label>
        <select v-model="selectedBranch" class="text-xs bg-white border border-gray-200 rounded-lg px-2.5 py-1.5 font-medium text-gray-700 focus:outline-none focus:ring-1 focus:ring-primary-500 cursor-pointer">
          <option value="all">All Branches</option>
          <option v-for="b in authStore.branches" :key="b.id" :value="b.id">{{ b.name }}</option>
        </select>
      </div>
    </div>

    <!-- Stock Report -->
    <div v-if="activeTab==='stock'">
      <div class="flex flex-wrap gap-2 mb-4">
        <select v-model="stockFilter" class="input w-44">
          <option value="all">All Medicines</option>
          <option value="low_stock">Low Stock</option>
          <option value="out_of_stock">Out of Stock</option>
          <option value="expiring_soon">Expiring Soon</option>
          <option value="expired">Expired</option>
        </select>
        <button @click="exportCSV('stock')" class="btn-secondary">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
          Export CSV
        </button>
      </div>
      <div class="card overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full">
            <thead class="bg-gray-50 border-b border-gray-100">
              <tr>
                <th class="table-header">Medicine</th>
                <th class="table-header">Category</th>
                <th class="table-header">Stock Qty</th>
                <th class="table-header">Reorder Level</th>
                <th class="table-header">Unit Cost</th>
                <th class="table-header">Stock Value</th>
                <th class="table-header">Expiry</th>
                <th class="table-header">Status</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
              <tr v-if="stockLoading"><td colspan="8" class="py-10 text-center text-gray-400 text-sm">Loading…</td></tr>
              <tr v-for="med in stockReport" :key="med.id" class="hover:bg-gray-50">
                <td class="table-cell"><p class="font-medium">{{ med.name }}</p><p class="text-xs text-gray-400">{{ med.sku }}</p></td>
                <td class="table-cell text-gray-600">{{ med.category?.name }}</td>
                <td class="table-cell font-mono font-semibold">{{ med.stock_quantity }} {{ med.unit }}</td>
                <td class="table-cell font-mono text-gray-500">{{ med.reorder_level }}</td>
                <td class="table-cell">${{ med.purchase_price }}</td>
                <td class="table-cell font-semibold text-primary-700">${{ (med.stock_quantity * med.purchase_price).toFixed(2) }}</td>
                <td class="table-cell text-sm">{{ med.expiry_date ? formatDate(med.expiry_date) : '—' }}</td>
                <td class="table-cell"><span :class="statusBadge(med.stock_status)">{{ statusLabel(med.stock_status) }}</span></td>
              </tr>
            </tbody>
            <tfoot class="bg-gray-50 border-t border-gray-200">
              <tr>
                <td colspan="5" class="table-cell font-semibold text-right text-gray-700">Total Inventory Value:</td>
                <td class="table-cell font-bold text-primary-700">${{ totalStockValue.toFixed(2) }}</td>
                <td colspan="2"></td>
              </tr>
            </tfoot>
          </table>
        </div>
      </div>
    </div>

    <!-- Sales Report -->
    <div v-if="activeTab==='sales'">
      <div class="flex flex-wrap items-center gap-2 mb-4">
        <input v-model="salesDateFrom" type="date" class="input w-40" />
        <input v-model="salesDateTo" type="date" class="input w-40" />
        <button @click="fetchSalesReport" class="btn-primary">Generate</button>
        <button @click="exportCSV('sales')" class="btn-secondary">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
          Export CSV
        </button>
      </div>
      <!-- Summary cards -->
      <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-5">
        <div class="card p-4"><p class="text-xs text-gray-500 uppercase font-semibold">Total Sales</p><p class="text-2xl font-bold text-gray-900 mt-1">{{ salesSummary.count }}</p></div>
        <div class="card p-4"><p class="text-xs text-gray-500 uppercase font-semibold">Revenue</p><p class="text-2xl font-bold text-primary-700 mt-1">${{ formatNum(salesSummary.revenue) }}</p></div>
        <div class="card p-4"><p class="text-xs text-gray-500 uppercase font-semibold">Avg Sale</p><p class="text-2xl font-bold text-gray-900 mt-1">${{ formatNum(salesSummary.avg) }}</p></div>
        <div class="card p-4"><p class="text-xs text-gray-500 uppercase font-semibold">Items Sold</p><p class="text-2xl font-bold text-gray-900 mt-1">{{ salesSummary.items }}</p></div>
      </div>
      <div class="card overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full">
            <thead class="bg-gray-50 border-b border-gray-100">
              <tr>
                <th class="table-header">Invoice</th>
                <th class="table-header">Customer</th>
                <th class="table-header">Items</th>
                <th class="table-header">Subtotal</th>
                <th class="table-header">Discount</th>
                <th class="table-header">Total</th>
                <th class="table-header">Payment</th>
                <th class="table-header">Date</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
              <tr v-if="salesLoading"><td colspan="8" class="py-10 text-center text-gray-400 text-sm">Loading…</td></tr>
              <tr v-for="s in salesReport" :key="s.id" class="hover:bg-gray-50">
                <td class="table-cell font-mono text-xs text-primary-700">{{ s.invoice_number }}</td>
                <td class="table-cell">{{ s.customer_name || 'Walk-in' }}</td>
                <td class="table-cell text-center">{{ s.items?.length }}</td>
                <td class="table-cell">${{ s.subtotal }}</td>
                <td class="table-cell text-red-600">{{ s.discount > 0 ? '-$'+s.discount : '—' }}</td>
                <td class="table-cell font-semibold">${{ s.total }}</td>
                <td class="table-cell capitalize text-gray-500">{{ s.payment_method?.replace('_',' ') }}</td>
                <td class="table-cell text-xs text-gray-400">{{ formatDate(s.created_at) }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Expiry Report -->
    <div v-if="activeTab==='expiry'">
      <div class="flex items-center gap-2 mb-4">
        <label class="text-sm text-gray-600">Show medicines expiring within</label>
        <select v-model="expiryDays" class="input w-32" @change="fetchExpiryReport">
          <option :value="30">30 days</option>
          <option :value="60">60 days</option>
          <option :value="90">90 days</option>
          <option :value="180">6 months</option>
          <option :value="365">1 year</option>
        </select>
      </div>
      <div class="card overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full">
            <thead class="bg-gray-50 border-b border-gray-100">
              <tr>
                <th class="table-header">Medicine</th>
                <th class="table-header">Category</th>
                <th class="table-header">Stock Qty</th>
                <th class="table-header">Expiry Date</th>
                <th class="table-header">Days Remaining</th>
                <th class="table-header">Stock Value at Risk</th>
                <th class="table-header">Risk</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
              <tr v-if="expiryLoading"><td colspan="7" class="py-10 text-center text-gray-400 text-sm">Loading…</td></tr>
              <tr v-for="med in expiryReport" :key="med.id" class="hover:bg-gray-50">
                <td class="table-cell font-medium">{{ med.name }}</td>
                <td class="table-cell text-gray-500">{{ med.category?.name }}</td>
                <td class="table-cell font-mono">{{ med.stock_quantity }} {{ med.unit }}</td>
                <td class="table-cell">{{ formatDate(med.expiry_date) }}</td>
                <td class="table-cell font-mono font-semibold" :class="med.days_to_expiry <= 30 ? 'text-red-700' : 'text-yellow-700'">{{ med.days_to_expiry }}d</td>
                <td class="table-cell font-semibold text-red-700">${{ (med.stock_quantity * med.purchase_price).toFixed(2) }}</td>
                <td class="table-cell"><span :class="med.days_to_expiry <= 30 ? 'badge-red' : 'badge-yellow'">{{ med.days_to_expiry <= 30 ? 'Critical' : 'Warning' }}</span></td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</template>
<script setup>
import { ref, watch, onMounted, computed } from 'vue'
import api from '@/composables/useApi'
import { format, parseISO, subDays } from 'date-fns'
import { useAuthStore } from '@/stores/auth'

const authStore = useAuthStore()
const activeTab = ref('stock')
const tabs = [{ id:'stock', label:'Stock Report' }, { id:'sales', label:'Sales Report' }, { id:'expiry', label:'Expiry Report' }]
const selectedBranch = ref(authStore.activeBranchId || 'all')

// Stock
const stockReport = ref([]); const stockLoading = ref(false); const stockFilter = ref('all')
const totalStockValue = computed(() => stockReport.value.reduce((s,m) => s + m.stock_quantity*m.purchase_price, 0))

// Sales
const salesReport = ref([]); const salesLoading = ref(false)
const salesDateFrom = ref(format(subDays(new Date(),30),'yyyy-MM-dd'))
const salesDateTo   = ref(format(new Date(),'yyyy-MM-dd'))
const salesSummary  = computed(() => ({
  count: salesReport.value.length,
  revenue: salesReport.value.reduce((s,x)=>s+parseFloat(x.total),0),
  avg: salesReport.value.length ? salesReport.value.reduce((s,x)=>s+parseFloat(x.total),0)/salesReport.value.length : 0,
  items: salesReport.value.reduce((s,x)=>s+(x.items?.length||0),0)
}))

// Expiry
const expiryReport = ref([]); const expiryLoading = ref(false); const expiryDays = ref(90)

onMounted(() => { fetchStockReport(); fetchSalesReport(); fetchExpiryReport() })
watch(stockFilter, fetchStockReport)

// Keep local selected branch filter synchronized with top bar, and reload on changes
watch(() => authStore.activeBranchId, (newId) => {
  selectedBranch.value = newId || 'all'
})
watch(selectedBranch, () => {
  fetchStockReport()
  fetchSalesReport()
  fetchExpiryReport()
})

async function fetchStockReport() {
  stockLoading.value = true
  try {
    const params = { per_page:200, branch_id: selectedBranch.value }
    if (stockFilter.value !== 'all') params.stock_status = stockFilter.value
    const { data } = await api.get('/medicines', { params })
    stockReport.value = data.data
  } finally { stockLoading.value=false }
}

async function fetchSalesReport() {
  salesLoading.value=true
  try {
    const { data } = await api.get('/sales', { params:{ per_page:200, date_from:salesDateFrom.value, date_to:salesDateTo.value, branch_id: selectedBranch.value }})
    salesReport.value = data.data
  } finally { salesLoading.value=false }
}

async function fetchExpiryReport() {
  expiryLoading.value=true
  try {
    const { data } = await api.get('/medicines', { params:{ stock_status:'expiring_soon', per_page:200, branch_id: selectedBranch.value }})
    expiryReport.value = data.data.filter(m => m.days_to_expiry <= expiryDays.value)
  } finally { expiryLoading.value=false }
}

function formatDate(d) { return d ? format(parseISO(d), 'dd MMM yyyy') : '—' }
function formatNum(n) { return Number(n||0).toLocaleString('en-US',{minimumFractionDigits:2,maximumFractionDigits:2}) }
function statusBadge(s) { return { in_stock:'badge-green', low_stock:'badge-yellow', out_of_stock:'badge-red' }[s]||'badge-gray' }
function statusLabel(s) { return { in_stock:'In Stock', low_stock:'Low Stock', out_of_stock:'Out of Stock' }[s]||s }

function exportCSV(type) {
  let rows = []; let filename = ''
  if (type==='stock') {
    filename='stock-report.csv'
    rows = [['Name','SKU','Category','Stock Qty','Unit','Reorder Level','Purchase Price','Stock Value','Expiry','Status'],
      ...stockReport.value.map(m => [m.name,m.sku,m.category?.name,m.stock_quantity,m.unit,m.reorder_level,m.purchase_price,(m.stock_quantity*m.purchase_price).toFixed(2),m.expiry_date||'',statusLabel(m.stock_status)])]
  } else if (type==='sales') {
    filename='sales-report.csv'
    rows = [['Invoice','Customer','Items','Subtotal','Discount','Total','Payment','Date'],
      ...salesReport.value.map(s => [s.invoice_number,s.customer_name||'Walk-in',s.items?.length,s.subtotal,s.discount,s.total,s.payment_method,s.created_at])]
  }
  const csv = rows.map(r => r.join(',')).join('\n')
  const blob = new Blob([csv], { type:'text/csv' })
  const a = document.createElement('a'); a.href = URL.createObjectURL(blob); a.download = filename; a.click()
}
</script>
