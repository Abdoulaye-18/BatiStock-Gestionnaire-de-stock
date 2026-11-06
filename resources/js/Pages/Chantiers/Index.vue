<script setup>
import { computed } from 'vue'
import { Head } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import ReferentielPage from '@/Components/ReferentielPage.vue'

const props = defineProps({
  chantiers: Array,
  responsables: Array,
  avecResponsable: Boolean,
  peutCreer: Boolean,
  peutSupprimer: Boolean,
})

const fields = computed(() => [
  { key: 'nom', label: 'Nom du chantier' },
  { key: 'localisation', label: 'Localisation' },
  {
    key: 'etat', label: 'État', type: 'select', required: true, default: 'actif', display: 'etat_label',
    options: [{ value: 'actif', label: 'Actif' }, { value: 'termine', label: 'Terminé' }],
  },
  ...(props.avecResponsable
    ? [{
        key: 'responsable_id', label: 'Responsable', type: 'select', display: 'responsable_nom',
        options: props.responsables.map((r) => ({ value: r.id, label: r.name })),
      }]
    : []),
])
</script>

<template>
  <Head title="Chantiers" />
  <AppLayout>
    <ReferentielPage title="Chantiers" subtitle="Chantiers internes de l'entreprise" base="chantiers"
      :rows="chantiers" :fields="fields" :can-edit="peutCreer" :can-delete="peutSupprimer" />
  </AppLayout>
</template>