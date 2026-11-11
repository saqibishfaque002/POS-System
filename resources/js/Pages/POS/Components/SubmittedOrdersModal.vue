<script setup>
import { ref, computed, watch } from 'vue';
import axios from 'axios';

const props = defineProps({
    isOpen: Boolean,
    branchId: [Number, String],
    orderTypes: {
        type: Array,
        default: () => []
    },
    businessDate: String,
});

const emit = defineEmits(['close', 'editOrder', 'settleOrder', 'printKot', 'voidOrder']);

const loading = ref(false);
const orders = ref([]);
const searchQuery = ref('');
const selectedOrderTypeId = ref('');
const selectedStatus = ref('');

const getInitialDate = () => props.businessDate || new Date().toLocaleDateString('en-CA');
const selectedDate = ref(getInitialDate());

const voidingOrderId = ref(null);
const voidReason = ref('');

const fetchOrders = async () => {
    if (!props.isOpen && orders.value.length > 0) return;
    loading.value = true;
    try {
        const params = {
            branch_id: props.branchId,
        };
        if (searchQuery.value.trim()) params.search = searchQuery.value.trim();
        if (selectedOrderTypeId.value) params.order_type_id = selectedOrderTypeId.value;
        if (selectedStatus.value) params.status = selectedStatus.value;
        if (selectedDate.value) {
            params.date = selectedDate.value;
        } else {
            params.date = 'all';
        }

        const response = await axios.get(route('pos.orders.submitted'), { params });
        orders.value = response.data.orders || [];
    } catch (e) {
        console.error('Error fetching submitted orders:', e);
    } finally {
        loading.value = false;
    }
};

watch(() => props.isOpen, (newVal) => {
    if (newVal) {
        voidingOrderId.value = null;
        voidReason.value = '';
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

watch([selectedOrderTypeId, selectedStatus, selectedDate], () => {
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

const getStatusBadgeClass = (status) => {
    switch (status) {
        case 'submitted':
            return 'inline-flex items-center px-2 py-0.5 rounded text-[10px] font-black uppercase bg-amber-100 dark:bg-amber-950 text-amber-800 dark:text-amber-300 border border-amber-300 dark:border-amber-800';
        case 'preparing':
            return 'inline-flex items-center px-2 py-0.5 rounded text-[10px] font-black uppercase bg-blue-100 dark:bg-blue-950 text-blue-800 dark:text-blue-300 border border-blue-300 dark:border-blue-800';
        case 'ready':
            return 'inline-flex items-center px-2 py-0.5 rounded text-[10px] font-black uppercase bg-emerald-100 dark:bg-emerald-950 text-emerald-800 dark:text-emerald-300 border border-emerald-300 dark:border-emerald-800';
        case 'delivered':
            return 'inline-flex items-center px-2 py-0.5 rounded text-[10px] font-black uppercase bg-purple-100 dark:bg-purple-950 text-purple-800 dark:text-purple-300 border border-purple-300 dark:border-purple-800';
        default:
            return 'inline-flex items-center px-2 py-0.5 rounded text-[10px] font-black uppercase bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-700';
    }
};

const startVoid = (orderId) => {
    voidingOrderId.value = orderId;
    voidReason.value = '';
};

const confirmVoid = (orderId) => {
    if (!voidReason.value.trim()) return;
    emit('voidOrder', { orderId, reason: voidReason.value.trim() });
    voidingOrderId.value = null;
    orders.value = orders.value.filter(o => o.id !== orderId);
};

const handleEdit = (order) => {
    emit('editOrder', order);
};

const handleSettle = (order) => {
    emit('settleOrder', order);
};

const handlePrintKot = (order) => {
    emit('printKot', order);
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
                    <div class="w-10 h-10 rounded-xl bg-amber-500/10 dark:bg-amber-500/20 text-amber-600 dark:text-amber-400 flex items-center justify-center font-bold">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                        </svg>
                    </div>
                    <div>
                        <div class="flex items-center space-x-2">
                            <h3 class="text-base font-black text-slate-900 dark:text-white">
                                Submitted Orders
                            </h3>
                            <span class="px-2 py-0.5 rounded-full text-xs font-black bg-amber-100 dark:bg-amber-950 text-amber-700 dark:text-amber-400 border border-amber-300 dark:border-amber-800">
                                {{ orders.length }} Active
                            </span>
                        </div>
                        <p class="text-xs text-slate-500 dark:text-slate-400">
                            View active orders, add items, modify quantities, or proceed to settlement
                        </p>
                    </div>
                </div>

                <div class="flex items-center space-x-2">
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

                <!-- Date Picker Input -->
                <input
                    type="date"
                    v-model="selectedDate"
                    class="py-1 px-2.5 text-xs bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg text-slate-700 dark:text-slate-300 font-medium"
                    title="Filter by specific date"
                />

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

                <!-- Status Filter -->
                <select
                    v-model="selectedStatus"
                    class="py-1 px-2.5 text-xs bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg text-slate-700 dark:text-slate-300 focus:outline-hidden focus:ring-2 focus:ring-indigo-500 font-bold"
                >
                    <option value="">All Statuses</option>
                    <option value="submitted">Submitted</option>
                    <option value="preparing">Preparing</option>
                    <option value="ready">Ready</option>
                    <option value="delivered">Delivered</option>
                </select>
            </div>

            <!-- Structured Table View -->
            <div class="overflow-x-auto flex-1 bg-slate-50/50 dark:bg-slate-950/50">
                <div v-if="loading" class="py-20 text-center">
                    <div class="inline-block w-8 h-8 border-3 border-indigo-600 dark:border-cyan-400 border-t-transparent rounded-full animate-spin"></div>
                    <p class="mt-2 text-xs text-slate-400 font-medium">Loading submitted orders...</p>
                </div>

                <div v-else-if="orders.length === 0" class="py-20 text-center text-slate-400 text-xs">
                    <div class="w-12 h-12 mx-auto rounded-full bg-slate-200/50 dark:bg-slate-800 flex items-center justify-center text-slate-400 mb-2">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                    </div>
                    <p class="font-bold text-slate-600 dark:text-slate-300">No submitted orders found</p>
                    <p class="text-[11px] text-slate-400 mt-0.5">Punch an order and click "Submit" to see it listed here</p>
                </div>

                <table v-else class="w-full text-left border-collapse min-w-[940px]">
                    <thead class="sticky top-0 bg-slate-100 dark:bg-slate-800 border-b border-slate-200 dark:border-slate-700 text-[11px] font-black uppercase tracking-wider text-slate-500 dark:text-slate-400 z-10 shadow-2xs">
                        <tr>
                            <th class="py-3 px-4 w-44">Token & Order</th>
                            <th class="py-3 px-4 w-48">Channel & Destination</th>
                            <th class="py-3 px-4">Items Summary</th>
                            <th class="py-3 px-4 w-32">Status & Server</th>
                            <th class="py-3 px-4 w-32 text-right">Grand Total</th>
                            <th class="py-3 px-4 w-52 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 dark:divide-slate-800 bg-white dark:bg-slate-900 text-xs">
                        <template v-for="o in orders" :key="o.id">
                            <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/40 transition-colors group">
                                <!-- Col 1: Token & Order # -->
                                <td class="py-3 px-4 align-top">
                                    <div class="flex items-center space-x-1.5">
                                        <span v-if="o.token_number" class="inline-flex items-center px-2 py-0.5 rounded-md font-black text-xs bg-indigo-50 dark:bg-cyan-950 text-indigo-700 dark:text-cyan-400 border border-indigo-200 dark:border-cyan-800">
                                            {{ o.token_number }}
                                        </span>
                                        <span class="font-black text-slate-900 dark:text-white text-xs">
                                            {{ o.order_number }}
                                        </span>
                                    </div>
                                    <div class="flex items-center space-x-2 mt-1 text-[11px] text-slate-400">
                                        <span>{{ formatTime(o.created_at) }}</span>
                                        <span>•</span>
                                        <span>{{ formatDate(o.created_at) }}</span>
                                    </div>
                                    <div v-if="o.kot_number" class="mt-1">
                                        <span class="inline-flex items-center px-1.5 py-0.2 rounded text-[10px] font-bold bg-amber-50 dark:bg-amber-950/70 text-amber-700 dark:text-amber-400 border border-amber-200 dark:border-amber-800">
                                            KOT #{{ o.kot_number }}
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
                                    <div v-if="o.rider_name" class="mt-1 text-[10px] text-slate-500 dark:text-slate-400">
                                        <span class="font-semibold text-slate-600 dark:text-slate-300">Rider:</span> {{ o.rider_name }}
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
                                    <p v-if="o.delivery_address" class="text-[10px] text-slate-400 mt-1 truncate max-w-sm" :title="o.delivery_address">
                                        <span class="font-semibold text-slate-500">Addr:</span> {{ o.delivery_address }}
                                    </p>
                                </td>

                                <!-- Col 4: Status & Server -->
                                <td class="py-3 px-4 align-top">
                                    <div>
                                        <span :class="getStatusBadgeClass(o.status)">
                                            {{ o.status }}
                                        </span>
                                    </div>
                                    <div v-if="o.user" class="text-[10px] text-slate-400 mt-1.5">
                                        Server: <span class="font-bold text-slate-700 dark:text-slate-300">{{ o.user.name }}</span>
                                    </div>
                                </td>

                                <!-- Col 5: Grand Total -->
                                <td class="py-3 px-4 align-top text-right">
                                    <div class="text-sm font-black text-slate-900 dark:text-white">
                                        Rs. {{ formatPrice(o.grand_total) }}
                                    </div>
                                    <span class="text-[10px] font-semibold text-amber-600 dark:text-amber-400 block mt-0.5">
                                        Unsettled
                                    </span>
                                </td>

                                <!-- Col 6: Actions -->
                                <td class="py-3 px-4 align-top text-right">
                                    <div class="flex items-center justify-end space-x-1.5">
                                        <!-- Edit / Add Items -->
                                        <button
                                            type="button"
                                            @click="handleEdit(o)"
                                            class="py-1 px-2.5 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs shadow-2xs transition-all flex items-center space-x-1"
                                            title="Load order into cart to add or edit items"
                                        >
                                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                            </svg>
                                            <span>Edit</span>
                                        </button>

                                        <!-- Direct Settle -->
                                        <button
                                            type="button"
                                            @click="handleSettle(o)"
                                            class="py-1 px-2.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white font-black text-xs shadow-2xs transition-all flex items-center space-x-1"
                                            title="Settle payment for this order"
                                        >
                                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                                            </svg>
                                            <span>Settle</span>
                                        </button>

                                        <!-- Reprint KOT -->
                                        <button
                                            type="button"
                                            @click="handlePrintKot(o)"
                                            class="p-1 rounded-lg bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 font-bold transition-colors"
                                            title="Reprint Kitchen Order Ticket (KOT)"
                                        >
                                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                                            </svg>
                                        </button>

                                        <!-- Void Order -->
                                        <button
                                            type="button"
                                            @click="startVoid(o.id)"
                                            class="p-1 rounded-lg bg-rose-50 hover:bg-rose-100 dark:bg-rose-950/40 dark:hover:bg-rose-900/60 text-rose-600 dark:text-rose-400 font-bold border border-rose-200 dark:border-rose-800 transition-colors"
                                            title="Cancel / Void Order"
                                        >
                                            ✕
                                        </button>
                                    </div>
                                </td>
                            </tr>

                            <!-- Inline Void Reason Row -->
                            <tr v-if="voidingOrderId === o.id" class="bg-rose-50/70 dark:bg-rose-950/40 border-b border-rose-200 dark:border-rose-800">
                                <td colspan="6" class="p-3">
                                    <div class="flex items-center space-x-2">
                                        <span class="text-xs font-bold text-rose-700 dark:text-rose-300 shrink-0">Reason to Void #{{ o.order_number }}:</span>
                                        <input
                                            type="text"
                                            v-model="voidReason"
                                            placeholder="Reason for cancellation (e.g. Customer cancelled order)..."
                                            class="flex-1 text-xs bg-white dark:bg-slate-900 border border-rose-300 dark:border-rose-700 rounded-lg px-2.5 py-1 text-slate-900 dark:text-white"
                                            @keyup.enter="confirmVoid(o.id)"
                                        />
                                        <button
                                            type="button"
                                            @click="confirmVoid(o.id)"
                                            class="px-3 py-1 bg-rose-600 text-white rounded-lg text-xs font-bold hover:bg-rose-700 shadow-2xs"
                                        >
                                            Confirm Void
                                        </button>
                                        <button
                                            type="button"
                                            @click="voidingOrderId = null"
                                            class="px-2.5 py-1 text-xs text-slate-500 hover:text-slate-700 dark:hover:text-slate-300"
                                        >
                                            Cancel
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>
