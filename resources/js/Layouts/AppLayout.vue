<script setup>
import { computed, ref, onMounted, onUnmounted } from 'vue'
import { Link, usePage, router } from '@inertiajs/vue3'
import {
  LayoutDashboard, Package, Boxes, ArrowLeftRight, Factory, Users,
  HardHat, ClipboardList, Truck, UserCog, LogOut, Search, Bell,
  Tags, Warehouse, FileText, Contact,
} from 'lucide-vue-next'


const user = computed(() => usePage().props.auth.user)

const all = ['admin', 'gestionnaire', 'chantier', 'controle']
const lecture = ['admin', 'gestionnaire', 'controle']
const flash = computed(() => usePage().props.flash ?? {})
const errors = computed(() => usePage().props.errors ?? {})
const alertes = computed(() => usePage().props.alertes ?? 0)
const recherche = ref('')
const peutChercher = computed(() => user.value.role !== 'chantier')

function chercher() {
  if (recherche.value.trim()) {
    router.get(route('articles.index'), { q: recherche.value.trim() })
  }
}

const menu = [
  { label: 'Tableau de bord', route: 'dashboard', icon: LayoutDashboard, roles: all },
  { label: 'Articles', route: 'articles.index', icon: Package, roles: lecture },
  { label: 'Catégories', route: 'categories.index', icon: Tags, roles: lecture },
  { label: 'Dépôts', route: 'entrepots.index', icon: Warehouse, roles: lecture },
  { label: 'Stocks', route: 'stocks.index', icon: Boxes, roles: lecture },
  { label: 'Mouvements', route: 'mouvements.index', icon: ArrowLeftRight, roles: lecture },
  { label: 'Fournisseurs', route: 'fournisseurs.index', icon: Factory, roles: ['admin', 'gestionnaire'] },
  { label: 'Clients', route: 'clients.index', icon: Users, roles: lecture },
  { label: 'Chantiers', route: 'chantiers.index', icon: HardHat, roles: all },
  { label: 'Demandes', route: 'demandes.index', icon: FileText, roles: all },
  { label: 'Commandes', route: 'commandes.index', icon: ClipboardList, roles: lecture },
  { label: 'Livraisons', route: 'livraisons.index', icon: Truck, roles: lecture },
  { label: 'Livreurs', route: 'livreurs.index', icon: Contact, roles: lecture },
  { label: 'Utilisateurs', route: 'users.index', icon: UserCog, roles: ['admin'] },
]

const visibleMenu = computed(() => menu.filter((m) => m.roles.includes(user.value.role)))

const href = (item) => (route().has(item.route) ? route(item.route) : '#')
const isActive = (item) => route().has(item.route) && route().current(item.route.replace('.index', '.*'))
const notifs = computed(() => usePage().props.notifications ?? { non_lues: 0, recentes: [] })
const ouvert = ref(false)

// Rafraîchit la cloche toutes les 15 secondes, sans recharger la page
let minuteur
onMounted(() => {
  minuteur = setInterval(() => router.reload({ only: ['notifications', 'alertes'] }), 15000)
})
onUnmounted(() => clearInterval(minuteur))
</script>

<template>
  <div class="min-h-screen flex bg-[#eef5ff] text-slate-800">
    <!-- Menu latéral -->
    <aside
      class="hidden md:flex w-64 shrink-0 flex-col p-4 sticky top-0 h-screen"
      style="background: linear-gradient(180deg, #dbeafe 0%, #93c5fd 55%, #60a5fa 100%)"
    >
      <div class="pt-3 pb-2 mb-2">
        <img src="/images/logo.png" alt="BatiStock" class="w-4/5 h-auto object-contain max-h-14" />
      </div>

      <nav class="flex-1 space-y-0.5 overflow-y-auto [&::-webkit-scrollbar]:hidden [-ms-overflow-style:none] [scrollbar-width:none]">
        <Link
          v-for="item in visibleMenu"
          :key="item.label"
          :href="href(item)"
          class="flex items-center gap-3 rounded-xl px-4 py-2 text-sm font-medium transition"
          :class="isActive(item)
            ? 'bg-white text-blue-700 shadow-sm'
            : 'text-blue-950/80 hover:bg-white/50'"
        >
          <component :is="item.icon" :size="18" />
          {{ item.label }}
        </Link>
      </nav>

      <Link
        :href="route('logout')"
        method="post"
        as="button"
        class="mt-4 flex items-center gap-3 rounded-xl px-4 py-2.5 text-sm font-medium text-blue-950/80 hover:bg-white/50"
      >
        <LogOut :size="18" /> Déconnexion
      </Link>
    </aside>

    <!-- Contenu -->
    <div class="flex-1 min-w-0">
      <header class="flex items-center justify-between gap-4 px-8 py-4">
        <div class="relative w-full max-w-md" :class="{ invisible: !peutChercher }">
          <Search :size="16" class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400" />
          <input
            v-model="recherche"
            @keyup.enter="chercher"
            type="text"
            placeholder="Rechercher un article..."
            class="w-full rounded-xl border-0 bg-white py-2.5 pl-10 pr-4 text-sm shadow-sm focus:ring-2 focus:ring-blue-400"
          />
        </div>
        <div class="flex items-center gap-5">
          <div class="relative">
            <button type="button" @click="ouvert = !ouvert" class="relative" title="Notifications">
              <Bell :size="20" class="text-slate-500" />
              <span v-if="notifs.non_lues > 0"
                class="absolute -right-2 -top-2 flex h-4 min-w-4 items-center justify-center rounded-full bg-red-500 px-1 text-[10px] font-semibold text-white">
                {{ notifs.non_lues }}
              </span>
            </button>

            <div v-if="ouvert" class="fixed inset-0 z-40" @click="ouvert = false"></div>
            <div v-if="ouvert" class="absolute right-0 z-50 mt-3 w-80 overflow-hidden rounded-2xl bg-white shadow-lg ring-1 ring-slate-100">
              <div class="flex items-center justify-between border-b border-slate-100 px-4 py-3">
                <p class="text-sm font-semibold text-blue-950">Notifications</p>
                <Link v-if="notifs.non_lues > 0" :href="route('notifications.lues')" method="patch" as="button" preserve-scroll
                  class="text-xs font-medium text-blue-600 hover:underline">
                  Tout marquer comme lu
                </Link>
              </div>

              <ul class="max-h-80 divide-y divide-slate-100 overflow-y-auto">
                <li v-for="n in notifs.recentes" :key="n.id">
                  <Link :href="route('notifications.lire', n.id)" method="patch" as="button" @click="ouvert = false"
                    class="block w-full px-4 py-3 text-left hover:bg-slate-50" :class="n.lu ? '' : 'bg-blue-50/60'">
                    <p class="text-sm" :class="n.lu ? 'text-slate-600' : 'font-semibold text-blue-950'">{{ n.titre }}</p>
                    <p class="text-xs text-slate-500">{{ n.message }}</p>
                    <p class="mt-1 text-[11px] text-slate-400">{{ n.date }}</p>
                  </Link>
                </li>
                <li v-if="!notifs.recentes.length" class="px-4 py-6 text-center text-sm text-slate-400">Aucune notification.</li>
              </ul>

              <Link v-if="alertes > 0" :href="route('articles.index', { statut: 'faible' })" @click="ouvert = false"
                class="block border-t border-slate-100 bg-red-50 px-4 py-3 text-sm text-red-700">
                {{ alertes }} article(s) en stock faible
              </Link>
            </div>
          </div>
          <div class="flex items-center gap-2">
            <div class="h-9 w-9 rounded-full bg-blue-600 text-white flex items-center justify-center text-sm font-semibold">
              {{ user.name.charAt(0).toUpperCase() }}
            </div>
            <span class="text-sm font-medium">{{ user.name }}</span>
          </div>
        </div>
      </header>

      <main class="px-8 pb-10">
        <div v-if="flash.success" class="mb-4 rounded-xl bg-emerald-100 px-4 py-3 text-sm text-emerald-800">
  {{ flash.success }}
</div>
<div v-if="errors.suppression || errors.statut" class="mb-4 rounded-xl bg-red-100 px-4 py-3 text-sm text-red-800">
  {{ errors.suppression ?? errors.statut }}
</div>
        <slot />
      </main>
    </div>
  </div>
</template>