<template>
    <section class="app-page grid gap-3 xl:grid-cols-[1.08fr_0.92fr]">
        <section class="space-y-3">
            <section class="overflow-hidden rounded-[2rem] border border-white/10 bg-[linear-gradient(145deg,rgba(48,214,169,0.12),rgba(255,255,255,0.03))] p-6 shadow-xl shadow-black/10 backdrop-blur-lg sm:p-8">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <p class="text-sm uppercase tracking-[0.32em] text-[var(--color-mint)]/85">Cuentas</p>
                        <h2 class="mt-4 font-[var(--font-display)] text-[clamp(2rem,4.8vw,3.3rem)] leading-none font-bold">
                            Cuenta normal o de ahorro
                        </h2>
                        <p class="mt-5 max-w-2xl text-base leading-8 text-white/72">
                            Crea cuentas normales o cuentas de ahorro. El importe inicial se define aqui
                            y puedes ajustarlo desde configuracion o retirar cantidad cuando lo necesites.
                        </p>
                    </div>

                    <button
                        type="button"
                        class="rounded-2xl border border-white/10 bg-white/8 px-5 py-3 text-sm font-semibold text-white transition hover:bg-white/12"
                        @click="isAccountsSectionOpen = !isAccountsSectionOpen"
                    >
                        {{ isAccountsSectionOpen ? 'Ocultar cuentas' : 'Mostrar cuentas' }}
                    </button>
                </div>

                <article class="mt-6 flex flex-col gap-4 rounded-[1.5rem] border border-cyan-300/20 bg-[linear-gradient(135deg,rgba(34,211,238,0.12),rgba(16,185,129,0.05))] p-5 sm:flex-row sm:items-center sm:justify-between">
                    <div class="flex min-w-0 items-center gap-4">
                        <span class="grid h-12 w-12 shrink-0 place-items-center rounded-full border border-cyan-300/20 bg-cyan-300/10 text-2xl text-cyan-300" aria-hidden="true">☆</span>
                        <div class="min-w-0">
                            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-cyan-200/70">Cuenta de ahorro</p>
                            <p v-if="savingsAccount" class="mt-1 text-2xl font-semibold text-white">{{ formatCurrency(savingsAccount.saldo_actual) }}</p>
                            <p v-else class="mt-1 text-sm text-white/60">Todavía no has configurado una cuenta de ahorro.</p>
                            <p v-if="savingsAccount" class="mt-1 truncate text-sm text-white/55">{{ savingsAccount.nombre }} · saldo actual</p>
                        </div>
                    </div>

                    <button
                        type="button"
                        class="shrink-0 rounded-xl bg-cyan-300 px-5 py-3 text-sm font-bold text-[var(--color-ink)] transition hover:brightness-105"
                        @click="configureSavingsAccount"
                    >
                        {{ savingsAccount ? 'Modificar ahorro' : 'Configurar ahorro' }}
                    </button>
                </article>

                <div v-if="isAccountsSectionOpen && accountErrors.length" class="mt-6 rounded-2xl border border-amber-400/30 bg-amber-400/10 px-4 py-3 text-sm text-amber-50">
                    <ul class="space-y-2">
                        <li v-for="error in accountErrors" :key="error">{{ error }}</li>
                    </ul>
                </div>

                <form v-if="isAccountsSectionOpen" class="mt-8 space-y-5" @submit.prevent="submitAccount">
                    <div class="grid gap-4 sm:grid-cols-2">
                        <FormField v-model="accountForm.nombre" label="Nombre de la cuenta" placeholder="Ej. Cuenta principal" required />

                        <div>
                            <label class="mb-2 block text-sm font-semibold text-white/80">Tipo<span class="ml-1 text-[var(--color-accent)]">*</span></label>
                            <select v-model="accountForm.tipo" class="w-full rounded-2xl border border-white/10 bg-[var(--color-ink-soft)]/85 px-4 py-3 text-white outline-none transition focus:border-[var(--color-mint)]">
                                <option value="normal">Cuenta normal</option>
                                <option value="ahorro">Cuenta de ahorro</option>
                            </select>
                        </div>
                    </div>

                    <FormField v-model="accountForm.saldo_inicial" label="Importe inicial" type="number" step="0.01" min="0" required focus-color="var(--color-mint)" />

                    <div class="flex flex-wrap gap-3">
                        <button type="submit" class="rounded-2xl bg-[var(--color-mint)] px-5 py-3 text-sm font-semibold text-[var(--color-ink)] transition hover:brightness-105">
                            {{ editingAccountId ? 'Guardar cambios' : 'Crear cuenta' }}
                        </button>
                        <button
                            v-if="editingAccountId"
                            type="button"
                            class="rounded-2xl border border-white/10 bg-white/8 px-5 py-3 text-sm font-semibold text-white transition hover:bg-white/12"
                            @click="resetAccountForm"
                        >
                            Cancelar edicion
                        </button>
                    </div>
                </form>

                <div v-if="isAccountsSectionOpen" class="mt-8 space-y-4">
                    <article
                        v-for="account in accounts"
                        :key="account.id"
                        class="rounded-[1.6rem] border border-white/10 bg-[var(--color-ink-soft)]/55 p-5"
                    >
                        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-3">
                                    <span
                                        :class="account.tipo === 'ahorro' ? 'bg-[var(--color-mint)]/14 text-[var(--color-mint)]' : 'bg-white/10 text-white/75'"
                                        class="rounded-full px-3 py-1 text-xs font-semibold uppercase tracking-[0.18em]"
                                    >
                                        {{ account.tipo === 'ahorro' ? 'Ahorro' : 'Normal' }}
                                    </span>
                                    <span class="rounded-full border border-white/10 bg-white/8 px-3 py-1 text-xs font-semibold uppercase tracking-[0.18em] text-white/60">
                                        Inicial {{ formatCurrency(account.saldo_inicial) }}
                                    </span>
                                </div>

                                <h4 class="mt-4 text-xl font-semibold text-white">{{ account.nombre }}</h4>
                                <p class="mt-2 text-sm text-white/65">
                                    Saldo actual {{ formatCurrency(account.saldo_actual) }}
                                </p>
                            </div>

                            <div class="w-full max-w-md space-y-4">
                                <div v-if="account.tipo === 'ahorro'" class="rounded-[1.35rem] border border-white/10 bg-white/6 p-4">
                                    <p class="text-xs uppercase tracking-[0.2em] text-white/50">Retirar ahorro</p>
                                    <div class="mt-3 flex flex-col gap-3 sm:flex-row">
                                        <input
                                            v-model="withdrawForms[account.id]"
                                            type="number"
                                            min="0.01"
                                            step="0.01"
                                            placeholder="Cantidad"
                                            class="w-full rounded-2xl border border-white/10 bg-black/10 px-4 py-3 text-white outline-none transition placeholder:text-white/35 focus:border-[var(--color-mint)]"
                                        >
                                        <button
                                            type="button"
                                            class="rounded-2xl bg-[var(--color-danger)] px-4 py-3 text-sm font-semibold text-white transition hover:brightness-110"
                                            @click="withdrawFromAccount(account)"
                                        >
                                            Quitar cantidad
                                        </button>
                                    </div>
                                </div>

                                <div class="flex flex-wrap gap-3">
                                    <button type="button" class="rounded-2xl border border-white/10 bg-white/8 px-4 py-3 text-sm font-semibold text-white transition hover:bg-white/12" @click="startEditAccount(account)">
                                        Editar
                                    </button>
                                    <button type="button" class="rounded-2xl bg-[var(--color-danger)] px-4 py-3 text-sm font-semibold text-white transition hover:brightness-90" @click="$emit('delete-account', account)">
                                        Borrar
                                    </button>
                                </div>
                            </div>
                        </div>
                    </article>

                    <div v-if="accounts.length === 0" class="rounded-[1.5rem] border border-dashed border-white/12 bg-white/4 p-6 text-sm leading-7 text-white/65">
                        Todavia no has creado ninguna cuenta.
                    </div>
                </div>
            </section>

            <section class="overflow-hidden rounded-[2rem] border border-white/10 bg-[linear-gradient(145deg,rgba(255,255,255,0.08),rgba(255,255,255,0.02))] p-6 shadow-xl shadow-black/10 backdrop-blur-lg sm:p-8">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <p class="text-sm uppercase tracking-[0.32em] text-[var(--color-gold)]/85">Configuracion</p>
                        <h2 class="mt-4 font-[var(--font-display)] text-[clamp(2rem,4.8vw,3.3rem)] leading-none font-bold">
                            Movimientos fijos mensuales
                        </h2>
                        <p class="mt-5 max-w-2xl text-base leading-8 text-white/72">
                            Guarda gastos o ingresos que se repiten cada mes y la app los generara automaticamente
                            en la fecha indicada.
                        </p>
                    </div>

                    <button
                        type="button"
                        class="rounded-2xl border border-white/10 bg-white/8 px-5 py-3 text-sm font-semibold text-white transition hover:bg-white/12"
                        @click="isFixedSectionOpen = !isFixedSectionOpen"
                    >
                        {{ isFixedSectionOpen ? 'Ocultar movimientos fijos' : 'Mostrar movimientos fijos' }}
                    </button>
                </div>

                <div v-if="isFixedSectionOpen && errors.length" class="mt-6 rounded-2xl border border-amber-400/30 bg-amber-400/10 px-4 py-3 text-sm text-amber-50">
                    <ul class="space-y-2">
                        <li v-for="error in errors" :key="error">{{ error }}</li>
                    </ul>
                </div>

                <form v-if="isFixedSectionOpen" class="mt-8 space-y-5" @submit.prevent="submit">
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-white/80">Tipo<span class="ml-1 text-[var(--color-accent)]">*</span></label>
                            <select v-model="form.tipo" class="w-full rounded-2xl border border-white/10 bg-[var(--color-ink-soft)]/85 px-4 py-3 text-white outline-none transition focus:border-[var(--color-gold)]">
                                <option value="gasto">Gasto fijo</option>
                                <option value="ingreso">Ingreso fijo</option>
                            </select>
                        </div>

                        <FormField v-model="form.titulo" label="Titulo" placeholder="Ej. Alquiler" required />
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <FormField v-model="form.importe" label="Importe" type="number" step="0.01" min="0.01" required />
                        <FormField v-model="form.dia" label="Dia del mes" type="number" min="1" step="1" required />
                    </div>

                    <div v-if="form.tipo === 'gasto'">
                        <label class="mb-2 block text-sm font-semibold text-white/80">Categoria<span class="ml-1 text-[var(--color-accent)]">*</span></label>
                        <select v-model="form.categoria_id" class="w-full rounded-2xl border border-white/10 bg-[var(--color-ink-soft)]/85 px-4 py-3 text-white outline-none transition focus:border-[var(--color-gold)]">
                            <option value="">Selecciona una categoria</option>
                            <option v-for="categoria in categories" :key="categoria.id" :value="String(categoria.id)">{{ categoria.nombre }}</option>
                        </select>
                    </div>

                    <FormTextarea v-model="form.observaciones" label="Observaciones" placeholder="Notas opcionales" />

                    <label class="flex items-center gap-3 rounded-2xl border border-white/10 bg-white/6 px-4 py-3 text-sm text-white/80">
                        <input v-model="form.activo" type="checkbox" class="h-4 w-4 rounded border-white/10 bg-transparent">
                        Movimiento activo
                    </label>

                    <div class="flex flex-wrap gap-3">
                        <button type="submit" class="rounded-2xl bg-[var(--color-gold)] px-5 py-3 text-sm font-semibold text-[var(--color-ink)] transition hover:brightness-105">
                            {{ editingId ? 'Guardar cambios' : 'Guardar movimiento fijo' }}
                        </button>
                        <button
                            v-if="editingId"
                            type="button"
                            class="rounded-2xl border border-white/10 bg-white/8 px-5 py-3 text-sm font-semibold text-white transition hover:bg-white/12"
                            @click="resetForm"
                        >
                            Cancelar edicion
                        </button>
                    </div>
                </form>
            </section>

            <section class="overflow-hidden rounded-[2rem] border border-white/10 bg-white/6 p-6 shadow-xl shadow-black/10 backdrop-blur-lg sm:p-8">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <p class="text-sm uppercase tracking-[0.28em] text-[var(--color-mint)]/85">Listado</p>
                        <h3 class="mt-3 font-[var(--font-display)] text-[clamp(1.9rem,4vw,2.8rem)] leading-none font-bold">
                            Fijos guardados
                        </h3>
                    </div>
                    <div class="flex flex-wrap items-center gap-3">
                        <span class="rounded-full border border-white/10 bg-white/8 px-4 py-2 text-sm text-white/70">
                            {{ fixedEntries.length }} movimientos
                        </span>
                        <button
                            type="button"
                            class="rounded-2xl border border-white/10 bg-white/8 px-5 py-3 text-sm font-semibold text-white transition hover:bg-white/12"
                            @click="isFixedEntriesSectionOpen = !isFixedEntriesSectionOpen"
                        >
                            {{ isFixedEntriesSectionOpen ? 'Ocultar listado' : 'Mostrar listado' }}
                        </button>
                    </div>
                </div>

                <div v-if="isFixedEntriesSectionOpen" class="mt-8 space-y-4">
                    <article
                        v-for="entry in fixedEntries"
                        :key="entry.id"
                        class="rounded-[1.6rem] border border-white/10 bg-[var(--color-ink-soft)]/55 p-5"
                    >
                        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-3">
                                    <span
                                        :class="entry.tipo === 'gasto' ? 'bg-[var(--color-danger)]/14 text-[var(--color-danger)]' : 'bg-[var(--color-mint)]/14 text-[var(--color-mint)]'"
                                        class="rounded-full px-3 py-1 text-xs font-semibold uppercase tracking-[0.18em]"
                                    >
                                        {{ entry.tipo }}
                                    </span>
                                    <span
                                        :class="entry.activo ? 'bg-emerald-400/14 text-emerald-200' : 'bg-white/10 text-white/55'"
                                        class="rounded-full px-3 py-1 text-xs font-semibold uppercase tracking-[0.18em]"
                                    >
                                        {{ entry.activo ? 'Activo' : 'Pausado' }}
                                    </span>
                                </div>

                                <h4 class="mt-4 text-xl font-semibold text-white">{{ entry.titulo }}</h4>
                                <p class="mt-2 text-sm text-white/60">
                                    {{ formatCurrency(entry.importe) }} · Dia {{ entry.dia }}
                                    <span v-if="entry.categoria">· {{ entry.categoria.nombre }}</span>
                                </p>
                                <p v-if="entry.observaciones" class="mt-3 text-sm leading-7 text-white/68">{{ entry.observaciones }}</p>
                            </div>

                            <div class="flex flex-wrap gap-3">
                                <button type="button" class="rounded-2xl border border-white/10 bg-white/8 px-4 py-3 text-sm font-semibold text-white transition hover:bg-white/12" @click="startEdit(entry)">
                                    Editar
                                </button>
                                <button type="button" class="rounded-2xl bg-[var(--color-danger)] px-4 py-3 text-sm font-semibold text-white transition hover:brightness-90" @click="$emit('delete-fixed-entry', entry)">
                                    Borrar
                                </button>
                            </div>
                        </div>
                    </article>

                    <div v-if="fixedEntries.length === 0" class="rounded-[1.5rem] border border-dashed border-white/12 bg-white/4 p-6 text-sm leading-7 text-white/65">
                        Todavia no has creado gastos o ingresos fijos mensuales.
                    </div>
                </div>
            </section>
        </section>

        <aside class="space-y-3">
            <AccountSecurityPanel @user-deleted="emit('user-deleted', $event)" />

            <section class="overflow-hidden rounded-[2rem] border border-white/10 bg-[linear-gradient(145deg,rgba(255,255,255,0.08),rgba(255,255,255,0.02))] p-6 shadow-xl shadow-black/10 backdrop-blur-lg sm:p-8">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <p class="text-sm uppercase tracking-[0.32em] text-[var(--color-gold)]/85">Excel</p>
                        <h2 class="mt-4 font-[var(--font-display)] text-[clamp(2rem,4.8vw,3.3rem)] leading-none font-bold">
                            Importa y exporta tu historial
                        </h2>
                        <p class="mt-5 max-w-2xl text-base leading-8 text-white/72">
                            Genera un archivo Excel ordenado por hojas con gastos, ingresos, movimientos
                            fijos y categorias, o importa despues ese mismo fichero para recuperar tus datos.
                        </p>
                    </div>

                    <button
                        type="button"
                        class="rounded-2xl border border-white/10 bg-white/8 px-5 py-3 text-sm font-semibold text-white transition hover:bg-white/12"
                        @click="isExportSectionOpen = !isExportSectionOpen"
                    >
                        {{ isExportSectionOpen ? 'Ocultar opciones Excel' : 'Mostrar opciones Excel' }}
                    </button>
                </div>

                <div v-if="isExportSectionOpen" class="mt-8 flex flex-col gap-4">
                    <button
                        type="button"
                        :disabled="isExporting"
                        class="inline-flex items-center justify-center rounded-2xl bg-[var(--color-gold)] px-6 py-4 text-sm font-semibold text-[var(--color-ink)] transition hover:brightness-105 disabled:cursor-wait disabled:opacity-70"
                        @click="$emit('export')"
                    >
                        {{ isExporting ? 'Preparando archivo...' : 'Exportar Excel' }}
                    </button>
                    <p class="text-sm leading-7 text-white/55">
                        Descarga un unico fichero compatible con Excel con un resumen inicial y hojas separadas
                        para gastos, ingresos, movimientos fijos y categorias.
                    </p>
                </div>

                <div v-if="isExportSectionOpen" class="mt-8 rounded-[1.6rem] border border-white/10 bg-[var(--color-ink-soft)]/55 p-5">
                    <p class="text-xs uppercase tracking-[0.24em] text-[var(--color-mint)]/85">Importacion</p>
                    <h3 class="mt-3 text-lg font-semibold text-white">Importa un Excel exportado por AppGastos</h3>
                    <p class="mt-2 text-sm leading-7 text-white/65">
                        Selecciona un fichero `.xls` o `.xml` generado por esta misma app. La importacion mezcla
                        categorias, gastos, ingresos y movimientos fijos, y omite duplicados exactos.
                    </p>

                    <div v-if="importErrors.length" class="mt-5 rounded-2xl border border-amber-400/30 bg-amber-400/10 px-4 py-3 text-sm text-amber-50">
                        <ul class="space-y-2">
                            <li v-for="error in importErrors" :key="error">{{ error }}</li>
                        </ul>
                    </div>

                    <div class="mt-5 flex flex-col gap-4">
                        <input
                            :key="importInputKey"
                            type="file"
                            accept=".xls,.xml"
                            class="w-full rounded-2xl border border-white/10 bg-black/10 px-4 py-3 text-sm text-white file:mr-4 file:rounded-xl file:border-0 file:bg-[var(--color-mint)] file:px-4 file:py-2 file:text-sm file:font-semibold file:text-[var(--color-ink)]"
                            @change="handleImportFileChange"
                        >
                        <p v-if="selectedImportFileName" class="text-sm text-white/55">
                            Archivo seleccionado: {{ selectedImportFileName }}
                        </p>
                        <button
                            type="button"
                            :disabled="isImporting"
                            class="inline-flex items-center justify-center rounded-2xl bg-[var(--color-mint)] px-6 py-4 text-sm font-semibold text-[var(--color-ink)] transition hover:brightness-105 disabled:cursor-wait disabled:opacity-70"
                            @click="submitImport"
                        >
                            {{ isImporting ? 'Importando Excel...' : 'Importar Excel' }}
                        </button>
                    </div>
                </div>
            </section>

            <section class="overflow-hidden rounded-[2rem] border border-rose-400/30 bg-[linear-gradient(145deg,rgba(244,63,94,0.16),rgba(255,255,255,0.03))] p-6 shadow-xl shadow-black/10 backdrop-blur-lg sm:p-8">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <p class="text-sm uppercase tracking-[0.32em] text-rose-200/85">Zona de peligro</p>
                        <h2 class="mt-4 font-[var(--font-display)] text-[clamp(2rem,4.8vw,3.1rem)] leading-none font-bold">
                            Borrar todos los datos
                        </h2>
                        <p class="mt-5 max-w-2xl text-base leading-8 text-white/72">
                            Elimina gastos, ingresos, movimientos fijos, cuentas y categorias personalizadas.
                            Las categorias base de AppGastos se mantendran disponibles para empezar de nuevo.
                        </p>
                        <p class="mt-4 text-sm leading-7 text-rose-100/80">
                            Esta accion no se puede deshacer y pedira una confirmacion extra antes de ejecutarse.
                        </p>
                    </div>

                    <button
                        type="button"
                        class="rounded-2xl border border-white/10 bg-white/8 px-5 py-3 text-sm font-semibold text-white transition hover:bg-white/12"
                        @click="isDangerSectionOpen = !isDangerSectionOpen"
                    >
                        {{ isDangerSectionOpen ? 'Ocultar borrado' : 'Mostrar borrado' }}
                    </button>
                </div>

                <div v-if="isDangerSectionOpen" class="mt-8 flex flex-col gap-4">
                    <button
                        type="button"
                        :disabled="isClearingData"
                        class="inline-flex items-center justify-center rounded-2xl bg-[var(--color-danger)] px-6 py-4 text-sm font-semibold text-white transition hover:brightness-90 disabled:cursor-wait disabled:opacity-70"
                        @click="emit('clear-data')"
                    >
                        {{ isClearingData ? 'Borrando datos...' : 'Borrar todos los datos' }}
                    </button>
                    <p class="text-sm leading-7 text-white/55">
                        Usa esta opcion solo si quieres reiniciar completamente la informacion guardada.
                    </p>
                </div>
            </section>
        </aside>
    </section>
</template>

<script setup>
import { computed, reactive, ref, watch } from 'vue';
import AccountSecurityPanel from './AccountSecurityPanel.vue';
import FormField from '../../ui/form/FormField.vue';
import FormTextarea from '../../ui/form/FormTextarea.vue';

const props = defineProps({
    isExporting: { type: Boolean, default: false },
    isImporting: { type: Boolean, default: false },
    isClearingData: { type: Boolean, default: false },
    fixedEntries: { type: Array, default: () => [] },
    accounts: { type: Array, default: () => [] },
    categories: { type: Array, default: () => [] },
});

const emit = defineEmits([
    'export',
    'import-workbook',
    'clear-data',
    'create-account',
    'update-account',
    'delete-account',
    'withdraw-account',
    'create-fixed-entry',
    'update-fixed-entry',
    'delete-fixed-entry',
    'user-deleted',
]);

const editingAccountId = ref(null);
const accountErrors = ref([]);
const editingId = ref(null);
const errors = ref([]);
const isAccountsSectionOpen = ref(true);
const isFixedSectionOpen = ref(false);
const isFixedEntriesSectionOpen = ref(false);
const isExportSectionOpen = ref(false);
const isDangerSectionOpen = ref(false);
const importErrors = ref([]);
const selectedImportFile = ref(null);
const selectedImportFileName = ref('');
const importInputKey = ref(0);
const withdrawForms = reactive({});
const accountForm = reactive(buildDefaultAccountForm());
const form = reactive(buildDefaultForm());
const savingsAccount = computed(() => props.accounts.find((account) => account.tipo === 'ahorro') ?? null);

watch(() => form.tipo, (value) => {
    if (value !== 'gasto') {
        form.categoria_id = '';
    }
});

function submitAccount() {
    accountErrors.value = [];

    if (!accountForm.nombre.trim()) {
        accountErrors.value = ['El nombre de la cuenta es obligatorio.'];
        return;
    }

    const payload = {
        nombre: accountForm.nombre,
        tipo: accountForm.tipo,
        saldo_inicial: accountForm.saldo_inicial,
    };

    if (editingAccountId.value) {
        emit('update-account', { id: editingAccountId.value, ...payload });
        resetAccountForm();
        return;
    }

    emit('create-account', payload);
    resetAccountForm();
}

function submit() {
    errors.value = [];

    if (!form.titulo.trim()) {
        errors.value = ['El titulo es obligatorio.'];
        return;
    }

    if (form.tipo === 'gasto' && !form.categoria_id) {
        errors.value = ['Debes seleccionar una categoria para el gasto fijo.'];
        return;
    }

    const payload = {
        tipo: form.tipo,
        titulo: form.titulo,
        importe: form.importe,
        dia: form.dia,
        categoria_id: form.tipo === 'gasto' && form.categoria_id ? Number(form.categoria_id) : null,
        observaciones: form.observaciones,
        activo: form.activo,
    };

    if (editingId.value) {
        emit('update-fixed-entry', { id: editingId.value, ...payload });
        resetForm();
        return;
    }

    emit('create-fixed-entry', payload);
    resetForm();
}

function startEditAccount(account) {
    isAccountsSectionOpen.value = true;
    editingAccountId.value = account.id;
    accountForm.nombre = account.nombre;
    accountForm.tipo = account.tipo;
    accountForm.saldo_inicial = String(account.saldo_inicial);
    accountErrors.value = [];
}

function configureSavingsAccount() {
    isAccountsSectionOpen.value = true;

    if (savingsAccount.value) {
        startEditAccount(savingsAccount.value);
        return;
    }

    resetAccountForm();
    accountForm.nombre = 'Cuenta de ahorro';
    accountForm.tipo = 'ahorro';
}

function startEdit(entry) {
    isFixedSectionOpen.value = true;
    isFixedEntriesSectionOpen.value = true;
    editingId.value = entry.id;
    form.tipo = entry.tipo;
    form.titulo = entry.titulo;
    form.importe = String(entry.importe);
    form.dia = String(entry.dia);
    form.categoria_id = entry.categoria_id ? String(entry.categoria_id) : '';
    form.observaciones = entry.observaciones ?? '';
    form.activo = Boolean(entry.activo);
    errors.value = [];
}

function resetForm() {
    editingId.value = null;
    Object.assign(form, buildDefaultForm());
    errors.value = [];
}

function resetAccountForm() {
    editingAccountId.value = null;
    Object.assign(accountForm, buildDefaultAccountForm());
    accountErrors.value = [];
}

function handleImportFileChange(event) {
    const [file] = Array.from(event.target?.files ?? []);
    selectedImportFile.value = file ?? null;
    selectedImportFileName.value = file?.name ?? '';
    importErrors.value = [];
}

function submitImport() {
    if (!selectedImportFile.value) {
        importErrors.value = ['Debes seleccionar un fichero exportado antes de importarlo.'];
        isExportSectionOpen.value = true;
        return;
    }

    importErrors.value = [];
    emit('import-workbook', selectedImportFile.value);
    selectedImportFile.value = null;
    selectedImportFileName.value = '';
    importInputKey.value += 1;
}

function buildDefaultForm() {
    return {
        tipo: 'gasto',
        titulo: '',
        importe: '',
        dia: '1',
        categoria_id: '',
        observaciones: '',
        activo: true,
    };
}

function buildDefaultAccountForm() {
    return {
        nombre: '',
        tipo: 'normal',
        saldo_inicial: '0',
    };
}

function formatCurrency(value) {
    return `${Number(value ?? 0).toLocaleString('es-ES', { minimumFractionDigits: 2, maximumFractionDigits: 2 })} EUR`;
}

function withdrawFromAccount(account) {
    const importe = withdrawForms[account.id];

    if (!importe || Number(importe) <= 0) {
        accountErrors.value = ['Debes indicar una cantidad valida para retirar de la cuenta de ahorro.'];
        return;
    }

    emit('withdraw-account', { id: account.id, importe });
    withdrawForms[account.id] = '';
    accountErrors.value = [];
}
</script>
