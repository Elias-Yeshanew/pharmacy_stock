<template>
  <div class="flex h-screen bg-gray-50 overflow-hidden relative">

    <!-- Mobile Sidebar Backdrop -->
    <transition name="fade">
      <div 
        v-if="mobileMenuOpen" 
        class="fixed inset-0 bg-black/60 backdrop-blur-xs z-40 lg:hidden transition-opacity"
        @click="mobileMenuOpen = false" 
      />
    </transition>

    <!-- Sidebar (Desktop static, Mobile drawer) -->
    <aside 
      class="fixed inset-y-0 left-0 z-50 w-72 bg-gray-900 flex flex-col transition-transform duration-200 ease-in-out lg:static lg:w-64 lg:translate-x-0"
      :class="mobileMenuOpen ? 'translate-x-0 shadow-2xl' : '-translate-x-full'"
    >
      <!-- Logo & Mobile Close -->
      <div class="flex items-center justify-between px-6 py-5 border-b border-gray-800">
        <div class="flex items-center gap-3">
          <div class="w-8 h-8 bg-primary-500 rounded-lg flex items-center justify-center flex-shrink-0">
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

        <button 
          @click="mobileMenuOpen = false" 
          class="lg:hidden text-gray-400 hover:text-white p-1 rounded-lg hover:bg-gray-800 transition-colors focus:outline-none"
          title="Close Menu"
        >
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>

      <!-- Nav -->
      <nav class="flex-1 px-3 py-4 space-y-0.5 overflow-y-auto">
        <NavItem v-for="item in navItems" :key="item.to" v-bind="item" />
      </nav>

      <!-- User Info & Logout -->
      <div class="px-4 py-4 border-t border-gray-800 bg-gray-900/60">
        <div class="flex items-center gap-3">
          <div class="w-8 h-8 bg-primary-600 rounded-full flex items-center justify-center text-white text-sm font-medium flex-shrink-0">
            {{ authStore.user?.name?.[0]?.toUpperCase() }}
          </div>
          <div class="flex-1 min-w-0">
            <p class="text-gray-200 text-sm font-medium truncate">{{ authStore.user?.name }}</p>
            <p class="text-gray-500 text-xs capitalize truncate">{{ authStore.user?.role }}</p>
          </div>
          <button @click="logout" class="text-gray-400 hover:text-red-400 transition-colors p-1.5 rounded-lg hover:bg-gray-800" title="Logout">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
            </svg>
          </button>
        </div>
      </div>
    </aside>

    <!-- Main Content Wrapper -->
    <div class="flex-1 flex flex-col overflow-hidden w-full">
      <!-- Top bar -->
      <header class="bg-white border-b border-gray-200 px-3.5 sm:px-6 py-3 sm:py-4 flex items-center justify-between flex-shrink-0 gap-2 sm:gap-4">
        <div class="flex items-center gap-2.5 sm:gap-3 min-w-0">
          <!-- Hamburger Menu for Mobile -->
          <button 
            @click="mobileMenuOpen = true" 
            class="lg:hidden text-gray-600 hover:text-gray-900 p-1.5 -ml-1 rounded-lg hover:bg-gray-100 transition-colors focus:outline-none flex-shrink-0" 
            aria-label="Open Navigation"
          >
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
            </svg>
          </button>
          
          <h1 class="text-base sm:text-lg font-semibold text-gray-900 truncate">{{ currentTitle }}</h1>
        </div>

        <div class="flex items-center gap-2 sm:gap-4 flex-shrink-0">
          <!-- Branch Switcher for Global Roles -->
          <div v-if="['admin', 'owner', 'ceo', 'supply_chain_manager', 'sales_manager'].includes(authStore.user?.role)" class="flex items-center gap-1 sm:gap-2">
            <label class="hidden md:flex text-xs text-gray-500 font-medium items-center gap-1 flex-shrink-0">
              <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" /></svg>
              Active Branch:
            </label>
            <select 
              :value="authStore.activeBranchId || 'all'" 
              @change="e => authStore.setActiveBranchId(e.target.value)" 
              class="text-xs bg-gray-50 border border-gray-200 rounded-lg px-2 sm:px-2.5 py-1.5 font-medium text-gray-700 focus:outline-none focus:ring-1 focus:ring-primary-500 cursor-pointer max-w-[130px] sm:max-w-[180px] truncate"
            >
              <option value="all">All Branches</option>
              <option v-for="b in authStore.branches" :key="b.id" :value="b.id">{{ b.name }}</option>
            </select>
          </div>
          
          <!-- Branch Badge for Dispenser/Pharmacist/Staff -->
          <div v-else-if="['dispenser', 'pharmacist', 'cashier'].includes(authStore.user?.role) && authStore.user?.branch" class="flex items-center gap-1.5 text-xs bg-indigo-50 text-indigo-700 font-medium px-2 sm:px-2.5 py-1 rounded-full max-w-[140px] sm:max-w-none truncate">
            <svg class="w-3.5 h-3.5 text-indigo-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" /></svg>
            <span class="truncate">{{ authStore.user?.branch?.name }}</span>
          </div>

          <span class="hidden md:inline text-xs text-gray-400 font-mono flex-shrink-0">{{ currentDate }}</span>
        </div>
      </header>

      <!-- Page content -->
      <main class="flex-1 overflow-y-auto p-3.5 sm:p-5 lg:p-6 pb-20 lg:pb-6">
        <router-view v-slot="{ Component }">
          <transition name="page" mode="out-in">
            <component :is="Component" />
          </transition>
        </router-view>
      </main>

      <!-- Mobile Bottom Navigation Bar -->
      <nav class="lg:hidden fixed bottom-0 inset-x-0 bg-white/95 backdrop-blur border-t border-gray-200 z-30 flex items-center justify-around py-1.5 px-2 safe-bottom shadow-lg">
        <router-link 
          to="/dashboard" 
          class="flex flex-col items-center justify-center py-1 px-2.5 rounded-lg text-xs font-medium transition-colors"
          :class="route.path === '/dashboard' ? 'text-primary-600 font-semibold' : 'text-gray-500 hover:text-gray-900'"
        >
          <svg class="w-5 h-5 mb-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
          </svg>
          <span>Home</span>
        </router-link>

        <router-link 
          to="/medicines" 
          class="flex flex-col items-center justify-center py-1 px-2.5 rounded-lg text-xs font-medium transition-colors"
          :class="route.path.startsWith('/medicines') ? 'text-primary-600 font-semibold' : 'text-gray-500 hover:text-gray-900'"
        >
          <svg class="w-5 h-5 mb-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z" />
          </svg>
          <span>Medicines</span>
        </router-link>

        <router-link 
          v-if="['admin', 'owner', 'ceo', 'sales_manager', 'dispenser', 'pharmacist', 'cashier'].includes(authStore.user?.role)"
          to="/sales" 
          class="flex flex-col items-center justify-center py-1 px-2.5 rounded-lg text-xs font-medium transition-colors"
          :class="route.path.startsWith('/sales') ? 'text-primary-600 font-semibold' : 'text-gray-500 hover:text-gray-900'"
        >
          <svg class="w-5 h-5 mb-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
          </svg>
          <span>Sales</span>
        </router-link>

        <router-link 
          v-else-if="['admin', 'owner', 'ceo', 'supply_chain_manager'].includes(authStore.user?.role)"
          to="/purchase-orders" 
          class="flex flex-col items-center justify-center py-1 px-2.5 rounded-lg text-xs font-medium transition-colors"
          :class="route.path.startsWith('/purchase-orders') ? 'text-primary-600 font-semibold' : 'text-gray-500 hover:text-gray-900'"
        >
          <svg class="w-5 h-5 mb-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
          </svg>
          <span>Orders</span>
        </router-link>

        <router-link 
          to="/stock" 
          v-if="['admin', 'owner', 'ceo', 'supply_chain_manager', 'dispenser', 'pharmacist'].includes(authStore.user?.role)"
          class="flex flex-col items-center justify-center py-1 px-2.5 rounded-lg text-xs font-medium transition-colors"
          :class="route.path === '/stock' ? 'text-primary-600 font-semibold' : 'text-gray-500 hover:text-gray-900'"
        >
          <svg class="w-5 h-5 mb-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4" />
          </svg>
          <span>Stock</span>
        </router-link>

        <!-- More / Open Drawer button -->
        <button 
          @click="mobileMenuOpen = true" 
          class="flex flex-col items-center justify-center py-1 px-2.5 rounded-lg text-xs font-medium text-gray-500 hover:text-primary-600 transition-colors focus:outline-none"
        >
          <svg class="w-5 h-5 mb-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
          </svg>
          <span>More</span>
        </button>
      </nav>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import NavItem from '@/components/NavItem.vue'
import { format } from 'date-fns'

const route          = useRoute()
const router         = useRouter()
const authStore      = useAuthStore()
const mobileMenuOpen = ref(false)

// Close mobile sidebar whenever route changes
watch(() => route.path, () => {
  mobileMenuOpen.value = false
})

const currentDate = format(new Date(), 'EEE, dd MMM yyyy')

const navItems = computed(() => {
  const role = authStore.user?.role
  const items = [
    { to: '/dashboard',       label: 'Dashboard',       icon: 'dashboard' },
    { to: '/medicines',       label: 'Medicines',        icon: 'pill' },
  ]

  if (['admin', 'owner', 'ceo', 'supply_chain_manager', 'dispenser', 'pharmacist'].includes(role)) {
    items.push({ to: '/stock',           label: 'Stock Movements',  icon: 'stock' })
  }

  if (['admin', 'owner', 'ceo', 'sales_manager', 'dispenser', 'pharmacist', 'cashier'].includes(role)) {
    items.push({ to: '/sales',           label: 'Sales',            icon: 'sales' })
  }

  if (['admin', 'owner', 'ceo', 'supply_chain_manager'].includes(role)) {
    items.push({ to: '/purchase-orders', label: 'Purchase Orders',  icon: 'orders' })
    items.push({ to: '/suppliers',       label: 'Suppliers',        icon: 'suppliers' })
    items.push({ to: '/categories',      label: 'Categories',       icon: 'categories' })
  }

  if (['admin', 'owner', 'ceo'].includes(role)) {
    items.push({ to: '/branches', label: 'Branches', icon: 'branches' })
  }

  if (role === 'admin') {
    items.push({ to: '/users', label: 'Users', icon: 'users' })
  }

  if (['admin', 'owner', 'ceo', 'sales_manager'].includes(role)) {
    items.push({ to: '/reports',         label: 'Reports',          icon: 'reports' })
  }

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
  '/users':           'Users',
  '/reports':         'Reports',
}

const currentTitle = computed(() =>
  titles[route.path] || route.meta?.title || 'PharmStock'
)

async function logout() {
  await authStore.logout()
  router.push('/login')
}
</script>
