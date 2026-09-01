<template>
    <section class="dashboard pb-8 pt-6 lg:pt-7">
        <div class="mb-5 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div class="flex items-center gap-3">
                <div class="grid h-11 w-11 place-items-center rounded-full bg-amber-300/[0.07] text-2xl text-amber-300">☼</div>
                <div>
                    <h2 class="font-[var(--font-display)] text-xl font-semibold text-white sm:text-2xl">
                        Buenos días<span v-if="firstName">, {{ firstName }}</span>
                    </h2>
                    <p class="mt-0.5 text-xs text-white/50">
                        Así van tus finanzas en <span class="font-medium text-emerald-400">{{ monthName }}</span>
                    </p>
                </div>
            </div>

            <div class="flex items-center gap-2 self-start rounded-full border border-white/[0.07] bg-[#071526] px-4 py-2 text-xs text-white/70 sm:self-auto">
                <span aria-hidden="true">▣</span>
                <span class="capitalize">{{ localState.selectedMonthLabel }}</span>
                <span class="text-white/35">⌄</span>
            </div>
        </div>

        <div class="grid gap-3 lg:grid-cols-12">
            <article class="panel relative min-h-36 overflow-hidden p-5 sm:p-6 lg:col-span-4">
                <div class="relative z-10">
                    <p class="flex items-center gap-2 text-sm text-white/75">Balance actual <span class="text-xs text-white/40">ⓘ</span></p>
                    <p class="mt-3 font-[var(--font-display)] text-4xl font-semibold tracking-tight text-white sm:text-5xl">
                        {{ formatCurrency(localState.summary.balance) }}
                    </p>
                    <p class="mt-2 text-xs text-white/50">
                        <span :class="balancePositive ? 'text-emerald-400' : 'text-rose-400'">{{ balancePositive ? '↑' : '↓' }} {{ balanceRatio }}</span>
                        <span class="ml-1">sobre tus ingresos</span>
                    </p>
                </div>
                <svg class="absolute bottom-0 right-0 h-24 w-[52%] text-cyan-300/80" viewBox="0 0 320 100" preserveAspectRatio="none" aria-hidden="true">
                    <defs>
                        <linearGradient id="balanceFill" x1="0" y1="0" x2="0" y2="1">
                            <stop offset="0" stop-color="currentColor" stop-opacity=".28" />
                            <stop offset="1" stop-color="currentColor" stop-opacity="0" />
                        </linearGradient>
                    </defs>
                    <path d="M0 78 C28 77 30 45 60 48 S95 61 120 35 S160 20 185 31 S222 46 245 16 S280 29 320 20 L320 100 L0 100Z" fill="url(#balanceFill)" />
                    <path d="M0 78 C28 77 30 45 60 48 S95 61 120 35 S160 20 185 31 S222 46 245 16 S280 29 320 20" fill="none" stroke="currentColor" stroke-width="1.5" />
                    <circle cx="320" cy="20" r="3" fill="#a5f3fc" />
                </svg>
            </article>

            <article v-for="card in summaryCards" :key="card.label" class="panel relative min-h-36 overflow-hidden p-5 lg:col-span-2">
                <div class="flex items-center gap-3.5">
                    <span class="grid h-10 w-10 place-items-center rounded-full border text-lg font-medium" :class="card.badgeClass">{{ card.icon }}</span>
                    <p class="text-sm font-medium text-white/65 sm:text-base">{{ card.label }}</p>
                </div>
                <p class="mt-3 text-xl font-semibold tracking-tight text-white sm:text-2xl">{{ card.value }}</p>
                <p class="mt-2 text-[11px] text-white/45"><span :class="card.accentClass">{{ card.captionLead }}</span> {{ card.caption }}</p>
                <div class="absolute inset-x-0 bottom-0 h-8 opacity-70" :class="card.waveClass"></div>
            </article>

            <article class="panel p-5 lg:col-span-5">
                <div class="flex items-center justify-between gap-4">
                    <h3 class="text-sm font-semibold text-white">Evolución del mes</h3>
                    <span class="rounded-full border border-white/[0.06] bg-white/[0.035] px-3 py-1.5 text-[10px] text-white/55">Acumulado⌄</span>
                </div>
                <div class="mt-3 flex gap-5 text-[10px] text-white/45">
                    <span><i class="mr-2 inline-block h-1.5 w-1.5 rounded-full bg-emerald-400"></i>Ingresos <b class="ml-2 font-medium text-white/75">{{ formatCurrency(localState.summary.totalIngresado) }}</b></span>
                    <span><i class="mr-2 inline-block h-1.5 w-1.5 rounded-full bg-amber-400"></i>Gastos <b class="ml-2 font-medium text-white/75">{{ formatCurrency(localState.summary.totalGastado) }}</b></span>
                </div>
                <div class="relative mt-3 h-48 overflow-hidden rounded-xl">
                    <div class="absolute inset-0 chart-grid"></div>
                    <div class="absolute inset-y-2 left-0 flex flex-col justify-between text-[9px] text-white/30"><span>{{ chartMax }}</span><span>{{ chartMid }}</span><span>0 €</span></div>
                    <svg class="absolute bottom-5 left-10 right-1 h-36 w-[calc(100%-2.75rem)]" viewBox="0 0 500 150" preserveAspectRatio="none" aria-hidden="true">
                        <defs>
                            <linearGradient id="incomeArea" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#14b8a6" stop-opacity=".28"/><stop offset="1" stop-color="#14b8a6" stop-opacity="0"/></linearGradient>
                            <linearGradient id="expenseArea" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#fbbf24" stop-opacity=".2"/><stop offset="1" stop-color="#fbbf24" stop-opacity="0"/></linearGradient>
                        </defs>
                        <path d="M0 142 C35 136 48 104 88 103 S126 93 153 88 S211 66 244 57 S292 44 326 46 S381 29 420 27 S462 19 500 20 L500 150 L0 150Z" fill="url(#incomeArea)"/>
                        <path d="M0 142 C35 136 48 104 88 103 S126 93 153 88 S211 66 244 57 S292 44 326 46 S381 29 420 27 S462 19 500 20" fill="none" stroke="#2dd4bf" stroke-width="2"/>
                        <path d="M0 144 C50 139 65 124 110 126 S160 122 205 119 S275 117 325 114 S400 93 500 91 L500 150 L0 150Z" fill="url(#expenseArea)"/>
                        <path d="M0 144 C50 139 65 124 110 126 S160 122 205 119 S275 117 325 114 S400 93 500 91" fill="none" stroke="#fbbf24" stroke-width="2"/>
                    </svg>
                    <div class="absolute inset-x-10 bottom-0 flex justify-between text-[9px] text-white/35"><span>1</span><span>8</span><span>15</span><span>22</span><span>29</span></div>
                </div>
            </article>

            <article class="panel p-5 lg:col-span-3">
                <h3 class="text-sm font-semibold text-white">Tus cuentas</h3>
                <div class="mt-3 space-y-2">
                    <div v-for="account in accounts" :key="account.label" class="account-card flex items-center gap-3 rounded-xl border border-emerald-400/20 bg-emerald-400/[0.025] p-3.5">
                        <span class="grid h-9 w-9 shrink-0 place-items-center rounded-full bg-emerald-400/10 text-lg text-emerald-400">{{ account.icon }}</span>
                        <div class="min-w-0 flex-1">
                            <p class="text-[10px] text-white/45">{{ account.label }}</p>
                            <p class="mt-0.5 truncate text-xl font-medium text-white">{{ account.value }}</p>
                            <p class="text-[9px] text-white/35">{{ account.caption }}</p>
                        </div>
                        <span class="text-lg text-white/45">›</span>
                    </div>
                    <a href="/configuracion" class="flex items-center justify-between rounded-lg border border-white/[0.05] px-3.5 py-2 text-[10px] text-white/65 transition hover:bg-white/[0.04]">
                        <span>☷ &nbsp; Ver todas las cuentas</span><span>›</span>
                    </a>
                </div>
            </article>

            <article class="panel p-5 lg:col-span-4">
                <div class="flex items-center justify-between">
                    <h3 class="text-sm font-semibold text-white">Resumen del mes</h3>
                    <a href="/gastos-mensuales" class="text-[10px] font-medium text-amber-300 hover:text-amber-200">Ver todos</a>
                </div>
                <div class="mt-3 divide-y divide-white/[0.05]">
                    <div v-for="row in movementRows" :key="row.label" class="flex items-center gap-3 py-3">
                        <span class="grid h-9 w-9 shrink-0 place-items-center rounded-xl" :class="row.iconClass">{{ row.icon }}</span>
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-xs font-medium text-white/85">{{ row.label }}</p>
                            <p class="text-[9px] text-white/35">{{ row.caption }}</p>
                        </div>
                        <p class="text-xs font-semibold" :class="row.valueClass">{{ row.value }}</p>
                    </div>
                </div>
            </article>

            <article class="panel p-5 lg:col-span-4">
                <div class="flex items-center justify-between">
                    <h3 class="text-sm font-semibold text-white">Gastos por categoría</h3>
                    <a href="/categorias" class="text-[10px] text-white/45 hover:text-white/70">Ver categorías</a>
                </div>
                <div v-if="localState.topCategories.length" class="mt-4 flex flex-col items-center gap-5 sm:flex-row">
                    <div class="relative h-32 w-32 shrink-0 rounded-full" :style="{ background: donutGradient }">
                        <div class="absolute inset-[19px] grid place-items-center rounded-full bg-[#081828] text-center shadow-[0_0_0_1px_rgba(255,255,255,.04)]">
                            <div><p class="text-sm font-semibold text-white">{{ formatCurrency(localState.summary.totalGastado) }}</p><p class="text-[8px] text-white/35">Total gastos</p></div>
                        </div>
                    </div>
                    <div class="w-full min-w-0 space-y-2.5">
                        <div v-for="(item, index) in localState.topCategories" :key="item.id" class="grid grid-cols-[1fr_auto] items-center gap-3 text-[10px]">
                            <p class="truncate text-white/55"><i class="mr-2 inline-block h-1.5 w-1.5 rounded-full" :style="{ backgroundColor: categoryColor(item, index) }"></i>{{ item.nombre }}</p>
                            <p class="font-medium text-white/80">{{ formatCurrency(item.total) }} <span class="ml-2 text-white/35">{{ formatPercent(item.total, localState.summary.totalGastado) }}</span></p>
                        </div>
                    </div>
                </div>
                <div v-else class="mt-5 rounded-xl border border-dashed border-white/10 p-7 text-center text-xs text-white/40">Todavía no hay gastos este mes.</div>
            </article>

            <article class="panel p-5 lg:col-span-4">
                <div class="flex items-center justify-between">
                    <h3 class="text-sm font-semibold text-white">Control mensual</h3>
                    <a href="/gastos-anuales" class="text-[10px] font-medium text-amber-300 hover:text-amber-200">Ver informe</a>
                </div>
                <div class="mt-4 space-y-4">
                    <div>
                        <div class="flex justify-between text-[10px]"><span class="text-white/60">Gastos sobre ingresos</span><span class="font-medium text-white">{{ expenseRatio }}</span></div>
                        <div class="mt-2 h-1.5 overflow-hidden rounded-full bg-white/[0.06]"><div class="h-full rounded-full bg-amber-400" :style="{ width: expenseRatioWidth }"></div></div>
                    </div>
                    <div>
                        <div class="flex justify-between text-[10px]"><span class="text-white/60">Margen disponible</span><span class="font-medium" :class="balancePositive ? 'text-emerald-400' : 'text-rose-400'">{{ formatCurrency(localState.summary.balance) }}</span></div>
                        <div class="mt-2 h-1.5 overflow-hidden rounded-full bg-white/[0.06]"><div class="h-full rounded-full bg-emerald-400" :style="{ width: balanceRatioWidth }"></div></div>
                    </div>
                </div>
            </article>

            <article class="panel relative flex min-h-48 overflow-hidden p-5 lg:col-span-4">
                <div class="absolute -bottom-16 -left-10 h-44 w-44 rounded-full bg-amber-300/10 blur-3xl"></div>
                <div class="absolute -right-12 -top-14 h-40 w-40 rounded-full bg-cyan-400/10 blur-3xl"></div>
                <div class="relative flex w-full items-center gap-5">
                    <div class="grid h-24 w-20 shrink-0 -rotate-6 place-items-center rounded-xl border border-cyan-300/30 bg-[#042229] text-4xl text-cyan-300 shadow-[0_0_30px_rgba(45,212,191,.14)]">≡</div>
                    <div>
                        <p class="text-xs text-white/45">Consejo para ti</p>
                        <p class="mt-2 text-sm leading-6 text-white/80">{{ advice }}</p>
                        <a href="/gastos-anuales" class="mt-4 inline-flex rounded-lg border border-amber-300/35 px-4 py-2 text-[10px] font-semibold text-amber-300 transition hover:bg-amber-300/10">Ver informe completo</a>
                    </div>
                </div>
            </article>
        </div>
    </section>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    menuItems: { type: Array, required: true },
    state: { type: Object, required: true },
    user: { type: Object, default: null },
});

const localState = computed(() => props.state);
const firstName = computed(() => props.user?.name?.trim()?.split(/\s+/)[0] ?? '');
const monthName = computed(() => localState.value.selectedMonthLabel?.split(' ')[0] ?? 'este mes');
const incomes = computed(() => Number(localState.value.summary?.totalIngresado ?? 0));
const expenses = computed(() => Number(localState.value.summary?.totalGastado ?? 0));
const investments = computed(() => Number(localState.value.summary?.totalInvertido ?? 0));
const balance = computed(() => Number(localState.value.summary?.balance ?? 0));
const balancePositive = computed(() => balance.value >= 0);
const expenseRatioValue = computed(() => incomes.value > 0 ? Math.round((expenses.value / incomes.value) * 100) : 0);
const expenseRatio = computed(() => `${expenseRatioValue.value}%`);
const expenseRatioWidth = computed(() => `${Math.min(expenseRatioValue.value, 100)}%`);
const balanceRatioValue = computed(() => incomes.value > 0 ? Math.max(0, Math.round((balance.value / incomes.value) * 100)) : 0);
const balanceRatio = computed(() => `${balanceRatioValue.value}%`);
const balanceRatioWidth = computed(() => `${Math.min(balanceRatioValue.value, 100)}%`);

const summaryCards = computed(() => [
    { label: 'Ingresos', value: formatCurrency(incomes.value), icon: '↓', badgeClass: 'border-emerald-400/25 bg-emerald-400/10 text-emerald-400', accentClass: 'text-emerald-400', captionLead: '↑ total', caption: 'del mes', waveClass: 'bg-[linear-gradient(170deg,transparent_45%,rgba(16,185,129,.12)_46%)]' },
    { label: 'Gastos', value: formatCurrency(expenses.value), icon: '↗', badgeClass: 'border-rose-400/25 bg-rose-400/10 text-rose-400', accentClass: 'text-rose-400', captionLead: `${expenseRatio.value}`, caption: 'de ingresos', waveClass: 'bg-[linear-gradient(170deg,transparent_45%,rgba(244,63,94,.12)_46%)]' },
    { label: 'Ahorro', value: formatCurrency(Math.max(balance.value, 0)), icon: '⬡', badgeClass: 'border-cyan-400/25 bg-cyan-400/10 text-cyan-400', accentClass: 'text-cyan-400', captionLead: balanceRatio.value, caption: 'del ingreso', waveClass: 'bg-[linear-gradient(170deg,transparent_45%,rgba(34,211,238,.12)_46%)]' },
    { label: 'Inversiones', value: formatCurrency(investments.value), icon: '◈', badgeClass: 'border-violet-400/25 bg-violet-400/10 text-violet-400', accentClass: 'text-violet-400', captionLead: '↑ acumulado', caption: 'este mes', waveClass: 'bg-[linear-gradient(170deg,transparent_45%,rgba(139,92,246,.14)_46%)]' },
]);

const accounts = computed(() => [
    { label: 'Cuenta normal', value: formatCurrency(localState.value.accountsSummary?.normal), caption: 'Disponible', icon: '▣' },
    { label: 'Cuenta ahorro', value: formatCurrency(localState.value.accountsSummary?.ahorro), caption: 'Total ahorrado', icon: '☆' },
]);

const movementRows = computed(() => [
    { label: 'Ingresos registrados', caption: 'Entradas del mes', value: formatCurrency(incomes.value), icon: '▣', iconClass: 'bg-emerald-400/10 text-emerald-400', valueClass: 'text-emerald-400' },
    { label: 'Gastos registrados', caption: `${formatCount(localState.value.summary?.totalMovimientos)} movimientos`, value: `-${formatCurrency(expenses.value)}`, icon: '⌁', iconClass: 'bg-rose-400/10 text-rose-400', valueClass: 'text-rose-400' },
    { label: 'Importe medio', caption: 'Por movimiento', value: formatCurrency(localState.value.summary?.importeMedio), icon: '≈', iconClass: 'bg-amber-400/10 text-amber-400', valueClass: 'text-white/75' },
]);

const palette = ['#2dd4bf', '#fbbf24', '#3b82f6', '#8b5cf6', '#fb7185'];
const donutGradient = computed(() => {
    const total = expenses.value;
    if (!total) return 'conic-gradient(#183044 0 100%)';
    let cursor = 0;
    const stops = localState.value.topCategories.map((item, index) => {
        const start = cursor;
        cursor += (Number(item.total ?? 0) / total) * 100;
        return `${categoryColor(item, index)} ${start}% ${cursor}%`;
    });
    if (cursor < 100) stops.push(`#183044 ${cursor}% 100%`);
    return `conic-gradient(${stops.join(', ')})`;
});

const chartScale = computed(() => Math.max(Math.ceil(Math.max(incomes.value, expenses.value, 100) / 500) * 500, 1000));
const chartMax = computed(() => formatAxis(chartScale.value));
const chartMid = computed(() => formatAxis(chartScale.value / 2));
const advice = computed(() => balancePositive.value
    ? `Mantienes un margen positivo de ${formatCurrency(balance.value)}. Sigue revisando las categorías con más peso.`
    : `Tus gastos superan a tus ingresos en ${formatCurrency(Math.abs(balance.value))}. Revisa las categorías principales.`);

function categoryColor(item, index) { return item.color || palette[index % palette.length]; }
function formatCurrency(value) { return `${Number(value ?? 0).toLocaleString('es-ES', { minimumFractionDigits: 2, maximumFractionDigits: 2 })} €`; }
function formatAxis(value) { return `${Number(value).toLocaleString('es-ES', { maximumFractionDigits: 0 })} €`; }
function formatCount(value) { return Number(value ?? 0).toLocaleString('es-ES'); }
function formatPercent(value, total) { return !Number(total) ? '0,0%' : `${((Number(value ?? 0) / Number(total)) * 100).toLocaleString('es-ES', { minimumFractionDigits: 1, maximumFractionDigits: 1 })}%`; }
</script>

<style scoped>
.panel { border: 1px solid rgba(148, 163, 184, .09); border-radius: 14px; background: linear-gradient(145deg, rgba(10, 28, 45, .96), rgba(6, 20, 35, .96)); box-shadow: inset 0 1px rgba(255, 255, 255, .015), 0 12px 30px rgba(0, 0, 0, .09); }
.chart-grid { background-image: linear-gradient(rgba(148, 163, 184, .08) 1px, transparent 1px); background-size: 100% 25%; }
.account-card { transition: border-color 160ms ease, background-color 160ms ease, transform 160ms ease; }
.account-card:hover { border-color: rgba(52, 211, 153, .4); background-color: rgba(52, 211, 153, .055); transform: translateY(-1px); }
</style>
