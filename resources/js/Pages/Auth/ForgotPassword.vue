<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3'
import BatiAuthLayout from '@/Layouts/BatiAuthLayout.vue'

defineProps({ status: String })

const form = useForm({ email: '' })

const submit = () => form.post(route('password.email'))
</script>

<template>
    <Head title="Mot de passe oublié" />

    <BatiAuthLayout
        title="Mot de passe oublié ?"
        subtitle="Saisissez votre email pour recevoir un lien de réinitialisation"
    >
        <div v-if="status" class="mt-4 rounded-lg bg-green-50 px-3 py-2 text-center text-sm text-green-700">
            {{ status }}
        </div>

        <form class="mt-[clamp(1rem,3vh,1.75rem)] space-y-[clamp(0.75rem,2.2vh,1.25rem)]" @submit.prevent="submit">
            <div>
                <div
                    class="flex items-center gap-3 rounded-xl border px-4 py-[clamp(0.7rem,2vh,1rem)] transition focus-within:border-blue-500 focus-within:ring-1 focus-within:ring-blue-300"
                    :class="form.errors.email ? 'border-red-400' : 'border-slate-200'"
                >
                    <svg class="h-6 w-6 shrink-0 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="5" width="18" height="14" rx="2.5"/><path d="m4 7 8 6 8-6"/>
                    </svg>
                    <input
                        v-model="form.email"
                        type="email"
                        placeholder="Adresse email"
                        autocomplete="username"
                        required
                        autofocus
                        class="w-full border-0 bg-transparent p-0 text-base text-[#0b2a6b] placeholder-slate-400 focus:outline-none focus:ring-0"
                    />
                </div>
                <p v-if="form.errors.email" class="mt-1 text-xs text-red-600">{{ form.errors.email }}</p>
            </div>

            <button
                type="submit"
                :disabled="form.processing"
                class="flex w-full items-center justify-center rounded-xl bg-blue-600 py-[clamp(0.75rem,2vh,1rem)] text-base font-semibold text-white shadow transition hover:bg-blue-700 disabled:opacity-60"
            >
                {{ form.processing ? 'Envoi…' : 'Envoyer le lien' }}
            </button>

            <div class="text-center">
                <Link :href="route('login')" class="text-base text-blue-600 hover:underline">
                    Retour à la connexion
                </Link>
            </div>
        </form>
    </BatiAuthLayout>
</template>