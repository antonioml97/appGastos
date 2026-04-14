<template>
    <main class="min-h-screen overflow-hidden bg-[var(--color-ink)] text-[var(--color-paper)]">
        <div class="pointer-events-none absolute inset-0 overflow-hidden">
            <div class="absolute left-[-8rem] top-[-6rem] h-64 w-64 rounded-full bg-[var(--color-accent)]/20 blur-3xl"></div>
            <div class="absolute right-[-5rem] top-20 h-56 w-56 rounded-full bg-[var(--color-gold)]/20 blur-3xl"></div>
            <div class="absolute bottom-[-8rem] left-1/3 h-72 w-72 rounded-full bg-[var(--color-mint)]/15 blur-3xl"></div>
        </div>

        <div class="relative mx-auto flex min-h-screen w-full max-w-[1600px] flex-col px-4 py-4 sm:px-6 lg:px-8 xl:px-10">
            <header class="sticky top-4 z-10 mb-8 rounded-[2rem] border border-white/10 bg-[rgba(8,16,27,0.74)] px-5 py-4 backdrop-blur-xl">
                <div class="flex min-w-0 flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                    <div class="flex min-w-0 items-center gap-4">
                        <img
                            :src="logoUrl"
                            alt="Logo de AppGastos"
                            class="h-14 w-14 rounded-2xl border border-white/10 bg-[#081225] p-2.5 shadow-lg shadow-black/20"
                        >
                        <div class="min-w-0">
                            <p class="text-sm uppercase tracking-[0.25em] text-[var(--color-gold)]/85">AppGastos</p>
                            <h1
                                v-if="showPageTitle"
                                class="mt-1 break-words font-[var(--font-display)] text-2xl font-bold sm:text-3xl"
                            >
                                {{ pageTitle }}
                            </h1>
                        </div>
                    </div>

                    <nav class="flex min-w-0 flex-wrap gap-3">
                        <a
                            v-for="item in menuItems"
                            :key="item.href"
                            :href="item.href"
                            :class="[
                                'max-w-full rounded-full px-4 py-2 text-sm font-semibold transition',
                                currentPath === item.href
                                    ? 'bg-[var(--color-gold)] text-[var(--color-ink)] shadow-lg'
                                    : 'border border-white/10 bg-white/6 text-white/80 hover:bg-white/10',
                            ]"
                        >
                            {{ item.label }}
                        </a>
                    </nav>
                </div>
            </header>

            <HomePage v-if="page === 'home'" :menu-items="menuItems" :state="home" />

            <CategoriesPage
                v-else-if="page === 'categories'"
                :categories="categories"
                :icon-options="categoryIcons"
                @create="createCategory"
                @update="updateCategory"
                @delete="deleteCategory"
            />

            <MonthlyPage
                v-else-if="page === 'monthly'"
                :state="monthly"
                @reload="reloadCurrentPage"
                @change-month="changeMonth"
                @create-expense="createExpense"
                @start-edit-expense="startEditExpense"
                @cancel-edit-expense="cancelEditExpense"
                @update-expense="updateExpense"
                @delete-expense="deleteExpense"
                @create-income="createIncome"
                @start-edit-income="startEditIncome"
                @cancel-edit-income="cancelEditIncome"
                @update-income="updateIncome"
                @delete-income="deleteIncome"
            />

            <SettingsPage
                v-else-if="page === 'settings'"
                :is-exporting="settings.isExporting"
                :is-importing="settings.isImporting"
                :is-clearing-data="settings.isClearingData"
                :fixed-entries="settings.fixedEntries"
                :accounts="settings.accounts"
                :categories="settings.categories"
                :base-categories="settings.baseCategories"
                @export="exportWorkbook"
                @import-workbook="importWorkbook"
                @clear-data="clearAllData"
                @create-account="createAccount"
                @update-account="updateAccount"
                @delete-account="deleteAccount"
                @withdraw-account="withdrawAccount"
                @create-fixed-entry="createFixedEntry"
                @update-fixed-entry="updateFixedEntry"
                @delete-fixed-entry="deleteFixedEntry"
            />

            <YearlyPage
                v-else
                :state="yearly"
                @change-year="changeYear"
            />
        </div>

        <transition name="toast">
            <div
                v-if="notice.message"
                :class="noticeClass"
                class="app-toast fixed z-50 rounded-2xl border px-4 py-3 text-center text-sm shadow-2xl"
            >
                {{ notice.message }}
            </div>
        </transition>
    </main>
</template>

<script setup>
import { computed, onBeforeUnmount, reactive, ref } from 'vue';
import CategoriesPage from '../pages/categories/CategoriesPage.vue';
import HomePage from '../pages/home/HomePage.vue';
import MonthlyPage from '../pages/monthly/MonthlyPage.vue';
import SettingsPage from '../pages/settings/SettingsPage.vue';
import YearlyPage from '../pages/yearly/YearlyPage.vue';

const initialData = window.__APP_DATA ?? { page: 'home', title: '' };

const page = ref(initialData.page ?? 'home');
const currentPath = ref(window.location.pathname);
const pageTitle = ref(initialData.title ?? '');
const notice = reactive({ type: '', message: '' });
const logoUrl = `${window.location.origin}/images/logo.svg`;
let noticeTimeout = null;
const showPageTitle = computed(() => page.value !== 'home' && Boolean(pageTitle.value));

const menuItems = [
    { label: 'Inicio', href: '/' },
    { label: 'Categorías', href: '/categorias' },
    { label: 'Gastos mensuales', href: '/gastos-mensuales' },
    { label: 'Gastos anuales', href: '/gastos-anuales' },
    { label: 'Configuracion', href: '/configuracion' },
];

const settings = reactive({
    exportUrl: initialData.settings?.exportUrl ?? '/configuracion/exportar-gastos',
    exportShareUrl: initialData.settings?.exportShareUrl ?? null,
    nativeShareExportEnabled: Boolean(initialData.settings?.nativeShareExportEnabled),
    clearDataUrl: initialData.settings?.clearDataUrl ?? '/configuracion/datos',
    isExporting: false,
    isImporting: false,
    isClearingData: false,
    fixedEntries: initialData.settings?.fixedEntries ?? [],
    accounts: initialData.settings?.accounts ?? [],
    categories: initialData.settings?.categories ?? [],
    baseCategories: initialData.settings?.baseCategories ?? [],
});
const home = reactive({
    selectedMonthLabel: initialData.home?.selectedMonthLabel ?? currentMonthLabel(),
    selectedMonthValue: initialData.home?.selectedMonthValue ?? currentMonthValue(),
    summary: initialData.home?.summary ?? { totalGastado: 0, totalIngresado: 0, totalMovimientos: 0, importeMedio: 0, balance: 0 },
    accountsSummary: initialData.home?.accountsSummary ?? { normal: 0, ahorro: 0 },
    topCategories: initialData.home?.topCategories ?? [],
});

const categories = ref(initialData.categories ?? []);
const categoryIcons = ref(initialData.categoryIcons ?? []);
const monthly = reactive({
    selectedMonthLabel: initialData.selectedMonthLabel ?? '',
    selectedMonthValue: initialData.selectedMonthValue ?? currentMonthValue(),
    accountsSummary: initialData.accountsSummary ?? { normal: 0, ahorro: 0 },
    summary: initialData.summary ?? { totalGastado: 0, totalIngresado: 0, totalMovimientos: 0, importeMedio: 0, balance: 0 },
    categorias: initialData.categorias ?? [],
    gastos: initialData.gastos ?? [],
    ingresos: initialData.ingresos ?? [],
    desglose: initialData.desglose ?? [],
    expenseErrors: [],
    incomeErrors: [],
    expenseForm: { titulo: '', importe: '', fecha: monthStart(initialData.selectedMonthValue), categoria_id: '', observaciones: '' },
    incomeForm: { titulo: '', importe: '', fecha: monthStart(initialData.selectedMonthValue), observaciones: '' },
    editExpenseId: null,
    editExpenseForm: { titulo: '', importe: '', fecha: todayValue(), categoria_id: '', observaciones: '' },
    editIncomeId: null,
    editIncomeForm: { titulo: '', importe: '', fecha: todayValue(), observaciones: '' },
});
const yearly = reactive({
    selectedYear: initialData.selectedYear ?? new Date().getFullYear(),
    years: initialData.years ?? [],
    summary: initialData.summary ?? { totalGastado: 0, totalIngresado: 0, balance: 0, totalMovimientos: 0 },
    monthly: initialData.monthly ?? [],
    categoryBreakdown: initialData.categoryBreakdown ?? [],
});

const noticeClass = computed(() => notice.type === 'error'
    ? 'border-rose-300/45 bg-[rgba(88,28,41,0.96)] text-rose-50'
    : 'border-emerald-300/45 bg-[rgba(6,78,59,0.96)] text-emerald-50');

async function createCategory(payload) {
    try {
        const { data } = await window.axios.post('/categorias', payload);
        categories.value.push(data.categoria);
        categories.value.sort((a, b) => a.nombre.localeCompare(b.nombre));
        monthly.categorias = categories.value.map(mapCategoryForMonthly);
        settings.categories = categories.value.map(mapCategoryForMonthly);
        showNotice('success', data.message);
    } catch (error) {
        const errors = extractErrors(error);
        showNotice('error', errors[0] ?? 'No se pudo crear la categoria.');
    }
}

async function updateCategory(payload) {
    try {
        const { data } = await window.axios.put(`/categorias/${payload.id}`, payload);
        const index = categories.value.findIndex((item) => item.id === payload.id);
        if (index !== -1) categories.value[index] = data.categoria;
        categories.value.sort((a, b) => a.nombre.localeCompare(b.nombre));
        monthly.categorias = categories.value.map(mapCategoryForMonthly);
        settings.categories = categories.value.map(mapCategoryForMonthly);
        showNotice('success', data.message);
    } catch (error) {
        showNotice('error', extractErrors(error)[0] ?? 'No se pudo actualizar la categoria.');
        await reloadCurrentPage();
    }
}

async function deleteCategory(payload) {
    if (!window.confirm(`Seguro que quieres borrar la categoria "${payload.nombre}"?`)) return;
    try {
        const { data } = await window.axios.delete(`/categorias/${payload.id}`);
        categories.value = categories.value.filter((item) => item.id !== payload.id);
        monthly.categorias = categories.value.map(mapCategoryForMonthly);
        settings.categories = categories.value.map(mapCategoryForMonthly);
        showNotice('success', data.message);
    } catch (error) {
        showNotice('error', extractErrors(error)[0] ?? 'No se pudo borrar la categoria.');
    }
}

async function changeMonth() {
    const url = `/gastos-mensuales?mes=${monthly.selectedMonthValue}`;
    history.replaceState({}, '', url);
    currentPath.value = '/gastos-mensuales';
    await reloadCurrentPage(url);
}

async function changeYear() {
    const url = `/gastos-anuales?anio=${yearly.selectedYear}`;
    history.replaceState({}, '', url);
    currentPath.value = '/gastos-anuales';
    await reloadCurrentPage(url);
}

async function createExpense(payload) {
    try {
        await window.axios.post('/gastos-mensuales/gastos', { ...payload, categoria_id: Number(payload.categoria_id) });
        monthly.expenseErrors = [];
        monthly.expenseForm = { titulo: '', importe: '', fecha: monthStart(monthly.selectedMonthValue), categoria_id: '', observaciones: '' };
        showNotice('success', 'Gasto añadido correctamente.');
        await reloadCurrentPage();
    } catch (error) {
        monthly.expenseErrors = extractErrors(error);
        showNotice('error', monthly.expenseErrors[0] ?? 'No se pudo guardar el gasto.');
    }
}

function startEditExpense(gasto) {
    monthly.editExpenseId = gasto.id;
    monthly.editExpenseForm = {
        titulo: gasto.titulo,
        importe: String(gasto.importe),
        fecha: gasto.fecha,
        categoria_id: gasto.categoria_id,
        observaciones: gasto.observaciones ?? '',
    };
}

function cancelEditExpense() {
    monthly.editExpenseId = null;
}

async function updateExpense(id) {
    try {
        await window.axios.put(`/gastos-mensuales/gastos/${id}`, { ...monthly.editExpenseForm, categoria_id: Number(monthly.editExpenseForm.categoria_id) });
        monthly.editExpenseId = null;
        showNotice('success', 'Gasto actualizado correctamente.');
        await reloadCurrentPage();
    } catch (error) {
        showNotice('error', extractErrors(error)[0] ?? 'No se pudo actualizar el gasto.');
    }
}

async function deleteExpense(gasto) {
    if (!window.confirm(`Seguro que quieres borrar el gasto "${gasto.titulo}"?`)) return;
    try {
        await window.axios.delete(`/gastos-mensuales/gastos/${gasto.id}`);
        showNotice('success', 'Gasto eliminado correctamente.');
        await reloadCurrentPage();
    } catch (error) {
        showNotice('error', extractErrors(error)[0] ?? 'No se pudo borrar el gasto.');
    }
}

async function createIncome(payload) {
    try {
        await window.axios.post('/gastos-mensuales/ingresos', payload);
        monthly.incomeErrors = [];
        monthly.incomeForm = { titulo: '', importe: '', fecha: monthStart(monthly.selectedMonthValue), observaciones: '' };
        showNotice('success', 'Ingreso añadido correctamente.');
        await reloadCurrentPage();
    } catch (error) {
        monthly.incomeErrors = extractErrors(error);
        showNotice('error', monthly.incomeErrors[0] ?? 'No se pudo guardar el ingreso.');
    }
}

function startEditIncome(ingreso) {
    monthly.editIncomeId = ingreso.id;
    monthly.editIncomeForm = {
        titulo: ingreso.titulo,
        importe: String(ingreso.importe),
        fecha: ingreso.fecha,
        observaciones: ingreso.observaciones ?? '',
    };
}

function cancelEditIncome() {
    monthly.editIncomeId = null;
}

async function updateIncome(id) {
    try {
        await window.axios.put(`/gastos-mensuales/ingresos/${id}`, { ...monthly.editIncomeForm });
        monthly.editIncomeId = null;
        showNotice('success', 'Ingreso actualizado correctamente.');
        await reloadCurrentPage();
    } catch (error) {
        showNotice('error', extractErrors(error)[0] ?? 'No se pudo actualizar el ingreso.');
    }
}

async function deleteIncome(ingreso) {
    if (!window.confirm(`Seguro que quieres borrar el ingreso "${ingreso.titulo}"?`)) return;
    try {
        await window.axios.delete(`/gastos-mensuales/ingresos/${ingreso.id}`);
        showNotice('success', 'Ingreso eliminado correctamente.');
        await reloadCurrentPage();
    } catch (error) {
        showNotice('error', extractErrors(error)[0] ?? 'No se pudo borrar el ingreso.');
    }
}

async function reloadCurrentPage(url = `${window.location.pathname}${window.location.search}`) {
    const { data } = await window.axios.get(url);
    hydrate(data);
}

async function createFixedEntry(payload) {
    try {
        const { data } = await window.axios.post('/configuracion/movimientos-fijos', payload);
        settings.fixedEntries.unshift(data.fixedEntry);
        sortFixedEntries();
        showNotice('success', data.message);
    } catch (error) {
        showNotice('error', extractErrors(error)[0] ?? 'No se pudo guardar el movimiento fijo.');
    }
}

async function createAccount(payload) {
    try {
        const { data } = await window.axios.post('/configuracion/cuentas', payload);
        settings.accounts.unshift(data.account);
        sortAccounts();
        showNotice('success', data.message);
    } catch (error) {
        showNotice('error', extractErrors(error)[0] ?? 'No se pudo guardar la cuenta.');
    }
}

async function updateAccount(payload) {
    try {
        const { data } = await window.axios.put(`/configuracion/cuentas/${payload.id}`, payload);
        const index = settings.accounts.findIndex((item) => item.id === payload.id);

        if (index !== -1) {
            settings.accounts[index] = data.account;
            sortAccounts();
        }

        showNotice('success', data.message);
    } catch (error) {
        showNotice('error', extractErrors(error)[0] ?? 'No se pudo actualizar la cuenta.');
    }
}

async function deleteAccount(account) {
    if (!window.confirm(`Seguro que quieres borrar la cuenta "${account.nombre}"?`)) return;

    try {
        const { data } = await window.axios.delete(`/configuracion/cuentas/${account.id}`);
        settings.accounts = settings.accounts.filter((item) => item.id !== account.id);
        showNotice('success', data.message);
    } catch (error) {
        showNotice('error', extractErrors(error)[0] ?? 'No se pudo borrar la cuenta.');
    }
}

async function withdrawAccount(payload) {
    try {
        const { data } = await window.axios.post(`/configuracion/cuentas/${payload.id}/retirar`, { importe: payload.importe });
        const index = settings.accounts.findIndex((item) => item.id === payload.id);

        if (index !== -1) {
            settings.accounts[index] = data.account;
            sortAccounts();
        }

        showNotice('success', data.message);
    } catch (error) {
        showNotice('error', extractErrors(error)[0] ?? 'No se pudo retirar dinero de la cuenta.');
    }
}

async function updateFixedEntry(payload) {
    try {
        const { data } = await window.axios.put(`/configuracion/movimientos-fijos/${payload.id}`, payload);
        const index = settings.fixedEntries.findIndex((item) => item.id === payload.id);

        if (index !== -1) {
            settings.fixedEntries[index] = data.fixedEntry;
            sortFixedEntries();
        }

        showNotice('success', data.message);
    } catch (error) {
        showNotice('error', extractErrors(error)[0] ?? 'No se pudo actualizar el movimiento fijo.');
    }
}

async function deleteFixedEntry(entry) {
    if (!window.confirm(`Seguro que quieres borrar el movimiento fijo "${entry.titulo}"?`)) return;

    try {
        const { data } = await window.axios.delete(`/configuracion/movimientos-fijos/${entry.id}`);
        settings.fixedEntries = settings.fixedEntries.filter((item) => item.id !== entry.id);
        showNotice('success', data.message);
    } catch (error) {
        showNotice('error', extractErrors(error)[0] ?? 'No se pudo borrar el movimiento fijo.');
    }
}

async function exportWorkbook() {
    if (settings.isExporting) return;

    settings.isExporting = true;

    try {
        if (canUseAndroidBridgeFileSave()) {
            const response = await window.axios.get(settings.exportUrl, {
                responseType: 'blob',
                headers: {
                    Accept: 'application/vnd.ms-excel',
                },
            });

            const contentDisposition = response.headers['content-disposition'] ?? '';
            const match = contentDisposition.match(/filename="?([^"]+)"?/i);
            const filename = normalizeExcelFilename(match?.[1] ?? 'appgastos-export.xls');
            const blob = new Blob([response.data], { type: response.data.type || 'application/vnd.ms-excel' });
            const saved = await saveWorkbookWithAndroidBridge(blob, filename);

            if (!saved) {
                throw new Error('No se pudo guardar el fichero en Android.');
            }

            showNotice('success', 'Excel guardado en Descargas/AppGastos.');
            return;
        }

        if (settings.nativeShareExportEnabled && settings.exportShareUrl) {
            const { data } = await window.axios.post(settings.exportShareUrl);
            showNotice('success', data.message ?? 'Se ha abierto el panel para exportar el Excel.');
            return;
        }

        const response = await window.axios.get(settings.exportUrl, {
            responseType: 'blob',
            headers: {
                Accept: 'application/vnd.ms-excel',
            },
        });

        const contentDisposition = response.headers['content-disposition'] ?? '';
        const match = contentDisposition.match(/filename="?([^"]+)"?/i);
        const filename = match?.[1] ?? 'appgastos-export.xls';
        const blob = new Blob([response.data], { type: response.data.type || 'application/vnd.ms-excel' });

        if (await shareWorkbookIfPossible(blob, filename)) {
            showNotice('success', 'Excel exportado correctamente.');
            return;
        }

        if (shouldUseDirectDownload()) {
            openDirectDownload();
            return;
        }

        const objectUrl = window.URL.createObjectURL(blob);
        const link = document.createElement('a');

        link.href = objectUrl;
        link.download = filename;
        link.rel = 'noopener';
        document.body.appendChild(link);
        link.click();
        link.remove();
        window.setTimeout(() => window.URL.revokeObjectURL(objectUrl), 1000);

        showNotice('success', 'Excel exportado correctamente.');
    } catch (error) {
        showNotice('error', extractErrors(error)[0] ?? 'No se pudo exportar el Excel.');
    } finally {
        settings.isExporting = false;
    }
}

function shouldUseDirectDownload() {
    const userAgent = window.navigator.userAgent || '';

    return /Android|iPhone|iPad|iPod/i.test(userAgent);
}

async function shareWorkbookIfPossible(blob, filename) {
    if (typeof navigator === 'undefined' || !shouldUseDirectDownload() || typeof navigator.share !== 'function') {
        return false;
    }

    try {
        const file = new File([blob], filename, { type: blob.type || 'application/vnd.ms-excel' });

        if (typeof navigator.canShare === 'function' && !navigator.canShare({ files: [file] })) {
            return false;
        }

        await navigator.share({
            title: filename,
            files: [file],
        });

        return true;
    } catch (error) {
        return false;
    }
}

function openDirectDownload() {
    const link = document.createElement('a');

    link.href = settings.exportUrl;
    link.target = '_blank';
    link.rel = 'noopener';
    document.body.appendChild(link);
    link.click();
    link.remove();
}

function canUseAndroidBridgeFileSave() {
    return typeof window !== 'undefined'
        && typeof window.AndroidBridge !== 'undefined'
        && typeof window.AndroidBridge.saveFile === 'function';
}

async function saveWorkbookWithAndroidBridge(blob, filename) {
    if (!canUseAndroidBridgeFileSave()) {
        return false;
    }

    try {
        const dataUrl = await blobToDataUrl(blob);
        const normalizedFilename = normalizeExcelFilename(filename);

        return window.AndroidBridge.saveFile(
            normalizedFilename,
            'application/vnd.ms-excel',
            dataUrl,
        ) !== false;
    } catch (error) {
        return false;
    }
}

function blobToDataUrl(blob) {
    return new Promise((resolve, reject) => {
        const reader = new FileReader();

        reader.onload = () => resolve(String(reader.result ?? ''));
        reader.onerror = () => reject(new Error('No se pudo convertir el fichero para guardarlo en Android.'));
        reader.readAsDataURL(blob);
    });
}

function normalizeExcelFilename(filename) {
    const trimmed = String(filename || 'appgastos-export.xls').trim();

    if (trimmed.toLowerCase().endsWith('.xls.html')) {
        return `${trimmed.slice(0, -5)}`;
    }

    if (trimmed.toLowerCase().endsWith('.html')) {
        return `${trimmed.slice(0, -5)}.xls`;
    }

    if (trimmed.toLowerCase().endsWith('.xls')) {
        return trimmed;
    }

    return `${trimmed}.xls`;
}

async function importWorkbook(file) {
    if (!file || settings.isImporting) return;

    settings.isImporting = true;

    try {
        const formData = new FormData();
        formData.append('archivo', file);

        const { data } = await window.axios.post('/configuracion/importar-excel', formData, {
            headers: {
                'Content-Type': 'multipart/form-data',
            },
        });

        showNotice('success', data.message);
        await reloadCurrentPage('/configuracion');
    } catch (error) {
        showNotice('error', extractErrors(error)[0] ?? 'No se pudo importar el Excel.');
    } finally {
        settings.isImporting = false;
    }
}

async function clearAllData() {
    if (settings.isClearingData) return;

    const accepted = window.confirm(
        'Se borraran gastos, ingresos, movimientos fijos, cuentas y categorias personalizadas. Esta accion no se puede deshacer. ¿Quieres continuar?',
    );

    if (!accepted) {
        return;
    }

    const confirmation = window.prompt('Escribe BORRAR para confirmar el borrado total de datos.');

    if (confirmation !== 'BORRAR') {
        showNotice('error', 'Borrado cancelado. No se ha eliminado ningun dato.');
        return;
    }

    settings.isClearingData = true;

    try {
        const { data } = await window.axios.delete(settings.clearDataUrl);
        showNotice('success', data.message ?? 'Todos los datos se han borrado correctamente.');
        currentPath.value = '/configuracion';
        history.replaceState({}, '', '/configuracion');
        await reloadCurrentPage('/configuracion');
    } catch (error) {
        showNotice('error', extractErrors(error)[0] ?? 'No se pudieron borrar los datos.');
    } finally {
        settings.isClearingData = false;
    }
}

function hydrate(payload) {
    page.value = payload.page ?? 'home';
    pageTitle.value = payload.title ?? '';

    if (payload.page === 'home') {
        home.selectedMonthLabel = payload.home?.selectedMonthLabel ?? currentMonthLabel();
        home.selectedMonthValue = payload.home?.selectedMonthValue ?? currentMonthValue();
        home.summary = payload.home?.summary ?? { totalGastado: 0, totalIngresado: 0, totalMovimientos: 0, importeMedio: 0, balance: 0 };
        home.accountsSummary = payload.home?.accountsSummary ?? { normal: 0, ahorro: 0 };
        home.topCategories = payload.home?.topCategories ?? [];
    }

    if (payload.page === 'categories') {
        categories.value = payload.categories ?? [];
        categoryIcons.value = payload.categoryIcons ?? [];
    }

    if (payload.page === 'monthly') {
        monthly.selectedMonthLabel = payload.selectedMonthLabel;
        monthly.selectedMonthValue = payload.selectedMonthValue;
        monthly.accountsSummary = payload.accountsSummary ?? { normal: 0, ahorro: 0 };
        monthly.summary = payload.summary;
        monthly.categorias = payload.categorias ?? [];
        monthly.gastos = payload.gastos ?? [];
        monthly.ingresos = payload.ingresos ?? [];
        monthly.desglose = payload.desglose ?? [];
        monthly.expenseForm.fecha = monthStart(payload.selectedMonthValue);
        monthly.incomeForm.fecha = monthStart(payload.selectedMonthValue);
    }

    if (payload.page === 'yearly') {
        yearly.selectedYear = payload.selectedYear;
        yearly.years = payload.years ?? [];
        yearly.summary = payload.summary ?? { totalGastado: 0, totalIngresado: 0, balance: 0, totalMovimientos: 0 };
        yearly.monthly = payload.monthly ?? [];
        yearly.categoryBreakdown = payload.categoryBreakdown ?? [];
    }

    if (payload.page === 'settings') {
        settings.exportUrl = payload.settings?.exportUrl ?? '/configuracion/exportar-gastos';
        settings.exportShareUrl = payload.settings?.exportShareUrl ?? null;
        settings.nativeShareExportEnabled = Boolean(payload.settings?.nativeShareExportEnabled);
        settings.clearDataUrl = payload.settings?.clearDataUrl ?? '/configuracion/datos';
        settings.fixedEntries = payload.settings?.fixedEntries ?? [];
        settings.accounts = payload.settings?.accounts ?? [];
        settings.categories = payload.settings?.categories ?? [];
        settings.baseCategories = payload.settings?.baseCategories ?? [];
    }
}

function extractErrors(error) {
    const errors = error?.response?.data?.errors;
    if (!errors) return error?.response?.data?.message ? [error.response.data.message] : [];
    return Object.values(errors).flat();
}

function showNotice(type, message) {
    if (noticeTimeout) {
        clearTimeout(noticeTimeout);
    }

    notice.type = type;
    notice.message = message;

    noticeTimeout = window.setTimeout(() => {
        notice.message = '';
        noticeTimeout = null;
    }, 3200);
}

function mapCategoryForMonthly(category) {
    return { id: category.id, nombre: category.nombre, color: category.color, icono: category.icono };
}

function sortFixedEntries() {
    settings.fixedEntries.sort((a, b) => {
        if (a.tipo !== b.tipo) {
            return a.tipo.localeCompare(b.tipo);
        }

        return a.titulo.localeCompare(b.titulo);
    });
}

function sortAccounts() {
    settings.accounts.sort((a, b) => {
        if (a.tipo !== b.tipo) {
            return a.tipo.localeCompare(b.tipo);
        }

        return a.nombre.localeCompare(b.nombre);
    });
}

function currentMonthValue() {
    const now = new Date();
    return `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}`;
}

function currentMonthLabel() {
    return new Intl.DateTimeFormat('es-ES', { month: 'long', year: 'numeric' }).format(new Date());
}

function monthStart(value) {
    return `${value ?? currentMonthValue()}-01`;
}

function todayValue() {
    const now = new Date();
    return `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}-${String(now.getDate()).padStart(2, '0')}`;
}

onBeforeUnmount(() => {
    if (noticeTimeout) {
        clearTimeout(noticeTimeout);
    }
});
</script>
