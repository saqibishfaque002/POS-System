<script setup>
const props = defineProps({
    isOpen: Boolean,
    summary: Object,
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
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm animate-fade-in"
    >
        <div class="bg-white dark:bg-slate-900 rounded-2xl shadow-2xl border border-slate-200 dark:border-slate-800 w-full max-w-md overflow-hidden flex flex-col max-h-[90vh]">
            <!-- Header -->
            <div class="px-5 py-4 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between bg-slate-50 dark:bg-slate-800/60 shrink-0 print:hidden">
                <div class="flex items-center space-x-2.5">
                    <div class="w-8 h-8 rounded-xl bg-indigo-100 dark:bg-cyan-950 text-indigo-700 dark:text-cyan-400 flex items-center justify-center text-sm font-black">
                        🧾
                    </div>
                    <div>
                        <h3 class="text-sm font-black text-slate-800 dark:text-slate-100">Shift X-Report</h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400">{{ summary?.shift_number }}</p>
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
                <div v-if="summary" class="space-y-3">
                    <!-- Receipt Header -->
                    <div class="text-center pb-3 border-b border-dashed border-slate-300 dark:border-slate-700 space-y-0.5">
                        <div class="font-black text-sm uppercase">{{ summary.branch_name }}</div>
                        <div class="text-[11px] font-bold text-slate-500">SHIFT X-REPORT (INTERIM AUDIT)</div>
                        <div class="text-[10px] text-slate-400">Shift #: {{ summary.shift_number }}</div>
                        <div class="text-[10px] text-slate-400">Business Date: {{ summary.business_date }}</div>
                    </div>

                    <!-- Shift Metadata -->
                    <div class="space-y-1 py-1 border-b border-dashed border-slate-300 dark:border-slate-700 text-[11px]">
                        <div class="flex justify-between">
                            <span class="text-slate-500">Cashier:</span>
                            <span class="font-bold">{{ summary.cashier_name }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-500">Opened At:</span>
                            <span>{{ summary.opened_at }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-500">Closed At:</span>
                            <span>{{ summary.closed_at || 'STILL OPEN (Active)' }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-500">Status:</span>
                            <span :class="summary.status === 'open' ? 'text-emerald-600 font-bold' : 'text-slate-600 font-bold'">
                                {{ summary.status?.toUpperCase() }}
                            </span>
                        </div>
                    </div>

                    <!-- Sales Metrics -->
                    <div class="space-y-1 py-1 border-b border-dashed border-slate-300 dark:border-slate-700">
                        <div class="flex justify-between">
                            <span>Total Tickets Punched:</span>
                            <span class="font-bold">{{ summary.orders_count }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span>Completed Orders:</span>
                            <span class="font-bold">{{ summary.completed_orders_count }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span>Unsettled / Active:</span>
                            <span>{{ summary.submitted_orders_count }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span>Voided Orders:</span>
                            <span>{{ summary.voided_orders_count }}</span>
                        </div>
                        <div class="flex justify-between pt-1">
                            <span>Subtotal:</span>
                            <span>Rs. {{ formatPrice(summary.subtotal) }}</span>
                        </div>
                        <div v-if="summary.discount_amount > 0" class="flex justify-between text-rose-500">
                            <span>Discounts Given:</span>
                            <span>-Rs. {{ formatPrice(summary.discount_amount) }}</span>
                        </div>
                        <div v-if="summary.tax_amount > 0" class="flex justify-between">
                            <span>Taxes:</span>
                            <span>Rs. {{ formatPrice(summary.tax_amount) }}</span>
                        </div>
                        <div v-if="summary.delivery_charges > 0" class="flex justify-between">
                            <span>Delivery Charges:</span>
                            <span>Rs. {{ formatPrice(summary.delivery_charges) }}</span>
                        </div>
                        <div class="flex justify-between font-black text-sm pt-1 border-t border-slate-200 dark:border-slate-700 text-indigo-600 dark:text-cyan-400">
                            <span>GROSS SALES:</span>
                            <span>Rs. {{ formatPrice(summary.gross_sales) }}</span>
                        </div>
                    </div>

                    <!-- Payment Tenders -->
                    <div class="space-y-1 py-1 border-b border-dashed border-slate-300 dark:border-slate-700">
                        <div class="text-[10px] font-bold uppercase text-slate-400">Payments Collected:</div>
                        <div
                            v-for="pay in summary.payments_by_type"
                            :key="pay.name"
                            class="flex justify-between"
                        >
                            <span>{{ pay.name }} ({{ pay.count }}):</span>
                            <span class="font-bold">Rs. {{ formatPrice(pay.amount) }}</span>
                        </div>
                        <div class="flex justify-between font-bold pt-1">
                            <span>Total Payments:</span>
                            <span>Rs. {{ formatPrice(summary.total_payments_collected) }}</span>
                        </div>
                    </div>

                    <!-- Drawer Till Reconciliation -->
                    <div class="space-y-1 pt-1">
                        <div class="text-[10px] font-bold uppercase text-slate-400">Cash Drawer Reconciliation:</div>
                        <div class="flex justify-between">
                            <span>Opening Cash Float:</span>
                            <span>Rs. {{ formatPrice(summary.opening_float) }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span>Cash Sales Collected:</span>
                            <span>Rs. {{ formatPrice(summary.cash_collected) }}</span>
                        </div>
                        <div class="flex justify-between font-bold">
                            <span>Expected Cash in Till:</span>
                            <span>Rs. {{ formatPrice(summary.expected_cash) }}</span>
                        </div>
                        <div v-if="summary.actual_cash !== null" class="flex justify-between font-bold">
                            <span>Counted Cash Actual:</span>
                            <span>Rs. {{ formatPrice(summary.actual_cash) }}</span>
                        </div>
                        <div
                            v-if="summary.actual_cash !== null"
                            :class="[
                                'flex justify-between font-black text-sm pt-1 border-t border-slate-300 dark:border-slate-700',
                                summary.cash_variance === 0
                                    ? 'text-emerald-600 dark:text-emerald-400'
                                    : summary.cash_variance > 0
                                        ? 'text-blue-600 dark:text-blue-400'
                                        : 'text-rose-600 dark:text-rose-400'
                            ]"
                        >
                            <span>VARIANCE:</span>
                            <span>
                                {{ summary.cash_variance >= 0 ? '+' : '' }}Rs. {{ formatPrice(summary.cash_variance) }}
                            </span>
                        </div>
                    </div>

                    <div class="text-center pt-3 text-[10px] text-slate-400 border-t border-dashed border-slate-300 dark:border-slate-700">
                        *** END OF X-REPORT ***
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div class="px-5 py-3 border-t border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-900 flex items-center justify-between shrink-0 print:hidden">
                <button
                    type="button"
                    @click="emit('close')"
                    class="text-xs font-bold text-slate-600 hover:text-slate-800 dark:text-slate-400 dark:hover:text-slate-200 px-3 py-2"
                >
                    Close
                </button>
                <button
                    type="button"
                    @click="handlePrint"
                    class="px-4 py-2 bg-slate-800 hover:bg-slate-900 text-white font-bold text-xs rounded-xl shadow-xs transition-all flex items-center space-x-1.5"
                >
                    <span>🖨️ Print Report</span>
                </button>
            </div>
        </div>
    </div>
</template>
