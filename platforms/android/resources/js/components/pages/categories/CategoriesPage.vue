<template>
    <section class="app-page grid min-w-0 gap-3 overflow-hidden lg:grid-cols-[0.78fr_1.22fr]">
        <aside class="min-w-0 overflow-hidden rounded-[2rem] border border-white/10 bg-white/6 p-6 shadow-xl backdrop-blur-lg sm:p-8">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <p class="text-sm uppercase tracking-[0.3em] text-[var(--color-gold)]/85">Nueva categoría</p>
                    <h2 class="mt-4 font-[var(--font-display)] text-4xl font-bold">Crear categoría</h2>
                    <p class="mt-4 text-sm leading-7 text-white/72">
                        Añade una categoría para organizar tus gastos. Puedes elegir su nombre, color e icono.
                    </p>
                </div>

                <button
                    type="button"
                    class="rounded-2xl border border-white/10 bg-white/8 px-4 py-2 text-sm font-semibold text-white transition hover:bg-white/12"
                    @click="isCreateSectionOpen = !isCreateSectionOpen"
                >
                    {{ isCreateSectionOpen ? 'Ocultar' : 'Mostrar' }}
                </button>
            </div>

            <div v-if="isCreateSectionOpen">
                <div class="mt-6 rounded-3xl border border-white/10 bg-[linear-gradient(180deg,rgba(255,255,255,0.08),rgba(255,255,255,0.03))] p-4 shadow-lg shadow-black/10">
                    <p class="text-xs uppercase tracking-[0.2em] text-white/55">Vista previa</p>
                    <div class="mt-3 flex items-center gap-3">
                        <CategoryIconBadge :icon="form.icono" :color="form.color" :alt="`Icono de ${form.nombre || 'la categoría'}`" />
                        <div>
                            <p class="font-semibold text-white">{{ form.nombre || 'Nueva categoría' }}</p>
                            <p class="text-sm text-white/55">{{ selectedIconLabel || 'Sin icono' }}</p>
                        </div>
                    </div>
                </div>

                <div v-if="formErrors.length" class="mt-6 rounded-2xl border border-amber-400/30 bg-amber-400/10 px-4 py-3 text-sm text-amber-50">
                    <ul class="space-y-2">
                        <li v-for="error in formErrors" :key="error">{{ error }}</li>
                    </ul>
                </div>

                <form class="mt-8 space-y-5" @submit.prevent="submit">
                    <FormField v-model="form.nombre" label="Nombre" placeholder="Ej. Alimentacion" required />

                    <div>
                        <label class="mb-2 block text-sm font-semibold text-white/80">Color<span class="ml-1 text-[var(--color-accent)]">*</span></label>
                        <div class="flex items-center gap-3">
                            <input v-model="form.color" type="color" required class="h-12 w-16 rounded-xl border border-white/10 bg-transparent p-1">
                            <input :value="form.color" type="text" disabled class="flex-1 rounded-2xl border border-white/10 bg-[var(--color-ink-soft)]/70 px-4 py-3 text-white/60">
                        </div>
                    </div>

                    <div>
                        <div class="mb-3 flex items-center justify-between gap-3">
                            <label class="block text-sm font-semibold text-white/80">Icono</label>
                            <button
                                v-if="form.icono"
                                type="button"
                                class="text-sm font-medium text-white/60 transition hover:text-white"
                                @click="form.icono = ''"
                            >
                                Quitar icono
                            </button>
                        </div>

                        <div v-if="iconOptions.length" class="grid min-w-0 grid-cols-3 gap-3 sm:grid-cols-5">
                            <button
                                v-for="icon in iconOptions"
                                :key="icon.name"
                                type="button"
                                :class="form.icono === icon.name ? 'border-[var(--color-gold)] bg-[var(--color-gold)]/14' : 'border-white/10 bg-white/5 hover:bg-white/10'"
                                class="flex items-center justify-center rounded-2xl border p-3 transition"
                                :title="icon.label"
                                @click="form.icono = icon.name"
                            >
                                <img :src="icon.url" :alt="icon.label" class="h-6 w-6 object-contain">
                            </button>
                        </div>

                        <div v-else class="rounded-2xl border border-dashed border-white/15 bg-white/5 px-4 py-4 text-sm leading-7 text-white/60">
                            No hay iconos disponibles todavía.
                        </div>
                    </div>

                    <button type="submit" class="w-full rounded-2xl bg-[var(--color-gold)] px-4 py-3 text-sm font-semibold text-[var(--color-ink)] transition hover:brightness-105">
                        Guardar categoría
                    </button>
                </form>
            </div>
        </aside>

        <section class="min-w-0 overflow-hidden rounded-[2rem] border border-white/10 bg-[linear-gradient(180deg,rgba(255,255,255,0.09),rgba(255,255,255,0.03))] p-6 backdrop-blur-lg sm:p-8">
            <div class="flex min-w-0 flex-wrap items-start justify-between gap-4">
                <div>
                    <p class="text-sm uppercase tracking-[0.2em] text-[var(--color-mint)]">Listado</p>
                    <h2 class="mt-2 font-[var(--font-display)] text-3xl font-bold">Categorías disponibles</h2>
                </div>

                <div class="flex flex-wrap items-center gap-3">
                    <div class="rounded-2xl border border-white/10 bg-white/8 px-4 py-2 text-sm text-white/70">
                        {{ localCategories.length }} categorías
                    </div>
                    <button
                        type="button"
                        class="rounded-2xl border border-white/10 bg-white/8 px-4 py-2 text-sm font-semibold text-white transition hover:bg-white/12"
                        @click="isListSectionOpen = !isListSectionOpen"
                    >
                        {{ isListSectionOpen ? 'Ocultar' : 'Mostrar' }}
                    </button>
                </div>
            </div>

            <div v-if="isListSectionOpen" class="mt-8 space-y-4">
                <article
                    v-for="categoria in localCategories"
                    :key="categoria.id"
                    class="rounded-3xl border border-white/10 bg-[linear-gradient(180deg,rgba(255,255,255,0.08),rgba(255,255,255,0.03))] p-5 shadow-lg shadow-black/10"
                >
                    <div class="flex min-w-0 flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                        <div class="flex min-w-0 items-center gap-4">
                            <CategoryIconBadge :icon="categoria.icono" :color="categoria.color" :alt="`Icono de ${categoria.nombre}`" />
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <h3 class="break-words text-xl font-semibold text-white">{{ categoria.nombre }}</h3>
                                    <span
                                        v-if="categoria.is_base"
                                        class="rounded-full border border-[var(--color-mint)]/25 bg-[var(--color-mint)]/10 px-2.5 py-1 text-[11px] font-semibold uppercase tracking-[0.18em] text-[var(--color-mint)]"
                                    >
                                        Base
                                    </span>
                                </div>
                                <p class="mt-1 text-sm text-white/60">
                                    Icono: {{ iconLabel(categoria.icono) || 'Sin icono' }} · {{ categoria.gastos_count }} gastos asociados
                                    <span v-if="categoria.is_base"> · Categoría predeterminada</span>
                                </p>
                            </div>
                        </div>

                        <div class="flex flex-wrap gap-2 lg:shrink-0 lg:justify-end">
                            <button
                                type="button"
                                class="rounded-xl border border-white/10 bg-white/8 px-3 py-2 text-xs font-semibold text-white transition hover:bg-white/12"
                                @click="toggleEdit(categoria)"
                            >
                                {{ editingCategoryId === categoria.id ? 'Cerrar' : 'Modificar' }}
                            </button>
                            <button
                                v-if="categoria.can_delete !== false"
                                type="button"
                                class="rounded-xl bg-[var(--color-danger)] px-3 py-2 text-xs font-semibold text-white transition hover:brightness-90"
                                @click="$emit('delete', categoria)"
                            >
                                Borrar
                            </button>
                        </div>
                    </div>

                    <form
                        v-if="editingCategoryId === categoria.id"
                        class="mt-5 grid gap-3 sm:grid-cols-3"
                        @submit.prevent="submitUpdate(categoria)"
                    >
                        <FormField v-model="categoria.nombre" label="Nombre" required />
                        <FormField v-model="categoria.color" label="Color" required />

                        <div class="sm:col-span-3">
                            <div class="mb-3 flex items-center justify-between gap-3">
                                <label class="block text-sm font-semibold text-white/80">Icono</label>
                                <button
                                    v-if="categoria.icono"
                                    type="button"
                                    class="text-sm font-medium text-white/60 transition hover:text-white"
                                    @click="categoria.icono = ''"
                                >
                                    Quitar icono
                                </button>
                            </div>

                            <div v-if="iconOptions.length" class="grid min-w-0 grid-cols-3 gap-3 sm:grid-cols-6">
                                <button
                                    v-for="icon in iconOptions"
                                    :key="`${categoria.id}-${icon.name}`"
                                    type="button"
                                    :class="categoria.icono === icon.name ? 'border-[var(--color-gold)] bg-[var(--color-gold)]/14' : 'border-white/10 bg-white/5 hover:bg-white/10'"
                                    class="flex items-center justify-center rounded-2xl border p-3 transition"
                                    :title="icon.label"
                                    @click="categoria.icono = icon.name"
                                >
                                    <img :src="icon.url" :alt="icon.label" class="h-6 w-6 object-contain">
                                </button>
                            </div>

                            <div v-else class="rounded-2xl border border-dashed border-white/15 bg-white/5 px-4 py-4 text-sm text-white/60">
                                No hay iconos disponibles.
                            </div>
                        </div>

                        <div class="sm:col-span-3 flex flex-wrap gap-2">
                            <button type="submit" class="rounded-xl border border-white/10 bg-white/8 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-white/12">
                                Guardar cambios
                            </button>
                            <button
                                type="button"
                                class="rounded-xl border border-white/10 bg-transparent px-4 py-2.5 text-sm font-semibold text-white/70 transition hover:bg-white/8 hover:text-white"
                                @click="cancelEdit"
                            >
                                Cancelar
                            </button>
                        </div>
                    </form>
                </article>

                <div v-if="localCategories.length === 0" class="rounded-3xl border border-dashed border-white/15 bg-white/5 px-5 py-8 text-center text-white/65">
                    Todavía no hay categorías creadas.
                </div>
            </div>
        </section>
    </section>
</template>

<script setup>
import { computed, reactive, ref, watch } from 'vue';
import CategoryIconBadge from '../../ui/category/CategoryIconBadge.vue';
import FormField from '../../ui/form/FormField.vue';

const props = defineProps({
    categories: { type: Array, required: true },
    iconOptions: { type: Array, default: () => [] },
});

const emit = defineEmits(['create', 'update', 'delete']);

const localCategories = ref(cloneCategories(props.categories));
const formErrors = ref([]);
const editingCategoryId = ref(null);
const isCreateSectionOpen = ref(false);
const isListSectionOpen = ref(true);
const form = reactive({
    nombre: '',
    color: '#ff7a59',
    icono: '',
});

const selectedIconLabel = computed(() => iconLabel(form.icono));

watch(() => props.categories, (value) => {
    localCategories.value = cloneCategories(value);

    if (editingCategoryId.value !== null && !localCategories.value.some((item) => item.id === editingCategoryId.value)) {
        editingCategoryId.value = null;
    }
}, { deep: true });

function submit() {
    formErrors.value = [];

    if (!form.nombre.trim()) {
        formErrors.value = ['El nombre es obligatorio.'];
        return;
    }

    emit('create', { ...form });
    form.nombre = '';
    form.color = '#ff7a59';
    form.icono = '';
}

function cloneCategories(value) {
    return value.map((item) => ({ ...item }));
}

function iconLabel(iconName) {
    return props.iconOptions.find((item) => item.name === iconName)?.label ?? iconName;
}

function toggleEdit(categoria) {
    editingCategoryId.value = editingCategoryId.value === categoria.id ? null : categoria.id;
}

function cancelEdit() {
    editingCategoryId.value = null;
    localCategories.value = cloneCategories(props.categories);
}

function submitUpdate(categoria) {
    emit('update', categoria);
    editingCategoryId.value = null;
}
</script>
