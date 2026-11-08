<script setup>
import { ref, computed, watch } from 'vue'
import { Head, useForm, router } from '@inertiajs/vue3'
import { Plus, Eye, Trash2, Check, X, PackageCheck } from 'lucide-vue-next'
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
  demandes: Object,
  statut: String,
  statuts: Object,
  chantiers: Array,
  articles: Array,
  moiId: Number,
  peutCreer: Boolean,
  peutDecider: Boolean,
  peutPreparer: Boolean,
})

const badges = {
  en_attente: 'warning',
  approuvee: 'success',
  refusee: 'danger',
  en_preparation: 'info',
  partiellement_satisfaite: 'purple',
  satisfaite: 'success',
}

// ----- Filtre -----
const filtre = ref(props.statut ?? '')
watch(filtre, (v) => router.get(route('demandes.index'), { statut: v }, { preserveState: true, replace: true }))

// ----- Création -----
const showCreate = ref(false)
const form = useForm({
  chantier_id: '',
  motif: '',
  date_souhaitee: '',
  lignes: [{ article_id: '', quantite: 1 }],
})

function ouvrirCreation() {
  form.reset()
  form.clearErrors()
  showCreate.value = true
}
const ajouterLigne = () => form.lignes.push({ article_id: '', quantite: 1 })
const retirerLigne = (i) => form.lignes.length > 1 && form.lignes.splice(i, 1)
const uniteDe = (id) => props.articles.find((a) => a.id === id)?.unite ?? ''

function soumettre() {
  form.post(route('demandes.store'), {
    preserveScroll: true,
    onSuccess: () => { showCreate.value = false; form.reset() },
  })
}

// ----- Détail et décisions -----
const detailId = ref(null)
const detail = computed(() => props.demandes.data.find((d) => d.id === detailId.value))
const refus = ref('')
const montrerRefus = ref(false)
const erreurAction = ref('')

function ouvrirDetail(d) {
  detailId.value = d.id
  refus.value = ''
  montrerRefus.value = false
  erreurAction.value = ''
}
function fermerDetail() { detailId.value = null }

function agir(action, data = {}) {
  erreurAction.value = ''
  router.patch(route(`demandes.${action}`, detail.value.id), data, {
    preserveScroll: true,
    onSuccess: fermerDetail,
    onError: (e) => (erreurAction.value = e.statut ?? e.motif_refus ?? 'Action impossible.'),
  })
}
</script>

<template>
  <Head title="Demandes" />
  <AppLayout>
    <div class="mb-6 flex items-end justify-between">
      <div>
        <h1 class="text-3xl font-bold text-blue-950">Demandes de chantier</h1>
        <p class="text-slate-500">Demandes d'approvisionnement en matériaux</p>
      </div>
      <button v-if="peutCreer" @click="ouvrirCreation"
        class="flex items-center gap-2 rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-medium text-white shadow-sm hover:bg-blue-700">
        <Plus :size="16" /> Nouvelle demande
      </button>
    </div>

    <select v-model="filtre" class="mb-4 rounded-xl border-0 bg-white py-2.5 text-sm shadow-sm">
      <option value="">Tous les statuts</option>
      <option v-for="(label, key) in statuts" :key="key" :value="key">{{ label }}</option>
    </select>

    <div class="overflow-x-auto rounded-2xl bg-white p-4 shadow-sm">
      <table class="w-full text-left text-sm">
        <thead class="text-slate-500">
          <tr>
            <th class="px-3 py-2 font-medium">N°</th>
            <th class="px-3 py-2 font-medium">Chantier</th>
            <th class="px-3 py-2 font-medium">Demandeur</th>
            <th class="px-3 py-2 font-medium">Souhaitée le</th>
            <th class="px-3 py-2 font-medium">Articles</th>
            <th class="px-3 py-2 font-medium">Statut</th>
            <th class="px-3 py-2 text-right font-medium">Détail</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="d in demandes.data" :key="d.id" class="border-t border-slate-100">
            <td class="px-3 py-3 font-mono text-xs">{{ d.numero }}</td>
            <td class="px-3 py-3 font-medium">{{ d.chantier }}</td>
            <td class="px-3 py-3">{{ d.demandeur }}</td>
            <td class="px-3 py-3">{{ d.date_souhaitee ?? '-' }}</td>
            <td class="px-3 py-3">{{ d.lignes.length }}</td>
            <td class="px-3 py-3"><StatusBadge :label="statuts[d.statut]" :type="badges[d.statut]" /></td>
            <td class="px-3 py-3 text-right">
              <button @click="ouvrirDetail(d)" class="text-slate-500 hover:text-blue-600"><Eye :size="18" /></button>
            </td>
          </tr>
          <tr v-if="!demandes.data.length">
            <td colspan="7" class="px-3 py-8 text-center text-slate-400">Aucune demande.</td>
          </tr>
        </tbody>
      </table>
      <Pagination :page="demandes" />
    </div>

    <!-- Détail -->
    <Modal :show="!!detail" max-width="2xl" @close="fermerDetail">
      <div v-if="detail" class="space-y-4 p-6">
        <div class="flex items-start justify-between">
          <div>
            <h2 class="text-lg font-semibold text-blue-950">Demande {{ detail.numero }}</h2>
            <p class="text-sm text-slate-500">{{ detail.chantier }} · par {{ detail.demandeur }} · le {{ detail.creee_le }}</p>
          </div>
          <StatusBadge :label="statuts[detail.statut]" :type="badges[detail.statut]" />
        </div>

        <p class="text-sm"><span class="font-medium">Motif :</span> {{ detail.motif }}</p>
        <p v-if="detail.validateur" class="text-sm"><span class="font-medium">Traitée par :</span> {{ detail.validateur }}</p>
        <p v-if="detail.motif_refus" class="rounded-lg bg-red-50 p-3 text-sm text-red-700">
          <span class="font-medium">Motif du refus :</span> {{ detail.motif_refus }}
        </p>

        <table class="w-full text-left text-sm">
          <thead class="text-slate-500">
            <tr>
              <th class="py-2 font-medium">Article</th>
              <th class="py-2 font-medium">Demandé</th>
              <th class="py-2 font-medium">Servi</th>
              <th v-if="detail.lignes[0]?.disponible !== null" class="py-2 font-medium">En stock</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="l in detail.lignes" :key="l.id" class="border-t border-slate-100">
              <td class="py-2">
                <p class="font-medium">{{ l.article }}</p>
                <p class="font-mono text-xs text-slate-400">{{ l.reference }}</p>
              </td>
              <td class="py-2">{{ l.quantite }} {{ l.unite }}</td>
              <td class="py-2">{{ l.quantite_servie }} {{ l.unite }}</td>
              <td v-if="l.disponible !== null" class="py-2"
                :class="l.disponible < l.quantite - l.quantite_servie ? 'font-semibold text-red-600' : 'text-emerald-600'">
                {{ l.disponible }} {{ l.unite }}
              </td>
            </tr>
          </tbody>
        </table>

        <p v-if="erreurAction" class="rounded-lg bg-red-100 p-3 text-sm text-red-800">{{ erreurAction }}</p>

        <!-- Décision du responsable de contrôle -->
        <div v-if="detail.statut === 'en_attente' && peutDecider && detail.demandeur_id !== moiId" class="space-y-3">
          <div v-if="montrerRefus">
            <InputLabel for="refus" value="Motif du refus (obligatoire)" />
            <TextInput id="refus" v-model="refus" class="mt-1 block w-full" />
          </div>
          <div class="flex justify-end gap-3">
            <template v-if="!montrerRefus">
              <button @click="montrerRefus = true"
                class="flex items-center gap-2 rounded-xl bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700">
                <X :size="16" /> Refuser
              </button>
              <button @click="agir('approuver')"
                class="flex items-center gap-2 rounded-xl bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700">
                <Check :size="16" /> Approuver
              </button>
            </template>
            <template v-else>
              <SecondaryButton @click="montrerRefus = false">Retour</SecondaryButton>
              <button @click="agir('refuser', { motif_refus: refus })"
                class="rounded-xl bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700">
                Confirmer le refus
              </button>
            </template>
          </div>
        </div>

        <!-- Préparation par le gestionnaire -->
        <div v-if="detail.statut === 'approuvee' && peutPreparer" class="flex justify-end">
          <button @click="agir('preparer')"
            class="flex items-center gap-2 rounded-xl bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">
            <PackageCheck :size="16" /> Passer en préparation
          </button>
        </div>

        <div class="flex justify-end">
          <SecondaryButton @click="fermerDetail">Fermer</SecondaryButton>
        </div>
      </div>
    </Modal>

    <!-- Création -->
    <Modal :show="showCreate" max-width="2xl" @close="showCreate = false">
      <form @submit.prevent="soumettre" class="space-y-4 p-6">
        <h2 class="text-lg font-semibold text-blue-950">Nouvelle demande de matériaux</h2>

        <div class="grid grid-cols-2 gap-4">
          <div>
            <InputLabel for="chantier" value="Chantier" />
            <select id="chantier" v-model="form.chantier_id" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm">
              <option value="">Choisir...</option>
              <option v-for="c in chantiers" :key="c.id" :value="c.id">{{ c.nom }}</option>
            </select>
            <InputError class="mt-1" :message="form.errors.chantier_id" />
          </div>
          <div>
            <InputLabel for="date" value="Date souhaitée (optionnel)" />
            <TextInput id="date" type="date" v-model="form.date_souhaitee" class="mt-1 block w-full" />
            <InputError class="mt-1" :message="form.errors.date_souhaitee" />
          </div>
        </div>

        <div>
          <InputLabel for="motif" value="Motif" />
          <TextInput id="motif" v-model="form.motif" class="mt-1 block w-full" placeholder="Ex. : coulage des fondations" />
          <InputError class="mt-1" :message="form.errors.motif" />
        </div>

        <div>
          <InputLabel value="Matériaux demandés" />
          <div v-for="(l, i) in form.lignes" :key="i" class="mt-2">
            <div class="flex items-center gap-2">
              <select v-model="l.article_id" class="block flex-1 rounded-md border-gray-300 text-sm shadow-sm">
                <option value="">Article...</option>
                <option v-for="a in articles" :key="a.id" :value="a.id">{{ a.label }}</option>
              </select>
              <input type="number" min="1" v-model="l.quantite" class="w-24 rounded-md border-gray-300 text-sm shadow-sm" />
              <span class="w-14 text-xs text-slate-500">{{ uniteDe(l.article_id) }}</span>
              <button type="button" @click="retirerLigne(i)" class="text-slate-400 hover:text-red-600"><Trash2 :size="16" /></button>
            </div>
            <InputError class="mt-1" :message="form.errors[`lignes.${i}.article_id`] ?? form.errors[`lignes.${i}.quantite`]" />
          </div>
          <InputError class="mt-1" :message="form.errors.lignes" />
          <button type="button" @click="ajouterLigne" class="mt-3 text-sm font-medium text-blue-600 hover:underline">+ Ajouter un article</button>
        </div>

        <div class="flex justify-end gap-3 pt-2">
          <SecondaryButton type="button" @click="showCreate = false">Annuler</SecondaryButton>
          <PrimaryButton :disabled="form.processing">Soumettre la demande</PrimaryButton>
        </div>
      </form>
    </Modal>
  </AppLayout>
</template>