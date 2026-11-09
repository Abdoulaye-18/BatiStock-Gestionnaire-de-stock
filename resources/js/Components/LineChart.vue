<script setup>
import { computed } from 'vue'

const props = defineProps({ points: Array }) // [{ label, value }]

const W = 600
const H = 220
const L = 52
const R = 16
const T = 12
const B = 28

const max = computed(() => {
  const m = Math.max(...props.points.map((p) => p.value), 4)
  const mag = 10 ** Math.floor(Math.log10(m))
  return Math.ceil(m / mag) * mag
})

const x = (i) => L + (i * (W - L - R)) / Math.max(props.points.length - 1, 1)
const y = (v) => T + (1 - v / max.value) * (H - T - B)

const ligne = computed(() => props.points.map((p, i) => `${x(i)},${y(p.value)}`).join(' '))
const aire = computed(() => `${x(0)},${y(0)} ${ligne.value} ${x(props.points.length - 1)},${y(0)}`)
const ticks = computed(() => [0, 1, 2, 3, 4].map((i) => Math.round((max.value * i) / 4)))
const fmt = (n) => new Intl.NumberFormat('fr-FR').format(n)
</script>

<template>
  <svg :viewBox="`0 0 ${W} ${H}`" class="w-full" role="img" aria-label="Évolution du stock sur 7 jours">
    <defs>
      <linearGradient id="aire-stock" x1="0" y1="0" x2="0" y2="1">
        <stop offset="0%" stop-color="#3b82f6" stop-opacity="0.28" />
        <stop offset="100%" stop-color="#3b82f6" stop-opacity="0" />
      </linearGradient>
    </defs>

    <g v-for="t in ticks" :key="t">
      <line :x1="L" :x2="W - R" :y1="y(t)" :y2="y(t)" stroke="#e2e8f0" stroke-width="1" />
      <text :x="L - 8" :y="y(t) + 4" text-anchor="end" font-size="11" fill="#64748b">{{ fmt(t) }}</text>
    </g>

    <polygon :points="aire" fill="url(#aire-stock)" />
    <polyline :points="ligne" fill="none" stroke="#2563eb" stroke-width="2.5" stroke-linejoin="round" stroke-linecap="round" />

    <g v-for="(p, i) in points" :key="i">
      <circle :cx="x(i)" :cy="y(p.value)" r="4" fill="#fff" stroke="#2563eb" stroke-width="2">
        <title>{{ p.label }} : {{ fmt(p.value) }}</title>
      </circle>
      <text :x="x(i)" :y="H - 8" text-anchor="middle" font-size="11" fill="#64748b">{{ p.label }}</text>
    </g>
  </svg>
</template>