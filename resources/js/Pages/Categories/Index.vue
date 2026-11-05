<script setup>
import { Head } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import ReferentielPage from '@/Components/ReferentielPage.vue'

defineProps({ categories: Array, peutModifier: Boolean })

const fields = [
  { key: 'code', label: 'Code' },
  { key: 'nom', label: 'Nom' },
]
</script>

<template>
  <Head title="Catégories" />
  <AppLayout>
    <ReferentielPage title="Catégories" subtitle="Familles de matériaux" base="categories"
      :rows="categories" :fields="fields" :can-edit="peutModifier" />
  </AppLayout>
</template>