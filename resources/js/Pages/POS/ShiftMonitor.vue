<script setup>
import { ref, onMounted, watch, computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import axios from 'axios';
import AppHeader from '@/Components/AppHeader.vue';
import DataTable from '@/Components/DataTable.vue';
import ShiftSummaryModal from './Components/ShiftSummaryModal.vue';
import DaySummaryModal from './Components/DaySummaryModal.vue';

const props = defineProps({
    branches: {
        type: Array,
        default: () => []
    },
    users: {
        type: Array,
        default: () => []
    },
});

// Active Tab: 'days' (Day Management) or 'shifts' (Shift Management)
const activeTab = ref('days');

// Date helpers
const getTodayStr = () => new Date().toISOString().slice(0, 10);
const getYesterdayStr = () => {
    const d = new Date();
    d.setDate(d.getDate() - 1);
    return d.toISOString().slice(0, 10);
};
const getPastDaysStr = (daysAgo) => {
    const d = new Date();
    d.setDate(d.getDate() - daysAgo);
    return d.toISOString().slice(0, 10);
};
const getFirstDayOfMonthStr = () => {
    const d = new Date();
    return new Date(d.getFullYear(), d.getMonth(), 1).toISOString().slice(0, 10);
};

// Filter States
const selectedBranchId = ref(props.branches[0]?.id || '');
const datePreset = ref('today'); // 'today', 'yesterday', 'last7', 'month', 'all', 'custom'
const startDate = ref(getTodayStr());
const endDate = ref(getTodayStr());
const selectedUserId = ref('');
const dayStatus = ref('all');
const shiftStatus = ref('all');
const searchQuery = ref('');

// Preset handler
const setDatePreset = (preset) => {
    datePreset.value = preset;
    const today = getTodayStr();
    if (preset === 'today') {
        startDate.value = today;
        endDate.value = today;
    } else if (preset === 'yesterday') {
        const y = getYesterdayStr();
        startDate.value = y;
        endDate.value = y;
    } else if (preset === 'last7') {
        startDate.value = getPastDaysStr(7);
        endDate.value = today;
    } else if (preset === 'month') {
        startDate.value = getFirstDayOfMonthStr();
        endDate.value = today;
    } else if (preset === 'all') {
        startDate.value = '';
        endDate.value = '';
    }
};

// Data & Loading States
const loadingDays = ref(false);
const loadingShifts = ref(false);
const days = ref([]);
const shifts = ref([]);

const dayKpis = ref({
    total_days: 0,
    open_days: 0,
    closed_days: 0,
    total_shifts: 0,
    total_sales: 0,
    total_variance: 0,
});

const shiftKpis = ref({
    total_shifts: 0,
    open_shifts: 0,
    closed_shifts: 0,
    total_orders: 0,
    total_sales: 0,
    total_variance: 0,
});

// Modals
const selectedShiftSummary = ref(null);
const isShiftSummaryModalOpen = ref(false);

const selectedDaySummary = ref(null);
const selectedDayRecord = ref(null);
const isDaySummaryModalOpen = ref(false);

// Column Definitions (Strictly adhering to DataTable rules: no formatters, no isHtml)
const dayColumns = [
    { key: 'business_date', label: 'Date & Day' },
    { key: 'branch_name', label: 'Branch' },
    { key: 'status', label: 'Status' },
    { key: 'opened_at_formatted', label: 'Opened At' },
    { key: 'opened_by_name', label: 'Opened By' },
    { key: 'closed_at_formatted', label: 'Closed At' },
    { key: 'closed_by_name', label: 'Closed By' },
    { key: 'duration', label: 'Total Duration' },
    { key: 'shifts_count', label: 'Shifts' },
    { key: 'gross_sales', label: 'Gross Sales' },
    { key: 'total_variance', label: 'Net Variance' },
];

const shiftColumns = [
    { key: 'shift_number', label: 'Shift #' },
    { key: 'branch_name', label: 'Branch' },
    { key: 'cashier_name', label: 'Opened By' },
    { key: 'closed_by_name', label: 'Closed By' },
    { key: 'status', label: 'Status' },
    { key: 'opened_at_formatted', label: 'Opened At' },
    { key: 'closed_at_formatted', label: 'Closed At' },
    { key: 'duration', label: 'Shift Duration' },
    { key: 'orders_count', label: 'Tickets' },
    { key: 'gross_sales', label: 'Gross Sales' },
    { key: 'opening_float', label: 'Float' },
    { key: 'cash_collected', label: 'Cash Sales' },
    { key: 'cash_variance', label: 'Till Variance' },
];

const formatPrice = (val) => {
    return Number(val || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
};

// Fetch Day Management Data
const fetchDays = async () => {
    loadingDays.value = true;
    try {
        const response = await axios.get(route('pos.work-periods.days'), {
            params: {
                branch_id: selectedBranchId.value || undefined,
                start_date: datePreset.value === 'all' ? undefined : (startDate.value || undefined),
                end_date: datePreset.value === 'all' ? undefined : (endDate.value || undefined),
                user_id: selectedUserId.value || undefined,
                status: dayStatus.value !== 'all' ? dayStatus.value : undefined,
                search: searchQuery.value || undefined,
            }
        });

        if (response.data.success) {
            days.value = response.data.days;
            dayKpis.value = response.data.kpis;
        }
    } catch (err) {
        console.error('Failed to load business days data', err);
    } finally {
        loadingDays.value = false;
    }
};

// Fetch Shift Management Data
const fetchShifts = async () => {
    loadingShifts.value = true;
    try {
        const response = await axios.get(route('pos.work-periods.shifts'), {
            params: {
                branch_id: selectedBranchId.value || undefined,
                start_date: datePreset.value === 'all' ? undefined : (startDate.value || undefined),
                end_date: datePreset.value === 'all' ? undefined : (endDate.value || undefined),
                user_id: selectedUserId.value || undefined,
                status: shiftStatus.value !== 'all' ? shiftStatus.value : undefined,
                search: searchQuery.value || undefined,
            }
        });

        if (response.data.success) {
            shifts.value = response.data.shifts;
            shiftKpis.value = response.data.kpis;
        }
    } catch (err) {
        console.error('Failed to load shift monitor data', err);
    } finally {
        loadingShifts.value = false;
    }
};

const refreshData = () => {
    if (activeTab.value === 'days') {
        fetchDays();
    } else {
        fetchShifts();
    }
};

// Watchers
watch([selectedBranchId, startDate, endDate, datePreset, selectedUserId, dayStatus, shiftStatus, activeTab], () => {
    refreshData();
});

onMounted(() => {
    fetchDays();
    fetchShifts();
});

// Row Action Handlers
const openShiftDetails = async (shift) => {
    try {
        const res = await axios.get(route('pos.shifts.summary'), {
            params: { shift_id: shift.id }
        });
        if (res.data.success) {
            selectedShiftSummary.value = res.data.summary;
            isShiftSummaryModalOpen.value = true;
        }
    } catch (e) {
        console.error('Error opening shift summary', e);
    }
};

const openDayDetails = async (day) => {
    try {
        const res = await axios.get(route('pos.work-periods.day-summary'), {
            params: { day_id: day.id }
        });
        if (res.data.success) {
            selectedDayRecord.value = res.data.business_day;
            selectedDaySummary.value = res.data.z_report;
            isDaySummaryModalOpen.value = true;
        }
    } catch (e) {
        console.error('Error opening day summary', e);
    }
};

// Jump to shifts of a specific business day
const viewShiftsForDay = (day) => {
    activeTab.value = 'shifts';
    datePreset.value = 'custom';
    startDate.value = day.business_date;
    endDate.value = day.business_date;
    if (day.branch_id) {
        selectedBranchId.value = day.branch_id;
    }
};
</script>

<template>
    <Head title="Day & Shift Records" />

    <div class="min-h-screen flex flex-col transition-colors duration-300 bg-slate-50 dark:bg-slate-900 pb-16">
        <div class="w-full mx-auto px-4 sm:px-6 lg:px-8 mt-6">
            <!-- ERP Header -->
            <AppHeader title="Day & Shift Records" :show-back="true">
                <template #actions>
                    <Link
                        :href="route('pos.index')"
                        class="pos-btn-primary !py-2 !px-4 text-xs font-bold flex items-center space-x-1.5 shadow-sm"
                    >
                        <span>🛒 Return to POS Terminal</span>
                    </Link>
                </template>
            </AppHeader>

            <main class="mt-8 space-y-6">
                <!-- Navigation Tabs: Day Management vs Shift Management -->
                <div class="flex flex-col sm:flex-row sm:items-center justify-between border-b border-slate-200 dark:border-slate-700/80 pb-3 gap-3">
                    <div class="flex flex-col sm:flex-row w-full sm:w-auto space-y-1 sm:space-y-0 sm:space-x-2 bg-slate-100 dark:bg-slate-800/80 p-1 rounded-2xl border border-slate-200 dark:border-slate-700">
                        <button
                            type="button"
                            @click="activeTab = 'days'"
                            :class="[
                                'px-3.5 sm:px-5 py-2 sm:py-2.5 rounded-xl text-xs font-black transition-all flex items-center justify-between sm:justify-start space-x-2 cursor-pointer',
                                activeTab === 'days'
                                    ? 'bg-white dark:bg-slate-900 text-indigo-600 dark:text-cyan-400 shadow-sm'
                                    : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white'
                            ]"
                        >
                            <span class="flex items-center space-x-1.5">
                                <span>📅</span>
                                <span class="sm:hidden">Day Records</span>
                                <span class="hidden sm:inline">Day Management (Business Days)</span>
                            </span>
                            <span class="px-2 py-0.5 rounded-full text-[10px] bg-indigo-50 dark:bg-cyan-950/60 text-indigo-700 dark:text-cyan-300 font-black">
                                {{ days.length }}
                            </span>
                        </button>

                        <button
                            type="button"
                            @click="activeTab = 'shifts'"
                            :class="[
                                'px-3.5 sm:px-5 py-2 sm:py-2.5 rounded-xl text-xs font-black transition-all flex items-center justify-between sm:justify-start space-x-2 cursor-pointer',
                                activeTab === 'shifts'
                                    ? 'bg-white dark:bg-slate-900 text-indigo-600 dark:text-cyan-400 shadow-sm'
                                    : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white'
                            ]"
                        >
                            <span class="flex items-center space-x-1.5">
                                <span>⏱️</span>
                                <span class="sm:hidden">Shift Records</span>
                                <span class="hidden sm:inline">Shift Management (Cashier Work Periods)</span>
                            </span>
                            <span class="px-2 py-0.5 rounded-full text-[10px] bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300 font-black">
                                {{ shifts.length }}
                            </span>
                        </button>
                    </div>

                    <!-- Quick Refresh Status -->
                    <div class="text-xs text-slate-400 hidden sm:flex items-center space-x-1.5 font-medium">
                        <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                        <span>Auditing live POS business records</span>
                    </div>
                </div>

                <!-- KPI Stat Cards (Dynamic based on selected tab) -->
                <transition mode="out-in" enter-active-class="transition duration-150 ease-out" enter-from-class="opacity-0 translate-y-1" leave-active-class="transition duration-100 ease-in" leave-to-class="opacity-0 -translate-y-1">
                    <!-- Day Management KPIs -->
                    <div v-if="activeTab === 'days'" key="day-kpis" class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-2.5 sm:gap-4">
                        <div class="p-3 sm:p-4 rounded-2xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 shadow-2xs">
                            <span class="text-[10px] uppercase font-bold text-slate-400">Total Business Days</span>
                            <div class="text-xl font-black text-slate-800 dark:text-slate-100 mt-1">
                                {{ dayKpis.total_days }}
                            </div>
                        </div>

                        <div class="p-4 rounded-2xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 shadow-2xs">
                            <span class="text-[10px] uppercase font-bold text-emerald-500">Days Open (Active)</span>
                            <div class="text-xl font-black text-emerald-600 dark:text-emerald-400 mt-1 flex items-center space-x-1.5">
                                <span class="inline-block w-2.5 h-2.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                <span>{{ dayKpis.open_days }}</span>
                            </div>
                        </div>

                        <div class="p-4 rounded-2xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 shadow-2xs">
                            <span class="text-[10px] uppercase font-bold text-slate-400">Days Closed</span>
                            <div class="text-xl font-black text-slate-600 dark:text-slate-300 mt-1">
                                {{ dayKpis.closed_days }}
                            </div>
                        </div>

                        <div class="p-4 rounded-2xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 shadow-2xs">
                            <span class="text-[10px] uppercase font-bold text-slate-400">Total Shifts Logged</span>
                            <div class="text-xl font-black text-slate-800 dark:text-slate-100 mt-1">
                                {{ dayKpis.total_shifts }}
                            </div>
                        </div>

                        <div class="p-4 rounded-2xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 shadow-2xs">
                            <span class="text-[10px] uppercase font-bold text-indigo-500 dark:text-cyan-400">Total Revenue</span>
                            <div class="text-xl font-black text-indigo-600 dark:text-cyan-400 mt-1">
                                Rs. {{ formatPrice(dayKpis.total_sales) }}
                            </div>
                        </div>

                        <div class="p-4 rounded-2xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 shadow-2xs">
                            <span class="text-[10px] uppercase font-bold text-slate-400">Net Till Variance</span>
                            <div
                                :class="[
                                    'text-xl font-black mt-1',
                                    dayKpis.total_variance === 0
                                        ? 'text-emerald-600 dark:text-emerald-400'
                                        : dayKpis.total_variance > 0
                                            ? 'text-blue-600 dark:text-blue-400'
                                            : 'text-rose-600 dark:text-rose-400'
                                ]"
                            >
                                {{ dayKpis.total_variance >= 0 ? '+' : '' }}Rs. {{ formatPrice(dayKpis.total_variance) }}
                            </div>
                        </div>
                    </div>

                    <!-- Shift Management KPIs -->
                    <div v-else key="shift-kpis" class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-2.5 sm:gap-4">
                        <div class="p-3 sm:p-4 rounded-2xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 shadow-2xs">
                            <span class="text-[10px] uppercase font-bold text-slate-400">Total Shifts</span>
                            <div class="text-xl font-black text-slate-800 dark:text-slate-100 mt-1">
                                {{ shiftKpis.total_shifts }}
                            </div>
                        </div>

                        <div class="p-3 sm:p-4 rounded-2xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 shadow-2xs">
                            <span class="text-[10px] uppercase font-bold text-emerald-500">Active Shifts</span>
                            <div class="text-xl font-black text-emerald-600 dark:text-emerald-400 mt-1 flex items-center space-x-1.5">
                                <span class="inline-block w-2.5 h-2.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                <span>{{ shiftKpis.open_shifts }}</span>
                            </div>
                        </div>

                        <div class="p-3 sm:p-4 rounded-2xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 shadow-2xs">
                            <span class="text-[10px] uppercase font-bold text-slate-400">Closed Shifts</span>
                            <div class="text-xl font-black text-slate-600 dark:text-slate-300 mt-1">
                                {{ shiftKpis.closed_shifts }}
                            </div>
                        </div>

                        <div class="p-3 sm:p-4 rounded-2xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 shadow-2xs">
                            <span class="text-[10px] uppercase font-bold text-slate-400">Tickets Punched</span>
                            <div class="text-xl font-black text-slate-800 dark:text-slate-100 mt-1">
                                {{ shiftKpis.total_orders }}
                            </div>
                        </div>

                        <div class="p-3 sm:p-4 rounded-2xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 shadow-2xs">
                            <span class="text-[10px] uppercase font-bold text-indigo-500 dark:text-cyan-400">Gross Sales</span>
                            <div class="text-xl font-black text-indigo-600 dark:text-cyan-400 mt-1">
                                Rs. {{ formatPrice(shiftKpis.total_sales) }}
                            </div>
                        </div>

                        <div class="p-3 sm:p-4 rounded-2xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 shadow-2xs">
                            <span class="text-[10px] uppercase font-bold text-slate-400">Net Till Variance</span>
                            <div
                                :class="[
                                    'text-xl font-black mt-1',
                                    shiftKpis.total_variance === 0
                                        ? 'text-emerald-600 dark:text-emerald-400'
                                        : shiftKpis.total_variance > 0
                                            ? 'text-blue-600 dark:text-blue-400'
                                            : 'text-rose-600 dark:text-rose-400'
                                ]"
                            >
                                {{ shiftKpis.total_variance >= 0 ? '+' : '' }}Rs. {{ formatPrice(shiftKpis.total_variance) }}
                            </div>
                        </div>
                    </div>
                </transition>

                <!-- Comprehensive Filter Bar -->
                <div class="p-4 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl shadow-2xs space-y-3">
                    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 dark:border-slate-700/60 pb-3">
                        <!-- Quick Date Presets -->
                        <div class="flex items-center space-x-1.5 flex-wrap gap-y-1">
                            <span class="text-[10px] font-bold uppercase text-slate-400 mr-1">Date Range:</span>
                            <button
                                type="button"
                                @click="setDatePreset('today')"
                                :class="[
                                    'px-2.5 py-1 text-xs font-bold rounded-lg transition-colors cursor-pointer',
                                    datePreset === 'today'
                                        ? 'bg-indigo-600 text-white dark:bg-cyan-500 dark:text-slate-900 shadow-xs'
                                        : 'bg-slate-100 hover:bg-slate-200 dark:bg-slate-700 dark:hover:bg-slate-600 text-slate-700 dark:text-slate-200'
                                ]"
                            >
                                Today
                            </button>
                            <button
                                type="button"
                                @click="setDatePreset('yesterday')"
                                :class="[
                                    'px-2.5 py-1 text-xs font-bold rounded-lg transition-colors cursor-pointer',
                                    datePreset === 'yesterday'
                                        ? 'bg-indigo-600 text-white dark:bg-cyan-500 dark:text-slate-900 shadow-xs'
                                        : 'bg-slate-100 hover:bg-slate-200 dark:bg-slate-700 dark:hover:bg-slate-600 text-slate-700 dark:text-slate-200'
                                ]"
                            >
                                Yesterday
                            </button>
                            <button
                                type="button"
                                @click="setDatePreset('last7')"
                                :class="[
                                    'px-2.5 py-1 text-xs font-bold rounded-lg transition-colors cursor-pointer',
                                    datePreset === 'last7'
                                        ? 'bg-indigo-600 text-white dark:bg-cyan-500 dark:text-slate-900 shadow-xs'
                                        : 'bg-slate-100 hover:bg-slate-200 dark:bg-slate-700 dark:hover:bg-slate-600 text-slate-700 dark:text-slate-200'
                                ]"
                            >
                                Last 7 Days
                            </button>
                            <button
                                type="button"
                                @click="setDatePreset('month')"
                                :class="[
                                    'px-2.5 py-1 text-xs font-bold rounded-lg transition-colors cursor-pointer',
                                    datePreset === 'month'
                                        ? 'bg-indigo-600 text-white dark:bg-cyan-500 dark:text-slate-900 shadow-xs'
                                        : 'bg-slate-100 hover:bg-slate-200 dark:bg-slate-700 dark:hover:bg-slate-600 text-slate-700 dark:text-slate-200'
                                ]"
                            >
                                This Month
                            </button>
                            <button
                                type="button"
                                @click="setDatePreset('all')"
                                :class="[
                                    'px-2.5 py-1 text-xs font-bold rounded-lg transition-colors cursor-pointer',
                                    datePreset === 'all'
                                        ? 'bg-indigo-600 text-white dark:bg-cyan-500 dark:text-slate-900 shadow-xs'
                                        : 'bg-slate-100 hover:bg-slate-200 dark:bg-slate-700 dark:hover:bg-slate-600 text-slate-700 dark:text-slate-200'
                                ]"
                            >
                                All Dates
                            </button>
                        </div>

                        <!-- Date Range Inputs -->
                        <div class="flex flex-wrap items-center gap-2">
                            <div class="flex items-center space-x-1">
                                <span class="text-[10px] font-bold uppercase text-slate-400">From:</span>
                                <input
                                    type="date"
                                    v-model="startDate"
                                    @change="datePreset = 'custom'"
                                    :disabled="datePreset === 'all'"
                                    class="text-xs font-semibold bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-xl px-2 py-1 text-slate-800 dark:text-slate-100 focus:ring-2 focus:ring-indigo-500 disabled:opacity-40"
                                />
                            </div>
                            <div class="flex items-center space-x-1">
                                <span class="text-[10px] font-bold uppercase text-slate-400">To:</span>
                                <input
                                    type="date"
                                    v-model="endDate"
                                    @change="datePreset = 'custom'"
                                    :disabled="datePreset === 'all'"
                                    class="text-xs font-semibold bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-xl px-2 py-1 text-slate-800 dark:text-slate-100 focus:ring-2 focus:ring-indigo-500 disabled:opacity-40"
                                />
                            </div>
                        </div>
                    </div>

                    <!-- Granular Dropdown Selectors -->
                    <div class="flex flex-wrap items-center gap-3">
                        <!-- Branch Filter -->
                        <div class="w-48">
                            <label class="block text-[10px] font-bold uppercase text-slate-400 mb-1">Branch</label>
                            <select
                                v-model="selectedBranchId"
                                class="w-full text-xs font-semibold bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-xl px-3 py-1.5 text-slate-800 dark:text-slate-100 focus:ring-2 focus:ring-indigo-500"
                            >
                                <option value="">All Branches</option>
                                <option v-for="b in branches" :key="b.id" :value="b.id">{{ b.name }}</option>
                            </select>
                        </div>

                        <!-- User / Cashier Filter -->
                        <div class="w-44">
                            <label class="block text-[10px] font-bold uppercase text-slate-400 mb-1">User / Cashier</label>
                            <select
                                v-model="selectedUserId"
                                class="w-full text-xs font-semibold bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-xl px-3 py-1.5 text-slate-800 dark:text-slate-100 focus:ring-2 focus:ring-indigo-500"
                            >
                                <option value="">All Users</option>
                                <option v-for="u in users" :key="u.id" :value="u.id">{{ u.name }}</option>
                            </select>
                        </div>

                        <!-- Contextual Status Filter -->
                        <div v-if="activeTab === 'days'" class="w-36">
                            <label class="block text-[10px] font-bold uppercase text-slate-400 mb-1">Day Status</label>
                            <select
                                v-model="dayStatus"
                                class="w-full text-xs font-semibold bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-xl px-3 py-1.5 text-slate-800 dark:text-slate-100 focus:ring-2 focus:ring-indigo-500"
                            >
                                <option value="all">All Statuses</option>
                                <option value="open">Day Open (Active)</option>
                                <option value="closed">Day Closed</option>
                            </select>
                        </div>

                        <div v-else class="w-36">
                            <label class="block text-[10px] font-bold uppercase text-slate-400 mb-1">Shift Status</label>
                            <select
                                v-model="shiftStatus"
                                class="w-full text-xs font-semibold bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-xl px-3 py-1.5 text-slate-800 dark:text-slate-100 focus:ring-2 focus:ring-indigo-500"
                            >
                                <option value="all">All Statuses</option>
                                <option value="open">Shift Active (Open)</option>
                                <option value="closed">Shift Closed</option>
                            </select>
                        </div>

                        <!-- Search Box -->
                        <div class="flex-1 min-w-[200px]">
                            <label class="block text-[10px] font-bold uppercase text-slate-400 mb-1">Search Keywords</label>
                            <input
                                type="text"
                                v-model="searchQuery"
                                @keyup.enter="refreshData"
                                :placeholder="activeTab === 'days' ? 'Search branch, user, notes...' : 'Search shift #, cashier, notes...'"
                                class="w-full text-xs font-semibold bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-xl px-3 py-1.5 text-slate-800 dark:text-slate-100 focus:ring-2 focus:ring-indigo-500"
                            />
                        </div>

                        <!-- Refresh Action Button -->
                        <div class="pt-4 ml-auto">
                            <button
                                type="button"
                                @click="refreshData"
                                :disabled="loadingDays || loadingShifts"
                                class="px-4 py-1.5 rounded-xl border border-slate-300 dark:border-slate-700 text-xs font-bold text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors flex items-center space-x-1.5 cursor-pointer shadow-xs"
                            >
                                <span :class="loadingDays || loadingShifts ? 'animate-spin' : ''">🔄</span>
                                <span>Apply Filters</span>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Tab 1: Day Management DataTable -->
                <div v-if="activeTab === 'days'" key="days-table">
                    <DataTable
                        title="Business Day Open / Close Audit Log"
                        :columns="dayColumns"
                        :data="days"
                    >
                        <template #cell-business_date="{ item }">
                            <div class="font-black text-slate-900 dark:text-slate-100">{{ item.business_date_formatted }}</div>
                            <div class="text-[10px] text-indigo-600 dark:text-cyan-400 font-semibold">{{ item.business_day }}</div>
                        </template>

                        <template #cell-branch_name="{ item }">
                            <span class="font-bold text-slate-800 dark:text-slate-200">{{ item.branch_name }}</span>
                        </template>

                        <template #cell-status="{ item }">
                            <span
                                :class="[
                                    'inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider',
                                    item.status === 'open'
                                        ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-400'
                                        : 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-400'
                                ]"
                            >
                                <span v-if="item.status === 'open'" class="w-1.5 h-1.5 rounded-full bg-emerald-500 mr-1 animate-pulse"></span>
                                {{ item.status }}
                            </span>
                        </template>

                        <template #cell-opened_at_formatted="{ item }">
                            <div class="font-mono text-xs text-slate-800 dark:text-slate-200 font-bold">{{ item.opened_at_formatted }}</div>
                        </template>

                        <template #cell-opened_by_name="{ item }">
                            <div class="font-bold text-slate-800 dark:text-slate-200 flex items-center space-x-1.5">
                                <span class="w-5 h-5 rounded-full bg-indigo-100 dark:bg-indigo-950/80 text-indigo-700 dark:text-indigo-300 text-[10px] font-black flex items-center justify-center">
                                    {{ item.opened_by_name?.charAt(0) || 'U' }}
                                </span>
                                <span>{{ item.opened_by_name }}</span>
                            </div>
                        </template>

                        <template #cell-closed_at_formatted="{ item }">
                            <span v-if="item.status === 'closed'" class="font-mono text-xs text-slate-800 dark:text-slate-200 font-bold">
                                {{ item.closed_at_formatted }}
                            </span>
                            <span v-else class="inline-flex items-center text-[11px] font-bold text-emerald-600 dark:text-emerald-400 italic">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 mr-1 animate-pulse"></span>
                                In Progress (Active)
                            </span>
                        </template>

                        <template #cell-closed_by_name="{ item }">
                            <div v-if="item.status === 'closed'" class="font-bold text-slate-800 dark:text-slate-200 flex items-center space-x-1.5">
                                <span class="w-5 h-5 rounded-full bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-300 text-[10px] font-black flex items-center justify-center">
                                    {{ item.closed_by_name?.charAt(0) || 'U' }}
                                </span>
                                <span>{{ item.closed_by_name }}</span>
                            </div>
                            <span v-else class="text-xs text-slate-400">-</span>
                        </template>

                        <template #cell-duration="{ item }">
                            <span
                                :class="[
                                    'inline-flex items-center px-2 py-0.5 rounded-lg text-xs font-mono font-bold',
                                    item.status === 'open'
                                        ? 'bg-amber-50 text-amber-800 dark:bg-amber-950/40 dark:text-amber-300 border border-amber-200 dark:border-amber-800'
                                        : 'bg-indigo-50 text-indigo-800 dark:bg-indigo-950/40 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800'
                                ]"
                            >
                                ⏱️ {{ item.duration }}
                            </span>
                        </template>

                        <template #cell-shifts_count="{ item }">
                            <div class="flex items-center space-x-1">
                                <span class="font-black text-slate-900 dark:text-slate-100">{{ item.shifts_count }}</span>
                                <span class="text-[10px] text-slate-400">({{ item.open_shifts_count }} active)</span>
                            </div>
                        </template>

                        <template #cell-gross_sales="{ item }">
                            <span class="font-black text-indigo-600 dark:text-cyan-400">Rs. {{ formatPrice(item.gross_sales) }}</span>
                        </template>

                        <template #cell-total_variance="{ item }">
                            <span
                                v-if="item.status === 'closed'"
                                :class="[
                                    'font-bold text-xs',
                                    item.total_variance === 0
                                        ? 'text-emerald-600 dark:text-emerald-400'
                                        : item.total_variance > 0
                                            ? 'text-blue-600 dark:text-blue-400'
                                            : 'text-rose-600 dark:text-rose-400'
                                ]"
                            >
                                {{ item.total_variance >= 0 ? '+' : '' }}Rs. {{ formatPrice(item.total_variance) }}
                            </span>
                            <span v-else class="text-[10px] text-slate-400 italic">Day Active</span>
                        </template>

                        <template #row-actions="{ item }">
                            <div class="flex items-center justify-end space-x-2">
                                <button
                                    type="button"
                                    @click="viewShiftsForDay(item)"
                                    class="px-2.5 py-1 text-xs font-bold text-slate-600 hover:text-slate-900 dark:text-slate-300 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800 rounded-lg transition-colors flex items-center space-x-1 cursor-pointer"
                                    title="View all cashier shifts of this business day"
                                >
                                    <span>⏱️</span>
                                    <span>Shifts ({{ item.shifts_count }})</span>
                                </button>
                                <button
                                    type="button"
                                    @click="openDayDetails(item)"
                                    class="px-2.5 py-1 text-xs font-bold text-indigo-600 hover:text-indigo-800 dark:text-cyan-400 dark:hover:text-cyan-300 hover:bg-indigo-50 dark:hover:bg-slate-800 rounded-lg transition-colors flex items-center space-x-1 cursor-pointer"
                                    title="View / Print Z-Report"
                                >
                                    <span>🏁</span>
                                    <span>Audit Z-Report</span>
                                </button>
                            </div>
                        </template>
                    </DataTable>
                </div>

                <!-- Tab 2: Shift Management DataTable -->
                <div v-else key="shifts-table">
                    <DataTable
                        title="Cashier Shift Open / Close Audit Log"
                        :columns="shiftColumns"
                        :data="shifts"
                    >
                        <template #cell-shift_number="{ item }">
                            <span class="font-mono font-bold text-slate-900 dark:text-slate-100">{{ item.shift_number }}</span>
                            <div class="text-[10px] text-slate-400">{{ item.business_date }}</div>
                        </template>

                        <template #cell-branch_name="{ item }">
                            <span class="font-bold text-slate-800 dark:text-slate-200">{{ item.branch_name }}</span>
                        </template>

                        <template #cell-cashier_name="{ item }">
                            <div class="font-bold text-slate-800 dark:text-slate-200 flex items-center space-x-1.5">
                                <span class="w-5 h-5 rounded-full bg-emerald-100 dark:bg-emerald-950/80 text-emerald-700 dark:text-emerald-300 text-[10px] font-black flex items-center justify-center">
                                    {{ item.cashier_name?.charAt(0) || 'C' }}
                                </span>
                                <span>{{ item.cashier_name }}</span>
                            </div>
                        </template>

                        <template #cell-closed_by_name="{ item }">
                            <div v-if="item.status === 'closed'" class="font-bold text-slate-800 dark:text-slate-200 flex items-center space-x-1.5">
                                <span class="w-5 h-5 rounded-full bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-300 text-[10px] font-black flex items-center justify-center">
                                    {{ item.closed_by_name?.charAt(0) || 'U' }}
                                </span>
                                <span>{{ item.closed_by_name }}</span>
                            </div>
                            <span v-else class="text-xs text-slate-400">-</span>
                        </template>

                        <template #cell-status="{ item }">
                            <span
                                :class="[
                                    'inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider',
                                    item.status === 'open'
                                        ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-400'
                                        : 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-400'
                                ]"
                            >
                                <span v-if="item.status === 'open'" class="w-1.5 h-1.5 rounded-full bg-emerald-500 mr-1 animate-pulse"></span>
                                {{ item.status }}
                            </span>
                        </template>

                        <template #cell-opened_at_formatted="{ item }">
                            <div class="font-mono text-xs text-slate-800 dark:text-slate-200 font-bold">{{ item.opened_at_formatted }}</div>
                        </template>

                        <template #cell-closed_at_formatted="{ item }">
                            <span v-if="item.status === 'closed'" class="font-mono text-xs text-slate-800 dark:text-slate-200 font-bold">
                                {{ item.closed_at_formatted }}
                            </span>
                            <span v-else class="inline-flex items-center text-[11px] font-bold text-emerald-600 dark:text-emerald-400 italic">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 mr-1 animate-pulse"></span>
                                In Progress (Active)
                            </span>
                        </template>

                        <template #cell-duration="{ item }">
                            <span
                                :class="[
                                    'inline-flex items-center px-2 py-0.5 rounded-lg text-xs font-mono font-bold',
                                    item.status === 'open'
                                        ? 'bg-amber-50 text-amber-800 dark:bg-amber-950/40 dark:text-amber-300 border border-amber-200 dark:border-amber-800'
                                        : 'bg-emerald-50 text-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800'
                                ]"
                            >
                                ⏱️ {{ item.duration }}
                            </span>
                        </template>

                        <template #cell-orders_count="{ item }">
                            <span class="font-bold">{{ item.orders_count }}</span>
                            <span class="text-[10px] text-slate-400 ml-1">({{ item.completed_orders_count }} paid)</span>
                        </template>

                        <template #cell-gross_sales="{ item }">
                            <span class="font-black text-indigo-600 dark:text-cyan-400">Rs. {{ formatPrice(item.gross_sales) }}</span>
                        </template>

                        <template #cell-opening_float="{ item }">
                            <span>Rs. {{ formatPrice(item.opening_float) }}</span>
                        </template>

                        <template #cell-cash_collected="{ item }">
                            <span class="font-bold text-emerald-600 dark:text-emerald-400">Rs. {{ formatPrice(item.cash_collected) }}</span>
                        </template>

                        <template #cell-cash_variance="{ item }">
                            <span
                                v-if="item.status === 'closed'"
                                :class="[
                                    'font-bold text-xs',
                                    item.cash_variance === 0
                                        ? 'text-emerald-600 dark:text-emerald-400'
                                        : item.cash_variance > 0
                                            ? 'text-blue-600 dark:text-blue-400'
                                            : 'text-rose-600 dark:text-rose-400'
                                ]"
                            >
                                {{ item.cash_variance >= 0 ? '+' : '' }}Rs. {{ formatPrice(item.cash_variance) }}
                            </span>
                            <span v-else class="text-[10px] text-slate-400 italic">In Progress</span>
                        </template>

                        <template #row-actions="{ item }">
                            <button
                                type="button"
                                @click="openShiftDetails(item)"
                                class="px-2.5 py-1 text-xs font-bold text-indigo-600 hover:text-indigo-800 dark:text-cyan-400 dark:hover:text-cyan-300 hover:bg-indigo-50 dark:hover:bg-slate-800 rounded-lg transition-colors flex items-center space-x-1 cursor-pointer"
                                title="View / Print X-Report"
                            >
                                <span>🧾</span>
                                <span>Audit X-Report</span>
                            </button>
                        </template>
                    </DataTable>
                </div>
            </main>
        </div>

        <!-- Shift X-Report Modal -->
        <ShiftSummaryModal
            :isOpen="isShiftSummaryModalOpen"
            :summary="selectedShiftSummary"
            @close="isShiftSummaryModalOpen = false"
        />

        <!-- Business Day Z-Report Modal -->
        <DaySummaryModal
            :isOpen="isDaySummaryModalOpen"
            :day="selectedDayRecord"
            :zReport="selectedDaySummary"
            @close="isDaySummaryModalOpen = false"
        />
    </div>
</template>
