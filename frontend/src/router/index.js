import { createRouter, createWebHistory } from 'vue-router'

const routes = [
  { path: '/login', component: () => import('@/views/LoginView.vue'), meta: { public: true } },

  {
    path: '/',
    component: () => import('@/components/AppLayout.vue'),
    meta: { requiresAuth: true },
    children: [
      { path: '',        redirect: '/dashboard' },
      { path: 'dashboard',       name: 'Dashboard',       component: () => import('@/views/DashboardView.vue') },
      { path: 'medicines',       name: 'Medicines',       component: () => import('@/views/MedicinesView.vue') },
      { path: 'medicines/new',   name: 'NewMedicine',     component: () => import('@/views/MedicineFormView.vue') },
      { path: 'medicines/:id/edit', name: 'EditMedicine', component: () => import('@/views/MedicineFormView.vue') },
      { path: 'stock',           name: 'Stock',           component: () => import('@/views/StockView.vue') },
      { path: 'sales',           name: 'Sales',           component: () => import('@/views/SalesView.vue') },
      { path: 'sales/new',       name: 'NewSale',         component: () => import('@/views/NewSaleView.vue') },
      { path: 'purchase-orders', name: 'PurchaseOrders',  component: () => import('@/views/PurchaseOrdersView.vue') },
      { path: 'purchase-orders/new', name: 'NewPO',       component: () => import('@/views/NewPurchaseOrderView.vue') },
      { path: 'suppliers',       name: 'Suppliers',       component: () => import('@/views/SuppliersView.vue') },
      { path: 'categories',      name: 'Categories',      component: () => import('@/views/CategoriesView.vue') },
      { path: 'branches',        name: 'Branches',        component: () => import('@/views/BranchesView.vue') },
      { path: 'users',           name: 'Users',           component: () => import('@/views/UsersView.vue') },
      { path: 'reports',         name: 'Reports',         component: () => import('@/views/ReportsView.vue') },
    ],
  },

  { path: '/:pathMatch(.*)*', redirect: '/' },
]

const router = createRouter({
  history: createWebHistory(),
  routes,
})

router.beforeEach((to, _from, next) => {
  const token = localStorage.getItem('token')
  const user = JSON.parse(localStorage.getItem('user') || 'null')

  if (to.meta.requiresAuth && !token) return next('/login')
  if (to.meta.public && token) return next('/dashboard')

  // Enforce client-side RBAC
  if (to.meta.requiresAuth && user) {
    const role = user.role
    const name = to.name

    if (name === 'Users' && role !== 'admin') {
      return next('/dashboard')
    }
    if (name === 'Branches' && !['admin', 'owner', 'ceo'].includes(role)) {
      return next('/dashboard')
    }
    if (['PurchaseOrders', 'NewPO', 'Suppliers', 'Categories'].includes(name) && !['admin', 'owner', 'ceo', 'supply_chain_manager'].includes(role)) {
      return next('/dashboard')
    }
    if (['Sales', 'NewSale'].includes(name) && !['admin', 'owner', 'ceo', 'sales_manager', 'dispenser', 'pharmacist', 'cashier'].includes(role)) {
      return next('/dashboard')
    }
    if (name === 'Reports' && !['admin', 'owner', 'ceo', 'sales_manager'].includes(role)) {
      return next('/dashboard')
    }
  }

  next()
})

export default router
