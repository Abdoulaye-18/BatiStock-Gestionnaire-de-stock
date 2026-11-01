<script setup>
import { Head } from '@inertiajs/vue3'
import { Package, Boxes, ShoppingCart, Truck, TriangleAlert } from 'lucide-vue-next'
import AppLayout from '@/Layouts/AppLayout.vue'
import StatCard from '@/Components/StatCard.vue'
import StatusBadge from '@/Components/StatusBadge.vue'
</script>

<template>
  <Head title="Tableau de bord" />
  <AppLayout>
    <h1 class="text-3xl font-bold text-blue-950">Tableau de bord</h1>
    <p class="mb-6 text-slate-500">Voici un aperçu de l'activité de votre entreprise.</p>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-5">
      <StatCard title="Articles" :value="124" subtitle="au catalogue" :icon="Package" color="blue" />
      <StatCard title="Stock total" :value="18450" subtitle="unités" :icon="Boxes" color="green" />
      <StatCard title="Commandes en cours" :value="5" subtitle="clients" :icon="ShoppingCart" color="blue" />
      <StatCard title="Livraisons en cours" :value="3" subtitle="en cours" :icon="Truck" color="blue" />
      <StatCard title="Stock faible" :value="8" subtitle="articles" :icon="TriangleAlert" color="red" />
    </div>

    <div class="mt-6 flex gap-2">
      <StatusBadge label="Entrée" type="success" />
      <StatusBadge label="Sortie" type="danger" />
      <StatusBadge label="Faible" type="warning" />
      <StatusBadge label="En cours" type="info" />
      <StatusBadge label="Planifiée" type="purple" />
    </div>
  </AppLayout>
</template>