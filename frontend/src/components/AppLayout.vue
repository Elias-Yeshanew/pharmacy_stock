<template>
  <div class="flex h-screen bg-gray-50 overflow-hidden">

    <!-- Sidebar -->
    <aside class="w-64 flex-shrink-0 bg-gray-900 flex flex-col">
      <!-- Logo -->
      <div class="flex items-center gap-3 px-6 py-5 border-b border-gray-800">
        <div class="w-8 h-8 bg-primary-500 rounded-lg flex items-center justify-center">
          <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
          </svg>
        </div>
        <div>
          <p class="text-white font-semibold text-sm leading-none">PharmStock</p>
          <p class="text-gray-400 text-xs mt-0.5">Management System</p>
        </div>
      </div>

      <!-- Nav -->
      <nav class="flex-1 px-3 py-4 space-y-0.5 overflow-y-auto">
        <NavItem v-for="item in navItems" :key="item.to" v-bind="item" />
      </nav>

      <!-- User -->
      <div class="px-4 py-4 border-t border-gray-800">
        <div class="flex items-center gap-3">
          <div class="w-8 h-8 bg-primary-600 rounded-full flex items-center justify-center text-white text-sm font-medium">
            {{ authStore.user?.name?.[0]?.toUpperCase() }}
          </div>
          <div class="flex-1 min-w-0">
            <p class="text-gray-200 text-sm font-medium truncate">{{ authStore.user?.name }}</p>
            <p class="text-gray-500 text-xs capitalize">{{ authStore.user?.role }}</p>
          </div>
          <button @click="logout" class="text-gray-500 hover:text-gray-300 transition-colors" title="Logout">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
            </svg>
          </button>
        </div>
      </div>
    </aside>

    <!-- Main -->
    <div class="flex-1 flex flex-col overflow-hidden">
      <!-- Top bar -->
      <!-- Top bar -->
      <header class="bg-white border-b border-gray-200 px-6 py-4 flex items-center justify-between flex-shrink-0">
        <h1 class="text-lg font-semibold text-gray-900">{{ currentTitle }}</h1>
        <div class="flex items-center gap-4">
          <!-- Branch Switcher for Admin -->
          <div v-if="authStore.user?.role === 'admin'" class="flex items-center gap-2">
            <label class="text-xs text-gray-500 font-medium flex items-center gap-1">
              <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" /></svg>
              Active Branch:
            </label>
            <select 
              :value="authStore.activeBranchId || 'all'" 
              @change="e => authStore.setActiveBranchId(e.target.value)" 
              class="text-xs bg-gray-50 border border-gray-200 rounded-lg px-2.5 py-1.5 font-medium text-gray-700 focus:outline-none focus:ring-1 focus:ring-primary-500 cursor-pointer"
            >
              <option value="all">All Branches Summary</option>
              <option v-for="b in authStore.branches" :key="b.id" :value="b.id">{{ b.name }}</option>
            </select>
          </div>
          
          <!-- Branch Badge for Pharmacist/Staff -->
          <div v-else-if="authStore.user?.branch" class="flex items-center gap-1.5 text-xs bg-indigo-50 text-indigo-700 font-medium px-2.5 py-1 rounded-full">
            <svg class="w-3.5 h-3.5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" /></svg>
            {{ authStore.user?.branch?.name }}
          </div>

          <span class="text-xs text-gray-400 font-mono">{{ currentDate }}</span>
        </div>
      </header>

      <!-- Page content -->
      <main class="flex-1 overflow-y-auto p-6">
        <router-view v-slot="{ Component }">
          <transition name="page" mode="out-in">
            <component :is="Component" />
          </transition>
        </router-view>
      </main>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import NavItem from '@/components/NavItem.vue'
import { format } from 'date-fns'

const route      = useRoute()
const router     = useRouter()
const authStore  = useAuthStore()

const currentDate = format(new Date(), 'EEE, dd MMM yyyy')

const navItems = computed(() => {
  const items = [
    { to: '/dashboard',       label: 'Dashboard',       icon: 'dashboard' },
    { to: '/medicines',       label: 'Medicines',        icon: 'pill' },
    { to: '/stock',           label: 'Stock Movements',  icon: 'stock' },
    { to: '/sales',           label: 'Sales',            icon: 'sales' },
    { to: '/purchase-orders', label: 'Purchase Orders',  icon: 'orders' },
    { to: '/suppliers',       label: 'Suppliers',        icon: 'suppliers' },
    { to: '/categories',      label: 'Categories',       icon: 'categories' },
  ]
  if (authStore.user?.role === 'admin') {
    items.push({ to: '/branches', label: 'Branches', icon: 'branches' })
  }
  items.push({ to: '/reports',         label: 'Reports',          icon: 'reports' })
  return items
})

const titles = {
  '/dashboard':       'Dashboard',
  '/medicines':       'Medicines',
  '/medicines/new':   'Add Medicine',
  '/stock':           'Stock Movements',
  '/sales':           'Sales',
  '/sales/new':       'New Sale',
  '/purchase-orders': 'Purchase Orders',
  '/purchase-orders/new': 'New Purchase Order',
  '/suppliers':       'Suppliers',
  '/categories':      'Categories',
  '/branches':        'Branches',
  '/reports':         'Reports',
}

const currentTitle = computed(() =>
  titles[route.path] || route.meta.title || 'PharmStock'
)

async function logout() {
  await authStore.logout()
  router.push('/login')
}
</script>
