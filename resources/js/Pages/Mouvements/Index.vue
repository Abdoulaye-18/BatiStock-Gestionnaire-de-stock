<script setup>
import { ref, reactive, watch, computed } from 'vue'
import { Head, useForm, router } from '@inertiajs/vue3'
import { ArrowDownToLine, ArrowUpFromLine, SlidersHorizontal, ArrowLeftRight, Search } from 'lucide-vue-next'
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
  mouvements: Object,
  filtres: Object,
  articles: Array,
  entrepots: Array,
  fournisseurs: Array,
  stocks: Array,
  peutModifier: Boolean,
})

const types = {
  entree: ['Entrée', 'success'],
  sortie: ['Sortie', 'danger'],
  ajustement: ['Ajustement', 'warning'],
  transfert_sortie: ['Transfert −', 'purple'],
  transfert_entree: ['Transfert +', 'purple'],
}

// ----- Filtres -----
const f = reactive({
  q: props.filtres?.q ?? '',
  type: props.filtres?.type ?? '',
  entrepot_id: props.filtres?.entrepot_id ?? '',
  date_debut: props.filtres?.date_debut ?? '',
  date_fin: props.filtres?.date_fin ?? '',
})
let timer
watch(f, () => {
  clearTimeout(timer)
  timer = setTimeout(() => {
    router.get(route('mouvements.index'), { ...f }, { preserveState: true, replace: true })
  }, 300)
})

// ----- Formulaire (un seul, selon le mode) -----
const modes = {
  entree: { titre: 'Nouvelle entrée', motif: 'Réception de marchandises', icon: ArrowDownToLine, classe: 'bg-emerald-600 hover:bg-emerald-700' },
  sortie: { titre: 'Nouvelle sortie', motif: '', icon: ArrowUpFromLine, classe: 'bg-red-600 hover:bg-red-700' },
  ajustement: { titre: 'Ajustement de stock', motif: '', icon: SlidersHorizontal, classe: 'bg-orange-500 hover:bg-orange-600' },
  transfert: { titre: 'Transfert entre dépôts', motif: 'Transfert entre dépôts', icon: ArrowLeftRight, classe: 'bg-violet-600 hover:bg-violet-700' },
}

const show = ref(false)
const mode = ref('entree')
const form = useForm({
  article_id: '',
  entrepot_id: '',
  entrepot_destination_id: '',
  quantite: '',
  fournisseur_id: '',
  motif: '',
})

function ouvrir(m) {
  mode.value = m
  form.reset()
  form.clearErrors()
  form.motif = modes[m].motif
  show.value = true
}

function submit() {
  form.post(route(`mouvements.${mode.value}`), {
    preserveScroll: true,
    onSuccess: () => {
      show.value = false
      form.reset()
    },
  })
}

const articleChoisi = computed(() => props.articles.find((a) => a.id === form.article_id))

const disponible = computed(() => {
  if (!form.article_id || !form.entrepot_id) return null
  const s = props.stocks.find((x) => x.article_id === form.article_id && x.entrepot_id === form.entrepot_id)
  return s ? s.quantite : 0
})

const signe = (q) => (q > 0 ? `+${q}` : `${q}`)
</script>

<template>
  <Head title="Mouvements" />
  <AppLayout>
    <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
      <div>
        <h1 class="text-3xl font-bold text-blue-950">Mouvements de stock</h1>
        <p class="text-slate-500">Historique des entrées, sorties, ajustements et transferts</p>
      </div>
      <div v-if="peutModifier" class="flex flex-wrap gap-2">
        <button v-for="(m, key) in modes" :key="key" @click="ouvrir(key)"
          class="flex items-center gap-2 rounded-xl px-4 py-2.5 text-sm font-medium text-white shadow-sm"
          :class="m.classe">
          <component :is="m.icon" :size="16" /> {{ m.titre.replace('Nouvelle ', '').replace('Nouveau ', '') }}
        </button>
      </div>
    </div>

    <!-- Filtres -->
    <div class="mb-4 flex flex-wrap gap-3">
      <div class="relative">
        <Search :size="16" class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" />
        <input v-model="f.q" placeholder="Article..."
          class="rounded-xl border-0 bg-white py-2.5 pl-9 pr-4 text-sm shadow-sm focus:ring-2 focus:ring-blue-400" />
      </div>
      <select v-model="f.type" class="rounded-xl border-0 bg-white py-2.5 text-sm shadow-sm">
        <option value="">Tous les types</option>
        <option v-for="(t, key) in types" :key="key" :value="key">{{ t[0] }}</option>
      </select>
      <select v-model="f.entrepot_id" class="rounded-xl border-0 bg-white py-2.5 text-sm shadow-sm">
        <option value="">Tous les dépôts</option>
        <option v-for="e in entrepots" :key="e.id" :value="e.id">{{ e.nom }}</option>
      </select>
      <input type="date" v-model="f.date_debut" class="rounded-xl border-0 bg-white py-2.5 text-sm shadow-sm" />
      <input type="date" v-model="f.date_fin" class="rounded-xl border-0 bg-white py-2.5 text-sm shadow-sm" />
    </div>

    <!-- Historique -->
    <div class="overflow-x-auto rounded-2xl bg-white p-4 shadow-sm">
      <table class="w-full text-left text-sm">
        <thead class="text-slate-500">
          <tr>
            <th class="px-3 py-2 font-medium">Date</th>
            <th class="px-3 py-2 font-medium">Type</th>
            <th class="px-3 py-2 font-medium">Article</th>
            <th class="px-3 py-2 font-medium">Dépôt</th>
            <th class="px-3 py-2 font-medium">Quantité</th>
            <th class="px-3 py-2 font-medium">Motif</th>
            <th class="px-3 py-2 font-medium">Utilisateur</th>
            <th class="px-3 py-2 font-medium">Détail</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="m in mouvements.data" :key="m.id" class="border-t border-slate-100">
            <td class="whitespace-nowrap px-3 py-3 text-xs text-slate-500">{{ m.date }}</td>
            <td class="px-3 py-3"><StatusBadge :label="types[m.type][0]" :type="types[m.type][1]" /></td>
            <td class="px-3 py-3">
              <p class="font-medium">{{ m.article }}</p>
              <p class="font-mono text-xs text-slate-400">{{ m.reference }}</p>
            </td>
            <td class="px-3 py-3">{{ m.entrepot }}</td>
            <td class="px-3 py-3 font-semibold" :class="m.quantite > 0 ? 'text-emerald-600' : 'text-red-600'">
              {{ signe(m.quantite) }} {{ m.unite }}
            </td>
            <td class="px-3 py-3">{{ m.motif }}</td>
            <td class="px-3 py-3">{{ m.utilisateur }}</td>
            <td class="px-3 py-3 text-xs text-slate-500">
              <span v-if="m.fournisseur">{{ m.fournisseur }}</span>
              <span v-else-if="m.transfert">Transfert #{{ m.transfert }}</span>
              <span v-else>-</span>
            </td>
          </tr>
          <tr v-if="!mouvements.data.length">
            <td colspan="8" class="px-3 py-8 text-center text-slate-400">Aucun mouvement.</td>
          </tr>
        </tbody>
      </table>
      <Pagination :page="mouvements" />
    </div>

    <!-- Formulaire -->
    <Modal :show="show" max-width="lg" @close="show = false">
      <form @submit.prevent="submit" class="space-y-4 p-6">
        <h2 class="text-lg font-semibold text-blue-950">{{ modes[mode].titre }}</h2>

        <div>
          <InputLabel for="article" value="Article" />
          <select id="article" v-model="form.article_id" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm">
            <option value="">Choisir...</option>
            <option v-for="a in articles" :key="a.id" :value="a.id">{{ a.label }}</option>
          </select>
          <InputError class="mt-1" :message="form.errors.article_id" />
        </div>

        <div class="grid grid-cols-2 gap-4">
          <div>
            <InputLabel for="depot" :value="mode === 'transfert' ? 'Dépôt source' : 'Dépôt'" />
            <select id="depot" v-model="form.entrepot_id" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm">
              <option value="">Choisir...</option>
              <option v-for="e in entrepots" :key="e.id" :value="e.id">{{ e.nom }}</option>
            </select>
            <InputError class="mt-1" :message="form.errors.entrepot_id" />
          </div>

          <div v-if="mode === 'transfert'">
            <InputLabel for="destination" value="Dépôt destination" />
            <select id="destination" v-model="form.entrepot_destination_id" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm">
              <option value="">Choisir...</option>
              <option v-for="e in entrepots" :key="e.id" :value="e.id">{{ e.nom }}</option>
            </select>
            <InputError class="mt-1" :message="form.errors.entrepot_destination_id" />
          </div>

          <div v-if="mode === 'entree'">
            <InputLabel for="fournisseur" value="Fournisseur (optionnel)" />
            <select id="fournisseur" v-model="form.fournisseur_id" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm">
              <option value="">Aucun</option>
              <option v-for="fo in fournisseurs" :key="fo.id" :value="fo.id">{{ fo.nom }}</option>
            </select>
            <InputError class="mt-1" :message="form.errors.fournisseur_id" />
          </div>
        </div>

        <div>
          <InputLabel for="quantite" :value="mode === 'ajustement' ? 'Quantité (+ pour ajouter, − pour retirer)' : 'Quantité'" />
          <TextInput id="quantite" type="number" :min="mode === 'ajustement' ? undefined : 1" v-model="form.quantite" class="mt-1 block w-full" />
          <p v-if="disponible !== null && mode !== 'entree'" class="mt-1 text-xs text-slate-500">
            Disponible dans ce dépôt : <strong>{{ disponible }} {{ articleChoisi?.unite }}</strong>
          </p>
          <InputError class="mt-1" :message="form.errors.quantite" />
        </div>

        <div>
          <InputLabel for="motif" value="Motif (obligatoire)" />
          <TextInput id="motif" v-model="form.motif" class="mt-1 block w-full" />
          <InputError class="mt-1" :message="form.errors.motif" />
        </div>

        <div class="flex justify-end gap-3 pt-2">
          <SecondaryButton type="button" @click="show = false">Annuler</SecondaryButton>
          <PrimaryButton :disabled="form.processing">Valider</PrimaryButton>
        </div>
      </form>
    </Modal>
  </AppLayout>
</template>