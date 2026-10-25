<script setup>
import { ref, onMounted, watch } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import axios from 'axios';
import AppHeader from '@/Components/AppHeader.vue';
import DataTable from '@/Components/DataTable.vue';
import ShiftSummaryModal from './Components/ShiftSummaryModal.vue';

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

const todayStr = new Date().toISOString().slice(0, 10);
const selectedBranchId = ref(props.branches[0]?.id || '');
const selectedDate = ref(todayStr);
const selectedUserId = ref('');
const selectedStatus = ref('all');
const isAllDates = ref(false);

const loading = ref(false);
const shifts = ref([]);
const kpis = ref({
    total_shifts: 0,
    open_shifts: 0,
    closed_shifts: 0,
    total_orders: 0,
    total_sales: 0,
    total_variance: 0,
});

const selectedShiftSummary = ref(null);
const isSummaryModalOpen = ref(false);

const columns = [
    { key: 'shift_number', label: 'Shift #' },
    { key: 'cashier_name', label: 'Cashier' },
    { key: 'branch_name', label: 'Branch' },
    { key: 'opened_at_time', label: 'Started' },
    { key: 'closed_at_time', label: 'Ended' },
    { key: 'status', label: 'Status' },
    { key: 'orders_count', label: 'Tickets' },
    { key: 'gross_sales', label: 'Gross Sales' },
    { key: 'opening_float', label: 'Float' },
    { key: 'cash_collected', label: 'Cash Sales' },
    { key: 'cash_variance', label: 'Variance' },
];

const formatPrice = (val) => {
    return Number(val || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
};

const fetchShifts = async () => {
    loading.value = true;
    try {
        const response = await axios.get(route('pos.shifts.monitor'), {
            params: {
                branch_id: selectedBranchId.value || undefined,
                date: isAllDates.value ? undefined : (selectedDate.value || undefined),
                user_id: selectedUserId.value || undefined,
                status: selectedStatus.value !== 'all' ? selectedStatus.value : undefined,
            }
        });

        if (response.data.success) {
            shifts.value = response.data.shifts;
            kpis.value = response.data.kpis;
        }
    } catch (err) {
        console.error('Failed to load shift monitor data', err);
    } finally {
        loading.value = false;
    }
};

watch([selectedBranchId, selectedDate, selectedUserId, selectedStatus, isAllDates], () => {
    fetchShifts();
});

onMounted(() => {
    fetchShifts();
});

const openShiftDetails = async (shift) => {
    try {
        const res = await axios.get(route('pos.shifts.summary'), {
            params: { shift_id: shift.id }
        });
        if (res.data.success) {
            selectedShiftSummary.value = res.data.summary;
            isSummaryModalOpen.value = true;
        }
    } catch (e) {
        console.error('Error opening shift summary', e);
    }
};
</script>

<template>
    <Head title="Shift Monitor & Audit" />

    <div class="min-h-screen flex flex-col transition-colors duration-300 bg-slate-50 dark:bg-slate-900 pb-16">
        <div class="w-full mx-auto px-4 sm:px-6 lg:px-8 mt-6">
            <!-- Header -->
            <AppHeader title="POS Shift & Work Period Monitor" :show-back="false">
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
                <!-- KPI Stat Cards -->
                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
                    <div class="p-4 rounded-2xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 shadow-2xs">
                        <span class="text-[10px] uppercase font-bold text-slate-400">Total Shifts</span>
                        <div class="text-xl font-black text-slate-800 dark:text-slate-100 mt-1">
                            {{ kpis.total_shifts }}
                        </div>
                    </div>

                    <div class="p-4 rounded-2xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 shadow-2xs">
                        <span class="text-[10px] uppercase font-bold text-emerald-500">Active Shifts</span>
                        <div class="text-xl font-black text-emerald-600 dark:text-emerald-400 mt-1 flex items-center space-x-1">
                            <span class="inline-block w-2.5 h-2.5 rounded-full bg-emerald-500 animate-pulse"></span>
                            <span>{{ kpis.open_shifts }}</span>
                        </div>
                    </div>

                    <div class="p-4 rounded-2xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 shadow-2xs">
                        <span class="text-[10px] uppercase font-bold text-slate-400">Closed Shifts</span>
                        <div class="text-xl font-black text-slate-600 dark:text-slate-300 mt-1">
                            {{ kpis.closed_shifts }}
                        </div>
                    </div>

                    <div class="p-4 rounded-2xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 shadow-2xs">
                        <span class="text-[10px] uppercase font-bold text-slate-400">Tickets Punched</span>
                        <div class="text-xl font-black text-slate-800 dark:text-slate-100 mt-1">
                            {{ kpis.total_orders }}
                        </div>
                    </div>

                    <div class="p-4 rounded-2xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 shadow-2xs">
                        <span class="text-[10px] uppercase font-bold text-indigo-500 dark:text-cyan-400">Gross Sales</span>
                        <div class="text-xl font-black text-indigo-600 dark:text-cyan-400 mt-1">
                            Rs. {{ formatPrice(kpis.total_sales) }}
                        </div>
                    </div>

                    <div class="p-4 rounded-2xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 shadow-2xs">
                        <span class="text-[10px] uppercase font-bold text-slate-400">Net Till Variance</span>
                        <div
                            :class="[
                                'text-xl font-black mt-1',
                                kpis.total_variance === 0
                                    ? 'text-emerald-600 dark:text-emerald-400'
                                    : kpis.total_variance > 0
                                        ? 'text-blue-600 dark:text-blue-400'
                                        : 'text-rose-600 dark:text-rose-400'
                            ]"
                        >
                            {{ kpis.total_variance >= 0 ? '+' : '' }}Rs. {{ formatPrice(kpis.total_variance) }}
                        </div>
                    </div>
                </div>

                <!-- Filter Controls Card -->
                <div class="p-4 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl shadow-2xs flex flex-wrap items-center gap-3">
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

                    <!-- Date Filter -->
                    <div class="w-44">
                        <label class="block text-[10px] font-bold uppercase text-slate-400 mb-1">Business Date</label>
                        <input
                            type="date"
                            v-model="selectedDate"
                            :disabled="isAllDates"
                            class="w-full text-xs font-semibold bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-xl px-3 py-1.5 text-slate-800 dark:text-slate-100 focus:ring-2 focus:ring-indigo-500 disabled:opacity-40"
                        />
                    </div>

                    <!-- All Dates Toggle -->
                    <div class="flex items-center space-x-1.5 pt-4">
                        <input
                            type="checkbox"
                            id="allDatesShift"
                            v-model="isAllDates"
                            class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 w-4 h-4 cursor-pointer"
                        />
                        <label for="allDatesShift" class="text-xs font-bold text-slate-600 dark:text-slate-400 cursor-pointer select-none">
                            All Dates
                        </label>
                    </div>

                    <!-- Cashier Filter -->
                    <div class="w-44">
                        <label class="block text-[10px] font-bold uppercase text-slate-400 mb-1">Cashier / User</label>
                        <select
                            v-model="selectedUserId"
                            class="w-full text-xs font-semibold bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-xl px-3 py-1.5 text-slate-800 dark:text-slate-100 focus:ring-2 focus:ring-indigo-500"
                        >
                            <option value="">All Cashiers</option>
                            <option v-for="u in users" :key="u.id" :value="u.id">{{ u.name }}</option>
                        </select>
                    </div>

                    <!-- Shift Status Filter -->
                    <div class="w-36">
                        <label class="block text-[10px] font-bold uppercase text-slate-400 mb-1">Shift Status</label>
                        <select
                            v-model="selectedStatus"
                            class="w-full text-xs font-semibold bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-xl px-3 py-1.5 text-slate-800 dark:text-slate-100 focus:ring-2 focus:ring-indigo-500"
                        >
                            <option value="all">All Statuses</option>
                            <option value="open">Active / Open</option>
                            <option value="closed">Closed</option>
                        </select>
                    </div>

                    <!-- Refresh Button -->
                    <div class="pt-4 ml-auto">
                        <button
                            type="button"
                            @click="fetchShifts"
                            :disabled="loading"
                            class="px-3.5 py-1.5 rounded-xl border border-slate-300 dark:border-slate-700 text-xs font-bold text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors flex items-center space-x-1.5"
                        >
                            <span :class="loading ? 'animate-spin' : ''">🔄</span>
                            <span>Refresh</span>
                        </button>
                    </div>
                </div>

                <!-- DataTable (Complying with Rule 5) -->
                <DataTable
                    title="Cashier Shifts Log"
                    :columns="columns"
                    :data="shifts"
                >
                    <template #cell-shift_number="{ item }">
                        <span class="font-mono font-bold text-slate-900 dark:text-slate-100">{{ item.shift_number }}</span>
                    </template>

                    <template #cell-cashier_name="{ item }">
                        <div class="font-bold text-slate-800 dark:text-slate-200">{{ item.cashier_name }}</div>
                        <div class="text-[10px] text-slate-400">{{ item.business_date }}</div>
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
                            class="px-2.5 py-1 text-xs font-bold text-indigo-600 hover:text-indigo-800 dark:text-cyan-400 dark:hover:text-cyan-300 hover:bg-indigo-50 dark:hover:bg-slate-800 rounded-lg transition-colors flex items-center space-x-1"
                            title="View / Print X-Report"
                        >
                            <span>🧾</span>
                            <span>Audit X-Report</span>
                        </button>
                    </template>
                </DataTable>
            </main>
        </div>

        <!-- Shift Summary / Thermal Print Modal -->
        <ShiftSummaryModal
            :isOpen="isSummaryModalOpen"
            :summary="selectedShiftSummary"
            @close="isSummaryModalOpen = false"
        />
    </div>
</template>
