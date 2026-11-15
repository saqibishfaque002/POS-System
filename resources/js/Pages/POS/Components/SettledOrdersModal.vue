<script setup>
import { ref, computed, watch } from 'vue';
import axios from 'axios';

const props = defineProps({
    isOpen: Boolean,
    branchId: [Number, String],
    paymentTypes: {
        type: Array,
        default: () => []
    },
    orderTypes: {
        type: Array,
        default: () => []
    },
    businessDate: String,
});

const emit = defineEmits(['close', 'reprintBill']);

const loading = ref(false);
const orders = ref([]);
const totalRevenue = ref(0);
const searchQuery = ref('');
const selectedOrderTypeId = ref('');
const selectedPaymentTypeId = ref('');

const getInitialDate = () => props.businessDate || new Date().toLocaleDateString('en-CA');
const selectedDate = ref(getInitialDate());

const fetchOrders = async () => {
    if (!props.isOpen && orders.value.length > 0) return;
    loading.value = true;
    try {
        const params = {
            branch_id: props.branchId,
        };
        if (searchQuery.value.trim()) params.search = searchQuery.value.trim();
        if (selectedOrderTypeId.value) params.order_type_id = selectedOrderTypeId.value;
        if (selectedPaymentTypeId.value) params.payment_type_id = selectedPaymentTypeId.value;
        if (selectedDate.value) {
            params.date = selectedDate.value;
        } else {
            params.date = 'all';
        }

        const response = await axios.get(route('pos.orders.settled'), { params });
        orders.value = response.data.orders || [];
        totalRevenue.value = response.data.total_revenue || 0;
    } catch (e) {
        console.error('Error fetching settled orders:', e);
    } finally {
        loading.value = false;
    }
};

watch(() => props.isOpen, (newVal) => {
    if (newVal) {
        if (!selectedDate.value) {
            selectedDate.value = getInitialDate();
        }
        fetchOrders();
    }
});

watch(() => props.businessDate, (newVal) => {
    if (newVal && !selectedDate.value) {
        selectedDate.value = newVal;
    }
});

let searchDebounceTimer = null;
watch(searchQuery, () => {
    clearTimeout(searchDebounceTimer);
    searchDebounceTimer = setTimeout(fetchOrders, 300);
});

watch([selectedOrderTypeId, selectedPaymentTypeId, selectedDate], () => {
    fetchOrders();
});

const setQuickDate = (type) => {
    if (type === 'today') {
        selectedDate.value = props.businessDate || new Date().toLocaleDateString('en-CA');
    } else if (type === 'yesterday') {
        const d = new Date();
        d.setDate(d.getDate() - 1);
        selectedDate.value = d.toLocaleDateString('en-CA');
    } else if (type === 'all') {
        selectedDate.value = '';
    }
};

const formatPrice = (val) => {
    return Number(val || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
};

const formatTime = (dateStr) => {
    if (!dateStr) return '';
    try {
        const d = new Date(dateStr);
        return d.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
    } catch {
        return dateStr;
    }
};

const formatDate = (dateStr) => {
    if (!dateStr) return '';
    try {
        const d = new Date(dateStr);
        return d.toLocaleDateString([], { month: 'short', day: 'numeric', year: 'numeric' });
    } catch {
        return dateStr;
    }
};

const handleReprint = (order) => {
    emit('reprintBill', order);
};

defineExpose({
    refresh: fetchOrders,
});
</script>

<template>
    <div v-if="isOpen" class="fixed inset-0 z-50 flex items-center justify-center p-2 sm:p-4 md:p-6 bg-slate-900/60 backdrop-blur-xs select-none">
        <div class="bg-white dark:bg-slate-900 w-full max-w-6xl rounded-2xl shadow-2xl border border-slate-200 dark:border-slate-800 overflow-hidden flex flex-col max-h-[92vh] animate-in fade-in zoom-in-95 duration-150">
            <!-- Modal Header -->
            <div class="px-5 py-3.5 border-b border-slate-200 dark:border-slate-800 bg-slate-50/80 dark:bg-slate-800/50 flex items-center justify-between shrink-0">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 rounded-xl bg-emerald-500/10 dark:bg-emerald-500/20 text-emerald-600 dark:text-emerald-400 flex items-center justify-center font-bold">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div>
                        <div class="flex items-center space-x-2">
                            <h3 class="text-base font-black text-slate-900 dark:text-white">
                                Settled Orders
                            </h3>
                            <span class="px-2 py-0.5 rounded-full text-xs font-black bg-emerald-100 dark:bg-emerald-950 text-emerald-700 dark:text-emerald-400 border border-emerald-300 dark:border-emerald-800">
                                {{ orders.length }} Settled
                            </span>
                        </div>
                        <p class="text-xs text-slate-500 dark:text-slate-400">
                            Completed & paid orders archive with thermal receipt reprinting
                        </p>
                    </div>
                </div>

                <div class="flex items-center space-x-3">
                    <!-- Revenue Summary Pill -->
                    <div class="hidden sm:flex items-center space-x-1.5 px-3 py-1 rounded-xl bg-emerald-50 dark:bg-emerald-950/60 border border-emerald-200 dark:border-emerald-800 text-emerald-700 dark:text-emerald-400 shadow-2xs">
                        <span class="text-xs font-semibold">Total Revenue:</span>
                        <span class="text-xs font-black">Rs. {{ formatPrice(totalRevenue) }}</span>
                    </div>

                    <button
                        type="button"
                        @click="fetchOrders"
                        :disabled="loading"
                        class="p-2 text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors"
                        title="Refresh list"
                    >
                        <svg class="w-4 h-4" :class="{ 'animate-spin': loading }" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                    </button>
                    <button
                        type="button"
                        @click="emit('close')"
                        class="p-2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors text-sm font-bold"
                    >
                        ✕
                    </button>
                </div>
            </div>

            <!-- Filters Bar -->
            <div class="px-5 py-2.5 border-b border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 flex flex-wrap items-center gap-2.5 shrink-0">
                <!-- Search Input -->
                <div class="flex-1 min-w-[200px] relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>
                    <input
                        type="text"
                        v-model="searchQuery"
                        placeholder="Search order #, token, customer, table..."
                        class="w-full pl-8.5 pr-7 py-1.5 text-xs bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 rounded-xl text-slate-900 dark:text-white placeholder-slate-400 focus:outline-hidden focus:ring-2 focus:ring-indigo-500"
                    />
                    <button
                        v-if="searchQuery"
                        type="button"
                        @click="searchQuery = ''"
                        class="absolute inset-y-0 right-0 pr-2 flex items-center text-slate-400 hover:text-slate-600 text-xs"
                    >
                        ✕
                    </button>
                </div>

                <!-- Quick Date Toggle Pills -->
                <div class="flex items-center space-x-1 bg-slate-100 dark:bg-slate-800/80 p-0.5 rounded-xl border border-slate-200 dark:border-slate-700">
                    <button
                        type="button"
                        @click="setQuickDate('today')"
                        :class="[
                            'px-2.5 py-1 text-xs font-bold rounded-lg transition-all',
                            selectedDate === (props.businessDate || new Date().toLocaleDateString('en-CA'))
                                ? 'bg-indigo-600 text-white dark:bg-cyan-600 shadow-xs'
                                : 'text-slate-600 dark:text-slate-400 hover:text-slate-900'
                        ]"
                    >
                        Today
                    </button>
                    <button
                        type="button"
                        @click="setQuickDate('yesterday')"
                        class="px-2.5 py-1 text-xs font-bold rounded-lg text-slate-600 dark:text-slate-400 hover:text-slate-900 transition-all"
                    >
                        Yesterday
                    </button>
                    <button
                        type="button"
                        @click="setQuickDate('all')"
                        :class="[
                            'px-2.5 py-1 text-xs font-bold rounded-lg transition-all',
                            selectedDate === ''
                                ? 'bg-indigo-600 text-white dark:bg-cyan-600 shadow-xs'
                                : 'text-slate-600 dark:text-slate-400 hover:text-slate-900'
                        ]"
                    >
                        All Dates
                    </button>
                </div>

                <!-- Custom Date Input -->
                <input
                    type="date"
                    v-model="selectedDate"
                    class="py-1 px-2.5 text-xs bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg text-slate-700 dark:text-slate-300 font-medium"
                    title="Filter by specific date"
                />

                <!-- Payment Type Filter -->
                <select
                    v-model="selectedPaymentTypeId"
                    class="py-1 px-2.5 text-xs bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg text-slate-700 dark:text-slate-300 focus:outline-hidden focus:ring-2 focus:ring-indigo-500 font-bold"
                >
                    <option value="">All Payment Types</option>
                    <option v-for="pt in paymentTypes" :key="pt.id" :value="pt.id">
                        {{ pt.name }}
                    </option>
                </select>

                <!-- Order Type Filter -->
                <select
                    v-model="selectedOrderTypeId"
                    class="py-1 px-2.5 text-xs bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg text-slate-700 dark:text-slate-300 focus:outline-hidden focus:ring-2 focus:ring-indigo-500 font-bold"
                >
                    <option value="">All Order Types</option>
                    <option v-for="ot in orderTypes" :key="ot.id" :value="ot.id">
                        {{ ot.name }}
                    </option>
                </select>
            </div>

            <!-- Structured Table View -->
            <div class="overflow-x-auto flex-1 bg-slate-50/50 dark:bg-slate-950/50">
                <div v-if="loading" class="py-20 text-center">
                    <div class="inline-block w-8 h-8 border-3 border-emerald-600 dark:border-emerald-400 border-t-transparent rounded-full animate-spin"></div>
                    <p class="mt-2 text-xs text-slate-400 font-medium">Loading settled orders...</p>
                </div>

                <div v-else-if="orders.length === 0" class="py-20 text-center text-slate-400 text-xs">
                    <div class="w-12 h-12 mx-auto rounded-full bg-slate-200/50 dark:bg-slate-800 flex items-center justify-center text-slate-400 mb-2">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <p class="font-bold text-slate-600 dark:text-slate-300">No settled orders found</p>
                    <p class="text-[11px] text-slate-400 mt-0.5">Settle an active order with payment to see it archived here</p>
                </div>

                <table v-else class="w-full text-left border-collapse min-w-[940px]">
                    <thead class="sticky top-0 bg-slate-100 dark:bg-slate-800 border-b border-slate-200 dark:border-slate-700 text-[11px] font-black uppercase tracking-wider text-slate-500 dark:text-slate-400 z-10 shadow-2xs">
                        <tr>
                            <th class="py-3 px-4 w-44">Token & Order</th>
                            <th class="py-3 px-4 w-48">Channel & Destination</th>
                            <th class="py-3 px-4">Items Summary</th>
                            <th class="py-3 px-4 w-56">Payment & Cashier</th>
                            <th class="py-3 px-4 w-32 text-right">Total Paid</th>
                            <th class="py-3 px-4 w-36 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 dark:divide-slate-800 bg-white dark:bg-slate-900 text-xs">
                        <tr
                            v-for="o in orders"
                            :key="o.id"
                            class="hover:bg-slate-50/80 dark:hover:bg-slate-800/40 transition-colors group"
                        >
                            <!-- Col 1: Token & Order # -->
                            <td class="py-3 px-4 align-top">
                                <div class="flex items-center space-x-1.5">
                                    <span v-if="o.token_number" class="inline-flex items-center px-2 py-0.5 rounded-md font-black text-xs bg-emerald-50 dark:bg-emerald-950 text-emerald-700 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800">
                                        {{ o.token_number }}
                                    </span>
                                    <span class="font-black text-slate-900 dark:text-white text-xs">
                                        {{ o.order_number }}
                                    </span>
                                </div>
                                <div class="flex items-center space-x-2 mt-1 text-[11px] text-slate-400">
                                    <span>{{ formatTime(o.updated_at) }}</span>
                                    <span>•</span>
                                    <span>{{ formatDate(o.updated_at) }}</span>
                                </div>
                                <div class="mt-1">
                                    <span class="inline-flex items-center px-1.5 py-0.2 rounded text-[10px] font-black uppercase bg-emerald-100 dark:bg-emerald-950 text-emerald-700 dark:text-emerald-400 border border-emerald-300 dark:border-emerald-800">
                                        ✓ Paid
                                    </span>
                                </div>
                            </td>

                            <!-- Col 2: Channel & Destination -->
                            <td class="py-3 px-4 align-top">
                                <div class="flex flex-wrap items-center gap-1">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-black uppercase bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300">
                                        {{ o.order_type?.name }}
                                    </span>
                                    <span v-if="o.party" class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold bg-purple-50 dark:bg-purple-950/60 text-purple-700 dark:text-purple-400 border border-purple-200 dark:border-purple-800">
                                        {{ o.party.name }}
                                    </span>
                                </div>
                                <div v-if="o.table" class="mt-1 text-[11px] font-bold text-amber-700 dark:text-amber-400 flex items-center space-x-1">
                                    <span>Table: {{ o.table.name }}</span>
                                </div>
                                <div v-if="o.customer" class="mt-1 text-[11px] text-slate-700 dark:text-slate-300">
                                    <span class="font-bold">{{ o.customer.name }}</span>
                                    <span class="text-slate-400 block text-[10px]">{{ o.customer.phone }}</span>
                                </div>
                            </td>

                            <!-- Col 3: Items Summary -->
                            <td class="py-3 px-4 align-top">
                                <div class="flex flex-wrap gap-1.5">
                                    <span
                                        v-for="item in o.items"
                                        :key="item.id"
                                        class="inline-flex items-center px-2 py-0.5 rounded-lg text-[11px] bg-slate-100 dark:bg-slate-800 text-slate-800 dark:text-slate-200 border border-slate-200/80 dark:border-slate-700"
                                    >
                                        <strong class="text-indigo-600 dark:text-cyan-400 mr-1 font-black">{{ item.quantity }}×</strong>
                                        <span>{{ item.food_item?.name }}</span>
                                        <span v-if="item.variation" class="text-slate-400 ml-1 text-[10px]">({{ item.variation.name }})</span>
                                        <span v-if="item.properties && item.properties.length > 0" class="text-purple-600 dark:text-purple-400 text-[10px] ml-1">
                                            +{{ item.properties.map(p => p.property_name).join(', ') }}
                                        </span>
                                    </span>
                                </div>
                            </td>

                            <!-- Col 4: Payment Breakdown & Cashier -->
                            <td class="py-3 px-4 align-top">
                                <div class="flex flex-wrap items-center gap-1.5">
                                    <div
                                        v-for="p in o.payments"
                                        :key="p.id"
                                        class="inline-flex items-center px-2 py-0.5 rounded-md bg-emerald-50 dark:bg-emerald-950/50 border border-emerald-200 dark:border-emerald-800 text-[11px] text-emerald-800 dark:text-emerald-300 font-semibold"
                                    >
                                        <span>{{ p.payment_type?.name || 'Payment' }}:</span>
                                        <span class="font-black ml-1">Rs. {{ formatPrice(p.amount) }}</span>
                                        <span v-if="p.card_last_four" class="text-[10px] text-slate-500 ml-1">(****{{ p.card_last_four }})</span>
                                    </div>
                                    <div v-if="!o.payments || o.payments.length === 0" class="text-[11px] text-slate-400">
                                        Paid
                                    </div>
                                </div>
                                <div v-if="o.user" class="text-[10px] text-slate-400 mt-1.5">
                                    Cashier: <span class="font-bold text-slate-700 dark:text-slate-300">{{ o.user.name }}</span>
                                </div>
                            </td>

                            <!-- Col 5: Total Paid -->
                            <td class="py-3 px-4 align-top text-right">
                                <div class="text-sm font-black text-emerald-600 dark:text-emerald-400">
                                    Rs. {{ formatPrice(o.grand_total) }}
                                </div>
                                <span class="text-[10px] font-semibold text-slate-400 block mt-0.5">
                                    Paid in full
                                </span>
                            </td>

                            <!-- Col 6: Actions -->
                            <td class="py-3 px-4 align-top text-right">
                                <button
                                    type="button"
                                    @click="handleReprint(o)"
                                    class="py-1 px-3 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white font-black text-xs shadow-2xs transition-all inline-flex items-center space-x-1.5"
                                    title="Reprint customer thermal invoice"
                                >
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                                    </svg>
                                    <span>Reprint Bill</span>
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>
