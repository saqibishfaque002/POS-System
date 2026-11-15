<script setup>
import { ref } from 'vue';

const props = defineProps({
    isOpen: Boolean,
    orders: {
        type: Array,
        default: () => []
    },
});

const emit = defineEmits(['close', 'resumeOrder', 'voidOrder']);

const voidReason = ref('');
const voidingOrderId = ref(null);

const startVoid = (orderId) => {
    voidingOrderId.value = orderId;
    voidReason.value = '';
};

const confirmVoid = (orderId) => {
    if (!voidReason.value.trim()) return;
    emit('voidOrder', { orderId, reason: voidReason.value.trim() });
    voidingOrderId.value = null;
};

const formatPrice = (val) => {
    return Number(val || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
};
</script>

<template>
    <div v-if="isOpen" class="fixed inset-0 z-50 flex items-center justify-center p-2.5 sm:p-4 bg-slate-900/60 backdrop-blur-xs select-none">
        <div class="bg-white dark:bg-slate-900 w-full max-w-2xl rounded-2xl shadow-2xl border border-slate-200 dark:border-slate-800 overflow-hidden flex flex-col max-h-[90vh] animate-in fade-in zoom-in-95 duration-150">
            <!-- Modal Header -->
            <div class="px-4 sm:px-5 py-3 sm:py-3.5 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between bg-slate-50/70 dark:bg-slate-800/40">
                <div class="flex items-center space-x-2">
                    <div class="w-8 h-8 rounded-lg bg-amber-50 dark:bg-amber-950/60 text-amber-600 dark:text-amber-400 flex items-center justify-center font-bold shrink-0">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-xs sm:text-sm font-black text-slate-900 dark:text-white">
                            Active & Parked Orders ({{ orders.length }})
                        </h3>
                        <p class="text-[10px] sm:text-[11px] text-slate-500 dark:text-slate-400">
                            Resume a parked order or cancel an active ticket
                        </p>
                    </div>
                </div>
                <button
                    type="button"
                    @click="emit('close')"
                    class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 p-1 rounded-lg text-sm cursor-pointer"
                >
                    ✕
                </button>
            </div>

            <!-- Orders List -->
            <div class="p-3 sm:p-5 overflow-y-auto flex-1 space-y-3 bg-slate-50 dark:bg-slate-950">
                <div v-if="orders.length === 0" class="py-12 text-center text-slate-400 text-xs">
                    No active or held orders at this branch right now.
                </div>

                <div
                    v-for="o in orders"
                    :key="o.id"
                    class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-4 shadow-xs hover:border-indigo-400 dark:hover:border-cyan-500 transition-all"
                >
                    <div class="flex items-start justify-between">
                        <div>
                            <div class="flex items-center space-x-2">
                                <span class="text-xs font-black text-slate-900 dark:text-white">
                                    {{ o.order_number }}
                                </span>
                                <span v-if="o.token_number" class="text-[10px] font-black px-2 py-0.5 rounded-full bg-indigo-50 dark:bg-cyan-950/60 text-indigo-700 dark:text-cyan-400 border border-indigo-200 dark:border-cyan-800">
                                    {{ o.token_number }}
                                </span>
                                <span class="text-[10px] font-bold uppercase px-2 py-0.5 rounded bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300">
                                    {{ o.order_type?.name }}
                                </span>
                                <span v-if="o.table" class="text-[10px] font-bold text-amber-700 dark:text-amber-400 bg-amber-50 dark:bg-amber-950/60 px-2 py-0.5 rounded">
                                    Table: {{ o.table.name }}
                                </span>
                            </div>

                            <!-- Customer info if any -->
                            <p v-if="o.customer" class="text-[11px] text-slate-500 dark:text-slate-400 mt-1">
                                Customer: <span class="font-bold text-slate-700 dark:text-slate-300">{{ o.customer.name }}</span> ({{ o.customer.phone }})
                            </p>

                            <!-- Items Summary -->
                            <div class="mt-2 flex flex-wrap gap-1 text-[11px] text-slate-600 dark:text-slate-400">
                                <span v-for="item in o.items" :key="item.id" class="bg-slate-50 dark:bg-slate-800 px-2 py-0.5 rounded border border-slate-200 dark:border-slate-700">
                                    {{ item.quantity }}x {{ item.food_item?.name }}
                                </span>
                            </div>
                        </div>

                        <!-- Amount & Action -->
                        <div class="text-right">
                            <span class="text-sm font-black text-indigo-600 dark:text-cyan-400">
                                Rs. {{ formatPrice(o.grand_total) }}
                            </span>
                            <div class="mt-2 flex items-center space-x-1.5">
                                <button
                                    type="button"
                                    @click="emit('resumeOrder', o)"
                                    class="py-1 px-3 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs shadow-xs transition-colors"
                                >
                                    Resume
                                </button>
                                <button
                                    type="button"
                                    @click="startVoid(o.id)"
                                    class="py-1 px-2.5 rounded-lg bg-rose-50 hover:bg-rose-100 text-rose-600 font-bold text-xs border border-rose-200 transition-colors"
                                >
                                    Void
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Void Reason Input Row -->
                    <div v-if="voidingOrderId === o.id" class="mt-3 pt-3 border-t border-rose-100 dark:border-rose-950/40 flex items-center space-x-2">
                        <input
                            type="text"
                            v-model="voidReason"
                            placeholder="Reason for cancellation (e.g. Customer changed mind)..."
                            class="flex-1 text-xs bg-rose-50/50 dark:bg-rose-950/30 border border-rose-300 dark:border-rose-800 rounded-lg px-2.5 py-1.5 text-slate-900 dark:text-white"
                        />
                        <button
                            type="button"
                            @click="confirmVoid(o.id)"
                            class="py-1.5 px-3 bg-rose-600 text-white rounded-lg text-xs font-bold shadow-xs hover:bg-rose-700"
                        >
                            Confirm Void
                        </button>
                        <button
                            type="button"
                            @click="voidingOrderId = null"
                            class="py-1.5 px-2 text-slate-400 hover:text-slate-600 text-xs"
                        >
                            Cancel
                        </button>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div class="px-5 py-3 border-t border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-right">
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
