<template>
  <div class="min-h-screen bg-gradient-to-br from-gray-900 via-gray-800 to-primary-900 flex items-center justify-center p-4">
    <div class="w-full max-w-md">
      <div class="text-center mb-8">
        <div class="inline-flex items-center justify-center w-16 h-16 bg-primary-500 rounded-2xl shadow-lg mb-4">
          <svg class="w-9 h-9 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
          </svg>
        </div>
        <h1 class="text-2xl font-bold text-white">PharmStock</h1>
        <p class="text-gray-400 text-sm mt-1">Pharmacy Stock Management System</p>
      </div>
      <div class="bg-white rounded-2xl shadow-2xl p-8">
        <h2 class="text-xl font-semibold text-gray-900 mb-6">Sign in to your account</h2>
        <form @submit.prevent="handleLogin" class="space-y-5">
          <div>
            <label class="label">Email address</label>
            <input v-model="form.email" type="email" class="input" placeholder="admin@pharmacy.com" required />
          </div>
          <div>
            <label class="label">Password</label>
            <input v-model="form.password" type="password" class="input" placeholder="••••••••" required />
          </div>
          <div v-if="error" class="bg-red-50 border border-red-200 rounded-lg p-3 text-red-700 text-sm">{{ error }}</div>
          <button type="submit" class="btn-primary w-full justify-center py-2.5" :disabled="loading">
            {{ loading ? 'Signing in…' : 'Sign in' }}
          </button>
        </form>
        <p class="text-center text-xs text-gray-400 mt-6">Demo: admin@pharmacy.com / password</p>
      </div>
    </div>
  </div>
</template>
<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
const router = useRouter()
const authStore = useAuthStore()
const form = ref({ email: '', password: '' })
const loading = ref(false)
const error = ref('')
async function handleLogin() {
  loading.value = true; error.value = ''
  try { await authStore.login(form.value.email, form.value.password); router.push('/dashboard') }
  catch (e) { error.value = e.response?.data?.message || 'Login failed. Please try again.' }
  finally { loading.value = false }
}
</script>
