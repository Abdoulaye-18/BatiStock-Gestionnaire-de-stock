<script setup>
import { Head } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import ReferentielPage from '@/Components/ReferentielPage.vue'

defineProps({ fournisseurs: Array, peutModifier: Boolean })

const fields = [
  { key: 'nom', label: 'Nom' },
  { key: 'telephone', label: 'Téléphone' },
  { key: 'email', label: 'Email' },
  { key: 'adresse', label: 'Adresse' },
]
</script>

<template>
  <Head title="Fournisseurs" />
  <AppLayout>
    <ReferentielPage title="Fournisseurs" subtitle="Sources d'approvisionnement" base="fournisseurs"
      :rows="fournisseurs" :fields="fields" :can-edit="peutModifier" />
  </AppLayout>
</template>