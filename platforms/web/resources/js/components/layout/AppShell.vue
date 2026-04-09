<template>
    <main class="min-h-screen overflow-hidden bg-[var(--color-ink)] text-[var(--color-paper)]">
        <div class="pointer-events-none absolute inset-0">
            <div class="absolute left-[-8rem] top-[-6rem] h-64 w-64 rounded-full bg-[var(--color-accent)]/20 blur-3xl"></div>
            <div class="absolute right-[-5rem] top-20 h-56 w-56 rounded-full bg-[var(--color-gold)]/20 blur-3xl"></div>
            <div class="absolute bottom-[-8rem] left-1/3 h-72 w-72 rounded-full bg-[var(--color-mint)]/15 blur-3xl"></div>
        </div>

        <div class="relative mx-auto flex min-h-screen w-full max-w-[1600px] flex-col px-4 py-4 sm:px-6 lg:px-8 xl:px-10">
            <header class="sticky top-4 z-10 mb-8 rounded-[2rem] border border-white/10 bg-[rgba(8,16,27,0.74)] px-5 py-4 backdrop-blur-xl">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                    <div class="flex items-center gap-4">
                        <img
                            :src="logoUrl"
                            alt="Logo de AppGastos"
                            class="h-14 w-14 rounded-2xl border border-white/10 bg-[#081225] p-2.5 shadow-lg shadow-black/20"
                        >
                        <div>
                            <p class="text-sm uppercase tracking-[0.25em] text-[var(--color-gold)]/85">AppGastos</p>
                            <h1 class="mt-1 font-[var(--font-display)] text-2xl font-bold sm:text-3xl">{{ pageTitle }}</h1>
                        </div>
                    </div>

                    <nav class="flex flex-wrap gap-3">
                        <a
                            v-for="item in menuItems"
                            :key="item.href"
                            :href="item.href"
                            :class="[
                                'rounded-full px-4 py-2 text-sm font-semibold transition',
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

            <HomePage v-if="page === 'home'" :menu-items="menuItems" />

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
                @delete-income="deleteIncome"
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
                class="fixed left-4 right-4 top-4 z-50 rounded-2xl border px-4 py-3 text-sm shadow-2xl backdrop-blur-xl sm:left-1/2 sm:right-auto sm:top-6 sm:w-full sm:max-w-md sm:-translate-x-1/2"
                :style="{ top: 'max(1rem, env(safe-area-inset-top))' }"
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
import YearlyPage from '../pages/yearly/YearlyPage.vue';

const initialData = window.__APP_DATA ?? { page: 'home', title: 'Panel principal de AppGastos' };

const page = ref(initialData.page ?? 'home');
const currentPath = ref(window.location.pathname);
const pageTitle = ref(initialData.title ?? 'Panel principal de AppGastos');
const notice = reactive({ type: '', message: '' });
const logoUrl = `${window.location.origin}/images/logo.svg`;
let noticeTimeout = null;

const menuItems = [
    { label: 'Inicio', href: '/' },
    { label: 'Categorias', href: '/categorias' },
    { label: 'Gastos mensuales', href: '/gastos-mensuales' },
    { label: 'Gastos anuales', href: '/gastos-anuales' },
];

const categories = ref(initialData.categories ?? []);
const categoryIcons = ref(initialData.categoryIcons ?? []);
const monthly = reactive({
    selectedMonthLabel: initialData.selectedMonthLabel ?? '',
    selectedMonthValue: initialData.selectedMonthValue ?? currentMonthValue(),
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
});
const yearly = reactive({
    selectedYear: initialData.selectedYear ?? new Date().getFullYear(),
    years: initialData.years ?? [],
    summary: initialData.summary ?? { totalGastado: 0, totalIngresado: 0, balance: 0, totalMovimientos: 0 },
    monthly: initialData.monthly ?? [],
    categoryBreakdown: initialData.categoryBreakdown ?? [],
});

const noticeClass = computed(() => notice.type === 'error'
    ? 'border border-rose-400/30 bg-rose-400/10 text-rose-100'
    : 'border border-emerald-400/30 bg-emerald-400/10 text-emerald-100');

async function createCategory(payload) {
    try {
        const { data } = await window.axios.post('/categorias', payload);
        categories.value.push(data.categoria);
        categories.value.sort((a, b) => a.nombre.localeCompare(b.nombre));
        monthly.categorias = categories.value.map(mapCategoryForMonthly);
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
        showNotice('success', 'Gasto anadido correctamente.');
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
        showNotice('success', 'Ingreso anadido correctamente.');
        await reloadCurrentPage();
    } catch (error) {
        monthly.incomeErrors = extractErrors(error);
        showNotice('error', monthly.incomeErrors[0] ?? 'No se pudo guardar el ingreso.');
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

function hydrate(payload) {
    page.value = payload.page ?? 'home';
    pageTitle.value = payload.title ?? 'Panel principal de AppGastos';

    if (payload.page === 'categories') {
        categories.value = payload.categories ?? [];
        categoryIcons.value = payload.categoryIcons ?? [];
    }

    if (payload.page === 'monthly') {
        monthly.selectedMonthLabel = payload.selectedMonthLabel;
        monthly.selectedMonthValue = payload.selectedMonthValue;
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

function currentMonthValue() {
    const now = new Date();
    return `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}`;
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
