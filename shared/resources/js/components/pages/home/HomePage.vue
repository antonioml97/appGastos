<template>
    <section class="space-y-8">
        <section class="grid gap-6 xl:grid-cols-[1.12fr_0.88fr]">
            <article class="rounded-[2rem] border border-white/10 bg-[linear-gradient(135deg,rgba(247,196,94,0.16),rgba(48,214,169,0.1),rgba(255,255,255,0.04))] p-6 shadow-2xl shadow-black/10 backdrop-blur-xl sm:p-8">
                <p class="text-sm uppercase tracking-[0.35em] text-[var(--color-gold)]/90">
                    Resumen del mes actual
                </p>

                <div class="mt-4 flex flex-wrap items-center gap-3">
                    <span class="rounded-full border border-white/10 bg-white/10 px-4 py-2 text-sm font-semibold text-white/85">
                        {{ localState.selectedMonthLabel }}
                    </span>
                    <span class="rounded-full border border-white/10 bg-black/15 px-4 py-2 text-sm text-white/70">
                        {{ formatCount(localState.summary.totalMovimientos) }} gastos registrados
                    </span>
                </div>

                <h2 class="mt-6 max-w-3xl font-[var(--font-display)] text-4xl leading-none font-bold sm:text-5xl lg:text-6xl">
                    Balance de {{ localState.selectedMonthLabel }}
                </h2>

                <p class="mt-6 text-[clamp(2.8rem,7vw,5rem)] leading-none font-bold tracking-tight" :class="balanceValueClass">
                    {{ formatCurrency(localState.summary.balance) }}
                </p>

                <p class="mt-4 max-w-2xl text-base leading-8 text-white/76 sm:text-lg">
                    {{ balanceMessage }}
                </p>

                <div class="mt-8 grid gap-4 sm:grid-cols-2 2xl:grid-cols-4">
                    <StatCard label="Ingresos" :value="formatCurrency(localState.summary.totalIngresado)" accent="text-[var(--color-mint)]/85" value-class="text-emerald-300" />
                    <StatCard label="Gastos" :value="formatCurrency(localState.summary.totalGastado)" accent="text-[var(--color-gold)]/85" />
                    <StatCard label="Media gasto" :value="formatCurrency(localState.summary.importeMedio)" accent="text-white/70" />
                    <StatCard label="Gastos anotados" :value="formatCount(localState.summary.totalMovimientos)" accent="text-[var(--color-accent)]/85" />
                </div>

                <div class="mt-8 flex flex-wrap gap-3">
                    <a href="/gastos-mensuales" class="rounded-2xl bg-[var(--color-gold)] px-5 py-3 text-sm font-semibold text-[var(--color-ink)] transition hover:brightness-105">
                        Abrir detalle mensual
                    </a>
                    <a href="/configuracion" class="rounded-2xl border border-white/10 bg-white/8 px-5 py-3 text-sm font-semibold text-white/85 transition hover:bg-white/12">
                        Revisar cuentas
                    </a>
                </div>
            </article>

            <aside class="space-y-6">
                <article class="rounded-[2rem] border border-white/10 bg-white/7 p-6 shadow-xl shadow-black/10 backdrop-blur-lg sm:p-7">
                    <p class="text-xs uppercase tracking-[0.32em] text-[var(--color-mint)]/85 sm:text-sm">Cuentas</p>
                    <h3 class="mt-3 font-[var(--font-display)] text-3xl font-bold">Como vas ahora</h3>

                    <div class="mt-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-1">
                        <div class="rounded-[1.6rem] border border-white/10 bg-[linear-gradient(180deg,rgba(255,255,255,0.09),rgba(255,255,255,0.03))] p-5">
                            <p class="text-xs uppercase tracking-[0.25em] text-white/55">Cuenta normal</p>
                            <p class="mt-4 text-3xl font-bold text-white">{{ formatCurrency(localState.accountsSummary.normal) }}</p>
                            <p class="mt-2 text-sm text-white/55">Disponible para el dia a dia</p>
                        </div>

                        <div class="rounded-[1.6rem] border border-white/10 bg-[linear-gradient(180deg,rgba(48,214,169,0.18),rgba(255,255,255,0.03))] p-5">
                            <p class="text-xs uppercase tracking-[0.25em] text-[var(--color-mint)]/85">Cuenta ahorro</p>
                            <p class="mt-4 text-3xl font-bold text-[var(--color-mint)]">{{ formatCurrency(localState.accountsSummary.ahorro) }}</p>
                            <p class="mt-2 text-sm text-white/55">Total guardado en ahorro</p>
                        </div>
                    </div>
                </article>

                <article class="rounded-[2rem] border border-white/10 bg-white/7 p-6 shadow-xl shadow-black/10 backdrop-blur-lg sm:p-7">
                    <div class="flex items-center justify-between gap-4">
                        <div>
                            <p class="text-xs uppercase tracking-[0.32em] text-[var(--color-gold)]/85 sm:text-sm">Donde se va el dinero</p>
                            <h3 class="mt-3 font-[var(--font-display)] text-3xl font-bold">Categorias del mes</h3>
                        </div>
                    </div>

                    <div v-if="localState.topCategories.length" class="mt-6 space-y-3">
                        <div
                            v-for="item in localState.topCategories"
                            :key="item.id"
                            class="rounded-[1.4rem] border border-white/10 bg-[linear-gradient(180deg,rgba(255,255,255,0.08),rgba(255,255,255,0.03))] px-4 py-4"
                        >
                            <div class="flex items-center justify-between gap-4">
                                <div class="min-w-0">
                                    <p class="truncate font-semibold text-white">{{ item.nombre }}</p>
                                    <p class="mt-1 text-sm text-white/55">
                                        {{ formatPercent(item.total, localState.summary.totalGastado) }} del gasto mensual
                                    </p>
                                </div>
                                <p class="shrink-0 font-semibold text-white">{{ formatCurrency(item.total) }}</p>
                            </div>
                        </div>
                    </div>

                    <div v-else class="mt-6 rounded-2xl border border-dashed border-white/15 bg-white/5 px-5 py-8 text-center text-white/65">
                        Todavia no hay gastos registrados este mes.
                    </div>
                </article>
            </aside>
        </section>

        <section class="grid gap-8 lg:grid-cols-[1.15fr_0.85fr] lg:items-start">
            <div class="space-y-8">
                <div class="space-y-5">
                    <p class="text-sm uppercase tracking-[0.35em] text-[var(--color-gold)]/90">
                        Informacion de la app
                    </p>

                    <h2 class="max-w-3xl font-[var(--font-display)] text-4xl leading-none font-bold sm:text-5xl">
                        AppGastos te ayuda a entender tu dinero sin perder tiempo.
                    </h2>

                    <p class="max-w-2xl text-base leading-8 text-white/78 sm:text-lg">
                        La idea es que al abrir la app veas tu situacion actual y, cuando necesites profundizar,
                        tengas a mano categorias, gastos, ingresos y configuracion en una interfaz pensada para movil.
                    </p>
                </div>

                <div class="grid gap-4 sm:grid-cols-3">
                    <article v-for="item in highlights" :key="item.title" class="rounded-3xl border border-white/10 bg-white/6 p-5 backdrop-blur-md">
                        <p class="text-sm uppercase tracking-[0.2em] text-[var(--color-gold)]/85">{{ item.eyebrow }}</p>
                        <h3 class="mt-3 text-xl font-semibold text-white">{{ item.title }}</h3>
                        <p class="mt-2 text-sm leading-7 text-white/70">{{ item.text }}</p>
                    </article>
                </div>
            </div>

            <aside class="relative overflow-hidden rounded-[2rem]">
                <div class="absolute inset-0 translate-x-4 translate-y-4 rounded-[2rem] bg-[var(--color-accent)]/15 blur-2xl"></div>

                <div class="relative rounded-[2rem] border border-white/10 bg-white/8 p-6 shadow-2xl backdrop-blur-xl">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm uppercase tracking-[0.22em] text-[var(--color-gold)]/85">Accesos directos</p>
                            <h3 class="mt-2 font-[var(--font-display)] text-3xl font-bold">Entra rapido a lo importante</h3>
                        </div>
                    </div>

                    <div class="mt-8 rounded-2xl bg-[linear-gradient(135deg,rgba(247,196,94,0.18),rgba(48,214,169,0.14))] p-5">
                        <p class="text-sm uppercase tracking-[0.2em] text-[var(--color-gold)]">Secciones principales</p>
                        <div class="mt-4 grid gap-3 sm:grid-cols-2">
                            <a
                                v-for="item in menuItems.filter((entry) => entry.href !== '/')"
                                :key="item.href"
                                :href="item.href"
                                class="rounded-2xl border border-white/10 bg-white/8 px-4 py-4 text-sm font-semibold text-white/90 transition hover:bg-white/12"
                            >
                                {{ item.label }}
                            </a>
                        </div>
                    </div>
                </div>
            </aside>
        </section>
    </section>
</template>

<script setup>
import { computed } from 'vue';
import StatCard from '../../ui/stats/StatCard.vue';

const props = defineProps({
    menuItems: { type: Array, required: true },
    state: { type: Object, required: true },
});

const localState = computed(() => props.state);

const highlights = [
    { eyebrow: 'Organiza', title: 'Categorias claras', text: 'Prepara una estructura simple para separar comida, transporte, hogar, ocio y cualquier otro gasto.' },
    { eyebrow: 'Registra', title: 'Movimientos rapidos', text: 'Anota importes, fechas y notas con pocos pasos para no dejar gastos sueltos.' },
    { eyebrow: 'Decide', title: 'Vision de conjunto', text: 'Con el balance y el reparto por categorias puedes detectar excesos antes de que el mes se te complique.' },
];

const balanceValueClass = computed(() => {
    const balance = Number(localState.value.summary?.balance ?? 0);

    if (balance > 0) return 'text-emerald-300';
    if (balance < 0) return 'text-rose-300';

    return 'text-white';
});

const balanceMessage = computed(() => {
    const summary = localState.value.summary ?? {};
    const incomes = Number(summary.totalIngresado ?? 0);
    const expenses = Number(summary.totalGastado ?? 0);
    const movements = Number(summary.totalMovimientos ?? 0);
    const balance = Number(summary.balance ?? 0);

    if (movements === 0 && incomes === 0 && expenses === 0) {
        return 'Aun no hay movimientos registrados este mes. Empieza a anadir gastos o ingresos para ver tu balance real en cuanto entres.';
    }

    if (balance > 0) {
        return `Este mes vas con margen positivo de ${formatCurrency(balance)} entre lo que entra y lo que sale.`;
    }

    if (balance < 0) {
        return `Ahora mismo vas por debajo en ${formatCurrency(Math.abs(balance))}. Revisar las categorias de arriba te ayudara a corregirlo a tiempo.`;
    }

    return 'De momento el mes esta totalmente equilibrado entre ingresos y gastos.';
});

function formatCurrency(value) {
    return `${Number(value ?? 0).toLocaleString('es-ES', { minimumFractionDigits: 2, maximumFractionDigits: 2 })} €`;
}

function formatCount(value) {
    return Number(value ?? 0).toLocaleString('es-ES');
}

function formatPercent(value, total) {
    if (!Number(total)) return '0,0%';

    return `${((Number(value ?? 0) / Number(total)) * 100).toLocaleString('es-ES', { minimumFractionDigits: 1, maximumFractionDigits: 1 })}%`;
}
</script>
