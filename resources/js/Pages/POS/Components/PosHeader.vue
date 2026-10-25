<script setup>
import { ref, computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import SelectInput from '@/Components/SelectInput.vue';

const props = defineProps({
    branches: {
        type: Array,
        default: () => []
    },
    currentBranchId: [Number, String],
    menus: {
        type: Array,
        default: () => []
    },
    currentMenuId: [Number, String],
    orderTypes: {
        type: Array,
        default: () => []
    },
    currentOrderTypeId: [Number, String],
    availableParties: {
        type: Array,
        default: () => []
    },
    selectedPartyId: [Number, String],
    selectedTable: Object,
    selectedCustomer: Object,
    submittedOrdersCount: {
        type: Number,
        default: 0
    },
    heldOrdersCount: {
        type: Number,
        default: 0
    },
    user: Object,
    businessDate: String,
    businessDay: String,
    businessDateFormatted: String,
    activeBusinessDay: Object,
    activeShift: Object,
});

const emit = defineEmits([
    'update:currentBranchId',
    'update:currentMenuId',
    'update:currentOrderTypeId',
    'update:selectedPartyId',
    'openTableModal',
    'openCustomerModal',
    'openHeldOrdersModal',
    'openSubmittedOrdersModal',
    'openSettledOrdersModal',
    'openStartShiftModal',
    'openCloseShiftModal',
    'openShiftSummaryModal',
    'openOpenBusinessDayModal',
    'openCloseBusinessDayModal',
]);

const isShiftDropdownOpen = ref(false);

const currentOrderType = computed(() => {
    return props.orderTypes.find(ot => ot.id == props.currentOrderTypeId);
});

const isDineIn = computed(() => {
    return currentOrderType.value?.name?.toLowerCase().includes('dine') || currentOrderType.value?.code === 'dine_in';
});

const isDelivery = computed(() => {
    return currentOrderType.value?.name?.toLowerCase().includes('delivery') || currentOrderType.value?.code === 'delivery';
});

const toggleFullscreen = () => {
    if (!document.fullscreenElement) {
        document.documentElement.requestFullscreen().catch(() => {});
    } else {
        if (document.exitFullscreen) {
            document.exitFullscreen();
        }
    }
};
</script>

<template>
    <header class="h-16 bg-white dark:bg-slate-900 border-b border-slate-200 dark:border-slate-800 px-4 flex items-center justify-between select-none shrink-0 shadow-sm z-20">
        <!-- Left: Logo & Branch / Menu Controls -->
        <div class="flex items-center space-x-2 md:space-x-3">
            <Link :href="route('dashboard')" class="flex items-center space-x-2 text-indigo-600 dark:text-cyan-400 group mr-1">
                <div class="w-9 h-9 rounded-xl bg-indigo-600 dark:bg-cyan-600 text-white flex items-center justify-center font-black text-lg shadow-sm group-hover:scale-105 transition-transform">
                    POS
                </div>
            </Link>

            <!-- Branch Select -->
            <div class="w-36 md:w-44">
                <SelectInput
                    :modelValue="currentBranchId"
                    @update:modelValue="emit('update:currentBranchId', Number($event))"
                    :options="branches"
                    valueKey="id"
                    labelKey="name"
                    placeholder="Select Branch"
                    size="sm"
                    :showSearch="branches.length > 5"
                />
            </div>

            <!-- Menu Select -->
            <div class="w-36 md:w-44">
                <SelectInput
                    :modelValue="currentMenuId"
                    @update:modelValue="emit('update:currentMenuId', Number($event))"
                    :options="menus"
                    valueKey="id"
                    labelKey="name"
                    placeholder="Select Menu"
                    size="sm"
                    :showSearch="menus.length > 5"
                />
            </div>

            <!-- Business Day Status Badge / Button -->
            <button
                type="button"
                @click="activeBusinessDay ? emit('openCloseBusinessDayModal') : emit('openOpenBusinessDayModal')"
                class="hidden xl:flex items-center space-x-2 px-2.5 py-1.5 rounded-xl border transition-all text-left shadow-2xs shrink-0 cursor-pointer hover:scale-[1.02]"
                :class="activeBusinessDay 
                    ? 'bg-slate-50 hover:bg-slate-100 dark:bg-slate-800/90 dark:hover:bg-slate-800 border-slate-200 dark:border-slate-700' 
                    : 'bg-rose-50 hover:bg-rose-100 dark:bg-rose-950/60 dark:hover:bg-rose-900/60 border-rose-300 dark:border-rose-800 animate-pulse'"
                :title="activeBusinessDay ? 'Business Day is Open. Click to view Day End / Close' : 'Business Day is Closed! Click to Open Day'"
            >
                <div 
                    class="w-6 h-6 rounded-lg flex items-center justify-center shrink-0"
                    :class="activeBusinessDay ? 'bg-indigo-100 dark:bg-cyan-950/70 text-indigo-600 dark:text-cyan-400' : 'bg-rose-200 dark:bg-rose-900 text-rose-700 dark:text-rose-300'"
                >
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                </div>
                <div class="flex flex-col text-left leading-tight">
                    <div class="flex items-center space-x-1">
                        <span class="w-1.5 h-1.5 rounded-full" :class="activeBusinessDay ? 'bg-emerald-500' : 'bg-rose-500'"></span>
                        <span class="text-[9px] font-black uppercase tracking-wider text-slate-400 dark:text-slate-500">
                            {{ activeBusinessDay ? 'Day Open' : 'Day Closed' }}
                        </span>
                    </div>
                    <span class="text-xs font-black text-slate-800 dark:text-slate-200 whitespace-nowrap">
                        {{ businessDateFormatted || businessDate }} <span v-if="businessDay" class="text-indigo-600 dark:text-cyan-400">({{ businessDay }})</span>
                    </span>
                </div>
            </button>

            <!-- Cashier Shift Badge & Dropdown -->
            <div class="relative hidden lg:block">
                <button
                    v-if="activeShift"
                    type="button"
                    @click="isShiftDropdownOpen = !isShiftDropdownOpen"
                    class="flex items-center space-x-2 px-2.5 py-1.5 rounded-xl bg-emerald-50 hover:bg-emerald-100 dark:bg-emerald-950/60 dark:hover:bg-emerald-900/60 border border-emerald-300 dark:border-emerald-800 text-emerald-900 dark:text-emerald-200 shadow-2xs transition-all text-left cursor-pointer"
                    title="Active Cashier Shift. Click for shift actions."
                >
                    <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                    <div class="flex flex-col text-left leading-tight">
                        <span class="text-[9px] font-black uppercase tracking-wider text-emerald-700 dark:text-emerald-400">Active Shift</span>
                        <span class="text-xs font-black font-mono">{{ activeShift.shift_number }}</span>
                    </div>
                    <svg class="w-3.5 h-3.5 text-emerald-600 ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>

                <button
                    v-else
                    type="button"
                    @click="emit('openStartShiftModal')"
                    class="flex items-center space-x-1.5 px-3 py-1.5 rounded-xl bg-amber-50 hover:bg-amber-100 dark:bg-amber-950/60 dark:hover:bg-amber-900/60 border border-amber-300 dark:border-amber-700 text-amber-800 dark:text-amber-200 shadow-2xs transition-all font-bold text-xs animate-pulse cursor-pointer"
                    title="No cashier shift active. Click to start shift."
                >
                    <span>🟡</span>
                    <span>Start Shift</span>
                </button>

                <!-- Shift Actions Dropdown -->
                <div
                    v-if="activeShift && isShiftDropdownOpen"
                    class="absolute left-0 mt-1 w-52 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl shadow-xl py-1 z-30"
                >
                    <div class="px-3 py-2 border-b border-slate-100 dark:border-slate-700 text-[11px] text-slate-500">
                        Shift: <span class="font-bold text-slate-800 dark:text-slate-200">{{ activeShift.shift_number }}</span>
                        <div class="text-[10px] text-slate-400">Float: Rs. {{ Number(activeShift.opening_float || 0).toLocaleString() }}</div>
                    </div>
                    <button
                        type="button"
                        @click="isShiftDropdownOpen = false; emit('openShiftSummaryModal')"
                        class="w-full text-left px-3 py-2 text-xs font-bold text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-700 flex items-center space-x-2"
                    >
                        <span>🧾</span>
                        <span>View / Print X-Report</span>
                    </button>
                    <button
                        type="button"
                        @click="isShiftDropdownOpen = false; emit('openCloseShiftModal')"
                        class="w-full text-left px-3 py-2 text-xs font-bold text-rose-600 dark:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-950/40 flex items-center space-x-2"
                    >
                        <span>🛑</span>
                        <span>End Shift & Reconcile</span>
                    </button>
                    <Link
                        :href="route('pos.monitor')"
                        class="w-full text-left px-3 py-2 text-xs font-bold text-indigo-600 dark:text-cyan-400 hover:bg-indigo-50 dark:hover:bg-slate-700 flex items-center space-x-2 border-t border-slate-100 dark:border-slate-700"
                    >
                        <span>📊</span>
                        <span>Admin Shift Monitor</span>
                    </Link>
                </div>
            </div>
        </div>

        <!-- Center: Order Type Toggle (Dine-In, Takeaway, Delivery) & Party Selector -->
        <div class="flex items-center space-x-2">
            <div class="flex items-center space-x-1 bg-slate-100 dark:bg-slate-800 p-1 rounded-xl border border-slate-200 dark:border-slate-700">
                <button
                    v-for="ot in orderTypes"
                    :key="ot.id"
                    type="button"
                    @click="emit('update:currentOrderTypeId', ot.id)"
                    :class="[
                        'px-3.5 py-1.5 text-xs font-bold rounded-lg transition-all flex items-center space-x-1.5',
                        currentOrderTypeId == ot.id
                            ? 'bg-indigo-600 text-white shadow-sm dark:bg-cyan-600'
                            : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white'
                    ]"
                >
                    <!-- Icons based on type -->
                    <svg v-if="ot.name.toLowerCase().includes('dine')" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M3 14h18m-9-4v8m-7 0h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                    </svg>
                    <svg v-else-if="ot.name.toLowerCase().includes('take')" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                    </svg>
                    <svg v-else class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                    </svg>
                    <span>{{ ot.name }}</span>
                </button>
            </div>

            <!-- Party Selector (Dynamically populated based on selected Order Type) -->
            <div v-if="availableParties && availableParties.length > 0" class="flex items-center space-x-1.5 bg-slate-50 dark:bg-slate-800/80 px-2 py-1 rounded-xl border border-slate-200 dark:border-slate-700">
                <span class="text-[11px] font-bold text-slate-500 dark:text-slate-400 pl-1">Party:</span>
                <div class="w-40 md:w-48">
                    <SelectInput
                        :modelValue="selectedPartyId"
                        @update:modelValue="emit('update:selectedPartyId', $event)"
                        :options="availableParties"
                        valueKey="id"
                        labelKey="name"
                        placeholder="Select Party"
                        size="sm"
                        :showSearch="availableParties.length > 4"
                    />
                </div>
            </div>
        </div>

        <!-- Right: Table/Customer Indicator, Held Orders & Actions -->
        <div class="flex items-center space-x-2.5">
            <!-- Table Selector (if Dine-in) -->
            <button
                v-if="isDineIn"
                type="button"
                @click="emit('openTableModal')"
                :class="[
                    'flex items-center space-x-1.5 px-3 py-1.5 text-xs font-bold rounded-lg border transition-all',
                    selectedTable
                        ? 'bg-amber-50 dark:bg-amber-950/40 border-amber-300 dark:border-amber-700 text-amber-700 dark:text-amber-400'
                        : 'bg-white dark:bg-slate-800 border-slate-300 dark:border-slate-700 text-slate-700 dark:text-slate-300 hover:border-indigo-400'
                ]"
            >
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16" />
                </svg>
                <span>{{ selectedTable ? selectedTable.name : 'Select Table' }}</span>
            </button>

            <!-- Customer Selector (if Delivery or Customer selected) -->
            <button
                type="button"
                @click="emit('openCustomerModal')"
                :class="[
                    'flex items-center space-x-1.5 px-3 py-1.5 text-xs font-bold rounded-lg border transition-all',
                    selectedCustomer
                        ? 'bg-emerald-50 dark:bg-emerald-950/40 border-emerald-300 dark:border-emerald-700 text-emerald-700 dark:text-emerald-400'
                        : isDelivery
                            ? 'bg-rose-50 dark:bg-rose-950/40 border-rose-300 dark:border-rose-700 text-rose-700 dark:text-rose-400 animate-pulse'
                            : 'bg-white dark:bg-slate-800 border-slate-300 dark:border-slate-700 text-slate-700 dark:text-slate-300 hover:border-indigo-400'
                ]"
            >
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                </svg>
                <span>{{ selectedCustomer ? selectedCustomer.name : (isDelivery ? 'Add Customer *' : 'Customer') }}</span>
            </button>

            <!-- Submitted Orders Button -->
            <button
                type="button"
                @click="emit('openSubmittedOrdersModal')"
                class="relative flex items-center space-x-1.5 px-3 py-1.5 text-xs font-bold rounded-lg bg-amber-50 hover:bg-amber-100 dark:bg-amber-950/60 dark:hover:bg-amber-900/60 border border-amber-200 dark:border-amber-800 text-amber-800 dark:text-amber-300 transition-all shadow-xs"
                title="Active Submitted Orders"
            >
                <svg class="w-4 h-4 text-amber-600 dark:text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                </svg>
                <span>Submitted</span>
                <span
                    v-if="submittedOrdersCount > 0"
                    class="ml-1 px-1.5 py-0.2 bg-amber-600 text-white rounded-full text-[10px] font-black"
                >
                    {{ submittedOrdersCount }}
                </span>
            </button>

            <!-- Settled Orders Button -->
            <button
                type="button"
                @click="emit('openSettledOrdersModal')"
                class="relative flex items-center space-x-1.5 px-3 py-1.5 text-xs font-bold rounded-lg bg-emerald-50 hover:bg-emerald-100 dark:bg-emerald-950/60 dark:hover:bg-emerald-900/60 border border-emerald-200 dark:border-emerald-800 text-emerald-800 dark:text-emerald-300 transition-all shadow-xs"
                title="Completed & Paid Orders Archive"
            >
                <svg class="w-4 h-4 text-emerald-600 dark:text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span>Settled</span>
            </button>

            <!-- Held / Parked Orders Button -->
            <button
                type="button"
                @click="emit('openHeldOrdersModal')"
                class="relative flex items-center space-x-1.5 px-3 py-1.5 text-xs font-bold rounded-lg bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-200 transition-all"
                title="Active & Parked Orders"
            >
                <svg class="w-4 h-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span>Held</span>
                <span
                    v-if="heldOrdersCount > 0"
                    class="ml-1 px-1.5 py-0.2 bg-rose-500 text-white rounded-full text-[10px] font-black"
                >
                    {{ heldOrdersCount }}
                </span>
            </button>

            <!-- Fullscreen Toggle -->
            <button
                type="button"
                @click="toggleFullscreen"
                class="p-2 text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors"
                title="Toggle Fullscreen"
            >
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4" />
                </svg>
            </button>

            <!-- Cashier Pill -->
            <div class="flex items-center space-x-2 pl-2 border-l border-slate-200 dark:border-slate-800">
                <div class="w-8 h-8 rounded-full bg-indigo-100 dark:bg-cyan-950 text-indigo-700 dark:text-cyan-400 flex items-center justify-center font-bold text-xs uppercase border border-indigo-200 dark:border-cyan-800">
                    {{ user?.name ? user.name.charAt(0) : 'U' }}
                </div>
                <div class="hidden lg:block text-left">
                    <p class="text-xs font-bold text-slate-800 dark:text-slate-200 leading-tight">{{ user?.name }}</p>
                    <p class="text-[10px] text-slate-400">Cashier</p>
                </div>
            </div>
        </div>
    </header>
</template>
