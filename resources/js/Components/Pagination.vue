<script setup>
import { Link } from '@inertiajs/vue3'
defineProps({ page: Object })
</script>

<template>
  <div class="flex items-center justify-between pt-4 text-sm text-slate-500">
    <span>{{ page.total }} résultat(s) · page {{ page.current_page }} / {{ page.last_page }}</span>
    <div class="flex gap-2">
      <Link v-if="page.prev_page_url" :href="page.prev_page_url" preserve-scroll
        class="rounded-lg bg-white px-3 py-1.5 shadow-sm hover:bg-blue-50">Précédent</Link>
      <Link v-if="page.next_page_url" :href="page.next_page_url" preserve-scroll
        class="rounded-lg bg-white px-3 py-1.5 shadow-sm hover:bg-blue-50">Suivant</Link>
    </div>
  </div>
</template>