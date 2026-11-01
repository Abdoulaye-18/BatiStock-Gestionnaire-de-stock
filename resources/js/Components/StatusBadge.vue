<script setup>
defineProps({
  label: String,
  type: { type: String, default: 'gray' },
})

const styles = {
  success: 'bg-emerald-100 text-emerald-700',
  danger: 'bg-red-100 text-red-700',
  warning: 'bg-orange-100 text-orange-700',
  info: 'bg-blue-100 text-blue-700',
  purple: 'bg-violet-100 text-violet-700',
  gray: 'bg-slate-100 text-slate-600',
}
</script>

<template>
  <span class="inline-block rounded-full px-3 py-1 text-xs font-medium" :class="styles[type]">
    {{ label }}
  </span>
</template>