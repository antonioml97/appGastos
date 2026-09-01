<template>
    <section class="app-page grid items-start gap-3 xl:grid-cols-[1.35fr_0.95fr]">
        <section class="space-y-3">
            <section class="rounded-[2rem] border border-white/10 bg-white/6 p-6 shadow-xl backdrop-blur-lg sm:p-8">
                <div class="flex flex-col gap-6 xl:flex-row xl:items-end xl:justify-between">
                    <div class="min-w-0">
                        <p class="text-sm uppercase tracking-[0.3em] text-[var(--color-gold)]/85">Seguimiento anual</p>
                        <h2 class="mt-3 font-[var(--font-display)] text-[clamp(2.4rem,5vw,4rem)] leading-none font-bold">
                            Balance {{ state.selectedYear }}
                        </h2>
                        <p class="mt-4 max-w-2xl text-base leading-8 text-white/72">
                            Vista global del año con ingresos, gastos y balance acumulado para entender cómo evoluciona el año completo.
                        </p>
                    </div>

                    <div class="w-full max-w-sm rounded-[1.5rem] border border-white/10 bg-[var(--color-ink-soft)]/45 p-4">
                        <label class="mb-3 block text-sm font-semibold text-white/78">Cambiar año</label>
                        <div class="grid grid-cols-[1fr_auto] gap-3">
                            <select
                                v-model="localYear"
                                class="w-full rounded-2xl border border-white/10 bg-[var(--color-ink-soft)]/80 px-4 py-3 text-base text-white outline-none transition focus:border-[var(--color-gold)]/60"
                            >
                                <option v-for="year in state.years" :key="year" :value="year">{{ year }}</option>
                            </select>
                            <button
                                type="button"
                                class="rounded-2xl bg-white/12 px-5 py-3 text-sm font-semibold text-white transition hover:bg-white/18"
                                @click="$emit('change-year')"
                            >
                                Ver
                            </button>
                        </div>
                    </div>
                </div>

                <div class="mt-8 grid gap-4 sm:grid-cols-2">
                    <article class="rounded-[1.75rem] border border-emerald-400/15 bg-[linear-gradient(180deg,rgba(48,214,169,0.14),rgba(255,255,255,0.03))] p-5">
                        <p class="text-sm uppercase tracking-[0.28em] text-[var(--color-mint)]">Ingresos</p>
                        <p class="mt-4 text-[clamp(1.9rem,3.4vw,2.8rem)] leading-[0.95] font-bold tracking-tight text-white [overflow-wrap:anywhere]">
                            {{ formatCurrency(state.summary.totalIngresado) }}
                        </p>
                    </article>
                    <article class="rounded-[1.75rem] border border-amber-300/15 bg-[linear-gradient(180deg,rgba(247,196,94,0.14),rgba(255,255,255,0.03))] p-5">
                        <p class="text-sm uppercase tracking-[0.28em] text-[var(--color-gold)]">Gastos</p>
                        <p class="mt-4 text-[clamp(1.9rem,3.4vw,2.8rem)] leading-[0.95] font-bold tracking-tight text-white [overflow-wrap:anywhere]">
                            {{ formatCurrency(state.summary.totalGastado) }}
                        </p>
                    </article>
                    <article class="rounded-[1.75rem] border border-white/10 bg-[linear-gradient(180deg,rgba(255,122,89,0.12),rgba(255,255,255,0.03))] p-5">
                        <p class="text-sm uppercase tracking-[0.28em] text-[var(--color-danger)]">Balance</p>
                        <p
                            :class="state.summary.balance >= 0 ? 'text-[var(--color-mint)]' : 'text-[var(--color-danger)]'"
                            class="mt-4 text-[clamp(1.9rem,3.4vw,2.8rem)] leading-[0.95] font-bold tracking-tight [overflow-wrap:anywhere]"
                        >
                            {{ formatCurrency(state.summary.balance) }}
                        </p>
                    </article>
                    <article class="rounded-[1.75rem] border border-white/10 bg-white/6 p-5">
                        <p class="text-sm uppercase tracking-[0.28em] text-white/68">Movimientos</p>
                        <p class="mt-4 text-[clamp(1.9rem,3.4vw,2.8rem)] leading-[0.95] font-bold tracking-tight text-white">
                            {{ state.summary.totalMovimientos }}
                        </p>
                    </article>
                </div>
            </section>

            <section class="rounded-[2rem] border border-white/10 bg-white/6 p-6 shadow-xl backdrop-blur-lg sm:p-8">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                    <div>
                        <p class="text-sm uppercase tracking-[0.3em] text-[var(--color-gold)]/85">Comparativa mensual</p>
                        <h3 class="mt-3 font-[var(--font-display)] text-[clamp(2rem,4vw,3.1rem)] leading-none font-bold">Ingresos vs gastos</h3>
                    </div>
                    <div class="flex flex-wrap gap-4 text-sm text-white/70">
                        <span class="flex items-center gap-2"><span class="h-3 w-3 rounded-full bg-[var(--color-mint)]"></span>Ingresos</span>
                        <span class="flex items-center gap-2"><span class="h-3 w-3 rounded-full bg-[var(--color-danger)]"></span>Gastos</span>
                    </div>
                </div>

                <div class="mt-8 space-y-4">
                    <article
                        v-for="item in chartItems"
                        :key="item.month"
                        class="rounded-[1.5rem] border border-white/10 bg-[var(--color-ink-soft)]/55 p-4 sm:p-5"
                    >
                        <div class="grid gap-4 lg:grid-cols-[72px_1fr] lg:items-center">
                            <div>
                                <p class="text-sm uppercase tracking-[0.24em] text-white/45">{{ item.label }}</p>
                                <p :class="item.balance >= 0 ? 'text-[var(--color-mint)]' : 'text-[var(--color-danger)]'" class="mt-2 text-sm font-semibold">
                                    {{ formatSignedCurrency(item.balance) }}
                                </p>
                            </div>

                            <div class="space-y-3">
                                <div>
                                    <div class="mb-2 flex items-center justify-between gap-3 text-sm">
                                        <span class="text-[var(--color-mint)]">Ingresos</span>
                                        <span class="font-semibold text-white">{{ formatCurrency(item.income) }}</span>
                                    </div>
                                    <div class="h-3 overflow-hidden rounded-full bg-white/8">
                                        <div
                                            class="h-full rounded-full bg-[var(--color-mint)] shadow-[0_0_18px_rgba(48,214,169,0.3)]"
                                            :style="{ width: `${item.incomeWidth}%` }"
                                        ></div>
                                    </div>
                                </div>

                                <div>
                                    <div class="mb-2 flex items-center justify-between gap-3 text-sm">
                                        <span class="text-[var(--color-danger)]">Gastos</span>
                                        <span class="font-semibold text-white">{{ formatCurrency(item.expense) }}</span>
                                    </div>
                                    <div class="h-3 overflow-hidden rounded-full bg-white/8">
                                        <div
                                            class="h-full rounded-full bg-[var(--color-danger)] shadow-[0_0_18px_rgba(251,79,100,0.25)]"
                                            :style="{ width: `${item.expenseWidth}%` }"
                                        ></div>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </article>
                </div>
            </section>
        </section>

        <aside class="space-y-3 self-start">
            <section class="rounded-[2rem] border border-white/10 bg-[linear-gradient(180deg,rgba(255,255,255,0.09),rgba(255,255,255,0.03))] p-6 backdrop-blur-lg sm:p-8">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="text-sm uppercase tracking-[0.2em] text-[var(--color-mint)]">Categorías</p>
                        <h3 class="mt-3 font-[var(--font-display)] text-[clamp(2rem,4vw,3rem)] leading-none font-bold">Resumen anual</h3>
                    </div>
                    <span class="rounded-full border border-white/10 bg-white/8 px-4 py-2 text-sm text-white/70">
                        {{ state.categoryBreakdown.length }} categorías
                    </span>
                </div>

                <div class="mt-7">
                    <article class="rounded-[1.35rem] border border-white/10 bg-[var(--color-ink-soft)]/60 p-4">
                        <p class="text-xs uppercase tracking-[0.24em] text-white/50">Top categoría</p>
                        <div class="mt-3 flex items-center gap-3">
                            <CategoryIconBadge
                                :icon="topCategory?.icono ?? ''"
                                :color="topCategory?.color ?? '#f7c45e'"
                                :alt="topCategory ? `Icono de ${topCategory.nombre}` : 'Sin categoria destacada'"
                            />
                            <p class="text-xl font-bold text-white">{{ topCategory?.nombre ?? 'Sin datos' }}</p>
                        </div>
                        <p class="mt-2 text-sm text-white/60">
                            {{ topCategory ? formatCurrency(topCategory.total) : 'Aún no hay gastos' }}
                        </p>
                    </article>
                </div>

                <div class="mt-8 space-y-4">
                    <article
                        v-for="category in rankedCategories"
                        :key="category.id"
                        class="rounded-[1.5rem] border border-white/10 bg-[var(--color-ink-soft)]/70 p-5"
                    >
                        <div class="flex items-center justify-between gap-4">
                            <div class="flex min-w-0 items-center gap-3">
                                <CategoryIconBadge :icon="category.icono" :color="category.color || '#f7c45e'" :alt="`Icono de ${category.nombre}`" />
                                <div class="min-w-0">
                                    <p class="truncate text-lg font-semibold text-white">{{ category.nombre }}</p>
                                    <p class="text-sm text-white/58">{{ category.movimientos }} movimientos</p>
                                </div>
                            </div>
                            <p class="shrink-0 text-xl font-bold text-white">{{ formatCurrency(category.total) }}</p>
                        </div>

                        <div class="mt-4 h-3 overflow-hidden rounded-full bg-white/8">
                            <div
                                class="h-full rounded-full"
                                :style="{ width: `${category.share}%`, backgroundColor: category.color || '#f7c45e' }"
                            ></div>
                        </div>
                        <div class="mt-3 flex items-center justify-between gap-3 text-sm text-white/55">
                            <span>{{ category.share.toFixed(1) }}% del gasto anual</span>
                            <span>{{ averagePerMovement(category) }} por movimiento</span>
                        </div>
                    </article>

                    <div v-if="rankedCategories.length === 0" class="rounded-[1.5rem] border border-dashed border-white/12 bg-white/4 p-6 text-sm leading-7 text-white/65">
                        Todavía no hay gastos en este año. Cuando registres movimientos aparecerán aquí el reparto por categorías.
                    </div>
                </div>
            </section>
        </aside>
    </section>
</template>

<script setup>
import { computed } from 'vue';
import CategoryIconBadge from '../../ui/category/CategoryIconBadge.vue';

const props = defineProps({
    state: {
        type: Object,
        required: true,
    },
});

defineEmits(['change-year']);

const localYear = computed({
    get: () => props.state.selectedYear,
    set: (value) => {
        props.state.selectedYear = Number(value);
    },
});

const maxMonthlyValue = computed(() => {
    const values = props.state.monthly.flatMap((item) => [item.income ?? 0, item.expense ?? 0]);
    return Math.max(...values, 0);
});

const chartItems = computed(() => props.state.monthly.map((item) => {
    return {
        ...item,
        incomeWidth: toWidth(item.income, maxMonthlyValue.value),
        expenseWidth: toWidth(item.expense, maxMonthlyValue.value),
    };
}));

const rankedCategories = computed(() => {
    const total = props.state.categoryBreakdown.reduce((carry, item) => carry + Number(item.total ?? 0), 0);

    return props.state.categoryBreakdown.map((item) => ({
        ...item,
        share: total > 0 ? (Number(item.total ?? 0) / total) * 100 : 0,
    }));
});

const topCategory = computed(() => rankedCategories.value[0] ?? null);

function toWidth(value, max) {
    if (max <= 0) return 0;
    return Math.max((Number(value ?? 0) / max) * 100, Number(value ?? 0) > 0 ? 7 : 0);
}

function averagePerMovement(category) {
    const movements = Number(category.movimientos ?? 0);
    if (movements <= 0) return formatCurrency(0);
    return formatCurrency(Number(category.total ?? 0) / movements);
}

function formatCurrency(value) {
    return new Intl.NumberFormat('es-ES', {
        style: 'currency',
        currency: 'EUR',
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }).format(Number(value ?? 0));
}

function formatSignedCurrency(value) {
    const amount = Number(value ?? 0);
    const formatted = formatCurrency(Math.abs(amount));
    return amount >= 0 ? `+${formatted}` : `-${formatted}`;
}
</script>

