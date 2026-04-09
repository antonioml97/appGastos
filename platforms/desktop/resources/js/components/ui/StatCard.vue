<template>
    <article class="min-w-0 rounded-[1.5rem] border border-white/10 bg-[linear-gradient(180deg,rgba(255,255,255,0.09),rgba(255,255,255,0.035))] p-4 shadow-lg shadow-black/10 transition hover:-translate-y-0.5 hover:bg-white/10 sm:p-5">
        <p class="text-[11px] uppercase tracking-[0.28em] sm:text-sm" :class="accent">{{ label }}</p>
        <p class="mt-3 text-[clamp(1.6rem,3vw,2.25rem)] leading-[0.95] font-bold tracking-tight [text-wrap:balance]" :class="valueClass || 'text-white'">{{ value }}</p>
    </article>
</template>

<script setup>
defineProps({
    label: { type: String, required: true },
    value: { type: String, required: true },
    accent: { type: String, default: 'text-white/70' },
    valueClass: { type: String, default: '' },
});
</script>
