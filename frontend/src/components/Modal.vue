<template>
  <teleport to="body">
    <transition name="modal">
      <div v-if="modelValue" class="fixed inset-0 z-50 flex items-end sm:items-center justify-center p-0 sm:p-4">
        <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" @click="$emit('update:modelValue', false)" />
        <div class="relative bg-white rounded-t-2xl sm:rounded-2xl shadow-2xl w-full overflow-hidden flex flex-col max-h-[90vh] sm:max-h-[85vh]" :class="maxWidthClass">
          <div class="flex items-center justify-between px-5 sm:px-6 py-3.5 sm:py-4 border-b border-gray-100 flex-shrink-0">
            <h3 class="text-base font-semibold text-gray-900 truncate pr-2">{{ title }}</h3>
            <button @click="$emit('update:modelValue', false)" class="text-gray-400 hover:text-gray-600 p-1 rounded-lg hover:bg-gray-100 transition-colors">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>
          <div class="px-5 sm:px-6 py-4 sm:py-5 overflow-y-auto flex-1 overscroll-contain">
            <slot />
          </div>
          <div v-if="$slots.footer" class="px-5 sm:px-6 py-3 sm:py-4 border-t border-gray-100 bg-gray-50 flex-shrink-0">
            <slot name="footer" />
          </div>
        </div>
      </div>
    </transition>
  </teleport>
</template>
<script setup>
import { computed } from 'vue'
const props = defineProps({ modelValue: Boolean, title: String, size: { type: String, default: 'md' } })
defineEmits(['update:modelValue'])
const maxWidthClass = computed(() => ({ sm: 'max-w-sm', md: 'max-w-lg', lg: 'max-w-2xl', xl: 'max-w-4xl' }[props.size] || 'max-w-lg'))
</script>
<style scoped>
.modal-enter-active, .modal-leave-active { transition: all 0.2s ease; }
.modal-enter-from, .modal-leave-to { opacity: 0; transform: scale(0.96); }
</style>
