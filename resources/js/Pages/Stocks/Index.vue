<script setup>
import { ref, watch } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import { Search } from 'lucide-vue-next'
import AppLayout from '@/Layouts/AppLayout.vue'
import StatusBadge from '@/Components/StatusBadge.vue'
import Pagination from '@/Components/Pagination.vue'

const props = defineProps({ articles: Object, entrepots: Array, q: String })

const search = ref(props.q ?? '')
let timer
watch(search, (v) => {
  clearTimeout(timer)
  timer = setTimeout(() => router.get(route('stocks.index'), { q: v }, { preserveState: true, replace: true }), 300)
})

const statuts = {
  ok: ['Normal', 'success'],
  faible: ['Faible', 'warning'],
  rupture: ['Rupture', 'danger'],
}
</script>

<template>
  <Head title="Stocks" />
  <AppLayout>
    <h1 class="text-3xl font-bold text-blue-950">Stocks</h1>
    <p class="mb-6 text-slate-500">Quantités disponibles par dépôt</p>

    <div class="relative mb-4 w-72">
      <Search :size="16" class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" />
      <input v-model="search" placeholder="Nom ou référence..."
        class="w-full rounded-xl border-0 bg-white py-2.5 pl-9 pr-4 text-sm shadow-sm focus:ring-2 focus:ring-blue-400" />
    </div>

    <div class="overflow-x-auto rounded-2xl bg-white p-4 shadow-sm">
      <table class="w-full text-left text-sm">
        <thead class="text-slate-500">
          <tr>
            <th class="px-3 py-2 font-medium">Article</th>
            <th v-for="e in entrepots" :key="e.id" class="px-3 py-2 font-medium">{{ e.nom }}</th>
            <th class="px-3 py-2 font-medium">Total</th>
            <th class="px-3 py-2 font-medium">Seuil</th>
            <th class="px-3 py-2 font-medium">Statut</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="a in articles.data" :key="a.id" class="border-t border-slate-100">
            <td class="px-3 py-3">
              <p class="font-medium">{{ a.nom }}</p>
              <p class="font-mono text-xs text-slate-400">{{ a.reference }}</p>
            </td>
            <td v-for="e in entrepots" :key="e.id" class="px-3 py-3">{{ a.par_depot[e.id] ?? 0 }} {{ a.unite }}</td>
            <td class="px-3 py-3 font-semibold">{{ a.total }} {{ a.unite }}</td>
            <td class="px-3 py-3">{{ a.seuil }}</td>
            <td class="px-3 py-3"><StatusBadge :label="statuts[a.statut][0]" :type="statuts[a.statut][1]" /></td>
          </tr>
          <tr v-if="!articles.data.length">
            <td :colspan="entrepots.length + 4" class="px-3 py-8 text-center text-slate-400">Aucun article.</td>
          </tr>
        </tbody>
      </table>
      <Pagination :page="articles" />
    </div>
  </AppLayout>
</template>