<script setup>
import { ref, reactive, watch } from 'vue'
import { Head, useForm, router } from '@inertiajs/vue3'
import { Plus, Pencil, Power, Search } from 'lucide-vue-next'
import AppLayout from '@/Layouts/AppLayout.vue'
import StatusBadge from '@/Components/StatusBadge.vue'
import Pagination from '@/Components/Pagination.vue'
import Modal from '@/Components/Modal.vue'
import TextInput from '@/Components/TextInput.vue'
import InputLabel from '@/Components/InputLabel.vue'
import InputError from '@/Components/InputError.vue'
import PrimaryButton from '@/Components/PrimaryButton.vue'
import SecondaryButton from '@/Components/SecondaryButton.vue'

const props = defineProps({
  articles: Object,
  filtres: Object,
  categories: Array,
  unites: Array,
  peutModifier: Boolean,
})

// ----- Filtres (recherche avec petit délai) -----
const f = reactive({
  q: props.filtres?.q ?? '',
  categorie_id: props.filtres?.categorie_id ?? '',
  statut: props.filtres?.statut ?? '',
})
let timer
watch(f, () => {
  clearTimeout(timer)
  timer = setTimeout(() => {
    router.get(route('articles.index'), { ...f }, { preserveState: true, replace: true })
  }, 300)
})

const statuts = {
  ok: ['Normal', 'success'],
  faible: ['Faible', 'warning'],
  rupture: ['Rupture', 'danger'],
}

// ----- Formulaire -----
const show = ref(false)
const editing = ref(null)
const form = useForm({
  reference: '', nom: '', categorie_id: '', unite_id: '', prix_vente: '', seuil_alerte: 0,
})

function openCreate() {
  editing.value = null
  form.reset()
  form.clearErrors()
  show.value = true
}

function openEdit(a) {
  editing.value = a
  form.clearErrors()
  form.reference = a.reference
  form.nom = a.nom
  form.categorie_id = a.categorie_id
  form.unite_id = a.unite_id
  form.prix_vente = a.prix_vente ?? ''
  form.seuil_alerte = a.seuil_alerte
  show.value = true
}

function submit() {
  const opts = { preserveScroll: true, onSuccess: () => { show.value = false; form.reset() } }
  editing.value
    ? form.put(route('articles.update', editing.value.id), opts)
    : form.post(route('articles.store'), opts)
}

function basculer(a) {
  router.patch(route('articles.toggle', a.id), {}, { preserveScroll: true })
}
</script>

<template>
  <Head title="Articles" />
  <AppLayout>
    <div class="mb-6 flex items-end justify-between">
      <div>
        <h1 class="text-3xl font-bold text-blue-950">Articles</h1>
        <p class="text-slate-500">Catalogue des matériaux</p>
      </div>
      <button v-if="peutModifier" @click="openCreate"
        class="flex items-center gap-2 rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-medium text-white shadow-sm hover:bg-blue-700">
        <Plus :size="16" /> Nouvel article
      </button>
    </div>

    <!-- Filtres -->
    <div class="mb-4 flex flex-wrap gap-3">
      <div class="relative">
        <Search :size="16" class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" />
        <input v-model="f.q" placeholder="Nom ou référence..."
          class="rounded-xl border-0 bg-white py-2.5 pl-9 pr-4 text-sm shadow-sm focus:ring-2 focus:ring-blue-400" />
      </div>
      <select v-model="f.categorie_id" class="rounded-xl border-0 bg-white py-2.5 text-sm shadow-sm">
        <option value="">Toutes les catégories</option>
        <option v-for="c in categories" :key="c.id" :value="c.id">{{ c.nom }}</option>
      </select>
      <select v-model="f.statut" class="rounded-xl border-0 bg-white py-2.5 text-sm shadow-sm">
        <option value="">Tous les stocks</option>
        <option value="faible">Sous le seuil</option>
      </select>
    </div>

    <!-- Tableau -->
    <div class="overflow-x-auto rounded-2xl bg-white p-4 shadow-sm">
      <table class="w-full text-left text-sm">
        <thead class="text-slate-500">
          <tr>
            <th class="px-3 py-2 font-medium">Référence</th>
            <th class="px-3 py-2 font-medium">Article</th>
            <th class="px-3 py-2 font-medium">Catégorie</th>
            <th class="px-3 py-2 font-medium">Stock total</th>
            <th class="px-3 py-2 font-medium">Seuil</th>
            <th class="px-3 py-2 font-medium">Statut</th>
            <th v-if="peutModifier" class="px-3 py-2 text-right font-medium">Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="a in articles.data" :key="a.id" class="border-t border-slate-100" :class="{ 'opacity-50': !a.actif }">
            <td class="px-3 py-3 font-mono text-xs">{{ a.reference }}</td>
            <td class="px-3 py-3 font-medium">{{ a.nom }}</td>
            <td class="px-3 py-3">{{ a.categorie }}</td>
            <td class="px-3 py-3">{{ a.stock_total }} {{ a.unite }}</td>
            <td class="px-3 py-3">{{ a.seuil_alerte }}</td>
            <td class="px-3 py-3">
              <StatusBadge v-if="!a.actif" label="Inactif" type="gray" />
              <StatusBadge v-else :label="statuts[a.statut][0]" :type="statuts[a.statut][1]" />
            </td>
            <td v-if="peutModifier" class="px-3 py-3">
              <div class="flex justify-end gap-3 text-slate-500">
                <button @click="openEdit(a)" title="Modifier" class="hover:text-blue-600"><Pencil :size="16" /></button>
                <button @click="basculer(a)" :title="a.actif ? 'Désactiver' : 'Activer'" class="hover:text-orange-600">
                  <Power :size="16" />
                </button>
              </div>
            </td>
          </tr>
          <tr v-if="!articles.data.length">
            <td colspan="7" class="px-3 py-8 text-center text-slate-400">Aucun article.</td>
          </tr>
        </tbody>
      </table>
      <Pagination :page="articles" />
    </div>

    <!-- Formulaire -->
    <Modal :show="show" max-width="lg" @close="show = false">
      <form @submit.prevent="submit" class="space-y-4 p-6">
        <h2 class="text-lg font-semibold text-blue-950">{{ editing ? 'Modifier l\'article' : 'Nouvel article' }}</h2>

        <div class="grid grid-cols-2 gap-4">
          <div>
            <InputLabel for="reference" value="Référence" />
            <TextInput id="reference" v-model="form.reference" class="mt-1 block w-full" />
            <InputError class="mt-1" :message="form.errors.reference" />
          </div>
          <div>
            <InputLabel for="nom" value="Nom" />
            <TextInput id="nom" v-model="form.nom" class="mt-1 block w-full" />
            <InputError class="mt-1" :message="form.errors.nom" />
          </div>
          <div>
            <InputLabel for="categorie_id" value="Catégorie" />
            <select id="categorie_id" v-model="form.categorie_id" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm">
              <option value="">Choisir...</option>
              <option v-for="c in categories" :key="c.id" :value="c.id">{{ c.nom }}</option>
            </select>
            <InputError class="mt-1" :message="form.errors.categorie_id" />
          </div>
          <div>
            <InputLabel for="unite_id" value="Unité" />
            <select id="unite_id" v-model="form.unite_id" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm">
              <option value="">Choisir...</option>
              <option v-for="u in unites" :key="u.id" :value="u.id">{{ u.nom }}</option>
            </select>
            <InputError class="mt-1" :message="form.errors.unite_id" />
          </div>
          <div>
            <InputLabel for="seuil" value="Seuil d'alerte" />
            <TextInput id="seuil" type="number" min="0" v-model="form.seuil_alerte" class="mt-1 block w-full" />
            <InputError class="mt-1" :message="form.errors.seuil_alerte" />
          </div>
          <div>
            <InputLabel for="prix" value="Prix de vente (optionnel)" />
            <TextInput id="prix" type="number" min="0" step="0.01" v-model="form.prix_vente" class="mt-1 block w-full" />
            <InputError class="mt-1" :message="form.errors.prix_vente" />
          </div>
        </div>

        <div class="flex justify-end gap-3 pt-2">
          <SecondaryButton type="button" @click="show = false">Annuler</SecondaryButton>
          <PrimaryButton :disabled="form.processing">Enregistrer</PrimaryButton>
        </div>
      </form>
    </Modal>
  </AppLayout>
</template>