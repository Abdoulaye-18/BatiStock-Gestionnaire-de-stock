<script setup>
import { ref, reactive, computed, watch } from 'vue'
import { Head, useForm, router } from '@inertiajs/vue3'
import { Plus, Eye, Trash2, Search, Pencil, Check, X, PackageCheck } from 'lucide-vue-next'
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
  commandes: Object,
  filtres: Object,
  statuts: Object,
  clients: Array,
  articles: Array,
  peutModifier: Boolean,
})

const badges = {
  brouillon: 'gray',
  confirmee: 'warning',
  en_preparation: 'info',
  partiellement_livree: 'purple',
  livree: 'success',
  annulee: 'danger',
}

const fcfa = (n) => `${new Intl.NumberFormat('fr-FR').format(n)} FCFA`

// ----- Filtres -----
const f = reactive({ q: props.filtres?.q ?? '', statut: props.filtres?.statut ?? '' })
let timer
watch(f, () => {
  clearTimeout(timer)
  timer = setTimeout(() => {
    router.get(route('commandes.index'), { ...f }, { preserveState: true, replace: true })
  }, 300)
})

// ----- Création / modification (brouillon) -----
const showForm = ref(false)
const editing = ref(null)
const form = useForm({
  client_id: '',
  adresse_destination: '',
  date_commande: '',
  lignes: [{ article_id: '', quantite: 1, prix_unitaire: '' }],
})

function ouvrirCreation() {
  editing.value = null
  form.reset()
  form.clearErrors()
  showForm.value = true
}

function ouvrirEdition(c) {
  fermerDetail()
  editing.value = c
  form.clearErrors()
  form.client_id = c.client_id
  form.adresse_destination = c.adresse_destination
  form.date_commande = c.date_iso
  form.lignes = c.lignes.map((l) => ({ article_id: l.article_id, quantite: l.quantite, prix_unitaire: l.prix_unitaire }))
  showForm.value = true
}

function choisirClient() {
  const c = props.clients.find((x) => x.id === form.client_id)
  if (c?.adresse_livraison) form.adresse_destination = c.adresse_livraison
}

function choisirArticle(l) {
  const a = props.articles.find((x) => x.id === l.article_id)
  l.prix_unitaire = a?.prix_vente ?? ''
}

const ajouterLigne = () => form.lignes.push({ article_id: '', quantite: 1, prix_unitaire: '' })
const retirerLigne = (i) => form.lignes.length > 1 && form.lignes.splice(i, 1)
const uniteDe = (id) => props.articles.find((a) => a.id === id)?.unite ?? ''
const totalApercu = computed(() =>
  form.lignes.reduce((s, l) => s + (Number(l.quantite) || 0) * (Number(l.prix_unitaire) || 0), 0)
)

function enregistrer() {
  const opts = { preserveScroll: true, onSuccess: () => { showForm.value = false; form.reset() } }
  editing.value
    ? form.put(route('commandes.update', editing.value.id), opts)
    : form.post(route('commandes.store'), opts)
}

// ----- Détail et actions -----
const detailId = ref(null)
const detail = computed(() => props.commandes.data.find((c) => c.id === detailId.value))
const motif = ref('')
const montrerAnnulation = ref(false)
const erreurAction = ref('')

function ouvrirDetail(c) {
  detailId.value = c.id
  motif.value = ''
  montrerAnnulation.value = false
  erreurAction.value = ''
}
function fermerDetail() { detailId.value = null }

function agir(action, data = {}) {
  erreurAction.value = ''
  router.patch(route(`commandes.${action}`, detail.value.id), data, {
    preserveScroll: true,
    onSuccess: fermerDetail,
    onError: (e) => (erreurAction.value = e.statut ?? e.motif_annulation ?? 'Action impossible.'),
  })
}

const peutAnnuler = computed(() => ['brouillon', 'confirmee', 'en_preparation'].includes(detail.value?.statut))
</script>

<template>
  <Head title="Commandes" />
  <AppLayout>
    <div class="mb-6 flex items-end justify-between">
      <div>
        <h1 class="text-3xl font-bold text-blue-950">Commandes clients</h1>
        <p class="text-slate-500">Création, confirmation et préparation des commandes</p>
      </div>
      <button v-if="peutModifier" @click="ouvrirCreation"
        class="flex items-center gap-2 rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-medium text-white shadow-sm hover:bg-blue-700">
        <Plus :size="16" /> Nouvelle commande
      </button>
    </div>

    <div class="mb-4 flex flex-wrap gap-3">
      <div class="relative">
        <Search :size="16" class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" />
        <input v-model="f.q" placeholder="N° ou client..."
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
            <th class="px-3 py-2 font-medium">N°</th>
            <th class="px-3 py-2 font-medium">Client</th>
            <th class="px-3 py-2 font-medium">Date</th>
            <th class="px-3 py-2 font-medium">Montant</th>
            <th class="px-3 py-2 font-medium">Statut</th>
            <th class="px-3 py-2 text-right font-medium">Détail</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="c in commandes.data" :key="c.id" class="border-t border-slate-100">
            <td class="px-3 py-3 font-mono text-xs">{{ c.numero }}</td>
            <td class="px-3 py-3 font-medium">{{ c.client }}</td>
            <td class="px-3 py-3">{{ c.date }}</td>
            <td class="px-3 py-3">{{ fcfa(c.montant_total) }}</td>
            <td class="px-3 py-3"><StatusBadge :label="statuts[c.statut]" :type="badges[c.statut]" /></td>
            <td class="px-3 py-3 text-right">
              <button @click="ouvrirDetail(c)" class="text-slate-500 hover:text-blue-600"><Eye :size="18" /></button>
            </td>
          </tr>
          <tr v-if="!commandes.data.length">
            <td colspan="6" class="px-3 py-8 text-center text-slate-400">Aucune commande.</td>
          </tr>
        </tbody>
      </table>
      <Pagination :page="commandes" />
    </div>

    <!-- Détail -->
    <Modal :show="!!detail" max-width="2xl" @close="fermerDetail">
      <div v-if="detail" class="space-y-4 p-6">
        <div class="flex items-start justify-between">
          <div>
            <h2 class="text-lg font-semibold text-blue-950">Commande {{ detail.numero }}</h2>
            <p class="text-sm text-slate-500">{{ detail.client }} · le {{ detail.date }} · par {{ detail.createur }}</p>
          </div>
          <StatusBadge :label="statuts[detail.statut]" :type="badges[detail.statut]" />
        </div>

        <p class="text-sm"><span class="font-medium">Adresse de livraison :</span> {{ detail.adresse_destination }}</p>

        <table class="w-full text-left text-sm">
          <thead class="text-slate-500">
            <tr>
              <th class="py-2 font-medium">Article</th>
              <th class="py-2 font-medium">Commandé</th>
              <th class="py-2 font-medium">Livré</th>
              <th class="py-2 font-medium">En stock</th>
              <th class="py-2 font-medium">Prix unit.</th>
              <th class="py-2 text-right font-medium">Total</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="l in detail.lignes" :key="l.id" class="border-t border-slate-100">
              <td class="py-2">
                <p class="font-medium">{{ l.article }}</p>
                <p class="font-mono text-xs text-slate-400">{{ l.reference }}</p>
              </td>
              <td class="py-2">{{ l.quantite }} {{ l.unite }}</td>
              <td class="py-2">{{ l.quantite_livree }} {{ l.unite }}</td>
              <td class="py-2"
                :class="l.disponible < l.quantite - l.quantite_livree ? 'font-semibold text-red-600' : 'text-emerald-600'">
                {{ l.disponible }} {{ l.unite }}
              </td>
              <td class="py-2">{{ fcfa(l.prix_unitaire) }}</td>
              <td class="py-2 text-right">{{ fcfa(l.quantite * l.prix_unitaire) }}</td>
            </tr>
          </tbody>
        </table>

        <p class="text-right text-base font-semibold text-blue-950">Total : {{ fcfa(detail.montant_total) }}</p>

        <p v-if="erreurAction" class="rounded-lg bg-red-100 p-3 text-sm text-red-800">{{ erreurAction }}</p>

        <div v-if="peutModifier && montrerAnnulation" class="space-y-3">
          <div>
            <InputLabel for="motif" value="Motif de l'annulation (obligatoire)" />
            <TextInput id="motif" v-model="motif" class="mt-1 block w-full" />
          </div>
          <div class="flex justify-end gap-3">
            <SecondaryButton @click="montrerAnnulation = false">Retour</SecondaryButton>
            <button @click="agir('annuler', { motif_annulation: motif })"
              class="rounded-xl bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700">
              Confirmer l'annulation
            </button>
          </div>
        </div>

        <div v-else-if="peutModifier" class="flex flex-wrap justify-end gap-3">
          <button v-if="peutAnnuler" @click="montrerAnnulation = true"
            class="flex items-center gap-2 rounded-xl bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700">
            <X :size="16" /> Annuler
          </button>
          <button v-if="detail.statut === 'brouillon'" @click="ouvrirEdition(detail)"
            class="flex items-center gap-2 rounded-xl bg-slate-600 px-4 py-2 text-sm font-medium text-white hover:bg-slate-700">
            <Pencil :size="16" /> Modifier
          </button>
          <button v-if="detail.statut === 'brouillon'" @click="agir('confirmer')"
            class="flex items-center gap-2 rounded-xl bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700">
            <Check :size="16" /> Confirmer
          </button>
          <button v-if="detail.statut === 'confirmee'" @click="agir('preparer')"
            class="flex items-center gap-2 rounded-xl bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">
            <PackageCheck :size="16" /> Passer en préparation
          </button>
        </div>

        <div class="flex justify-end">
          <SecondaryButton @click="fermerDetail">Fermer</SecondaryButton>
        </div>
      </div>
    </Modal>

    <!-- Formulaire -->
    <Modal :show="showForm" max-width="2xl" @close="showForm = false">
      <form @submit.prevent="enregistrer" class="space-y-4 p-6">
        <h2 class="text-lg font-semibold text-blue-950">
          {{ editing ? `Modifier ${editing.numero}` : 'Nouvelle commande' }}
        </h2>

        <div class="grid grid-cols-2 gap-4">
          <div>
            <InputLabel for="client" value="Client" />
            <select id="client" v-model="form.client_id" @change="choisirClient"
              class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm">
              <option value="">Choisir...</option>
              <option v-for="c in clients" :key="c.id" :value="c.id">{{ c.nom }}</option>
            </select>
            <InputError class="mt-1" :message="form.errors.client_id" />
          </div>
          <div>
            <InputLabel for="date" value="Date (aujourd'hui par défaut)" />
            <TextInput id="date" type="date" v-model="form.date_commande" class="mt-1 block w-full" />
            <InputError class="mt-1" :message="form.errors.date_commande" />
          </div>
        </div>

        <div>
          <InputLabel for="adresse" value="Adresse de livraison" />
          <TextInput id="adresse" v-model="form.adresse_destination" class="mt-1 block w-full" />
          <InputError class="mt-1" :message="form.errors.adresse_destination" />
        </div>

        <div>
          <InputLabel value="Articles commandés" />
          <div v-for="(l, i) in form.lignes" :key="i" class="mt-2">
            <div class="flex items-center gap-2">
              <select v-model="l.article_id" @change="choisirArticle(l)"
                class="block flex-1 rounded-md border-gray-300 text-sm shadow-sm">
                <option value="">Article...</option>
                <option v-for="a in articles" :key="a.id" :value="a.id">{{ a.label }}</option>
              </select>
              <input type="number" min="1" v-model="l.quantite" title="Quantité"
                class="w-20 rounded-md border-gray-300 text-sm shadow-sm" />
              <span class="w-12 text-xs text-slate-500">{{ uniteDe(l.article_id) }}</span>
              <input type="number" min="0" step="0.01" v-model="l.prix_unitaire" title="Prix unitaire"
                class="w-28 rounded-md border-gray-300 text-sm shadow-sm" />
              <button type="button" @click="retirerLigne(i)" class="text-slate-400 hover:text-red-600">
                <Trash2 :size="16" />
              </button>
            </div>
            <InputError class="mt-1"
              :message="form.errors[`lignes.${i}.article_id`] ?? form.errors[`lignes.${i}.quantite`] ?? form.errors[`lignes.${i}.prix_unitaire`]" />
          </div>
          <InputError class="mt-1" :message="form.errors.lignes" />
          <button type="button" @click="ajouterLigne" class="mt-3 text-sm font-medium text-blue-600 hover:underline">
            + Ajouter un article
          </button>
        </div>

        <p class="text-right text-sm text-slate-500">Total estimé : <strong>{{ fcfa(totalApercu) }}</strong></p>

        <div class="flex justify-end gap-3 pt-2">
          <SecondaryButton type="button" @click="showForm = false">Annuler</SecondaryButton>
          <PrimaryButton :disabled="form.processing">Enregistrer</PrimaryButton>
        </div>
      </form>
    </Modal>
  </AppLayout>
</template>