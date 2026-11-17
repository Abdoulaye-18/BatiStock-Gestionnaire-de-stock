<script setup>
defineProps({
    title: String,
    subtitle: String,
})
</script>

<template>
    <!-- Fond identique au login -->
    <div
        class="fixed inset-0 overflow-hidden bg-cover bg-no-repeat"
        style="background-image: url('/images/login-bg.jpg'); background-position: left bottom"
    >
        <div
            class="absolute inset-0"
            style="background: linear-gradient(to bottom, rgba(236,245,255,0.50) 0%, rgba(236,245,255,0.18) 50%, rgba(236,245,255,0) 100%)"
        ></div>
        <div class="absolute -top-20 -left-20 h-64 w-64 rounded-full bg-blue-300/20"></div>
        <div class="absolute -bottom-24 -right-16 h-64 w-64 rounded-full bg-blue-400/25"></div>
        <div class="absolute top-0 right-0 h-40 w-40 rounded-bl-full bg-blue-200/40"></div>
    </div>

    <!-- Carte centrée, sans scroll -->
    <div class="fixed inset-0 z-10 flex items-center justify-center overflow-hidden px-6">
        <div class="w-full max-w-[520px] rounded-3xl bg-white px-12 py-[clamp(1.5rem,5vh,3rem)] shadow-[0_20px_60px_-10px_rgba(30,64,175,0.25)]">
            <img src="/images/logo.png" alt="BatiStock" class="mx-auto h-[clamp(3.5rem,10vh,5.5rem)] w-auto object-contain" />

            <h2 class="mt-[clamp(0.5rem,2vh,1rem)] text-center text-3xl font-extrabold text-[#0b2a6b]">{{ title }}</h2>
            <p class="mt-1 text-center text-base text-slate-500">{{ subtitle }}</p>

            <slot />
        </div>
    </div>
</template>