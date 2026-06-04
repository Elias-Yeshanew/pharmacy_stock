<template>
  <div class="space-y-6">
    <!-- Stat Cards -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
      <StatCard label="Total Medicines" :value="stats.total_medicines" icon="pill" color="blue" />
      <StatCard label="Low Stock" :value="stats.low_stock" icon="warning" color="yellow" :to="'/medicines?stock_status=low_stock'" />
      <StatCard label="Out of Stock" :value="stats.out_of_stock" icon="error" color="red" :to="'/medicines?stock_status=out_of_stock'" />
      <StatCard label="Expiring Soon" :value="stats.expiring_soon" icon="clock" color="orange" :to="'/medicines?stock_status=expiring_soon'" />
    </div>

    <!-- Revenue Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
      <div class="card p-5">
        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Today's Sales</p>
        <p class="text-2xl font-bold text-gray-900 mt-1">${{ formatNum(stats.today_sales) }}</p>
      </div>
      <div class="card p-5">
        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">This Month</p>
        <p class="text-2xl font-bold text-gray-900 mt-1">${{ formatNum(stats.month_sales) }}</p>
      </div>
      <div class="card p-5">
        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Inventory Value</p>
        <p class="text-2xl font-bold text-gray-900 mt-1">${{ formatNum(stats.inventory_value) }}</p>
      </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      <!-- Low Stock Alert -->
      <div class="card">
        <div class="flex items-center justify-between px-5 pt-5 pb-3 border-b border-gray-100">
          <h3 class="font-semibold text-gray-900 text-sm">Low Stock Alert</h3>
          <router-link to="/medicines?stock_status=low_stock" class="text-xs text-primary-600 hover:underline">View all</router-link>
        </div>
        <div class="divide-y divide-gray-50">
          <div v-if="lowStockMedicines.length === 0" class="px-5 py-8 text-center text-sm text-gray-400">All medicines are well stocked</div>
          <div v-for="med in lowStockMedicines" :key="med.id" class="flex items-center justify-between px-5 py-3">
            <div>
              <p class="text-sm font-medium text-gray-900">{{ med.name }}</p>
              <p class="text-xs text-gray-500">{{ med.category?.name }}</p>
            </div>
            <div class="text-right">
              <span class="badge-yellow">{{ med.stock_quantity }} {{ med.unit }}</span>
              <p class="text-xs text-gray-400 mt-0.5">Min: {{ med.reorder_level }}</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Expiring Soon -->
      <div class="card">
        <div class="flex items-center justify-between px-5 pt-5 pb-3 border-b border-gray-100">
          <h3 class="font-semibold text-gray-900 text-sm">Expiring Soon (90 days)</h3>
          <router-link to="/medicines?stock_status=expiring_soon" class="text-xs text-primary-600 hover:underline">View all</router-link>
        </div>
        <div class="divide-y divide-gray-50">
          <div v-if="expiringSoon.length === 0" class="px-5 py-8 text-center text-sm text-gray-400">No medicines expiring soon</div>
          <div v-for="med in expiringSoon" :key="med.id" class="flex items-center justify-between px-5 py-3">
            <div>
              <p class="text-sm font-medium text-gray-900">{{ med.name }}</p>
              <p class="text-xs text-gray-500">{{ med.stock_quantity }} {{ med.unit }} in stock</p>
            </div>
            <div class="text-right">
              <span :class="med.days_to_expiry <= 30 ? 'badge-red' : 'badge-yellow'">
                {{ med.days_to_expiry }} days
              </span>
              <p class="text-xs text-gray-400 mt-0.5">{{ formatDate(med.expiry_date) }}</p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Recent Movements -->
    <div class="card">
      <div class="px-5 pt-5 pb-3 border-b border-gray-100">
        <h3 class="font-semibold text-gray-900 text-sm">Recent Stock Movements</h3>
      </div>
      <div class="overflow-x-auto">
        <table class="w-full">
          <thead class="bg-gray-50 border-b border-gray-100">
            <tr>
              <th class="table-header">Medicine</th>
              <th class="table-header">Type</th>
              <th class="table-header">Qty</th>
              <th class="table-header">Before → After</th>
              <th class="table-header">By</th>
              <th class="table-header">Time</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-50">
            <tr v-for="m in recentMovements" :key="m.id" class="hover:bg-gray-50">
              <td class="table-cell font-medium">{{ m.medicine?.name }}</td>
              <td class="table-cell">
                <span :class="movementBadge(m.type)">{{ m.type }}</span>
              </td>
              <td class="table-cell font-mono text-sm">{{ m.quantity }}</td>
              <td class="table-cell font-mono text-xs text-gray-500">{{ m.quantity_before }} → {{ m.quantity_after }}</td>
              <td class="table-cell text-gray-500">{{ m.user?.name }}</td>
              <td class="table-cell text-gray-400 text-xs">{{ timeAgo(m.created_at) }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue'
import api from '@/composables/useApi'
import StatCard from '@/components/StatCard.vue'
import { format, formatDistanceToNow, parseISO } from 'date-fns'
import { useAuthStore } from '@/stores/auth'

const authStore = useAuthStore()

const stats = ref({ total_medicines:0, low_stock:0, out_of_stock:0, expiring_soon:0, today_sales:0, month_sales:0, inventory_value:0 })
const lowStockMedicines = ref([])
const expiringSoon = ref([])
const recentMovements = ref([])
const loading = ref(true)

onMounted(fetchDashboardData)

watch(() => authStore.activeBranchId, fetchDashboardData)

async function fetchDashboardData() {
  loading.value = true
  try {
    const { data } = await api.get('/dashboard')
    stats.value = data.stats
    lowStockMedicines.value = data.low_stock_medicines
    expiringSoon.value = data.expiring_soon
    recentMovements.value = data.recent_movements
  } finally { loading.value = false }
}

function formatNum(n) { return Number(n || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }
function formatDate(d) { return d ? format(parseISO(d), 'dd MMM yyyy') : '—' }
function timeAgo(d) { return d ? formatDistanceToNow(parseISO(d), { addSuffix: true }) : '' }

function movementBadge(type) {
  return {
    in: 'badge-green', out: 'badge-red', adjustment: 'badge-blue',
    return: 'badge-yellow', expired: 'badge-gray'
  }[type] || 'badge-gray'
}
</script>
