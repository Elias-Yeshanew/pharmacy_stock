<template>
  <router-link
    :to="to"
    class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all duration-150"
    :class="isActive
      ? 'bg-primary-600 text-white'
      : 'text-gray-400 hover:bg-gray-800 hover:text-white'"
  >
    <component :is="iconComponent" class="w-4 h-4 flex-shrink-0" />
    {{ label }}
  </router-link>
</template>

<script setup>
import { computed } from 'vue'
import { useRoute } from 'vue-router'
import {
  HomeIcon, BeakerIcon, ArrowsRightLeftIcon, ShoppingCartIcon,
  ClipboardDocumentListIcon, TruckIcon, TagIcon, ChartBarIcon,
  BuildingStorefrontIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({ to: String, label: String, icon: String })
const route = useRoute()
const isActive = computed(() => route.path === props.to || (props.to !== '/' && route.path.startsWith(props.to + '/')))

const iconMap = {
  dashboard:  HomeIcon,
  pill:       BeakerIcon,
  stock:      ArrowsRightLeftIcon,
  sales:      ShoppingCartIcon,
  orders:     ClipboardDocumentListIcon,
  suppliers:  TruckIcon,
  categories: TagIcon,
  reports:    ChartBarIcon,
  branches:   BuildingStorefrontIcon,
}

const iconComponent = computed(() => iconMap[props.icon] || HomeIcon)
</script>
