<script setup>
import { Head } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import ReferentielPage from '@/Components/ReferentielPage.vue'

defineProps({ clients: Array, peutModifier: Boolean })

const fields = [
  { key: 'nom', label: 'Nom' },
  { key: 'telephone', label: 'Téléphone' },
  { key: 'email', label: 'Email' },
  { key: 'adresse_livraison', label: 'Adresse de livraison' },
]
</script>

<template>
  <Head title="Clients" />
  <AppLayout>
    <ReferentielPage title="Clients" subtitle="Clients et adresses de livraison" base="clients"
      :rows="clients" :fields="fields" :can-edit="peutModifier" />
  </AppLayout>
</template>