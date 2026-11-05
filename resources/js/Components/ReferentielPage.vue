<script setup>
import { ref } from 'vue'
import { useForm, router } from '@inertiajs/vue3'
import { Plus, Pencil, Trash2 } from 'lucide-vue-next'
import Modal from '@/Components/Modal.vue'
import TextInput from '@/Components/TextInput.vue'
import InputLabel from '@/Components/InputLabel.vue'
import InputError from '@/Components/InputError.vue'
import PrimaryButton from '@/Components/PrimaryButton.vue'
import SecondaryButton from '@/Components/SecondaryButton.vue'

const props = defineProps({
  title: String,
  subtitle: String,
  base: String, // ex. 'categories' : sert à construire categories.store, etc.
  rows: Array,
  fields: Array, // [{ key, label }]
  canEdit: Boolean,
})

const show = ref(false)
const editing = ref(null)
const form = useForm(Object.fromEntries(props.fields.map((f) => [f.key, ''])))

function openCreate() {
  editing.value = null
  form.reset()
  form.clearErrors()
  show.value = true
}

function openEdit(row) {
  editing.value = row
  form.clearErrors()
  props.fields.forEach((f) => (form[f.key] = row[f.key] ?? ''))
  show.value = true
}

function submit() {
  const opts = { preserveScroll: true, onSuccess: () => { show.value = false; form.reset() } }
  editing.value
    ? form.put(route(`${props.base}.update`, editing.value.id), opts)
    : form.post(route(`${props.base}.store`), opts)
}

function remove(row) {
  if (confirm('Supprimer cet élément ?')) {
    router.delete(route(`${props.base}.destroy`, row.id), { preserveScroll: true })
  }
}
</script>

<template>
  <div class="mb-6 flex items-end justify-between">
    <div>
      <h1 class="text-3xl font-bold text-blue-950">{{ title }}</h1>
      <p class="text-slate-500">{{ subtitle }}</p>
    </div>
    <button v-if="canEdit" @click="openCreate"
      class="flex items-center gap-2 rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-medium text-white shadow-sm hover:bg-blue-700">
      <Plus :size="16" /> Nouveau
    </button>
  </div>

  <div class="overflow-x-auto rounded-2xl bg-white p-4 shadow-sm">
    <table class="w-full text-left text-sm">
      <thead class="text-slate-500">
        <tr>
          <th v-for="f in fields" :key="f.key" class="px-3 py-2 font-medium">{{ f.label }}</th>
          <th v-if="canEdit" class="px-3 py-2 text-right font-medium">Actions</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="row in rows" :key="row.id" class="border-t border-slate-100">
          <td v-for="f in fields" :key="f.key" class="px-3 py-3">{{ row[f.key] || '-' }}</td>
          <td v-if="canEdit" class="px-3 py-3">
            <div class="flex justify-end gap-3 text-slate-500">
              <button @click="openEdit(row)" class="hover:text-blue-600"><Pencil :size="16" /></button>
              <button @click="remove(row)" class="hover:text-red-600"><Trash2 :size="16" /></button>
            </div>
          </td>
        </tr>
        <tr v-if="!rows.length">
          <td :colspan="fields.length + 1" class="px-3 py-8 text-center text-slate-400">Aucun élément.</td>
        </tr>
      </tbody>
    </table>
  </div>

  <Modal :show="show" max-width="md" @close="show = false">
    <form @submit.prevent="submit" class="space-y-4 p-6">
      <h2 class="text-lg font-semibold text-blue-950">{{ editing ? 'Modifier' : 'Nouveau' }}</h2>
      <div v-for="f in fields" :key="f.key">
        <InputLabel :for="f.key" :value="f.label" />
        <TextInput :id="f.key" v-model="form[f.key]" class="mt-1 block w-full" />
        <InputError class="mt-1" :message="form.errors[f.key]" />
      </div>
      <div class="flex justify-end gap-3 pt-2">
        <SecondaryButton type="button" @click="show = false">Annuler</SecondaryButton>
        <PrimaryButton :disabled="form.processing">Enregistrer</PrimaryButton>
      </div>
    </form>
  </Modal>
</template>