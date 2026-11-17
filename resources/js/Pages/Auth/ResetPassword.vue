<script setup>
import { ref } from 'vue'
import { Head, useForm } from '@inertiajs/vue3'
import BatiAuthLayout from '@/Layouts/BatiAuthLayout.vue'

const props = defineProps({
    email: { type: String, required: true },
    token: { type: String, required: true },
})

const form = useForm({
    token: props.token,
    email: props.email,
    password: '',
    password_confirmation: '',
})

const showPassword = ref(false)

const submit = () => {
    form.post(route('password.store'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    })
}
</script>

<template>
    <Head title="Nouveau mot de passe" />

    <BatiAuthLayout title="Nouveau mot de passe" subtitle="Choisissez un mot de passe sécurisé">
        <form class="mt-[clamp(1rem,3vh,1.75rem)] space-y-[clamp(0.75rem,2.2vh,1.25rem)]" @submit.prevent="submit">

            <!-- Email (lecture seule) -->
            <div>
                <div class="flex items-center gap-3 rounded-xl border border-slate-200 bg-slate-50 px-4 py-[clamp(0.7rem,2vh,1rem)]">
                    <svg class="h-6 w-6 shrink-0 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="5" width="18" height="14" rx="2.5"/><path d="m4 7 8 6 8-6"/>
                    </svg>
                    <input v-model="form.email" type="email" readonly class="w-full border-0 bg-transparent p-0 text-base text-slate-500 focus:outline-none focus:ring-0" />
                </div>
                <p v-if="form.errors.email" class="mt-1 text-xs text-red-600">{{ form.errors.email }}</p>
            </div>

            <!-- Nouveau mot de passe -->
            <div>
                <div
                    class="flex items-center gap-3 rounded-xl border px-4 py-[clamp(0.7rem,2vh,1rem)] transition focus-within:border-blue-500 focus-within:ring-1 focus-within:ring-blue-300"
                    :class="form.errors.password ? 'border-red-400' : 'border-slate-200'"
                >
                    <svg class="h-6 w-6 shrink-0 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="4.5" y="10" width="15" height="10.5" rx="2.5"/><path d="M8 10V7.5a4 4 0 0 1 8 0V10"/><circle cx="12" cy="15" r="1" fill="currentColor"/>
                    </svg>
                    <input
                        v-model="form.password"
                        :type="showPassword ? 'text' : 'password'"
                        placeholder="Nouveau mot de passe"
                        autocomplete="new-password"
                        required
                        autofocus
                        class="w-full border-0 bg-transparent p-0 text-base text-[#0b2a6b] placeholder-slate-400 focus:outline-none focus:ring-0"
                    />
                    <button type="button" class="shrink-0 text-slate-400 hover:text-slate-600" @click="showPassword = !showPassword">
                        <svg v-if="!showPassword" class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/><path d="m4 4 16 16"/>
                        </svg>
                        <svg v-else class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/>
                        </svg>
                    </button>
                </div>
                <p v-if="form.errors.password" class="mt-1 text-xs text-red-600">{{ form.errors.password }}</p>
            </div>

            <!-- Confirmation -->
            <div>
                <div
                    class="flex items-center gap-3 rounded-xl border px-4 py-[clamp(0.7rem,2vh,1rem)] transition focus-within:border-blue-500 focus-within:ring-1 focus-within:ring-blue-300"
                    :class="form.errors.password_confirmation ? 'border-red-400' : 'border-slate-200'"
                >
                    <svg class="h-6 w-6 shrink-0 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="4.5" y="10" width="15" height="10.5" rx="2.5"/><path d="M8 10V7.5a4 4 0 0 1 8 0V10"/><circle cx="12" cy="15" r="1" fill="currentColor"/>
                    </svg>
                    <input
                        v-model="form.password_confirmation"
                        :type="showPassword ? 'text' : 'password'"
                        placeholder="Confirmer le mot de passe"
                        autocomplete="new-password"
                        required
                        class="w-full border-0 bg-transparent p-0 text-base text-[#0b2a6b] placeholder-slate-400 focus:outline-none focus:ring-0"
                    />
                </div>
                <p v-if="form.errors.password_confirmation" class="mt-1 text-xs text-red-600">{{ form.errors.password_confirmation }}</p>
            </div>

            <button
                type="submit"
                :disabled="form.processing"
                class="flex w-full items-center justify-center rounded-xl bg-blue-600 py-[clamp(0.75rem,2vh,1rem)] text-base font-semibold text-white shadow transition hover:bg-blue-700 disabled:opacity-60"
            >
                {{ form.processing ? 'Enregistrement…' : 'Réinitialiser le mot de passe' }}
            </button>
        </form>
    </BatiAuthLayout>
</template>