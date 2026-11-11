<script setup>
const props = defineProps({
    isOpen: Boolean,
    day: Object,
    zReport: Object,
});

const emit = defineEmits(['close']);

const formatPrice = (val) => {
    return Number(val || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
};

const handlePrint = () => {
    window.print();
};
</script>

<template>
    <div
        v-if="isOpen"
        class="fixed inset-0 z-50 flex items-center justify-center p-2.5 sm:p-4 bg-slate-900/60 backdrop-blur-sm animate-fade-in"
    >
        <div class="bg-white dark:bg-slate-900 rounded-2xl shadow-2xl border border-slate-200 dark:border-slate-800 w-full max-w-md overflow-hidden flex flex-col max-h-[90vh]">
            <!-- Header -->
            <div class="px-5 py-4 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between bg-slate-50 dark:bg-slate-800/60 shrink-0 print:hidden">
                <div class="flex items-center space-x-2.5">
                    <div class="w-8 h-8 rounded-xl bg-amber-100 dark:bg-amber-950/60 text-amber-700 dark:text-amber-400 flex items-center justify-center text-sm font-black">
                        🏁
                    </div>
                    <div>
                        <h3 class="text-sm font-black text-slate-800 dark:text-slate-100">Business Day Z-Report</h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400">{{ day?.business_date }} &bull; {{ day?.branch_name }}</p>
                    </div>
                </div>
                <button
                    type="button"
                    @click="emit('close')"
                    class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 p-1.5 rounded-lg text-lg leading-none transition-colors"
                >
                    ✕
                </button>
            </div>

            <!-- Body: Thermal Receipt Style -->
            <div class="p-6 overflow-y-auto space-y-4 font-mono text-xs text-slate-800 dark:text-slate-200 bg-white dark:bg-slate-900 print:p-0">
                <div v-if="zReport && day" class="space-y-3">
                    <!-- Receipt Header -->
                    <div class="text-center pb-3 border-b border-dashed border-slate-300 dark:border-slate-700 space-y-0.5">
                        <div class="font-black text-sm uppercase">{{ day.branch_name }}</div>
                        <div class="text-[11px] font-bold text-slate-500">Z-REPORT (DAY AUDIT SUMMARY)</div>
                        <div class="text-[10px] text-slate-400">Business Date: {{ day.business_date }} ({{ day.business_day }})</div>
                    </div>

                    <!-- Day Open/Close Metadata -->
                    <div class="space-y-1 py-1 border-b border-dashed border-slate-300 dark:border-slate-700 text-[11px]">
                        <div class="flex justify-between">
                            <span class="text-slate-500">Status:</span>
                            <span :class="day.status === 'open' ? 'text-emerald-600 font-bold' : 'text-slate-600 font-bold'">
                                {{ day.status?.toUpperCase() }}
                            </span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-500">Opened At:</span>
                            <span>{{ day.opened_at }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-500">Opened By:</span>
                            <span class="font-bold">{{ day.opened_by }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-500">Closed At:</span>
                            <span>{{ day.closed_at || 'STILL OPEN (Active)' }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-500">Closed By:</span>
                            <span class="font-bold">{{ day.closed_by }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-500">Total Duration:</span>
                            <span class="font-black text-indigo-600 dark:text-cyan-400">{{ day.duration }}</span>
                        </div>
                    </div>

                    <!-- Shifts & Orders Summary -->
                    <div class="space-y-1 py-1 border-b border-dashed border-slate-300 dark:border-slate-700">
                        <div class="flex justify-between">
                            <span>Total Cashier Shifts:</span>
                            <span class="font-bold">{{ zReport.shifts_count }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span>Total Orders Punched:</span>
                            <span class="font-bold">{{ zReport.orders_count }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span>Completed / Paid Orders:</span>
                            <span class="font-bold">{{ zReport.completed_orders_count }}</span>
                        </div>
                        <div class="flex justify-between text-indigo-600 dark:text-cyan-400 font-black pt-1">
                            <span>GROSS SALES REVENUE:</span>
                            <span>Rs. {{ formatPrice(zReport.gross_sales) }}</span>
                        </div>
                    </div>

                    <!-- Payments Breakdown -->
                    <div class="space-y-1 py-1 border-b border-dashed border-slate-300 dark:border-slate-700">
                        <div class="text-[10px] font-bold uppercase text-slate-400">Payment Breakdown:</div>
                        <div
                            v-for="pay in zReport.payments_by_type"
                            :key="pay.name"
                            class="flex justify-between"
                        >
                            <span>{{ pay.name }} ({{ pay.count }}):</span>
                            <span class="font-bold">Rs. {{ formatPrice(pay.amount) }}</span>
                        </div>
                        <div class="flex justify-between font-bold pt-1">
                            <span>Total Payments Collected:</span>
                            <span>Rs. {{ formatPrice(zReport.total_payments_collected) }}</span>
                        </div>
                    </div>

                    <!-- Drawer Reconciliation -->
                    <div class="space-y-1 pt-1">
                        <div class="flex justify-between">
                            <span>Total Starting Floats:</span>
                            <span>Rs. {{ formatPrice(zReport.total_opening_floats) }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span>Total Cash Sales:</span>
                            <span>Rs. {{ formatPrice(zReport.cash_collected) }}</span>
                        </div>
                        <div class="flex justify-between font-bold">
                            <span>Total Expected Cash:</span>
                            <span>Rs. {{ formatPrice(zReport.total_expected_cash) }}</span>
                        </div>
                        <div class="flex justify-between font-bold">
                            <span>Total Actual Cash Counted:</span>
                            <span>Rs. {{ formatPrice(zReport.total_actual_cash) }}</span>
                        </div>
                        <div
                            :class="[
                                'flex justify-between font-black text-sm pt-1 border-t border-slate-300 dark:border-slate-700',
                                zReport.total_variance === 0
                                    ? 'text-emerald-600 dark:text-emerald-400'
                                    : zReport.total_variance > 0
                                        ? 'text-blue-600 dark:text-blue-400'
                                        : 'text-rose-600 dark:text-rose-400'
                            ]"
                        >
                            <span>NET TILL VARIANCE:</span>
                            <span>
                                {{ zReport.total_variance >= 0 ? '+' : '' }}Rs. {{ formatPrice(zReport.total_variance) }}
                            </span>
                        </div>
                    </div>

                    <div v-if="day.notes" class="pt-2 text-[10px] text-slate-400 border-t border-dashed border-slate-300 dark:border-slate-700">
                        Notes: {{ day.notes }}
                    </div>
                </div>

                <div v-else class="text-center py-8 text-slate-400">
                    Loading Z-Report summary...
                </div>
            </div>

            <!-- Footer -->
            <div class="px-5 py-3 border-t border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-800/60 flex items-center justify-between shrink-0 print:hidden">
                <button
                    type="button"
                    @click="emit('close')"
                    class="text-xs font-bold text-slate-600 hover:text-slate-800 dark:text-slate-400 dark:hover:text-slate-200 cursor-pointer"
                >
                    Close
                </button>
                <button
                    type="button"
                    @click="handlePrint"
                    class="pos-btn-primary !py-2 !px-4 text-xs font-bold flex items-center space-x-1.5"
                >
                    <span>🖨️</span>
                    <span>Print Thermal Z-Report</span>
                </button>
            </div>
        </div>
    </div>
</template>
