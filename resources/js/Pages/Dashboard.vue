<script setup>
import { computed } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import {
  Package, Boxes, ShoppingCart, FileText, Truck, TriangleAlert, HardHat, PackageCheck,
  ArrowDownToLine, ArrowUpFromLine, ArrowRight, Clock, TrendingUp, Layers,
} from 'lucide-vue-next'
import AppLayout from '@/Layouts/AppLayout.vue'
import StatCard from '@/Components/StatCard.vue'
import StatusBadge from '@/Components/StatusBadge.vue'
import Panel from '@/Components/Panel.vue'
import LineChart from '@/Components/LineChart.vue'
import DonutChart from '@/Components/DonutChart.vue'

const props = defineProps({
  vue: String,
  donnees: Object,
  actions: Array,
})

const iconesKpi = {
  package: Package, boxes: Boxes, cart: ShoppingCart, file: FileText,
  truck: Truck, alert: TriangleAlert, hardhat: HardHat, check: PackageCheck,
}
const iconesAction = { entree: ArrowDownToLine, sortie: ArrowUpFromLine, livraison: Truck, demande: FileText }
const couleursAction = {
  green: 'bg-emerald-100 text-emerald-600',
  red: 'bg-red-100 text-red-600',
  blue: 'bg-blue-100 text-blue-600',
  purple: 'bg-violet-100 text-violet-600',
}

const typesMouvement = {
  entree: ['Entrée', 'success'],
  sortie: ['Sortie', 'danger'],
  ajustement: ['Ajustement', 'warning'],
  transfert_sortie: ['Transfert −', 'purple'],
  transfert_entree: ['Transfert +', 'purple'],
}
const statutsLivraison = { planifiee: ['Planifiée', 'purple'], en_cours: ['En cours', 'info'] }
const statutsAlerte = { faible: ['Faible', 'warning'], rupture: ['Rupture', 'danger'] }
const badgesDemande = {
  en_attente: 'warning', approuvee: 'success', refusee: 'danger',
  en_preparation: 'info', partiellement_satisfaite: 'purple', satisfaite: 'success',
}

const fmt = (n) => new Intl.NumberFormat('fr-FR').format(n)
const signe = (q) => (q > 0 ? `+${q}` : `${q}`)

const aujourdhui = computed(() => {
  const d = new Date().toLocaleDateString('fr-FR', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' })
  return d.charAt(0).toUpperCase() + d.slice(1)
})

const gridKpi = computed(() =>
  props.vue === 'global'
    ? 'grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-6'
    : 'grid-cols-1 sm:grid-cols-2 xl:grid-cols-4'
)

const pointsEvolution = computed(() => (props.donnees.evolution ?? []).map((e) => ({ label: e.date, value: e.total })))
const totalCategories = computed(() => (props.donnees.categories ?? []).reduce((s, c) => s + c.value, 0))
</script>

<template>
  <Head title="Tableau de bord" />
  <AppLayout>
    <div class="mb-6 flex flex-wrap items-end justify-between gap-3">
      <div>
        <h1 class="text-3xl font-bold text-blue-950">Tableau de bord</h1>
        <p class="text-slate-500">Voici un aperçu de l'activité de votre entreprise.</p>
      </div>
      <p class="rounded-xl bg-white px-4 py-2 text-sm text-slate-600 shadow-sm">{{ aujourdhui }}</p>
    </div>

    <!-- Indicateurs -->
    <div class="grid gap-4" :class="gridKpi">
      <StatCard v-for="k in donnees.kpis" :key="k.titre"
        :title="k.titre" :value="fmt(k.valeur)" :subtitle="k.sous_titre"
        :icon="iconesKpi[k.icone]" :color="k.couleur" :href="k.href" />
    </div>

    <!-- ===== Vue complète ===== -->
    <template v-if="vue === 'global'">
      <div class="mt-6 grid gap-4 xl:grid-cols-3">
        <Panel title="Évolution du stock (7 derniers jours)" :icon="TrendingUp" :href="route('stocks.index')">
          <LineChart :points="pointsEvolution" />
        </Panel>

        <Panel title="Derniers mouvements" :icon="Clock" :href="route('mouvements.index')">
          <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
              <thead class="text-slate-500">
                <tr>
                  <th class="py-2 font-medium">Type</th>
                  <th class="py-2 font-medium">Article</th>
                  <th class="py-2 font-medium">Qté</th>
                  <th class="py-2 font-medium">Date</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="(m, i) in donnees.mouvements" :key="i" class="border-t border-slate-100">
                  <td class="py-2"><StatusBadge :label="typesMouvement[m.type][0]" :type="typesMouvement[m.type][1]" /></td>
                  <td class="py-2">
                    <p class="font-medium">{{ m.article }}</p>
                    <p class="text-slate-400">{{ m.utilisateur }}</p>
                  </td>
                  <td class="whitespace-nowrap py-2 font-semibold" :class="m.quantite > 0 ? 'text-emerald-600' : 'text-red-600'">
                    {{ signe(m.quantite) }} {{ m.unite }}
                  </td>
                  <td class="whitespace-nowrap py-2 text-slate-500">{{ m.date }}</td>
                </tr>
                <tr v-if="!donnees.mouvements.length">
                  <td colspan="4" class="py-6 text-center text-slate-400">Aucun mouvement.</td>
                </tr>
              </tbody>
            </table>
          </div>
        </Panel>

        <Panel title="Répartition du stock par catégorie" :icon="Layers" :href="route('stocks.index')">
          <div v-if="donnees.categories.length" class="flex flex-wrap items-center gap-4">
            <DonutChart :segments="donnees.categories" :total="totalCategories" />
            <ul class="min-w-0 flex-1 space-y-1.5 text-sm">
              <li v-for="c in donnees.categories" :key="c.label" class="flex items-center justify-between gap-2">
                <span class="flex min-w-0 items-center gap-2">
                  <span class="h-2.5 w-2.5 shrink-0 rounded-full" :style="{ background: c.color }"></span>
                  <span class="truncate">{{ c.label }}</span>
                </span>
                <span class="text-slate-500">{{ c.pct }} %</span>
              </li>
            </ul>
          </div>
          <p v-else class="py-6 text-center text-sm text-slate-400">Aucun stock enregistré.</p>
        </Panel>
      </div>

      <div class="mt-4 grid gap-4 xl:grid-cols-5">
        <div class="xl:col-span-3">
          <Panel title="Alertes de stock faible" tone="red" :icon="TriangleAlert"
            :href="route('articles.index', { statut: 'faible' })">
            <div v-if="donnees.alertes.length" class="overflow-x-auto">
              <table class="w-full text-left text-sm">
                <thead class="text-slate-500">
                  <tr>
                    <th class="py-2 font-medium">Article</th>
                    <th class="py-2 font-medium">Catégorie</th>
                    <th class="py-2 font-medium">Stock actuel</th>
                    <th class="py-2 font-medium">Seuil</th>
                    <th class="py-2 font-medium">Statut</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="a in donnees.alertes" :key="a.reference" class="border-t border-slate-100">
                    <td class="py-2.5">
                      <p class="font-medium">{{ a.article }}</p>
                      <p class="font-mono text-xs text-slate-400">{{ a.reference }}</p>
                    </td>
                    <td class="py-2.5">{{ a.categorie }}</td>
                    <td class="py-2.5 font-semibold text-red-600">{{ fmt(a.stock) }} {{ a.unite }}</td>
                    <td class="py-2.5">{{ fmt(a.seuil) }} {{ a.unite }}</td>
                    <td class="py-2.5"><StatusBadge :label="statutsAlerte[a.statut][0]" :type="statutsAlerte[a.statut][1]" /></td>
                  </tr>
                </tbody>
              </table>
            </div>
            <p v-else class="py-6 text-center text-sm text-emerald-600">Aucune alerte : tous les stocks sont au-dessus du seuil.</p>
          </Panel>
        </div>

        <div class="xl:col-span-2">
          <Panel title="Prochaines livraisons" :icon="Truck" :href="route('livraisons.index')">
            <div class="overflow-x-auto">
              <table class="w-full text-left text-sm">
                <thead class="text-slate-500">
                  <tr>
                    <th class="py-2 font-medium">N°</th>
                    <th class="py-2 font-medium">Destinataire</th>
                    <th class="py-2 font-medium">Date prévue</th>
                    <th class="py-2 font-medium">Statut</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="l in donnees.livraisons" :key="l.numero" class="border-t border-slate-100">
                    <td class="py-2.5 font-mono text-xs">{{ l.numero }}</td>
                    <td class="py-2.5">{{ l.destinataire ?? '-' }}</td>
                    <td class="py-2.5">{{ l.date }}</td>
                    <td class="py-2.5">
                      <StatusBadge v-if="l.en_retard" label="En retard" type="danger" />
                      <StatusBadge v-else :label="statutsLivraison[l.statut][0]" :type="statutsLivraison[l.statut][1]" />
                    </td>
                  </tr>
                  <tr v-if="!donnees.livraisons.length">
                    <td colspan="4" class="py-6 text-center text-slate-400">Aucune livraison à venir.</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </Panel>
        </div>
      </div>
    </template>

    <!-- ===== Vue du responsable de chantier ===== -->
    <template v-else>
      <div class="mt-6">
        <Panel title="Mes dernières demandes" :icon="FileText" :href="route('demandes.index')">
          <table class="w-full text-left text-sm">
            <thead class="text-slate-500">
              <tr>
                <th class="py-2 font-medium">N°</th>
                <th class="py-2 font-medium">Chantier</th>
                <th class="py-2 font-medium">Date</th>
                <th class="py-2 font-medium">Statut</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="d in donnees.demandes" :key="d.numero" class="border-t border-slate-100">
                <td class="py-2.5 font-mono text-xs">{{ d.numero }}</td>
                <td class="py-2.5 font-medium">{{ d.chantier }}</td>
                <td class="py-2.5">{{ d.date }}</td>
                <td class="py-2.5"><StatusBadge :label="d.statut_label" :type="badgesDemande[d.statut]" /></td>
              </tr>
              <tr v-if="!donnees.demandes.length">
                <td colspan="4" class="py-6 text-center text-slate-400">Vous n'avez pas encore de demande.</td>
              </tr>
            </tbody>
          </table>
        </Panel>
      </div>
    </template>

    <!-- Actions rapides -->
    <div v-if="actions.length" class="mt-4 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
      <Link v-for="a in actions" :key="a.label" :href="a.href"
        class="flex items-center gap-4 rounded-2xl bg-white p-5 shadow-sm transition hover:shadow-md">
        <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl" :class="couleursAction[a.couleur]">
          <component :is="iconesAction[a.icone]" :size="22" />
        </div>
        <div class="min-w-0 flex-1">
          <p class="font-semibold text-blue-950">{{ a.label }}</p>
          <p class="truncate text-xs text-slate-500">{{ a.description }}</p>
        </div>
        <ArrowRight :size="16" class="text-slate-400" />
      </Link>
    </div>
  </AppLayout>
</template>