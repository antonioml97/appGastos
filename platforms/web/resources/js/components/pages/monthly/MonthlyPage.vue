<template>
    <section class="grid gap-6 2xl:grid-cols-[0.98fr_1.02fr]">
        <aside class="space-y-6">
            <section class="rounded-[2rem] border border-white/10 bg-[linear-gradient(180deg,rgba(255,255,255,0.08),rgba(255,255,255,0.03))] p-5 shadow-xl shadow-black/10 backdrop-blur-lg sm:p-7">
                <div class="flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
                    <div class="min-w-0">
                        <p class="text-xs uppercase tracking-[0.38em] text-[var(--color-gold)]/85 sm:text-sm">Periodo</p>
                        <h2 class="mt-3 text-[clamp(2rem,5vw,3rem)] leading-none font-bold font-[var(--font-display)]">
                            {{ localState.selectedMonthLabel }}
                        </h2>
                    </div>

                    <form class="grid gap-3 sm:grid-cols-[1fr_auto] sm:items-end" @submit.prevent="$emit('change-month')">
                        <div class="min-w-0">
                            <label class="mb-2 block text-sm font-semibold text-white/80">Cambiar mes</label>
                            <input
                                v-model="localState.selectedMonthValue"
                                type="month"
                                class="w-full rounded-2xl border border-white/10 bg-[var(--color-ink-soft)]/85 px-4 py-3 text-white outline-none transition focus:border-[var(--color-gold)]"
                            >
                        </div>
                        <button type="submit" class="rounded-2xl border border-white/10 bg-white/10 px-5 py-3 text-sm font-semibold text-white transition hover:bg-white/14">
                            Ver
                        </button>
                    </form>
                </div>

                <div class="mt-7 grid grid-cols-2 gap-3">
                    <StatCard label="Ingresos" :value="formatCurrency(localState.summary.totalIngresado)" accent="text-[var(--color-mint)]/85" />
                    <StatCard label="Gastos" :value="formatCurrency(localState.summary.totalGastado)" accent="text-[var(--color-gold)]/85" />
                    <StatCard
                        label="Balance"
                        :value="formatCurrency(localState.summary.balance)"
                        :value-class="localState.summary.balance >= 0 ? 'text-emerald-300' : 'text-rose-300'"
                        accent="text-[var(--color-accent)]/85"
                    />
                    <StatCard label="Media gasto" :value="formatCurrency(localState.summary.importeMedio)" accent="text-white/70" />
                </div>
            </section>

            <section class="rounded-[2rem] border border-white/10 bg-white/6 p-5 shadow-xl shadow-black/10 backdrop-blur-lg sm:p-7">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <p class="text-xs uppercase tracking-[0.38em] text-[var(--color-gold)]/85 sm:text-sm">Nuevo gasto</p>
                        <h2 class="mt-4 text-[clamp(2rem,5vw,3rem)] leading-none font-bold font-[var(--font-display)]">Anadir gasto</h2>
                    </div>

                    <button
                        type="button"
                        class="rounded-2xl bg-[var(--color-gold)] px-5 py-3 text-sm font-semibold text-[var(--color-ink)] transition hover:brightness-105"
                        @click="expenseFormOpen = !expenseFormOpen"
                    >
                        {{ expenseFormOpen ? 'Cerrar formulario' : 'Anadir gasto' }}
                    </button>
                </div>

                <div v-if="!localState.categorias.length" class="mt-6 rounded-2xl border border-rose-400/30 bg-rose-400/10 px-4 py-3 text-sm text-rose-100">
                    Primero crea al menos una categoria para poder registrar gastos.
                </div>

                <template v-else-if="expenseFormOpen">
                    <div v-if="localState.expenseErrors.length" class="mt-6 rounded-2xl border border-amber-400/30 bg-amber-400/10 px-4 py-3 text-sm text-amber-50">
                        <ul class="space-y-2">
                            <li v-for="error in localState.expenseErrors" :key="error">{{ error }}</li>
                        </ul>
                    </div>

                    <form class="mt-8 space-y-5" @submit.prevent="$emit('create-expense', localState.expenseForm)">
                        <FormField v-model="localState.expenseForm.titulo" label="Titulo" placeholder="Ej. Supermercado" />

                        <div class="grid gap-4 sm:grid-cols-2">
                            <FormField v-model="localState.expenseForm.importe" label="Importe" type="number" step="0.01" min="0.01" />
                            <FormField v-model="localState.expenseForm.fecha" label="Fecha" type="date" />
                        </div>

                        <div>
                            <label class="mb-2 block text-sm font-semibold text-white/80">Categoria</label>
                            <select v-model="localState.expenseForm.categoria_id" class="w-full rounded-2xl border border-white/10 bg-[var(--color-ink-soft)]/85 px-4 py-3 text-white outline-none transition focus:border-[var(--color-gold)]">
                                <option value="">Selecciona una categoria</option>
                                <option v-for="categoria in localState.categorias" :key="categoria.id" :value="String(categoria.id)">{{ categoria.nombre }}</option>
                            </select>
                        </div>

                        <FormTextarea v-model="localState.expenseForm.observaciones" label="Observaciones" placeholder="Notas opcionales" />

                        <button type="submit" class="w-full rounded-2xl bg-[var(--color-gold)] px-4 py-3 text-sm font-semibold text-[var(--color-ink)] transition hover:brightness-105">
                            Guardar gasto
                        </button>
                    </form>
                </template>
            </section>

            <section class="rounded-[2rem] border border-white/10 bg-white/6 p-5 shadow-xl shadow-black/10 backdrop-blur-lg sm:p-7">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <p class="text-xs uppercase tracking-[0.38em] text-[var(--color-mint)]/85 sm:text-sm">Nuevo ingreso</p>
                        <h2 class="mt-4 text-[clamp(2rem,5vw,3rem)] leading-none font-bold font-[var(--font-display)]">Anadir ingreso</h2>
                    </div>

                    <button
                        type="button"
                        class="rounded-2xl bg-[var(--color-mint)] px-5 py-3 text-sm font-semibold text-[var(--color-ink)] transition hover:brightness-105"
                        @click="incomeFormOpen = !incomeFormOpen"
                    >
                        {{ incomeFormOpen ? 'Cerrar formulario' : 'Anadir ingreso' }}
                    </button>
                </div>

                <template v-if="incomeFormOpen">
                    <div v-if="localState.incomeErrors.length" class="mt-6 rounded-2xl border border-amber-400/30 bg-amber-400/10 px-4 py-3 text-sm text-amber-50">
                        <ul class="space-y-2">
                            <li v-for="error in localState.incomeErrors" :key="error">{{ error }}</li>
                        </ul>
                    </div>

                    <form class="mt-8 space-y-5" @submit.prevent="$emit('create-income', localState.incomeForm)">
                        <FormField v-model="localState.incomeForm.titulo" label="Titulo" placeholder="Ej. Nomina" focus-color="var(--color-mint)" />

                        <div class="grid gap-4 sm:grid-cols-2">
                            <FormField v-model="localState.incomeForm.importe" label="Importe" type="number" step="0.01" min="0.01" focus-color="var(--color-mint)" />
                            <FormField v-model="localState.incomeForm.fecha" label="Fecha" type="date" focus-color="var(--color-mint)" />
                        </div>

                        <FormTextarea v-model="localState.incomeForm.observaciones" label="Observaciones" placeholder="Notas opcionales" focus-color="var(--color-mint)" />

                        <button type="submit" class="w-full rounded-2xl bg-[var(--color-mint)] px-4 py-3 text-sm font-semibold text-[var(--color-ink)] transition hover:brightness-105">
                            Guardar ingreso
                        </button>
                    </form>
                </template>
            </section>
        </aside>

        <section class="space-y-6">
            <section class="rounded-[2rem] border border-white/10 bg-[linear-gradient(180deg,rgba(255,255,255,0.09),rgba(255,255,255,0.03))] p-5 shadow-xl shadow-black/10 backdrop-blur-lg sm:p-7">
                <div class="grid gap-6 lg:grid-cols-[0.72fr_1.28fr] lg:items-center">
                    <div class="flex flex-col items-center justify-center">
                        <div class="relative h-52 w-52 rounded-full border border-white/10 shadow-2xl sm:h-56 sm:w-56" :style="{ background: chartBackground }">
                            <div class="absolute left-1/2 top-1/2 h-24 w-24 -translate-x-1/2 -translate-y-1/2 rounded-full border border-white/10 bg-[var(--color-ink)]/95"></div>
                        </div>
                        <p class="mt-4 text-sm uppercase tracking-[0.2em] text-white/55">Grafica de tarta</p>
                    </div>

                    <div>
                        <p class="text-xs uppercase tracking-[0.32em] text-[var(--color-mint)] sm:text-sm">Desglose</p>
                        <h2 class="mt-2 text-[clamp(1.8rem,4vw,2.3rem)] leading-tight font-bold font-[var(--font-display)]">
                            Gasto por categoria
                        </h2>

                        <div class="mt-6 space-y-3">
                            <div
                                v-for="item in localState.desglose"
                                :key="item.id"
                                class="rounded-2xl border border-white/10 bg-[linear-gradient(180deg,rgba(255,255,255,0.08),rgba(255,255,255,0.03))] px-4 py-4 shadow-lg shadow-black/10"
                            >
                                <div class="flex items-center justify-between gap-4">
                                    <div class="min-w-0 flex items-center gap-3">
                                        <CategoryIconBadge :icon="item.icono" :color="item.color" :alt="`Icono de ${item.nombre}`" />
                                        <div class="min-w-0">
                                            <p class="truncate font-semibold text-white">{{ item.nombre }}</p>
                                            <p class="text-sm text-white/55">{{ item.movimientos }} movimientos</p>
                                        </div>
                                    </div>

                                    <div class="shrink-0 text-right">
                                        <p class="font-semibold text-white">{{ formatCurrency(item.total) }}</p>
                                        <p class="text-sm text-white/55">{{ formatPercent(item.total, localState.summary.totalGastado) }}</p>
                                    </div>
                                </div>
                            </div>

                            <div v-if="localState.desglose.length === 0" class="rounded-2xl border border-dashed border-white/15 bg-white/5 px-5 py-8 text-center text-white/65">
                                Aun no hay datos para mostrar la grafica de este mes.
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section class="rounded-[2rem] border border-white/10 bg-white/6 p-5 shadow-xl shadow-black/10 backdrop-blur-lg sm:p-7">
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <p class="text-xs uppercase tracking-[0.32em] text-[var(--color-gold)]/85 sm:text-sm">Gastos</p>
                        <h2 class="mt-2 text-[clamp(1.8rem,4vw,2.3rem)] leading-tight font-bold font-[var(--font-display)]">
                            Listado del mes
                        </h2>
                    </div>
                    <div class="rounded-2xl border border-white/10 bg-white/8 px-4 py-2 text-sm text-white/70">
                        {{ localState.gastos.length }} gastos
                    </div>
                </div>

                <div class="mt-8 space-y-4">
                    <article
                        v-for="gasto in localState.gastos"
                        :key="gasto.id"
                        class="rounded-3xl border border-white/10 bg-[linear-gradient(180deg,rgba(255,255,255,0.08),rgba(255,255,255,0.03))] p-5 shadow-lg shadow-black/10"
                    >
                        <div v-if="localState.editExpenseId !== gasto.id" class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                            <div class="min-w-0">
                                <div class="flex items-center gap-3">
                                    <CategoryIconBadge :icon="gasto.categoria.icono" :color="gasto.categoria.color" :alt="`Icono de ${gasto.categoria.nombre}`" />
                                    <h3 class="truncate text-xl font-semibold text-white">{{ gasto.titulo }}</h3>
                                </div>
                                <p class="mt-2 text-sm text-white/60">{{ gasto.fecha_label }} · {{ gasto.categoria.nombre }}</p>
                                <p v-if="gasto.observaciones" class="mt-3 text-sm leading-7 text-white/72">{{ gasto.observaciones }}</p>
                            </div>

                            <div class="flex flex-col items-start gap-3 lg:items-end">
                                <p class="text-2xl font-bold text-white">{{ formatCurrency(gasto.importe) }}</p>
                                <div class="flex flex-wrap gap-3">
                                    <button @click="$emit('start-edit-expense', gasto)" type="button" class="rounded-2xl border border-white/10 bg-white/8 px-4 py-3 text-sm font-semibold text-white transition hover:bg-white/12">Editar</button>
                                    <button @click="$emit('delete-expense', gasto)" type="button" class="rounded-2xl bg-[var(--color-accent)] px-4 py-3 text-sm font-semibold text-white transition hover:brightness-110">Borrar</button>
                                </div>
                            </div>
                        </div>

                        <form v-else class="space-y-4" @submit.prevent="$emit('update-expense', gasto.id)">
                            <div class="grid gap-4 lg:grid-cols-[1.2fr_0.8fr_0.8fr_0.8fr]">
                                <FormField v-model="localState.editExpenseForm.titulo" label="Titulo" />
                                <FormField v-model="localState.editExpenseForm.importe" label="Importe" type="number" step="0.01" min="0.01" />
                                <FormField v-model="localState.editExpenseForm.fecha" label="Fecha" type="date" />
                                <div>
                                    <label class="mb-2 block text-sm font-semibold text-white/75">Categoria</label>
                                    <select v-model="localState.editExpenseForm.categoria_id" class="w-full rounded-2xl border border-white/10 bg-black/10 px-4 py-3 text-white outline-none transition focus:border-[var(--color-gold)]">
                                        <option v-for="categoria in localState.categorias" :key="categoria.id" :value="categoria.id">{{ categoria.nombre }}</option>
                                    </select>
                                </div>
                            </div>

                            <FormTextarea v-model="localState.editExpenseForm.observaciones" label="Observaciones" />

                            <div class="flex flex-wrap gap-3">
                                <button type="submit" class="rounded-2xl border border-white/10 bg-white/8 px-4 py-3 text-sm font-semibold text-white transition hover:bg-white/12">Guardar cambios</button>
                                <button @click="$emit('cancel-edit-expense')" type="button" class="rounded-2xl bg-[var(--color-accent)] px-4 py-3 text-sm font-semibold text-white transition hover:brightness-110">Cancelar</button>
                            </div>
                        </form>
                    </article>

                    <div v-if="localState.gastos.length === 0" class="rounded-3xl border border-dashed border-white/15 bg-white/5 px-5 py-10 text-center text-white/65">
                        Aun no hay gastos registrados para este mes.
                    </div>
                </div>
            </section>

            <section class="rounded-[2rem] border border-white/10 bg-white/6 p-5 shadow-xl shadow-black/10 backdrop-blur-lg sm:p-7">
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <p class="text-xs uppercase tracking-[0.32em] text-[var(--color-mint)]/85 sm:text-sm">Ingresos</p>
                        <h2 class="mt-2 text-[clamp(1.8rem,4vw,2.3rem)] leading-tight font-bold font-[var(--font-display)]">
                            Entradas del mes
                        </h2>
                    </div>
                    <div class="rounded-2xl border border-white/10 bg-white/8 px-4 py-2 text-sm text-white/70">
                        {{ localState.ingresos.length }} ingresos
                    </div>
                </div>

                <div class="mt-8 space-y-4">
                    <article
                        v-for="ingreso in localState.ingresos"
                        :key="ingreso.id"
                        class="rounded-3xl border border-white/10 bg-[linear-gradient(180deg,rgba(255,255,255,0.08),rgba(255,255,255,0.03))] p-5 shadow-lg shadow-black/10"
                    >
                        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                            <div class="min-w-0">
                                <h3 class="truncate text-xl font-semibold text-white">{{ ingreso.titulo }}</h3>
                                <p class="mt-2 text-sm text-white/60">{{ ingreso.fecha_label }}</p>
                                <p v-if="ingreso.observaciones" class="mt-3 text-sm leading-7 text-white/72">{{ ingreso.observaciones }}</p>
                            </div>

                            <div class="flex flex-col items-start gap-3 lg:items-end">
                                <p class="text-2xl font-bold text-emerald-300">{{ formatCurrency(ingreso.importe) }}</p>
                                <button @click="$emit('delete-income', ingreso)" type="button" class="rounded-2xl border border-white/10 bg-white/8 px-4 py-3 text-sm font-semibold text-white transition hover:bg-white/12">Borrar</button>
                            </div>
                        </div>
                    </article>

                    <div v-if="localState.ingresos.length === 0" class="rounded-3xl border border-dashed border-white/15 bg-white/5 px-5 py-10 text-center text-white/65">
                        Aun no hay ingresos registrados para este mes.
                    </div>
                </div>
            </section>
        </section>
    </section>
</template>

<script setup>
import { computed, ref, watch } from 'vue';
import CategoryIconBadge from '../../ui/category/CategoryIconBadge.vue';
import FormField from '../../ui/form/FormField.vue';
import FormTextarea from '../../ui/form/FormTextarea.vue';
import StatCard from '../../ui/stats/StatCard.vue';

const props = defineProps({
    state: { type: Object, required: true },
});

defineEmits(['change-month', 'create-expense', 'start-edit-expense', 'cancel-edit-expense', 'update-expense', 'delete-expense', 'create-income', 'delete-income']);

const localState = computed(() => props.state);
const expenseFormOpen = ref(false);
const incomeFormOpen = ref(false);

watch(() => localState.value.expenseErrors.length, (count) => {
    if (count > 0) {
        expenseFormOpen.value = true;
    }
});

watch(() => localState.value.incomeErrors.length, (count) => {
    if (count > 0) {
        incomeFormOpen.value = true;
    }
});

const chartBackground = computed(() => {
    if (localState.value.desglose.length === 0 || Number(localState.value.summary.totalGastado) <= 0) {
        return 'conic-gradient(rgba(255,255,255,0.12) 0deg 360deg)';
    }

    let angle = 0;
    const parts = localState.value.desglose.map((item) => {
        const size = (Number(item.total) / Number(localState.value.summary.totalGastado)) * 360;
        const start = angle;
        const end = angle + size;
        angle = end;
        return `${item.color} ${start}deg ${end}deg`;
    });

    return `conic-gradient(${parts.join(', ')})`;
});

function formatCurrency(value) {
    return `${Number(value ?? 0).toLocaleString('es-ES', { minimumFractionDigits: 2, maximumFractionDigits: 2 })} €`;
}

function formatPercent(value, total) {
    if (!total) return '0,0%';
    return `${((Number(value) / Number(total)) * 100).toLocaleString('es-ES', { minimumFractionDigits: 1, maximumFractionDigits: 1 })}%`;
}
</script>
