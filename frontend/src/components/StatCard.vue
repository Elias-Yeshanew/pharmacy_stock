<template>
  <component :is="to ? 'router-link' : 'div'" :to="to"
    class="card p-5 transition-all hover:shadow-md"
    :class="to ? 'cursor-pointer hover:-translate-y-0.5' : ''">
    <div class="flex items-center justify-between">
      <div>
        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ label }}</p>
        <p class="text-3xl font-bold mt-1" :class="valueColor">{{ value ?? '—' }}</p>
      </div>
      <div class="w-10 h-10 rounded-xl flex items-center justify-center" :class="bgColor">
        <component :is="iconComponent" class="w-5 h-5" :class="iconColor" />
      </div>
    </div>
  </component>
</template>
<script setup>
import { computed } from 'vue'
import { BeakerIcon, ExclamationTriangleIcon, XCircleIcon, ClockIcon } from '@heroicons/vue/24/outline'
const props = defineProps({ label: String, value: [Number, String], icon: String, color: String, to: String })
const colorMap = {
  blue:   { bg: 'bg-blue-100',   icon: 'text-blue-600',   value: 'text-blue-700' },
  yellow: { bg: 'bg-yellow-100', icon: 'text-yellow-600', value: 'text-yellow-700' },
  red:    { bg: 'bg-red-100',    icon: 'text-red-600',    value: 'text-red-700' },
  orange: { bg: 'bg-orange-100', icon: 'text-orange-600', value: 'text-orange-700' },
  green:  { bg: 'bg-green-100',  icon: 'text-green-600',  value: 'text-green-700' },
}
const colors = computed(() => colorMap[props.color] || colorMap.blue)
const bgColor = computed(() => colors.value.bg)
const iconColor = computed(() => colors.value.icon)
const valueColor = computed(() => colors.value.value)
const iconMap = { pill: BeakerIcon, warning: ExclamationTriangleIcon, error: XCircleIcon, clock: ClockIcon }
const iconComponent = computed(() => iconMap[props.icon] || BeakerIcon)
</script>
