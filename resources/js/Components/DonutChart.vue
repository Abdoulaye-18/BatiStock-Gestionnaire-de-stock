<script setup>
import { computed } from 'vue'

const props = defineProps({
  segments: Array, // [{ label, value, color }]
  total: Number,
})

const R = 42
const C = 2 * Math.PI * R

const arcs = computed(() => {
  let cumul = 0

  return props.segments.map((s) => {
    const len = props.total ? (s.value / props.total) * C : 0
    const arc = { ...s, len, offset: -cumul }
    cumul += len

    return arc
  })
})

const fmt = (n) => new Intl.NumberFormat('fr-FR').format(n)
</script>

<template>
  <svg viewBox="0 0 120 120" class="w-full max-w-[190px]" role="img" aria-label="Répartition du stock par catégorie">
    <circle cx="60" cy="60" :r="R" fill="none" stroke="#f1f5f9" stroke-width="16" />
    <circle v-for="a in arcs" :key="a.label" cx="60" cy="60" :r="R" fill="none"
      :stroke="a.color" stroke-width="16"
      :stroke-dasharray="`${a.len} ${C - a.len}`" :stroke-dashoffset="a.offset"
      transform="rotate(-90 60 60)">
      <title>{{ a.label }} : {{ fmt(a.value) }}</title>
    </circle>
    <text x="60" y="59" text-anchor="middle" font-size="14" font-weight="700" fill="#1e3a8a">{{ fmt(total) }}</text>
    <text x="60" y="73" text-anchor="middle" font-size="8" fill="#64748b">unités</text>
  </svg>
</template>