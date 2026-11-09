<script setup>
import { ref, reactive, computed, watch, onMounted } from 'vue'
import { Head, useForm, router } from '@inertiajs/vue3'
import { Plus, Eye, Trash2, Search, Play, PackageCheck } from 'lucide-vue-next'
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
  livraisons: Object,
  filtres: Object,
  statuts: Object,
  sources: Array,
  livreurs: Array,
  entrepots: Array,
  stocks: Array,
  peutModifier: Boolean,
})

const badges = {
  planifiee: 'purple',
  en_cours: 'info',
  livree: 'success',
  partiellement_livree: 'warning',
  echouee: 'danger',
}

// ----- Filtres -----
const f = reactive({ q: props.filtres?.q ?? '', statut: props.filtres?.statut ?? '' })
let timer
watch(f, () => {
  clearTimeout(timer)
  timer = setTimeout(() => {
    router.get(route('livraisons.index'), { ...f }, { preserveState: true, replace: true })
  }, 300)
})

// ----- Planification -----
const showForm = ref(false)
const form = useForm({
  source: '',
  livreur_id: '',
  adresse_destination: '',
  date_prevue: '',
  observation: '',
  lignes: [],
})

const sourceChoisie = computed(() => props.sources.find((s) => `${s.type}-${s.id}` === form.source))

const stockDe = (articleId, entrepotId) =>
  props.stocks.find((s) => s.article_id === articleId && s.entrepot_id === entrepotId)?.quantite ?? 0

function meilleurDepot(articleId) {
  const rows = props.stocks.filter((s) => s.article_id === articleId).sort((a, b) => b.quantite - a.quantite)
  return rows[0]?.entrepot_id ?? ''
}

function ouvrirPlanification() {
  form.reset()
  form.clearErrors()
  showForm.value = true
}

function choisirSource() {
  const s = sourceChoisie.value
  form.adresse_destination = s?.adresse ?? ''
  form.lignes = (s?.lignes ?? []).map((l) => ({
    article_id: l.article_id,
    article: l.article,
    unite: l.unite,
    restant: l.restant,
    entrepot_id: meilleurDepot(l.article_id),
    qte_prevue: l.restant,
  }))
}

const retirerLigne = (i) => form.lignes.splice(i, 1)

function planifier() {
  form
    .transform((d) => {
      const [type, id] = d.source.split('-')
      return {
        source_type: type,
        source_id: Number(id),
        livreur_id: d.livreur_id,
        adresse_destination: d.adresse_destination,
        date_prevue: d.date_prevue,
        observation: d.observation,
        lignes: d.lignes.map((l) => ({ article_id: l.article_id, entrepot_id: l.entrepot_id, qte_prevue: l.qte_prevue })),
      }
    })
    .post(route('livraisons.store'), {
      preserveScroll: true,
      onSuccess: () => { showForm.value = false; form.reset() },
    })
}

// ----- Détail, démarrage, confirmation de la remise -----
const detailId = ref(null)
const detail = computed(() => props.livraisons.data.find((l) => l.id === detailId.value))
const modeConfirmation = ref(false)
const saisies = ref([])
const observation = ref('')
const erreurAction = ref('')

function ouvrirDetail(l) {
  detailId.value = l.id
  modeConfirmation.value = false
  erreurAction.value = ''
}
function fermerDetail() { detailId.value = null }

function demarrer() {
  erreurAction.value = ''
  router.patch(route('livraisons.demarrer', detail.value.id), {}, {
    preserveScroll: true,
    onSuccess: fermerDetail,
    onError: (e) => (erreurAction.value = Object.values(e)[0]),
  })
}

function preparerConfirmation() {
  saisies.value = detail.value.lignes.map((l) => ({
    id: l.id, article: l.article, unite: l.unite, prevue: l.qte_prevue,
    disponible: l.disponible, entrepot: l.entrepot, qte_livree: l.qte_prevue, motif_ecart: '',
  }))
  observation.value = detail.value.observation ?? ''
  erreurAction.value = ''
  modeConfirmation.value = true
}

const resultat = computed(() => {
  const livre = saisies.value.reduce((s, l) => s + (Number(l.qte_livree) || 0), 0)
  const prevu = saisies.value.reduce((s, l) => s + l.prevue, 0)
  return livre === 0 ? 'Échouée' : livre >= prevu ? 'Livrée' : 'Partiellement livrée'
})

function confirmer() {
  erreurAction.value = ''
  router.patch(route('livraisons.confirmer', detail.value.id), {
    observation: observation.value,
    lignes: saisies.value.map((s) => ({
      id: s.id,
      qte_livree: s.qte_livree,
      motif_ecart: Number(s.qte_livree) < s.prevue ? s.motif_ecart : null,
    })),
  }, {
    preserveScroll: true,
    onSuccess: fermerDetail,
    onError: (e) => (erreurAction.value = Object.values(e)[0]),
  })
}

const peutAgir = computed(() => props.peutModifier && ['planifiee', 'en_cours'].includes(detail.value?.statut))

onMounted(() => {
  if (props.peutModifier && new URLSearchParams(window.location.search).get('ouvrir') === 'nouvelle') {
    ouvrirPlanification()
    window.history.replaceState(window.history.state, '', window.location.pathname)
  }
})
</script>

<template>
  <Head title="Livraisons" />
  <AppLayout>
    <div class="mb-6 flex items-end justify-between">
      <div>
        <h1 class="text-3xl font-bold text-blue-950">Livraisons</h1>
        <p class="text-slate-500">Planification, suivi et confirmation des remises</p>
      </div>
      <button v-if="peutModifier" @click="ouvrirPlanification"
        class="flex items-center gap-2 rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-medium text-white shadow-sm hover:bg-blue-700">
        <Plus :size="16" /> Planifier une livraison
      </button>
    </div>

    <div class="mb-4 flex flex-wrap gap-3">
      <div class="relative">
        <Search :size="16" class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" />
        <input v-model="f.q" placeholder="N° ou adresse..."
          class="rounded-xl border-0 bg-white py-2.5 pl-9 pr-4 text-sm shadow-sm focus:ring-2 focus:ring-blue-400" />
      </div>
      <select v-model="f.statut" class="rounded-xl border-0 bg-white py-2.5 text-sm shadow-sm">
        <option value="">Tous les statuts</option>
        <option v-for="(label, key) in statuts" :key="key" :value="key">{{ label }}</option>
      </select>
    </div>

    <div class="overflow-x-auto rounded-2xl bg-white p-4 shadow-sm">
      <table class="w-full text-left text-sm">
        <thead class="text-slate-500">
          <tr>
            <th class="px-3 py-2 font-medium">N° livraison</th>
            <th class="px-3 py-2 font-medium">Origine</th>
            <th class="px-3 py-2 font-medium">Destinataire</th>
            <th class="px-3 py-2 font-medium">Date prévue</th>
            <th class="px-3 py-2 font-medium">Livreur</th>
            <th class="px-3 py-2 font-medium">Statut</th>
            <th class="px-3 py-2 text-right font-medium">Détail</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="l in livraisons.data" :key="l.id" class="border-t border-slate-100">
            <td class="px-3 py-3 font-mono text-xs">{{ l.numero }}</td>
            <td class="px-3 py-3 font-mono text-xs">{{ l.origine }}</td>
            <td class="px-3 py-3 font-medium">{{ l.destinataire }}</td>
            <td class="px-3 py-3">{{ l.date_prevue }}</td>
            <td class="px-3 py-3">{{ l.livreur ?? '-' }}</td>
            <td class="px-3 py-3"><StatusBadge :label="statuts[l.statut]" :type="badges[l.statut]" /></td>
            <td class="px-3 py-3 text-right">
              <button @click="ouvrirDetail(l)" class="text-slate-500 hover:text-blue-600"><Eye :size="18" /></button>
            </td>
          </tr>
          <tr v-if="!livraisons.data.length">
            <td colspan="7" class="px-3 py-8 text-center text-slate-400">Aucune livraison.</td>
          </tr>
        </tbody>
      </table>
      <Pagination :page="livraisons" />
    </div>

    <!-- Détail et confirmation -->
    <Modal :show="!!detail" max-width="2xl" @close="fermerDetail">
      <div v-if="detail" class="space-y-4 p-6">
        <div class="flex items-start justify-between">
          <div>
            <h2 class="text-lg font-semibold text-blue-950">Livraison {{ detail.numero }}</h2>
            <p class="text-sm text-slate-500">{{ detail.type }} {{ detail.origine }} · {{ detail.destinataire }}</p>
          </div>
          <StatusBadge :label="statuts[detail.statut]" :type="badges[detail.statut]" />
        </div>

        <div class="grid grid-cols-2 gap-2 text-sm">
          <p><span class="font-medium">Adresse :</span> {{ detail.adresse_destination }}</p>
          <p><span class="font-medium">Livreur :</span> {{ detail.livreur ?? '-' }}</p>
          <p><span class="font-medium">Date prévue :</span> {{ detail.date_prevue }}</p>
          <p v-if="detail.date_effective"><span class="font-medium">Date effective :</span> {{ detail.date_effective }}</p>
        </div>
        <p v-if="detail.observation" class="rounded-lg bg-slate-50 p-3 text-sm">{{ detail.observation }}</p>

        <!-- Lecture -->
        <table v-if="!modeConfirmation" class="w-full text-left text-sm">
          <thead class="text-slate-500">
            <tr>
              <th class="py-2 font-medium">Article</th>
              <th class="py-2 font-medium">Dépôt</th>
              <th class="py-2 font-medium">Prévu</th>
              <th class="py-2 font-medium">Livré</th>
              <th class="py-2 font-medium">Écart</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="l in detail.lignes" :key="l.id" class="border-t border-slate-100">
              <td class="py-2">
                <p class="font-medium">{{ l.article }}</p>
                <p class="font-mono text-xs text-slate-400">{{ l.reference }}</p>
              </td>
              <td class="py-2">{{ l.entrepot }}</td>
              <td class="py-2">{{ l.qte_prevue }} {{ l.unite }}</td>
              <td class="py-2">{{ l.qte_livree }} {{ l.unite }}</td>
              <td class="py-2 text-xs text-slate-500">{{ l.motif_ecart ?? '-' }}</td>
            </tr>
          </tbody>
        </table>

        <!-- Confirmation de la remise -->
        <div v-else class="space-y-3">
          <p class="text-sm text-slate-500">Saisis les quantités <strong>réellement remises</strong>. Le stock ne baissera que de ces quantités.</p>
          <div v-for="s in saisies" :key="s.id" class="rounded-xl border border-slate-100 p-3">
            <div class="flex items-center justify-between gap-3">
              <div>
                <p class="text-sm font-medium">{{ s.article }}</p>
                <p class="text-xs text-slate-400">Prévu : {{ s.prevue }} {{ s.unite }} · {{ s.entrepot }} (stock : {{ s.disponible }})</p>
              </div>
              <input type="number" min="0" :max="s.prevue" v-model="s.qte_livree"
                class="w-28 rounded-md border-gray-300 text-sm shadow-sm" />
            </div>
            <div v-if="Number(s.qte_livree) < s.prevue" class="mt-2">
              <TextInput v-model="s.motif_ecart" placeholder="Motif de l'écart (obligatoire)" class="block w-full" />
            </div>
          </div>
          <div>
            <InputLabel for="obs" value="Observation (optionnel)" />
            <TextInput id="obs" v-model="observation" class="mt-1 block w-full" />
          </div>
          <p class="text-sm">Résultat : <strong>{{ resultat }}</strong></p>
        </div>

        <p v-if="erreurAction" class="rounded-lg bg-red-100 p-3 text-sm text-red-800">{{ erreurAction }}</p>

        <div class="flex flex-wrap justify-end gap-3">
          <template v-if="peutAgir && !modeConfirmation">
            <button v-if="detail.statut === 'planifiee'" @click="demarrer"
              class="flex items-center gap-2 rounded-xl bg-slate-600 px-4 py-2 text-sm font-medium text-white hover:bg-slate-700">
              <Play :size="16" /> Démarrer
            </button>
            <button @click="preparerConfirmation"
              class="flex items-center gap-2 rounded-xl bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700">
              <PackageCheck :size="16" /> Confirmer la remise
            </button>
          </template>
          <template v-if="modeConfirmation">
            <SecondaryButton @click="modeConfirmation = false">Retour</SecondaryButton>
            <button @click="confirmer"
              class="rounded-xl bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700">
              Valider la remise
            </button>
          </template>
          <SecondaryButton v-if="!modeConfirmation" @click="fermerDetail">Fermer</SecondaryButton>
        </div>
      </div>
    </Modal>

    <!-- Planification -->
    <Modal :show="showForm" max-width="2xl" @close="showForm = false">
      <form @submit.prevent="planifier" class="space-y-4 p-6">
        <h2 class="text-lg font-semibold text-blue-950">Planifier une livraison</h2>

        <p v-if="form.errors.statut" class="rounded-lg bg-red-100 p-3 text-sm text-red-800">{{ form.errors.statut }}</p>
        <p v-if="!sources.length" class="rounded-lg bg-orange-50 p-3 text-sm text-orange-800">
          Aucune commande ni demande « En préparation » avec des quantités restantes à livrer.
        </p>

        <div>
          <InputLabel for="source" value="Commande ou demande à livrer" />
          <select id="source" v-model="form.source" @change="choisirSource"
            class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm">
            <option value="">Choisir...</option>
            <optgroup label="Commandes clients">
              <option v-for="s in sources.filter((x) => x.type === 'commande')" :key="`c${s.id}`" :value="`commande-${s.id}`">{{ s.label }}</option>
            </optgroup>
            <optgroup label="Demandes de chantier">
              <option v-for="s in sources.filter((x) => x.type === 'demande')" :key="`d${s.id}`" :value="`demande-${s.id}`">{{ s.label }}</option>
            </optgroup>
          </select>
          <InputError class="mt-1" :message="form.errors.source_id" />
        </div>

        <div class="grid grid-cols-2 gap-4">
          <div>
            <InputLabel for="livreur" value="Livreur" />
            <select id="livreur" v-model="form.livreur_id" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm">
              <option value="">Choisir...</option>
              <option v-for="l in livreurs" :key="l.id" :value="l.id">{{ l.nom }}</option>
            </select>
            <InputError class="mt-1" :message="form.errors.livreur_id" />
          </div>
          <div>
            <InputLabel for="date" value="Date prévue" />
            <TextInput id="date" type="date" v-model="form.date_prevue" class="mt-1 block w-full" />
            <InputError class="mt-1" :message="form.errors.date_prevue" />
          </div>
        </div>

        <div>
          <InputLabel for="adresse" value="Adresse de destination" />
          <TextInput id="adresse" v-model="form.adresse_destination" class="mt-1 block w-full" />
          <InputError class="mt-1" :message="form.errors.adresse_destination" />
        </div>

        <div v-if="form.lignes.length">
          <InputLabel value="Articles à livrer" />
          <div v-for="(l, i) in form.lignes" :key="l.article_id" class="mt-2 rounded-xl border border-slate-100 p-3">
            <div class="flex items-center gap-2">
              <div class="flex-1">
                <p class="text-sm font-medium">{{ l.article }}</p>
                <p class="text-xs text-slate-400">Reste à livrer : {{ l.restant }} {{ l.unite }}</p>
              </div>
              <select v-model="l.entrepot_id" class="w-44 rounded-md border-gray-300 text-sm shadow-sm">
                <option value="">Dépôt...</option>
                <option v-for="e in entrepots" :key="e.id" :value="e.id">{{ e.nom }}</option>
              </select>
              <input type="number" min="1" :max="l.restant" v-model="l.qte_prevue"
                class="w-24 rounded-md border-gray-300 text-sm shadow-sm" />
              <button type="button" @click="retirerLigne(i)" class="text-slate-400 hover:text-red-600"><Trash2 :size="16" /></button>
            </div>
            <p v-if="l.entrepot_id" class="mt-1 text-xs"
              :class="stockDe(l.article_id, l.entrepot_id) < l.qte_prevue ? 'text-red-600' : 'text-emerald-600'">
              Stock dans ce dépôt : {{ stockDe(l.article_id, l.entrepot_id) }} {{ l.unite }}
            </p>
            <InputError class="mt-1"
              :message="form.errors[`lignes.${i}.entrepot_id`] ?? form.errors[`lignes.${i}.qte_prevue`]" />
          </div>
          <InputError class="mt-1" :message="form.errors.lignes" />
        </div>

        <div class="flex justify-end gap-3 pt-2">
          <SecondaryButton type="button" @click="showForm = false">Annuler</SecondaryButton>
          <PrimaryButton :disabled="form.processing || !form.lignes.length">Planifier</PrimaryButton>
        </div>
      </form>
    </Modal>
  </AppLayout>
</template>