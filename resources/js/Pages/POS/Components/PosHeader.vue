<script setup>
import { ref, computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import SelectInput from '@/Components/SelectInput.vue';
import { useTheme } from '@/Composables/useTheme';

const { isDark, toggleTheme } = useTheme();

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
    mobileActiveView: {
        type: String,
        default: 'catalog'
    },
    cartItemsCount: {
        type: Number,
        default: 0
    },
});

const emit = defineEmits([
    'update:currentBranchId',
    'update:currentMenuId',
    'update:mobileActiveView',
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
const isMobileDrawerOpen = ref(false);

const currentBranchName = computed(() => {
    const b = props.branches.find(br => Number(br.id) === Number(props.currentBranchId));
    return b ? b.name : 'Branch';
});

const currentMenuName = computed(() => {
    const m = props.menus.find(me => Number(me.id) === Number(props.currentMenuId));
    return m ? m.name : 'Menu';
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
    <header class="relative bg-white dark:bg-slate-900 border-b border-slate-200 dark:border-slate-800 select-none shrink-0 shadow-xs z-30">
        <!-- Main Top Bar -->
        <div class="h-14 lg:h-16 px-2.5 sm:px-4 flex items-center justify-between">
            <!-- Section 1 (Left): Logo & Desktop Selectors / Mobile Branch Tag -->
            <div class="flex items-center space-x-2 sm:space-x-3">
                <Link :href="route('dashboard')" class="flex items-center space-x-2 text-indigo-600 dark:text-cyan-400 group">
                    <div class="w-8 h-8 sm:w-9 sm:h-9 rounded-xl bg-indigo-600 dark:bg-cyan-600 text-white flex items-center justify-center font-black text-sm sm:text-base shadow-sm group-hover:scale-105 transition-transform">
                        POS
                    </div>
                </Link>

                <!-- Desktop Selectors (Hidden on mobile < 1024px) -->
                <div class="hidden lg:flex items-center space-x-3">
                    <!-- Branch Select -->
                    <div class="w-40 xl:w-48">
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
                    <div class="w-40 xl:w-48">
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
                </div>

                <!-- Mobile Active Branch/Menu Tag -->
                <div class="lg:hidden flex flex-col text-left max-w-[100px] sm:max-w-[130px] truncate">
                    <span class="text-[11px] font-black text-slate-800 dark:text-slate-100 truncate leading-tight">
                        {{ currentBranchName }}
                    </span>
                    <span class="text-[9px] font-bold text-slate-400 dark:text-slate-500 truncate leading-tight">
                        {{ currentMenuName }}
                    </span>
                </div>
            </div>

            <!-- Mobile View Switcher Tabs (Center on Mobile, Hidden on Desktop) -->
            <div class="flex lg:hidden items-center p-0.5 bg-slate-100 dark:bg-slate-800 rounded-lg border border-slate-200 dark:border-slate-700 mx-1">
                <button
                    type="button"
                    @click="emit('update:mobileActiveView', 'catalog')"
                    :class="[
                        'px-2 py-1 text-[11px] font-black rounded-md transition-all cursor-pointer flex items-center space-x-1',
                        mobileActiveView === 'catalog'
                            ? 'bg-indigo-600 dark:bg-cyan-600 text-white shadow-xs'
                            : 'text-slate-600 dark:text-slate-300 hover:text-slate-900'
                    ]"
                >
                    <span>🍽️</span>
                    <span>Menu</span>
                </button>
                <button
                    type="button"
                    @click="emit('update:mobileActiveView', 'cart')"
                    :class="[
                        'px-2 py-1 text-[11px] font-black rounded-md transition-all flex items-center space-x-1 cursor-pointer',
                        mobileActiveView === 'cart'
                            ? 'bg-indigo-600 dark:bg-cyan-600 text-white shadow-xs'
                            : 'text-slate-600 dark:text-slate-300 hover:text-slate-900'
                    ]"
                >
                    <span>🛒</span>
                    <span>Cart</span>
                    <span
                        v-if="cartItemsCount > 0"
                        class="px-1 py-0.2 bg-rose-500 text-white rounded-full text-[9px] font-black"
                    >
                        {{ cartItemsCount }}
                    </span>
                </button>
            </div>

            <!-- Section 2 (Center): Desktop Business Day & Shift Operations -->
            <div class="hidden lg:flex items-center space-x-2.5">
                <!-- Business Day Status Badge / Button -->
                <button
                    type="button"
                    @click="activeBusinessDay ? emit('openCloseBusinessDayModal') : emit('openOpenBusinessDayModal')"
                    class="flex items-center space-x-2 px-3 py-1.5 rounded-xl border transition-all text-left shadow-2xs shrink-0 cursor-pointer hover:scale-[1.02]"
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
                            {{ businessDateFormatted || businessDate }} <span v-if="businessDay" class="text-indigo-600 dark:text-cyan-400 font-semibold">({{ businessDay }})</span>
                        </span>
                    </div>
                </button>

                <!-- Cashier Shift Badge & Dropdown -->
                <div class="relative">
                    <button
                        v-if="activeShift"
                        type="button"
                        @click="isShiftDropdownOpen = !isShiftDropdownOpen"
                        class="flex items-center space-x-2 px-3 py-1.5 rounded-xl bg-emerald-50 hover:bg-emerald-100 dark:bg-emerald-950/60 dark:hover:bg-emerald-900/60 border border-emerald-300 dark:border-emerald-800 text-emerald-900 dark:text-emerald-200 shadow-2xs transition-all text-left cursor-pointer"
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
                        class="flex items-center space-x-2 px-3.5 py-1.5 rounded-xl bg-amber-50 hover:bg-amber-100 dark:bg-amber-950/60 dark:hover:bg-amber-900/60 border border-amber-300 dark:border-amber-700 text-amber-800 dark:text-amber-200 shadow-2xs transition-all font-bold text-xs animate-pulse cursor-pointer"
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
                            class="w-full text-left px-3 py-2 text-xs font-bold text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-700 flex items-center space-x-2 cursor-pointer"
                        >
                            <span>🧾</span>
                            <span>View / Print X-Report</span>
                        </button>
                        <button
                            type="button"
                            @click="isShiftDropdownOpen = false; emit('openCloseShiftModal')"
                            class="w-full text-left px-3 py-2 text-xs font-bold text-rose-600 dark:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-950/40 flex items-center space-x-2 cursor-pointer"
                        >
                            <span>🛑</span>
                            <span>End Shift & Reconcile</span>
                        </button>
                        <Link
                            :href="route('pos.work-periods')"
                            class="w-full text-left px-3 py-2 text-xs font-bold text-indigo-600 dark:text-cyan-400 hover:bg-indigo-50 dark:hover:bg-slate-700 flex items-center space-x-2 border-t border-slate-100 dark:border-slate-700 cursor-pointer"
                        >
                            <span>📊</span>
                            <span>Day & Shift Records</span>
                        </Link>
                    </div>
                </div>
            </div>

            <!-- Section 3 (Right): Queues, Utility & Mobile Drawer Toggle -->
            <div class="flex items-center space-x-1.5 sm:space-x-2.5">
                <!-- Submitted Orders Button -->
                <button
                    type="button"
                    @click="emit('openSubmittedOrdersModal')"
                    class="relative flex items-center space-x-1 px-2 sm:px-3 py-1.5 text-xs font-bold rounded-lg bg-amber-50 hover:bg-amber-100 dark:bg-amber-950/60 dark:hover:bg-amber-900/60 border border-amber-200 dark:border-amber-800 text-amber-800 dark:text-amber-300 transition-all shadow-xs cursor-pointer"
                    title="Active Submitted Orders"
                >
                    <svg class="w-4 h-4 text-amber-600 dark:text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                    </svg>
                    <span class="hidden sm:inline">Submitted</span>
                    <span
                        v-if="submittedOrdersCount > 0"
                        class="px-1.5 py-0.2 bg-amber-600 text-white rounded-full text-[10px] font-black"
                    >
                        {{ submittedOrdersCount }}
                    </span>
                </button>

                <!-- Settled Orders Button (Desktop Only) -->
                <button
                    type="button"
                    @click="emit('openSettledOrdersModal')"
                    class="hidden lg:flex relative items-center space-x-1.5 px-3 py-1.5 text-xs font-bold rounded-lg bg-emerald-50 hover:bg-emerald-100 dark:bg-emerald-950/60 dark:hover:bg-emerald-900/60 border border-emerald-200 dark:border-emerald-800 text-emerald-800 dark:text-emerald-300 transition-all shadow-xs cursor-pointer"
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
                    class="relative flex items-center space-x-1 px-2 sm:px-3 py-1.5 text-xs font-bold rounded-lg bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-200 transition-all cursor-pointer"
                    title="Active & Parked Orders"
                >
                    <svg class="w-4 h-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span class="hidden sm:inline">Held</span>
                    <span
                        v-if="heldOrdersCount > 0"
                        class="px-1.5 py-0.2 bg-rose-500 text-white rounded-full text-[10px] font-black"
                    >
                        {{ heldOrdersCount }}
                    </span>
                </button>

                <!-- Desktop Theme Switcher -->
                <button
                    type="button"
                    @click="toggleTheme"
                    class="hidden lg:flex p-2 text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors cursor-pointer"
                    :title="isDark ? 'Switch to Light Mode' : 'Switch to Dark Mode'"
                >
                    <!-- Sun Icon (for dark mode) -->
                    <svg v-if="isDark" xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-[#06b6d4] transition-transform duration-300 hover:rotate-45" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
                    </svg>
                    <!-- Moon Icon (for light mode) -->
                    <svg v-else xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-slate-600 transition-transform duration-300 hover:-rotate-12" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                    </svg>
                </button>

                <!-- Desktop Fullscreen Toggle -->
                <button
                    type="button"
                    @click="toggleFullscreen"
                    class="hidden lg:flex p-2 text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors cursor-pointer"
                    title="Toggle Fullscreen"
                >
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4" />
                    </svg>
                </button>

                <!-- Desktop Cashier Pill -->
                <div class="hidden lg:flex items-center space-x-2 pl-2 border-l border-slate-200 dark:border-slate-800">
                    <div class="w-8 h-8 rounded-full bg-indigo-100 dark:bg-cyan-950 text-indigo-700 dark:text-cyan-400 flex items-center justify-center font-bold text-xs uppercase border border-indigo-200 dark:border-cyan-800">
                        {{ user?.name ? user.name.charAt(0) : 'U' }}
                    </div>
                    <div class="text-left">
                        <p class="text-xs font-bold text-slate-800 dark:text-slate-200 leading-tight">{{ user?.name }}</p>
                        <p class="text-[10px] text-slate-400">Cashier</p>
                    </div>
                </div>

                <!-- Mobile Drawer Menu Button (Hamburger) -->
                <button
                    type="button"
                    @click="isMobileDrawerOpen = !isMobileDrawerOpen"
                    class="lg:hidden p-2 text-slate-700 dark:text-slate-200 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors cursor-pointer"
                    :title="isMobileDrawerOpen ? 'Close Menu' : 'Open Menu & Operations'"
                >
                    <svg v-if="!isMobileDrawerOpen" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                    <svg v-else class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>

        <!-- Mobile Drawer Overlay & Panel (Shown on Mobile when isMobileDrawerOpen is true) -->
        <div
            v-if="isMobileDrawerOpen"
            class="lg:hidden fixed inset-0 top-14 bg-slate-900/50 backdrop-blur-xs z-40 flex flex-col justify-start"
            @click.self="isMobileDrawerOpen = false"
        >
            <div class="bg-white dark:bg-slate-900 border-b border-slate-200 dark:border-slate-800 p-4 shadow-2xl max-h-[85vh] overflow-y-auto space-y-4 animate-fade-in">
                <!-- User Profile & Quick Actions Row -->
                <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800">
                    <div class="flex items-center space-x-2.5">
                        <div class="w-9 h-9 rounded-full bg-indigo-100 dark:bg-cyan-950 text-indigo-700 dark:text-cyan-400 flex items-center justify-center font-bold text-sm uppercase border border-indigo-200 dark:border-cyan-800">
                            {{ user?.name ? user.name.charAt(0) : 'U' }}
                        </div>
                        <div class="text-left">
                            <p class="text-xs font-black text-slate-800 dark:text-slate-100">{{ user?.name }}</p>
                            <p class="text-[10px] text-slate-400">Cashier • {{ currentBranchName }}</p>
                        </div>
                    </div>

                    <div class="flex items-center space-x-2">
                        <!-- Theme Toggle in Mobile Drawer -->
                        <button
                            type="button"
                            @click="toggleTheme"
                            class="p-2 text-slate-600 dark:text-slate-300 rounded-lg bg-slate-100 dark:bg-slate-800 cursor-pointer"
                        >
                            <svg v-if="isDark" class="w-4 h-4 text-[#06b6d4]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
                            </svg>
                            <svg v-else class="w-4 h-4 text-slate-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                            </svg>
                        </button>

                        <!-- Fullscreen Toggle in Mobile Drawer -->
                        <button
                            type="button"
                            @click="toggleFullscreen"
                            class="p-2 text-slate-600 dark:text-slate-300 rounded-lg bg-slate-100 dark:bg-slate-800 cursor-pointer"
                        >
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4" />
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Selectors Section in Mobile Drawer -->
                <div class="space-y-2.5">
                    <label class="text-[11px] font-black uppercase tracking-wider text-slate-400">Branch & Menu</label>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                        <div>
                            <span class="text-[10px] font-bold text-slate-500 mb-1 block">Active Branch</span>
                            <SelectInput
                                :modelValue="currentBranchId"
                                @update:modelValue="emit('update:currentBranchId', Number($event)); isMobileDrawerOpen = false;"
                                :options="branches"
                                valueKey="id"
                                labelKey="name"
                                placeholder="Select Branch"
                                size="sm"
                            />
                        </div>
                        <div>
                            <span class="text-[10px] font-bold text-slate-500 mb-1 block">Active Menu</span>
                            <SelectInput
                                :modelValue="currentMenuId"
                                @update:modelValue="emit('update:currentMenuId', Number($event)); isMobileDrawerOpen = false;"
                                :options="menus"
                                valueKey="id"
                                labelKey="name"
                                placeholder="Select Menu"
                                size="sm"
                            />
                        </div>
                    </div>
                </div>

                <!-- Business Day Operations Section -->
                <div class="space-y-2 pt-2 border-t border-slate-100 dark:border-slate-800">
                    <label class="text-[11px] font-black uppercase tracking-wider text-slate-400">Business Day</label>
                    <div class="p-3 rounded-xl border flex items-center justify-between"
                        :class="activeBusinessDay 
                            ? 'bg-slate-50 dark:bg-slate-800/80 border-slate-200 dark:border-slate-700' 
                            : 'bg-rose-50 dark:bg-rose-950/40 border-rose-200 dark:border-rose-800'"
                    >
                        <div>
                            <div class="flex items-center space-x-1.5">
                                <span class="w-2 h-2 rounded-full" :class="activeBusinessDay ? 'bg-emerald-500' : 'bg-rose-500'"></span>
                                <span class="text-xs font-black text-slate-800 dark:text-slate-100">
                                    {{ activeBusinessDay ? 'Business Day Open' : 'Business Day Closed' }}
                                </span>
                            </div>
                            <p class="text-[11px] text-slate-500 mt-0.5">
                                {{ businessDateFormatted || businessDate }} {{ businessDay ? `(${businessDay})` : '' }}
                            </p>
                        </div>

                        <button
                            type="button"
                            @click="isMobileDrawerOpen = false; activeBusinessDay ? emit('openCloseBusinessDayModal') : emit('openOpenBusinessDayModal')"
                            class="px-3 py-1.5 text-xs font-black rounded-lg cursor-pointer"
                            :class="activeBusinessDay 
                                ? 'bg-slate-200 hover:bg-slate-300 dark:bg-slate-700 dark:hover:bg-slate-600 text-slate-800 dark:text-slate-200' 
                                : 'bg-rose-600 text-white'"
                        >
                            {{ activeBusinessDay ? 'Day End' : 'Open Day' }}
                        </button>
                    </div>
                </div>

                <!-- Cashier Shift Operations Section -->
                <div class="space-y-2 pt-2 border-t border-slate-100 dark:border-slate-800">
                    <label class="text-[11px] font-black uppercase tracking-wider text-slate-400">Cashier Shift</label>
                    <div v-if="activeShift" class="p-3 rounded-xl bg-emerald-50/70 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800 space-y-2.5">
                        <div class="flex items-center justify-between">
                            <div>
                                <span class="text-xs font-black text-emerald-800 dark:text-emerald-300">Shift {{ activeShift.shift_number }}</span>
                                <p class="text-[10px] text-emerald-600 dark:text-emerald-400">Float: Rs. {{ Number(activeShift.opening_float || 0).toLocaleString() }}</p>
                            </div>
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-black bg-emerald-100 dark:bg-emerald-900/80 text-emerald-700 dark:text-emerald-300">Active</span>
                        </div>
                        <div class="grid grid-cols-2 gap-2 pt-1">
                            <button
                                type="button"
                                @click="isMobileDrawerOpen = false; emit('openShiftSummaryModal')"
                                class="py-2 px-2.5 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg text-xs font-bold text-slate-700 dark:text-slate-200 text-center cursor-pointer"
                            >
                                🧾 X-Report
                            </button>
                            <button
                                type="button"
                                @click="isMobileDrawerOpen = false; emit('openCloseShiftModal')"
                                class="py-2 px-2.5 bg-rose-50 hover:bg-rose-100 dark:bg-rose-950/60 border border-rose-200 dark:border-rose-800 rounded-lg text-xs font-bold text-rose-700 dark:text-rose-300 text-center cursor-pointer"
                            >
                                🛑 End Shift
                            </button>
                        </div>
                    </div>
                    <div v-else class="p-3 rounded-xl bg-amber-50 dark:bg-amber-950/40 border border-amber-200 dark:border-amber-800 flex items-center justify-between">
                        <div>
                            <span class="text-xs font-bold text-amber-800 dark:text-amber-300">No Shift Active</span>
                            <p class="text-[10px] text-amber-600 dark:text-amber-400">Start a shift to punch orders</p>
                        </div>
                        <button
                            type="button"
                            @click="isMobileDrawerOpen = false; emit('openStartShiftModal')"
                            class="px-3 py-1.5 bg-amber-600 hover:bg-amber-700 text-white rounded-lg text-xs font-black cursor-pointer"
                        >
                            Start Shift
                        </button>
                    </div>
                </div>

                <!-- Navigation & Archive Links -->
                <div class="space-y-2 pt-2 border-t border-slate-100 dark:border-slate-800">
                    <label class="text-[11px] font-black uppercase tracking-wider text-slate-400">Archives & Records</label>
                    <div class="grid grid-cols-2 gap-2">
                        <button
                            type="button"
                            @click="isMobileDrawerOpen = false; emit('openSettledOrdersModal')"
                            class="py-2.5 px-3 bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800 rounded-xl text-xs font-bold text-emerald-800 dark:text-emerald-300 flex items-center justify-center space-x-1.5 cursor-pointer"
                        >
                            <span>✅</span>
                            <span>Settled Orders</span>
                        </button>

                        <Link
                            :href="route('pos.work-periods')"
                            class="py-2.5 px-3 bg-indigo-50 dark:bg-cyan-950/40 border border-indigo-200 dark:border-cyan-900 rounded-xl text-xs font-bold text-indigo-700 dark:text-cyan-300 flex items-center justify-center space-x-1.5 cursor-pointer"
                        >
                            <span>📊</span>
                            <span>Shift Monitor</span>
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </header>
</template>
