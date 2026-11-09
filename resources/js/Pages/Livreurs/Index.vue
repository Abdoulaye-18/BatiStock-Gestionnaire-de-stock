<script setup>
import { Head } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import ReferentielPage from '@/Components/ReferentielPage.vue'

defineProps({ livreurs: Array, peutModifier: Boolean })

const fields = [
  { key: 'nom', label: 'Nom' },
  { key: 'telephone', label: 'Téléphone' },
  {
    key: 'disponible', label: 'Disponibilité', type: 'select', required: true, default: 1, display: 'disponible_label',
    options: [{ value: 1, label: 'Disponible' }, { value: 0, label: 'Indisponible' }],
  },
]
</script>

<template>
  <Head title="Livreurs" />
  <AppLayout>
    <ReferentielPage title="Livreurs" subtitle="Équipe de livraison" base="livreurs"
      :rows="livreurs" :fields="fields" :can-edit="peutModifier" />
  </AppLayout>
</template>