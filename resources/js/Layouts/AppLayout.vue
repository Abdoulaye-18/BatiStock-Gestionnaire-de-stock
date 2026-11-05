<script setup>
import { computed } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'
import {
  LayoutDashboard, Package, Boxes, ArrowLeftRight, Factory, Users,
  HardHat, ClipboardList, Truck, UserCog, LogOut, Search, Bell, Box,
  Tags, Warehouse,
} from 'lucide-vue-next'

const user = computed(() => usePage().props.auth.user)

const all = ['admin', 'gestionnaire', 'chantier', 'controle']
const lecture = ['admin', 'gestionnaire', 'controle']
const flash = computed(() => usePage().props.flash ?? {})
const errors = computed(() => usePage().props.errors ?? {})

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
  { label: 'Commandes', route: 'commandes.index', icon: ClipboardList, roles: lecture },
  { label: 'Livraisons', route: 'livraisons.index', icon: Truck, roles: lecture },
  { label: 'Utilisateurs', route: 'users.index', icon: UserCog, roles: ['admin'] },
]

const visibleMenu = computed(() => menu.filter((m) => m.roles.includes(user.value.role)))

const href = (item) => (route().has(item.route) ? route(item.route) : '#')
const isActive = (item) => route().has(item.route) && route().current(item.route.replace('.index', '.*'))
</script>

<template>
  <div class="min-h-screen flex bg-[#eef5ff] text-slate-800">
    <!-- Menu latéral -->
    <aside
      class="hidden md:flex w-64 shrink-0 flex-col p-4 sticky top-0 h-screen"
      style="background: linear-gradient(180deg, #dbeafe 0%, #93c5fd 55%, #60a5fa 100%)"
    >
      <div class="flex items-center gap-3 px-2 py-3 mb-4">
        <div class="h-10 w-10 rounded-xl bg-blue-600 flex items-center justify-center text-white">
          <Box :size="22" />
        </div>
        <div>
          <p class="text-lg font-bold text-blue-950 leading-tight">BatiStock</p>
          <p class="text-[11px] text-blue-900/70 leading-tight">Gestion des stocks &amp; livraisons</p>
        </div>
      </div>

      <nav class="flex-1 space-y-1 overflow-y-auto">
        <Link
          v-for="item in visibleMenu"
          :key="item.label"
          :href="href(item)"
          class="flex items-center gap-3 rounded-xl px-4 py-2.5 text-sm font-medium transition"
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
        <div class="relative w-full max-w-md">
          <Search :size="16" class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400" />
          <input
            type="text"
            placeholder="Rechercher un article, une commande, un client..."
            class="w-full rounded-xl border-0 bg-white py-2.5 pl-10 pr-4 text-sm shadow-sm focus:ring-2 focus:ring-blue-400"
          />
        </div>
        <div class="flex items-center gap-5">
          <Bell :size="20" class="text-slate-500" />
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
<div v-if="errors.suppression" class="mb-4 rounded-xl bg-red-100 px-4 py-3 text-sm text-red-800">
  {{ errors.suppression }}
</div>
        <slot />
      </main>
    </div>
  </div>
</template>