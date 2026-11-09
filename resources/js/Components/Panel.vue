<script setup>
import { Link } from '@inertiajs/vue3'
import { ArrowRight } from 'lucide-vue-next'

defineProps({
  title: String,
  icon: Object,
  href: String,
  tone: { type: String, default: 'blue' },
})

const tones = { blue: 'bg-blue-100 text-blue-600', red: 'bg-red-100 text-red-600' }
</script>

<template>
  <section class="rounded-2xl bg-white p-5 shadow-sm">
    <header class="mb-4 flex items-center justify-between">
      <div class="flex items-center gap-3">
        <div v-if="icon" class="flex h-9 w-9 items-center justify-center rounded-xl" :class="tones[tone]">
          <component :is="icon" :size="18" />
        </div>
        <h2 class="font-semibold text-blue-950">{{ title }}</h2>
      </div>
      <Link v-if="href" :href="href" class="flex items-center gap-1 text-xs font-medium text-blue-600 hover:underline">
        Voir tout <ArrowRight :size="12" />
      </Link>
    </header>
    <slot />
  </section>
</template>