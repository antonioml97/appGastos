<template>
    <span
        class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl border border-white/10 bg-white/8 shadow-lg shadow-black/10"
        :style="wrapperStyle"
    >
        <img
            v-if="iconSrc"
            :src="iconSrc"
            :alt="alt"
            class="h-5 w-5 object-contain"
        >
        <span
            v-else
            class="h-3.5 w-3.5 rounded-full border border-white/15"
            :style="{ backgroundColor: color || '#f7c45e' }"
        ></span>
    </span>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    icon: { type: String, default: '' },
    color: { type: String, default: '#f7c45e' },
    alt: { type: String, default: 'Icono de categoría' },
});

const iconSrc = computed(() => {
    if (!props.icon) {
        return '';
    }

    const normalizedIcon = /\.[a-z0-9]+$/i.test(props.icon)
        ? props.icon
        : `${props.icon}.svg`;

    return `/images/icons/${normalizedIcon}`;
});

const wrapperStyle = computed(() => props.icon
    ? { boxShadow: `inset 0 0 0 1px ${props.color}22` }
    : {});
</script>
