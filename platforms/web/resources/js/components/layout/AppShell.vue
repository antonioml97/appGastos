<template>
    <main v-if="authStatus === 'loading'" class="flex min-h-screen items-center justify-center bg-[var(--color-ink)] text-[var(--color-paper)]">
        <div class="rounded-3xl border border-white/10 bg-white/6 px-8 py-6 text-center shadow-2xl">
            <p class="font-[var(--font-display)] text-xl font-bold">AppGastos</p>
            <p class="mt-2 text-sm text-white/60">Sincronizando tu sesión…</p>
        </div>
    </main>

    <AuthPage
        v-else-if="authStatus === 'guest'"
        :error-message="authError"
        :submitting="authSubmitting"
        @authenticate="authenticate"
        @clear-error="authError = ''"
    />

    <main v-else class="min-h-screen overflow-hidden bg-[var(--color-ink)] text-[var(--color-paper)]">
        <div class="relative mx-auto flex min-h-screen w-full max-w-[1536px] flex-col px-4 sm:px-6 lg:px-8">
            <header class="sticky top-0 z-20 border-b border-white/[0.07] bg-[rgba(3,13,25,0.9)] py-3 backdrop-blur-xl">
                <div class="flex min-w-0 flex-wrap items-center gap-5 lg:flex-nowrap lg:gap-8">
                    <a href="/" class="flex min-w-0 shrink-0 items-center gap-3">
                        <img
                            :src="logoUrl"
                            alt="Logo de AppGastos"
                            class="h-10 w-10 rounded-xl border border-cyan-300/10 bg-[#061425] p-1.5"
                        >
                        <p class="hidden text-base font-bold uppercase tracking-[0.22em] text-white sm:block">
                            App<span class="text-[var(--color-gold)]">Gastos</span>
                        </p>
                    </a>

                    <nav class="scrollbar-none order-3 -mx-4 flex min-w-0 flex-1 gap-1 overflow-x-auto px-4 sm:mx-0 sm:px-0 lg:order-none">
                        <a
                            v-for="item in menuItems"
                            :key="item.href"
                            :href="item.href"
                            :class="[
                                'relative flex shrink-0 items-center gap-2 px-3 py-3 text-xs font-medium transition lg:px-4',
                                currentPath === item.href
                                    ? 'text-white after:absolute after:inset-x-2 after:-bottom-3 after:h-0.5 after:bg-[var(--color-gold)]'
                                    : 'text-white/55 hover:text-white/90',
                            ]"
                        >
                            <span class="text-base leading-none" aria-hidden="true">{{ item.icon }}</span>
                            {{ item.label }}
                        </a>
                    </nav>

                    <div class="ml-auto flex shrink-0 items-center gap-3">
                        <a href="/gastos-mensuales" class="hidden items-center gap-2 rounded-lg bg-[var(--color-gold)] px-4 py-2.5 text-xs font-bold text-[#07111f] shadow-[0_8px_24px_rgba(247,196,94,0.18)] transition hover:brightness-110 md:flex">
                            <span class="text-lg leading-none">+</span> Añadir movimiento
                        </a>
                        <button type="button" class="grid h-9 w-9 place-items-center rounded-full border border-white/10 bg-white/[0.04] text-xs font-semibold text-white/80 transition hover:border-white/20 hover:bg-white/[0.08]" :title="`Cerrar sesión${currentUser?.name ? ` de ${currentUser.name}` : ''}`" @click="logout">
                            {{ userInitials }}
                        </button>
                    </div>
                </div>
            </header>

            <div v-if="showPageTitle" class="pb-6 pt-8">
                <h1 class="font-[var(--font-display)] text-3xl font-bold">{{ pageTitle }}</h1>
            </div>

            <HomePage v-if="page === 'home'" :menu-items="menuItems" :state="home" :user="currentUser" />

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
                @user-deleted="handleUserDeleted"
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
import { computed, onBeforeUnmount, onMounted, reactive, ref } from 'vue';
import AuthPage from '../pages/auth/AuthPage.vue';
import CategoriesPage from '../pages/categories/CategoriesPage.vue';
import HomePage from '../pages/home/HomePage.vue';
import MonthlyPage from '../pages/monthly/MonthlyPage.vue';
import SettingsPage from '../pages/settings/SettingsPage.vue';
import YearlyPage from '../pages/yearly/YearlyPage.vue';

const initialData = window.__APP_DATA ?? { page: 'home', title: '' };

const authStatus = ref('loading');
const authSubmitting = ref(false);
const authError = ref('');
const currentUser = ref(null);

const page = ref(initialData.page ?? 'home');
const currentPath = ref(window.location.pathname);
const pageTitle = ref(initialData.title ?? '');
const notice = reactive({ type: '', message: '' });
const logoUrl = `${window.location.origin}/images/logo.svg`;
let noticeTimeout = null;
const showPageTitle = computed(() => page.value !== 'home' && Boolean(pageTitle.value));

const menuItems = [
    { label: 'Inicio', href: '/', icon: '⌂' },
    { label: 'Movimientos', href: '/gastos-mensuales', icon: '☷' },
    { label: 'Categorías', href: '/categorias', icon: '⌘' },
    { label: 'Informes', href: '/gastos-anuales', icon: '▥' },
    { label: 'Configuración', href: '/configuracion', icon: '⚙' },
];

const userInitials = computed(() => {
    const name = currentUser.value?.name?.trim();
    if (!name) return 'AG';

    return name.split(/\s+/).slice(0, 2).map((part) => part[0]).join('').toUpperCase();
});

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
        const { data } = await window.api.post('/categorias', payload);
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
        const { data } = await window.api.put(`/categorias/${payload.id}`, payload);
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
        const { data } = await window.api.delete(`/categorias/${payload.id}`);
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
        await window.api.post('/gastos-mensuales/gastos', { ...payload, categoria_id: Number(payload.categoria_id) });
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

async function updateExpense(payload) {
    try {
        const { id, ...expense } = payload;
        await window.api.put(`/gastos-mensuales/gastos/${id}`, expense);
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
        await window.api.delete(`/gastos-mensuales/gastos/${gasto.id}`);
        showNotice('success', 'Gasto eliminado correctamente.');
        await reloadCurrentPage();
    } catch (error) {
        showNotice('error', extractErrors(error)[0] ?? 'No se pudo borrar el gasto.');
    }
}

async function createIncome(payload) {
    try {
        await window.api.post('/gastos-mensuales/ingresos', payload);
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
        await window.api.put(`/gastos-mensuales/ingresos/${id}`, { ...monthly.editIncomeForm });
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
        await window.api.delete(`/gastos-mensuales/ingresos/${ingreso.id}`);
        showNotice('success', 'Ingreso eliminado correctamente.');
        await reloadCurrentPage();
    } catch (error) {
        showNotice('error', extractErrors(error)[0] ?? 'No se pudo borrar el ingreso.');
    }
}

async function reloadCurrentPage(url = `${window.location.pathname}${window.location.search}`) {
    const parsedUrl = new URL(url, window.location.origin);
    const endpoint = parsedUrl.pathname === '/' ? '/dashboard' : `${parsedUrl.pathname}${parsedUrl.search}`;
    const { data } = await window.api.get(endpoint);
    hydrate(data);
}

async function createFixedEntry(payload) {
    try {
        const { data } = await window.api.post('/configuracion/movimientos-fijos', payload);
        settings.fixedEntries.unshift(data.fixedEntry);
        sortFixedEntries();
        showNotice('success', data.message);
    } catch (error) {
        showNotice('error', extractErrors(error)[0] ?? 'No se pudo guardar el movimiento fijo.');
    }
}

async function createAccount(payload) {
    try {
        const { data } = await window.api.post('/configuracion/cuentas', payload);
        settings.accounts.unshift(data.account);
        sortAccounts();
        showNotice('success', data.message);
    } catch (error) {
        showNotice('error', extractErrors(error)[0] ?? 'No se pudo guardar la cuenta.');
    }
}

async function updateAccount(payload) {
    try {
        const { data } = await window.api.put(`/configuracion/cuentas/${payload.id}`, payload);
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
        const { data } = await window.api.delete(`/configuracion/cuentas/${account.id}`);
        settings.accounts = settings.accounts.filter((item) => item.id !== account.id);
        showNotice('success', data.message);
    } catch (error) {
        showNotice('error', extractErrors(error)[0] ?? 'No se pudo borrar la cuenta.');
    }
}

async function withdrawAccount(payload) {
    try {
        const { data } = await window.api.post(`/configuracion/cuentas/${payload.id}/retirar`, { importe: payload.importe });
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
        const { data } = await window.api.put(`/configuracion/movimientos-fijos/${payload.id}`, payload);
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
        const { data } = await window.api.delete(`/configuracion/movimientos-fijos/${entry.id}`);
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
            const response = await window.api.get(settings.exportUrl, {
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
            const { data } = await window.api.post(settings.exportShareUrl);
            showNotice('success', data.message ?? 'Se ha abierto el panel para exportar el Excel.');
            return;
        }

        const response = await window.api.get(settings.exportUrl, {
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

        const { data } = await window.api.post('/configuracion/importar-excel', formData, {
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
        const { data } = await window.api.delete(settings.clearDataUrl);
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

async function initializeAuth() {
    const token = window.localStorage.getItem('appgastos_token');

    if (!token) {
        authStatus.value = 'guest';
        return;
    }

    try {
        const { data } = await window.api.get('/auth/user');
        currentUser.value = data.user;
        authStatus.value = 'authenticated';
        await reloadCurrentPage();
    } catch (error) {
        clearSession();
        authError.value = extractErrors(error)[0] ?? 'No se pudo recuperar la sesión.';
    }
}

async function authenticate({ mode, payload }) {
    authSubmitting.value = true;
    authError.value = '';

    try {
        const { data } = await window.api.post(`/auth/${mode}`, {
            ...payload,
            device_name: nativeDeviceName(),
        });

        window.localStorage.setItem('appgastos_token', data.token);
        currentUser.value = data.user;
        authStatus.value = 'authenticated';
        await reloadCurrentPage();
    } catch (error) {
        authError.value = extractErrors(error)[0] ?? 'No se pudo iniciar sesión.';
    } finally {
        authSubmitting.value = false;
    }
}

async function logout() {
    try {
        await window.api.delete('/auth/logout');
    } catch {
        // El token local se elimina igualmente si el servidor no está disponible.
    } finally {
        clearSession();
    }
}

function handleUserDeleted(message) {
    clearSession();
    authError.value = message ?? 'Tu cuenta se ha eliminado definitivamente.';
}

function clearSession() {
    window.localStorage.removeItem('appgastos_token');
    currentUser.value = null;
    authStatus.value = 'guest';
}

function nativeDeviceName() {
    const agent = window.navigator.userAgent ?? '';
    if (/Android/i.test(agent)) return 'AppGastos Android';
    if (/Windows/i.test(agent)) return 'AppGastos Windows';
    return 'AppGastos Web';
}

function handleUnauthorized() {
    if (authStatus.value !== 'guest') {
        clearSession();
        authError.value = 'Tu sesión ha caducado. Vuelve a iniciar sesión.';
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

onMounted(() => {
    window.addEventListener('appgastos:unauthorized', handleUnauthorized);
    initializeAuth();
});

onBeforeUnmount(() => {
    window.removeEventListener('appgastos:unauthorized', handleUnauthorized);
    if (noticeTimeout) {
        clearTimeout(noticeTimeout);
    }
});
</script>
