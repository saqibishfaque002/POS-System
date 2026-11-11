<script setup>
import { ref, watch } from 'vue';
import axios from 'axios';

const props = defineProps({
    isOpen: Boolean,
    branchId: [Number, String],
    branchName: String,
    activeBusinessDay: Object,
});

const emit = defineEmits(['close', 'dayClosed']);

const notes = ref('');
const forceClose = ref(false);
const submitting = ref(false);
const errorMessage = ref('');
const zReport = ref(null);
const warningMessage = ref('');

watch(() => props.isOpen, (val) => {
    if (val) {
        notes.value = '';
        forceClose.value = false;
        errorMessage.value = '';
        zReport.value = null;
        warningMessage.value = '';
    }
});

const formatPrice = (val) => {
    return Number(val || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
};

const handleCloseDay = async () => {
    if (!props.branchId) return;

    submitting.value = true;
    errorMessage.value = '';

    try {
        const response = await axios.post(route('pos.business-day.close'), {
            branch_id: props.branchId,
            notes: notes.value,
            force: forceClose.value,
        });

        if (response.data.success) {
            zReport.value = response.data.z_report;
            warningMessage.value = response.data.unsettled_orders_warning || '';
            emit('dayClosed', response.data);
        } else {
            errorMessage.value = response.data.message || 'Failed to close business day.';
        }
    } catch (err) {
        errorMessage.value = err.response?.data?.message || 'Error occurred while closing business day.';
    } finally {
        submitting.value = false;
    }
};

const printZReport = () => {
    window.print();
};
</script>

<template>
    <div
        v-if="isOpen"
        class="fixed inset-0 z-50 flex items-center justify-center p-2.5 sm:p-4 bg-slate-900/60 backdrop-blur-sm animate-fade-in"
    >
        <div class="bg-white dark:bg-slate-900 rounded-2xl shadow-2xl border border-slate-200 dark:border-slate-800 w-full max-w-lg overflow-hidden flex flex-col max-h-[90vh]">
            <!-- Header -->
            <div class="px-5 py-4 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between bg-rose-50 dark:bg-rose-950/40 shrink-0">
                <div class="flex items-center space-x-2.5">
                    <div class="w-8 h-8 rounded-xl bg-rose-100 dark:bg-rose-900/60 text-rose-700 dark:text-rose-300 flex items-center justify-center text-sm font-black">
                        🏁
                    </div>
                    <div>
                        <h3 class="text-sm font-black text-slate-800 dark:text-slate-100">
                            {{ zReport ? 'Z-Report (Day End Summary)' : 'Close Business Day (Day End)' }}
                        </h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400">
                            {{ branchName }} &bull; {{ activeBusinessDay?.business_date_formatted || activeBusinessDay?.business_date }}
                        </p>
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

            <!-- Body -->
            <div class="p-5 space-y-4 overflow-y-auto">
                <div
                    v-if="errorMessage"
                    class="p-3 bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-800 rounded-xl text-xs text-rose-700 dark:text-rose-300 flex items-start space-x-2"
                >
                    <span>⚠️</span>
                    <div class="space-y-1">
                        <div class="font-bold">{{ errorMessage }}</div>
                        <div v-if="errorMessage.includes('Active shift')">
                            Tick <strong>"Force close remaining cashier shifts"</strong> below if cashiers have departed.
                        </div>
                    </div>
                </div>

                <div
                    v-if="warningMessage"
                    class="p-3 bg-amber-50 dark:bg-amber-950/40 border border-amber-200 dark:border-amber-800 rounded-xl text-xs text-amber-800 dark:text-amber-300"
                >
                    {{ warningMessage }}
                </div>

                <!-- If Z-Report Generated (Closed View) -->
                <div v-if="zReport" class="space-y-4 print:p-0">
                    <div class="p-4 bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800 rounded-xl text-xs text-emerald-800 dark:text-emerald-300 flex items-center space-x-2">
                        <span class="text-lg">✅</span>
                        <div>
                            <strong>Business Day Closed Successfully!</strong> Z-Report generated for audit and accounting records.
                        </div>
                    </div>

                    <!-- Z-Report Breakdown Card -->
                    <div class="bg-slate-50 dark:bg-slate-800/80 rounded-xl p-4 border border-slate-200 dark:border-slate-700 space-y-3 font-mono text-xs text-slate-800 dark:text-slate-200">
                        <div class="text-center pb-2 border-b border-dashed border-slate-300 dark:border-slate-700">
                            <div class="font-black text-sm uppercase">{{ branchName }}</div>
                            <div class="text-[11px] text-slate-500">Z-REPORT (DAY END AUDIT)</div>
                            <div class="text-[10px] text-slate-400">Date: {{ zReport.business_date }} &bull; Closed: {{ zReport.closed_at }}</div>
                        </div>

                        <!-- Shifts & Orders -->
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
                            <div class="flex justify-between text-indigo-600 dark:text-cyan-400 font-black">
                                <span>GROSS SALES REVENUE:</span>
                                <span>Rs. {{ formatPrice(zReport.gross_sales) }}</span>
                            </div>
                        </div>

                        <!-- Payments By Type -->
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

                        <!-- Cash Drawer Reconciliation -->
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
                                <span>NET VARIANCE:</span>
                                <span>
                                    {{ zReport.total_variance >= 0 ? '+' : '' }}Rs. {{ formatPrice(zReport.total_variance) }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- If Day Not Yet Closed (Confirm Form) -->
                <div v-else class="space-y-4">
                    <div class="p-3 bg-amber-50 dark:bg-amber-950/30 border border-amber-200 dark:border-amber-800 rounded-xl text-xs text-amber-800 dark:text-amber-300 flex items-start space-x-2">
                        <span class="text-base leading-none">⚠️</span>
                        <div>
                            Closing the business day finishes all cashier operations for <strong>{{ activeBusinessDay?.business_date_formatted || activeBusinessDay?.business_date }}</strong>. Once closed, no more orders can be punched under this accounting day.
                        </div>
                    </div>

                    <!-- Force Close Checkbox (Rule 5: Inside main form grid) -->
                    <div class="p-3 rounded-xl bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 flex items-center space-x-3">
                        <input
                            type="checkbox"
                            id="forceCloseShifts"
                            v-model="forceClose"
                            class="rounded border-slate-300 text-rose-600 focus:ring-rose-500 w-4 h-4 cursor-pointer"
                        />
                        <label for="forceCloseShifts" class="text-xs font-bold text-slate-700 dark:text-slate-300 cursor-pointer select-none">
                            Force close all remaining open cashier shifts automatically
                        </label>
                    </div>

                    <!-- Day End Notes -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                            Closing Remarks / Manager Handover Notes
                        </label>
                        <textarea
                            v-model="notes"
                            rows="2"
                            placeholder="e.g. End of day audit completed, safe deposit made..."
                            class="w-full text-xs bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl p-2.5 text-slate-800 dark:text-slate-100 placeholder-slate-400 focus:ring-2 focus:ring-rose-500"
                        ></textarea>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div class="px-5 py-3 border-t border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-900 flex items-center justify-between shrink-0">
                <button
                    type="button"
                    @click="emit('close')"
                    class="text-xs font-bold text-slate-600 hover:text-slate-800 dark:text-slate-400 dark:hover:text-slate-200 px-3 py-2"
                >
                    {{ zReport ? 'Close' : 'Cancel' }}
                </button>

                <div class="flex items-center space-x-2">
                    <button
                        v-if="zReport"
                        type="button"
                        @click="printZReport"
                        class="px-4 py-2 bg-slate-800 hover:bg-slate-900 text-white font-bold text-xs rounded-xl shadow-xs transition-all flex items-center space-x-1.5"
                    >
                        <span>🖨️ Print Z-Report</span>
                    </button>

                    <button
                        v-if="!zReport"
                        type="button"
                        @click="handleCloseDay"
                        :disabled="submitting"
                        class="px-5 py-2.5 bg-rose-600 hover:bg-rose-700 text-white font-bold text-xs rounded-xl shadow-md active:scale-95 transition-all disabled:opacity-50 flex items-center space-x-1.5"
                    >
                        <span v-if="submitting" class="animate-spin mr-1">⌛</span>
                        <span>Confirm & Close Business Day</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
