<script setup>
import { ref } from 'vue'
import { Head, Link, useForm } from '@inertiajs/vue3'

defineProps({
    canResetPassword: { type: Boolean, default: true },
    status: String,
})

const form = useForm({
    email: '',
    password: '',
    remember: true,
})

const showPassword = ref(false)

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    })
}
</script>

<template>
    <Head title="Connexion" />

    <!-- Image plein écran en arrière-plan (bâtiment bien visible en bas à gauche) -->
    <div
        class="fixed inset-0 overflow-hidden bg-cover bg-no-repeat"
        style="background-image: url('/images/login-bg.jpg'); background-position: left bottom"
    >
        <!-- Voile léger : plus présent en haut, quasi nul en bas pour garder l'image nette -->
        <div
            class="absolute inset-0"
            style="background: linear-gradient(to bottom, rgba(236,245,255,0.50) 0%, rgba(236,245,255,0.18) 50%, rgba(236,245,255,0) 100%)"
        ></div>

        <!-- Formes décoratives discrètes -->
        <div class="absolute -top-20 -left-20 h-64 w-64 rounded-full bg-blue-300/20"></div>
        <div class="absolute -bottom-24 -right-16 h-64 w-64 rounded-full bg-blue-400/25"></div>
        <div class="absolute -bottom-32 right-24 h-72 w-72 rounded-full bg-blue-300/20"></div>
        <div class="absolute top-0 right-0 h-40 w-40 rounded-bl-full bg-blue-200/40"></div>
    </div>

    <!-- Contenu : fixé à l'écran, jamais de scroll -->
    <div class="fixed inset-0 z-10 flex items-center justify-center overflow-hidden px-6">
        <div class="grid w-full max-w-6xl gap-6 lg:grid-cols-[1fr_520px] lg:items-center">

            <!-- ===== COLONNE GAUCHE ===== -->
            <div class="hidden flex-col items-center justify-center px-6 text-center lg:flex">
                <img src="/images/logo.png" alt="BatiStock" class="h-[clamp(5.5rem,16vh,8.5rem)] w-auto object-contain" />

                <!-- Texte collé sous le logo, centré par rapport à lui -->
                <p class="mt-1 text-lg font-semibold text-[#12306e]">
                    Gérez intelligemment vos matériaux,<br />construisez sereinement.
                </p>

                <ul class="mt-[clamp(1rem,3vh,1.75rem)] w-fit space-y-[clamp(0.5rem,1.6vh,1rem)] text-left text-base text-[#12306e]">
                    <li class="flex items-center gap-3 rounded-2xl bg-white/60 py-2 pl-2 pr-5">
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-blue-100 text-blue-600">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M12 3 4 7.5v9L12 21l8-4.5v-9L12 3Z"/><path d="M4 7.5 12 12l8-4.5M12 12v9"/>
                            </svg>
                        </span>
                        <span>Gestion des stocks<br />en temps réel</span>
                    </li>
                    <li class="flex items-center gap-3 rounded-2xl bg-white/60 py-2 pl-2 pr-5">
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-blue-100 text-blue-600">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M4 20V14M10 20V9M16 20v-6M22 20V5"/>
                            </svg>
                        </span>
                        <span>Suivi des commandes<br />et livraisons</span>
                    </li>
                    <li class="flex items-center gap-3 rounded-2xl bg-white/60 py-2 pl-2 pr-5">
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-blue-100 text-blue-600">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M12 3 5 6v5c0 4.5 3 8 7 10 4-2 7-5.5 7-10V6l-7-3Z"/><path d="m9 12 2 2 4-4"/>
                            </svg>
                        </span>
                        <span>Une plateforme sécurisée</span>
                    </li>
                </ul>
            </div>

            <!-- ===== FORMULAIRE (agrandi, s'adapte à la hauteur de l'écran) ===== -->
            <div class="w-full rounded-3xl bg-white px-12 py-[clamp(1.5rem,5vh,3rem)] shadow-[0_20px_60px_-10px_rgba(30,64,175,0.25)]">

                <h2 class="text-center text-3xl font-extrabold text-[#0b2a6b]">Connectez-vous</h2>
                <p class="mt-1 text-center text-base text-slate-500"> Accéder a votre espace BatiStock </p>

                <div v-if="status" class="mt-3 rounded-lg bg-green-50 px-3 py-2 text-center text-sm text-green-700">
                    {{ status }}
                </div>

                <form class="mt-[clamp(1rem,3vh,1.75rem)] space-y-[clamp(0.75rem,2.2vh,1.25rem)]" @submit.prevent="submit">

                    <!-- Email -->
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

                    <!-- Mot de passe -->
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
                                placeholder="Mot de passe"
                                autocomplete="current-password"
                                required
                                class="w-full border-0 bg-transparent p-0 text-base text-[#0b2a6b] placeholder-slate-400 focus:outline-none focus:ring-0"
                            />
                            <button
                                type="button"
                                class="shrink-0 text-slate-400 hover:text-slate-600"
                                :aria-label="showPassword ? 'Masquer le mot de passe' : 'Afficher le mot de passe'"
                                @click="showPassword = !showPassword"
                            >
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

                    <!-- Se souvenir / oublié -->
                    <div class="flex items-center justify-between text-base">
                        <label class="flex cursor-pointer items-center gap-2 text-[#0b2a6b]">
                            <input v-model="form.remember" type="checkbox" class="h-5 w-5 rounded border-slate-300 text-blue-600 focus:ring-blue-500" />
                            Se souvenir de moi
                        </label>
                        <Link v-if="canResetPassword" :href="route('password.request')" class="text-blue-600 hover:underline">
                            Mot de passe oublié ?
                        </Link>
                    </div>

                    <!-- Bouton connexion -->
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="flex w-full items-center justify-center gap-2 rounded-xl bg-blue-600 py-[clamp(0.75rem,2vh,1rem)] text-base font-semibold text-white shadow transition hover:bg-blue-700 disabled:opacity-60"
                    >
                        <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M14 4h4a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2h-4"/><path d="M3 12h11m0 0-4-4m4 4-4 4"/>
                        </svg>
                        {{ form.processing ? 'Connexion…' : 'Se connecter' }}
                    </button>

                </form>
            </div>

        </div>
    </div>
</template>