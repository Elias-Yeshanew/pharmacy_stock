import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import api from '@/composables/useApi'

export const useAuthStore = defineStore('auth', () => {
  const user  = ref(JSON.parse(localStorage.getItem('user') || 'null'))
  const token = ref(localStorage.getItem('token') || null)
  const activeBranchId = ref(localStorage.getItem('activeBranchId') || null)
  const branches = ref([])

  const isLoggedIn = computed(() => !!token.value)

  async function login(email, password) {
    const { data } = await api.post('/auth/login', { email, password })
    token.value = data.token
    user.value  = data.user
    localStorage.setItem('token', data.token)
    localStorage.setItem('user', JSON.stringify(data.user))
    
    if (data.user.role === 'admin') {
      await fetchBranches()
      const defaultBranchId = data.user.branch_id || (branches.value[0]?.id || null)
      setActiveBranchId(defaultBranchId)
    } else {
      setActiveBranchId(data.user.branch_id || null)
    }
  }

  async function fetchBranches() {
    try {
      const { data } = await api.get('/branches')
      branches.value = data
    } catch (e) {
      console.error('Failed to load branches', e)
    }
  }

  function setActiveBranchId(id) {
    if (id) {
      activeBranchId.value = String(id)
      localStorage.setItem('activeBranchId', String(id))
    } else {
      activeBranchId.value = null
      localStorage.removeItem('activeBranchId')
    }
  }

  async function logout() {
    try { await api.post('/auth/logout') } catch {}
    token.value = null
    user.value  = null
    activeBranchId.value = null
    branches.value = []
    localStorage.removeItem('token')
    localStorage.removeItem('user')
    localStorage.removeItem('activeBranchId')
  }

  // Fetch branches on boot if logged in as admin
  if (isLoggedIn.value && user.value?.role === 'admin') {
    fetchBranches()
  }

  return { user, token, activeBranchId, branches, isLoggedIn, login, logout, fetchBranches, setActiveBranchId }
})
