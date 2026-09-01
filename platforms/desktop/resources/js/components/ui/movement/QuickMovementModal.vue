<template>
    <Teleport to="body">
        <Transition name="quick-movement">
            <div
                v-if="open"
                class="fixed inset-0 z-[60] flex items-end justify-center bg-black/70 p-0 backdrop-blur-sm sm:items-center sm:p-5"
                @click.self="close"
            >
                <section
                    role="dialog"
                    aria-modal="true"
                    aria-labelledby="quick-movement-title"
                    class="max-h-[92dvh] w-full overflow-y-auto rounded-t-2xl border border-white/10 bg-[#071828] p-5 pb-[calc(1.25rem+env(safe-area-inset-bottom))] shadow-2xl sm:max-w-xl sm:rounded-2xl sm:p-6"
                >
                    <header class="flex items-start justify-between gap-4">
                        <div>
                            <p class="text-[10px] font-semibold uppercase tracking-[0.2em] text-[var(--color-gold)]">Nuevo movimiento</p>
                            <h2 id="quick-movement-title" class="mt-1 text-2xl font-semibold text-white">Añadir movimiento</h2>
                        </div>
                        <button
                            type="button"
                            class="grid h-11 w-11 shrink-0 place-items-center rounded-xl border border-white/10 bg-white/5 text-xl text-white/70 transition hover:bg-white/10 hover:text-white"
                            aria-label="Cerrar formulario"
                            @click="close"
                        >
                            ×
                        </button>
                    </header>

                    <div class="mt-5 grid grid-cols-2 gap-2 rounded-xl border border-white/10 bg-black/15 p-1.5" role="tablist" aria-label="Tipo de movimiento">
                        <button
                            v-for="option in typeOptions"
                            :key="option.value"
                            type="button"
                            role="tab"
                            :aria-selected="movementType === option.value"
                            :class="movementType === option.value ? option.activeClass : 'text-white/55 hover:bg-white/5 hover:text-white'"
                            class="min-h-11 rounded-lg px-4 py-2.5 text-sm font-semibold transition"
                            @click="movementType = option.value"
                        >
                            {{ option.icon }} {{ option.label }}
                        </button>
                    </div>

                    <div v-if="loading" class="grid min-h-64 place-items-center text-sm text-white/60">
                        Preparando formulario…
                    </div>

                    <template v-else-if="movementType === 'expense'">
                        <div v-if="!state.categorias.length" class="mt-5 rounded-xl border border-amber-300/25 bg-amber-300/10 p-4 text-sm text-amber-50">
                            Primero crea una categoría para poder registrar gastos.
                        </div>

                        <div v-if="state.expenseErrors.length" class="mt-5 rounded-xl border border-rose-400/25 bg-rose-400/10 p-4 text-sm text-rose-100">
                            {{ state.expenseErrors[0] }}
                        </div>

                        <form v-if="state.categorias.length" class="mt-5 space-y-4" @submit.prevent="$emit('submit-expense', state.expenseForm)">
                            <FormField v-model="state.expenseForm.titulo" label="Título" placeholder="Ej. Supermercado" required />
                            <div class="grid gap-4 sm:grid-cols-2">
                                <FormField v-model="state.expenseForm.importe" label="Importe" type="number" step="0.01" min="0.01" required />
                                <FormField v-model="state.expenseForm.fecha" label="Fecha" type="date" required />
                            </div>
                            <div>
                                <label class="mb-2 block text-sm font-semibold text-white/80">Categoría<span class="ml-1 text-[var(--color-accent)]">*</span></label>
                                <select v-model="state.expenseForm.categoria_id" required class="min-h-11 w-full rounded-lg border border-white/10 bg-[#03111f] px-4 py-3 text-sm text-white outline-none transition focus:border-[var(--color-gold)]">
                                    <option value="">Selecciona una categoría</option>
                                    <option v-for="category in state.categorias" :key="category.id" :value="String(category.id)">{{ category.nombre }}</option>
                                </select>
                            </div>
                            <FormTextarea v-model="state.expenseForm.observaciones" label="Observaciones" placeholder="Notas opcionales" />
                            <button type="submit" :disabled="submitting" class="min-h-12 w-full rounded-lg bg-[var(--color-gold)] px-5 py-3 text-sm font-bold text-[var(--color-ink)] transition hover:brightness-105 disabled:cursor-wait disabled:opacity-60">
                                {{ submitting ? 'Guardando…' : 'Guardar gasto' }}
                            </button>
                        </form>
                    </template>

                    <template v-else>
                        <div v-if="state.incomeErrors.length" class="mt-5 rounded-xl border border-rose-400/25 bg-rose-400/10 p-4 text-sm text-rose-100">
                            {{ state.incomeErrors[0] }}
                        </div>

                        <form class="mt-5 space-y-4" @submit.prevent="$emit('submit-income', state.incomeForm)">
                            <FormField v-model="state.incomeForm.titulo" label="Título" placeholder="Ej. Nómina" focus-color="var(--color-mint)" required />
                            <div class="grid gap-4 sm:grid-cols-2">
                                <FormField v-model="state.incomeForm.importe" label="Importe" type="number" step="0.01" min="0.01" focus-color="var(--color-mint)" required />
                                <FormField v-model="state.incomeForm.fecha" label="Fecha" type="date" focus-color="var(--color-mint)" required />
                            </div>
                            <FormTextarea v-model="state.incomeForm.observaciones" label="Observaciones" placeholder="Notas opcionales" focus-color="var(--color-mint)" />
                            <button type="submit" :disabled="submitting" class="min-h-12 w-full rounded-lg bg-[var(--color-mint)] px-5 py-3 text-sm font-bold text-[var(--color-ink)] transition hover:brightness-105 disabled:cursor-wait disabled:opacity-60">
                                {{ submitting ? 'Guardando…' : 'Guardar ingreso' }}
                            </button>
                        </form>
                    </template>
                </section>
            </div>
        </Transition>
    </Teleport>
</template>

<script setup>
import { onBeforeUnmount, onMounted, ref, watch } from 'vue';
import FormField from '../form/FormField.vue';
import FormTextarea from '../form/FormTextarea.vue';

const props = defineProps({
    open: { type: Boolean, default: false },
    loading: { type: Boolean, default: false },
    submitting: { type: Boolean, default: false },
    state: { type: Object, required: true },
});

const emit = defineEmits(['close', 'submit-expense', 'submit-income']);
const movementType = ref('expense');
const typeOptions = [
    { value: 'expense', label: 'Gasto', icon: '↗', activeClass: 'bg-rose-400/12 text-rose-300' },
    { value: 'income', label: 'Ingreso', icon: '↓', activeClass: 'bg-emerald-400/12 text-emerald-300' },
];

let previousBodyOverflow = '';

watch(() => props.open, (isOpen) => {
    if (isOpen) {
        previousBodyOverflow = document.body.style.overflow;
        document.body.style.overflow = 'hidden';
        return;
    }

    document.body.style.overflow = previousBodyOverflow;
});

function close() {
    if (!props.submitting) emit('close');
}

function handleKeydown(event) {
    if (event.key === 'Escape' && props.open) close();
}

onMounted(() => window.addEventListener('keydown', handleKeydown));
onBeforeUnmount(() => {
    window.removeEventListener('keydown', handleKeydown);
    document.body.style.overflow = previousBodyOverflow;
});
</script>

<style scoped>
.quick-movement-enter-active,
.quick-movement-leave-active {
    transition: opacity 160ms ease;
}

.quick-movement-enter-from,
.quick-movement-leave-to {
    opacity: 0;
}
</style>
