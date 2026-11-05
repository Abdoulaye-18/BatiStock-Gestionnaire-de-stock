<script setup>
import { Head } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import ReferentielPage from '@/Components/ReferentielPage.vue'

defineProps({ entrepots: Array, peutModifier: Boolean })

const fields = [
  { key: 'code', label: 'Code' },
  { key: 'nom', label: 'Nom' },
  { key: 'localisation', label: 'Localisation' },
]
</script>

<template>
  <Head title="Dépôts" />
  <AppLayout>
    <ReferentielPage title="Dépôts" subtitle="Lieux de stockage" base="entrepots"
      :rows="entrepots" :fields="fields" :can-edit="peutModifier" />
  </AppLayout>
</template>