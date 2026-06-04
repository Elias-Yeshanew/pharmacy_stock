<template>
  <div class="space-y-5">
    <div class="flex flex-wrap items-center gap-2">
      <input v-model="search" type="text" class="input w-64" placeholder="Search by medicine or reference…" />
      <select v-model="filterType" class="input w-40">
        <option value="">All Types</option>
        <option value="in">Stock In</option>
        <option value="out">Stock Out</option>
        <option value="adjustment">Adjustment</option>
        <option value="return">Return</option>
        <option value="expired">Expired</option>
      </select>
      <input v-model="dateFrom" type="date" class="input w-40" />
      <input v-model="dateTo" type="date" class="input w-40" />
    </div>

    <div class="card overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full">
          <thead class="bg-gray-50 border-b border-gray-100">
            <tr>
              <th class="table-header">Medicine</th>
              <th class="table-header">Type</th>
              <th class="table-header">Qty</th>
              <th class="table-header">Before</th>
              <th class="table-header">After</th>
              <th class="table-header">Reference</th>
              <th class="table-header">Batch</th>
              <th class="table-header">By</th>
              <th class="table-header">Date</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-50">
            <tr v-if="loading"><td colspan="9" class="py-12 text-center text-gray-400 text-sm">Loading…</td></tr>
            <tr v-else-if="movements.length === 0"><td colspan="9" class="py-12 text-center text-gray-400 text-sm">No movements found</td></tr>
            <tr v-for="m in movements" :key="m.id" class="hover:bg-gray-50">
              <td class="table-cell font-medium">{{ m.medicine?.name }}</td>
              <td class="table-cell"><span :class="typeBadge(m.type)">{{ m.type }}</span></td>
              <td class="table-cell font-mono font-semibold" :class="m.type === 'in' ? 'text-green-700' : 'text-red-700'">
                {{ m.type === 'in' ? '+' : '-' }}{{ m.quantity }}
              </td>
              <td class="table-cell font-mono text-gray-500">{{ m.quantity_before }}</td>
              <td class="table-cell font-mono text-gray-900 font-semibold">{{ m.quantity_after }}</td>
              <td class="table-cell text-gray-500 text-xs">{{ m.reference_number || '—' }}</td>
              <td class="table-cell text-gray-500 text-xs">{{ m.batch_number || '—' }}</td>
              <td class="table-cell text-gray-500">{{ m.user?.name }}</td>
              <td class="table-cell text-xs text-gray-400">{{ formatDate(m.created_at) }}</td>
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
  </div>
</template>
<script setup>
import { ref, watch, onMounted } from 'vue'
import api from '@/composables/useApi'
import { format, parseISO } from 'date-fns'
import { useAuthStore } from '@/stores/auth'

const authStore = useAuthStore()
const movements = ref([]); const loading = ref(true)
const page = ref(1); const search = ref(''); const filterType = ref('')
const dateFrom = ref(''); const dateTo = ref('')
const pagination = ref({ current_page:1, last_page:1, total:0 })

onMounted(fetchMovements)
watch([search, filterType, dateFrom, dateTo, () => authStore.activeBranchId], () => { page.value=1; fetchMovements() })
watch(page, fetchMovements)

async function fetchMovements() {
  loading.value = true
  try {
    const { data } = await api.get('/stock-movements', { params:{ page:page.value, search:search.value, type:filterType.value, date_from:dateFrom.value, date_to:dateTo.value, per_page:20 }})
    movements.value = data.data
    pagination.value = { current_page:data.current_page, last_page:data.last_page, total:data.total }
  } finally { loading.value = false }
}

function formatDate(d) { return d ? format(parseISO(d), 'dd MMM yyyy HH:mm') : '—' }
function typeBadge(t) { return { in:'badge-green', out:'badge-red', adjustment:'badge-blue', return:'badge-yellow', expired:'badge-gray' }[t]||'badge-gray' }
</script>
