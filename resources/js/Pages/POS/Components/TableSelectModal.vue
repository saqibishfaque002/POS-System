<script setup>
import { ref, computed } from 'vue';

const props = defineProps({
    isOpen: Boolean,
    floors: {
        type: Array,
        default: () => []
    },
    selectedTableId: [Number, String],
});

const emit = defineEmits(['close', 'selectTable', 'resumeTableOrder']);

const activeFloorId = ref(null);

// Initialize active floor when floors load or change
const currentFloor = computed(() => {
    if (!props.floors || props.floors.length === 0) return null;
    if (!activeFloorId.value) {
        return props.floors[0];
    }
    return props.floors.find(f => f.id == activeFloorId.value) || props.floors[0];
});

const formatPrice = (val) => {
    return Number(val || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
};
</script>

<template>
    <div v-if="isOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs select-none">
        <div class="bg-white dark:bg-slate-900 w-full max-w-2xl rounded-2xl shadow-2xl border border-slate-200 dark:border-slate-800 overflow-hidden flex flex-col max-h-[85vh] animate-in fade-in zoom-in-95 duration-150">
            <!-- Modal Header -->
            <div class="px-5 py-3.5 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between bg-slate-50/70 dark:bg-slate-800/40">
                <div class="flex items-center space-x-2">
                    <div class="w-8 h-8 rounded-lg bg-indigo-50 dark:bg-cyan-950/60 text-indigo-600 dark:text-cyan-400 flex items-center justify-center font-bold">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-sm font-black text-slate-900 dark:text-white">
                            Floor & Table Layout
                        </h3>
                        <p class="text-[11px] text-slate-500 dark:text-slate-400">
                            Select a table for Dine-in or resume an occupied table
                        </p>
                    </div>
                </div>
                <button
                    type="button"
                    @click="emit('close')"
                    class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 p-1 rounded-lg text-sm"
                >
                    ✕
                </button>
            </div>

            <!-- Floor Tabs -->
            <div class="px-5 py-2.5 border-b border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 flex items-center space-x-2 overflow-x-auto">
                <button
                    v-for="floor in floors"
                    :key="floor.id"
                    type="button"
                    @click="activeFloorId = floor.id"
                    :class="[
                        'px-3.5 py-1.5 rounded-lg text-xs font-bold transition-all shrink-0',
                        currentFloor?.id == floor.id
                            ? 'bg-indigo-600 dark:bg-cyan-600 text-white shadow-xs'
                            : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 hover:bg-slate-200'
                    ]"
                >
                    {{ floor.name }} ({{ floor.tables ? floor.tables.length : 0 }})
                </button>
            </div>

            <!-- Tables Grid -->
            <div class="p-5 overflow-y-auto flex-1 bg-slate-50 dark:bg-slate-950">
                <div v-if="!currentFloor || !currentFloor.tables || currentFloor.tables.length === 0" class="h-48 flex flex-col items-center justify-center text-slate-400">
                    <p class="text-xs font-bold">No tables found on this floor</p>
                </div>

                <div v-else class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-3">
                    <div
                        v-for="table in currentFloor.tables"
                        :key="table.id"
                        :class="[
                            'rounded-2xl border p-3 flex flex-col justify-between transition-all relative select-none',
                            table.is_occupied
                                ? 'bg-amber-50/70 dark:bg-amber-950/30 border-amber-300 dark:border-amber-700/60 hover:shadow-md'
                                : selectedTableId == table.id
                                    ? 'bg-indigo-50 dark:bg-cyan-950/40 border-indigo-500 dark:border-cyan-500 ring-2 ring-indigo-500/20'
                                    : 'bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-800 hover:border-emerald-400 hover:shadow-md cursor-pointer'
                        ]"
                    >
                        <!-- Table Card Top -->
                        <div>
                            <div class="flex items-center justify-between mb-1">
                                <span class="text-xs font-black text-slate-900 dark:text-white">
                                    {{ table.name }}
                                </span>
                                <span
                                    :class="[
                                        'text-[9px] font-black uppercase px-1.5 py-0.5 rounded-full',
                                        table.is_occupied
                                            ? 'bg-amber-200 text-amber-900 dark:bg-amber-900/60 dark:text-amber-200'
                                            : 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300'
                                    ]"
                                >
                                    {{ table.is_occupied ? 'Occupied' : 'Free' }}
                                </span>
                            </div>
                            <span class="text-[10px] text-slate-400 font-medium">
                                Capacity: {{ table.capacity }} Seats
                            </span>
                        </div>

                        <!-- Occupied Table Details -->
                        <div v-if="table.is_occupied && table.active_order" class="mt-3 pt-2 border-t border-amber-200/60 dark:border-amber-800/40">
                            <div class="flex items-center justify-between text-[11px] font-bold text-slate-800 dark:text-slate-200">
                                <span>{{ table.active_order.order_number }}</span>
                                <span class="text-amber-700 dark:text-amber-400 font-black">
                                    Rs. {{ formatPrice(table.active_order.grand_total) }}
                                </span>
                            </div>
                            <button
                                type="button"
                                @click="emit('resumeTableOrder', table.active_order.id)"
                                class="mt-2 w-full py-1.5 px-2 bg-amber-600 hover:bg-amber-700 text-white rounded-lg text-[10px] font-bold shadow-xs transition-colors"
                            >
                                Resume / Edit Bill
                            </button>
                        </div>

                        <!-- Free Table Action -->
                        <div v-else class="mt-3 pt-2 border-t border-slate-100 dark:border-slate-800">
                            <button
                                type="button"
                                @click="emit('selectTable', table)"
                                class="w-full py-1.5 px-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-[10px] font-bold shadow-xs transition-colors"
                            >
                                Assign Table
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Footer: Legend -->
            <div class="px-5 py-3 border-t border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 flex items-center justify-between text-xs">
                <div class="flex items-center space-x-4 text-[11px] text-slate-500">
                    <span class="flex items-center space-x-1.5">
                        <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span>
                        <span>Available</span>
                    </span>
                    <span class="flex items-center space-x-1.5">
                        <span class="w-2.5 h-2.5 rounded-full bg-amber-500"></span>
                        <span>Occupied (Running Bill)</span>
                    </span>
                </div>
                <button
                    type="button"
                    @click="emit('close')"
                    class="px-4 py-1.5 text-xs font-bold text-slate-600 dark:text-slate-400 hover:text-slate-800"
                >
                    Close
                </button>
            </div>
        </div>
    </div>
</template>
