<script setup>
import { Link } from '@inertiajs/vue3'

defineProps({
  title: String,
  value: [String, Number],
  subtitle: String,
  icon: Object,
  color: { type: String, default: 'blue' },
  href: String,
})

const colors = {
  blue: 'bg-blue-100 text-blue-600',
  green: 'bg-emerald-100 text-emerald-600',
  orange: 'bg-orange-100 text-orange-600',
  red: 'bg-red-100 text-red-600',
  purple: 'bg-violet-100 text-violet-600',
}
</script>

<template>
  <component :is="href ? Link : 'div'" :href="href"
    class="flex items-center gap-4 rounded-2xl bg-white p-5 shadow-sm transition"
    :class="href ? 'hover:shadow-md' : ''">
    <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl" :class="colors[color]">
      <component :is="icon" :size="26" />
    </div>
    <div class="min-w-0">
      <p class="truncate text-sm text-slate-500">{{ title }}</p>
      <p class="text-3xl font-bold text-blue-950">{{ value }}</p>
      <p class="truncate text-xs text-slate-400">{{ subtitle }}</p>
    </div>
  </component>
</template>