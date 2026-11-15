<script setup>
import { ref, watch, computed } from 'vue';
import axios from 'axios';

const props = defineProps({
    isOpen: Boolean,
    shift: Object,
    branchId: [Number, String],
    branchName: String,
});

const emit = defineEmits(['close', 'shiftClosed', 'openXReport']);

const loadingSummary = ref(false);
const summary = ref(null);
const actualCash = ref('');
const notes = ref('');
const submitting = ref(false);
const errorMessage = ref('');
const closedResult = ref(null);

const formatPrice = (val) => {
    return Number(val || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
};

const expectedCash = computed(() => {
    if (!summary.value) return 0;
    return Number(summary.value.expected_cash || 0);
});

const variance = computed(() => {
    if (actualCash.value === '' || isNaN(actualCash.value)) return 0;
    return Number(actualCash.value) - expectedCash.value;
});

const fetchLiveSummary = async () => {
    if (!props.shift?.id) return;
    loadingSummary.value = true;
    errorMessage.value = '';

    try {
        const response = await axios.get(route('pos.shifts.summary'), {
            params: {
                shift_id: props.shift.id,
                branch_id: props.branchId,
            }
        });
        if (response.data.success) {
            summary.value = response.data.summary;
            actualCash.value = summary.value.expected_cash; // default to expected for convenience
        }
    } catch (err) {
        errorMessage.value = 'Failed to load live shift summary.';
    } finally {
        loadingSummary.value = false;
    }
};

watch(() => props.isOpen, (val) => {
    if (val) {
        notes.value = '';
        errorMessage.value = '';
        closedResult.value = null;
        fetchLiveSummary();
    }
});

const handleCloseShift = async () => {
    if (actualCash.value === '' || Number(actualCash.value) < 0) {
        errorMessage.value = 'Please enter a valid counted cash amount.';
        return;
    }

    submitting.value = true;
    errorMessage.value = '';

    try {
        const response = await axios.post(route('pos.shifts.close'), {
            shift_id: props.shift?.id,
            branch_id: props.branchId,
            closing_cash_actual: Number(actualCash.value),
            notes: notes.value,
        });

        if (response.data.success) {
            closedResult.value = response.data;
            emit('shiftClosed', response.data);
        } else {
            errorMessage.value = response.data.message || 'Failed to close shift.';
        }
    } catch (err) {
        errorMessage.value = err.response?.data?.message || 'Error occurred while closing shift.';
    } finally {
        submitting.value = false;
    }
};

const handleViewPrintXReport = () => {
    if (closedResult.value?.x_report) {
        emit('openXReport', closedResult.value.x_report);
    }
};
</script>

<template>
    <div
        v-if="isOpen"
        class="fixed inset-0 z-50 flex items-center justify-center p-2.5 sm:p-4 bg-slate-900/60 backdrop-blur-sm animate-fade-in"
    >
        <div class="bg-white dark:bg-slate-900 rounded-2xl shadow-2xl border border-slate-200 dark:border-slate-800 w-full max-w-lg overflow-hidden flex flex-col max-h-[90vh]">
            <!-- Header -->
            <div class="px-5 py-4 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between bg-amber-50 dark:bg-amber-950/40 shrink-0">
                <div class="flex items-center space-x-2.5">
                    <div class="w-8 h-8 rounded-xl bg-amber-100 dark:bg-amber-900/60 text-amber-700 dark:text-amber-300 flex items-center justify-center text-sm font-black">
                        🟡
                    </div>
                    <div>
                        <h3 class="text-sm font-black text-slate-800 dark:text-slate-100">
                            {{ closedResult ? 'Shift Closed (X-Report Ready)' : 'End Shift & Drawer Reconciliation' }}
                        </h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400">
                            {{ shift?.shift_number }} &bull; Cashier: {{ summary?.cashier_name || 'Cashier' }}
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
                    <div>{{ errorMessage }}</div>
                </div>

                <div v-if="loadingSummary" class="py-12 flex flex-col items-center justify-center text-slate-400 space-y-2">
                    <div class="w-8 h-8 border-3 border-amber-500 border-t-transparent rounded-full animate-spin"></div>
                    <span class="text-xs font-semibold">Calculating live till drawer totals...</span>
                </div>

                <!-- If Closed Successfully View -->
                <div v-else-if="closedResult" class="space-y-4">
                    <div class="p-4 bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800 rounded-xl text-xs text-emerald-800 dark:text-emerald-300 flex items-center space-x-2">
                        <span class="text-lg">✅</span>
                        <div>
                            <strong>Shift successfully closed!</strong> Drawer cash reconciled and X-Report generated.
                        </div>
                    </div>

                    <div class="bg-slate-50 dark:bg-slate-800/80 rounded-xl p-4 border border-slate-200 dark:border-slate-700 space-y-2 text-xs font-mono">
                        <div class="flex justify-between">
                            <span>Shift #:</span>
                            <span class="font-bold">{{ closedResult.shift?.shift_number }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span>Expected Cash in Till:</span>
                            <span class="font-bold">Rs. {{ formatPrice(closedResult.shift?.closing_cash_expected) }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span>Counted Cash Actual:</span>
                            <span class="font-bold">Rs. {{ formatPrice(closedResult.shift?.closing_cash_actual) }}</span>
                        </div>
                        <div
                            :class="[
                                'flex justify-between font-black pt-1 border-t border-slate-200 dark:border-slate-700',
                                closedResult.shift?.cash_variance === 0
                                    ? 'text-emerald-600 dark:text-emerald-400'
                                    : closedResult.shift?.cash_variance > 0
                                        ? 'text-blue-600 dark:text-blue-400'
                                        : 'text-rose-600 dark:text-rose-400'
                            ]"
                        >
                            <span>VARIANCE:</span>
                            <span>
                                {{ closedResult.shift?.cash_variance >= 0 ? '+' : '' }}Rs. {{ formatPrice(closedResult.shift?.cash_variance) }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- If Open - Reconciliation Form -->
                <div v-else-if="summary" class="space-y-4">
                    <!-- Shift Performance Summary Box -->
                    <div class="grid grid-cols-3 gap-2.5">
                        <div class="p-2.5 rounded-xl bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 text-center">
                            <span class="text-[10px] text-slate-400 uppercase font-black">Tickets</span>
                            <div class="text-sm font-black text-slate-800 dark:text-slate-100 mt-0.5">
                                {{ summary.orders_count }}
                            </div>
                        </div>
                        <div class="p-2.5 rounded-xl bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 text-center">
                            <span class="text-[10px] text-slate-400 uppercase font-black">Gross Sales</span>
                            <div class="text-sm font-black text-indigo-600 dark:text-cyan-400 mt-0.5">
                                Rs. {{ formatPrice(summary.gross_sales) }}
                            </div>
                        </div>
                        <div class="p-2.5 rounded-xl bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 text-center">
                            <span class="text-[10px] text-slate-400 uppercase font-black">Cash Sales</span>
                            <div class="text-sm font-black text-emerald-600 dark:text-emerald-400 mt-0.5">
                                Rs. {{ formatPrice(summary.cash_collected) }}
                            </div>
                        </div>
                    </div>

                    <!-- Expected Cash Calculation Breakdown -->
                    <div class="p-3 bg-slate-50 dark:bg-slate-800/80 rounded-xl border border-slate-200 dark:border-slate-700 space-y-1.5 text-xs">
                        <div class="flex justify-between text-slate-600 dark:text-slate-300">
                            <span>Opening Cash Float:</span>
                            <span class="font-bold">Rs. {{ formatPrice(summary.opening_float) }}</span>
                        </div>
                        <div class="flex justify-between text-slate-600 dark:text-slate-300">
                            <span>+ Cash Payments Collected:</span>
                            <span class="font-bold text-emerald-600 dark:text-emerald-400">Rs. {{ formatPrice(summary.cash_collected) }}</span>
                        </div>
                        <div class="flex justify-between text-slate-800 dark:text-slate-100 font-black text-sm pt-1.5 border-t border-slate-200 dark:border-slate-700">
                            <span>System Expected Till Cash:</span>
                            <span class="text-indigo-600 dark:text-cyan-400">Rs. {{ formatPrice(expectedCash) }}</span>
                        </div>
                    </div>

                    <!-- Physical Count Input -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                            Counted Cash in Drawer (Physical Count) <span class="text-rose-500">*</span>
                        </label>
                        <div class="relative">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-xs font-bold text-slate-400">Rs.</span>
                            <input
                                type="number"
                                min="0"
                                step="any"
                                v-model="actualCash"
                                placeholder="0.00"
                                class="w-full text-base font-black pl-10 pr-3 py-2 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl text-slate-800 dark:text-slate-100 focus:ring-2 focus:ring-amber-500"
                            />
                        </div>
                    </div>

                    <!-- Live Calculated Variance Indicator -->
                    <div
                        v-if="actualCash !== ''"
                        :class="[
                            'p-3 rounded-xl border flex items-center justify-between text-xs font-bold',
                            variance === 0
                                ? 'bg-emerald-50 dark:bg-emerald-950/40 border-emerald-300 dark:border-emerald-700 text-emerald-700 dark:text-emerald-300'
                                : variance > 0
                                    ? 'bg-blue-50 dark:bg-blue-950/40 border-blue-300 dark:border-blue-700 text-blue-700 dark:text-blue-300'
                                    : 'bg-rose-50 dark:bg-rose-950/40 border-rose-300 dark:border-rose-700 text-rose-700 dark:text-rose-300'
                        ]"
                    >
                        <div class="flex items-center space-x-2">
                            <span>{{ variance === 0 ? '🎯' : variance > 0 ? '📈' : '📉' }}</span>
                            <span>{{ variance === 0 ? 'Drawer Balanced (Zero Discrepancy)' : variance > 0 ? 'Cash Overage in Drawer' : 'Cash Shortage in Drawer' }}</span>
                        </div>
                        <div class="text-sm font-black">
                            {{ variance >= 0 ? '+' : '' }}Rs. {{ formatPrice(variance) }}
                        </div>
                    </div>

                    <!-- Shift Notes -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                            Shift Handover / Discrepancy Notes
                        </label>
                        <textarea
                            v-model="notes"
                            rows="2"
                            placeholder="e.g. Handed over to evening cashier Usman, drawer counted..."
                            class="w-full text-xs bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl p-2.5 text-slate-800 dark:text-slate-100 placeholder-slate-400 focus:ring-2 focus:ring-amber-500"
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
                    {{ closedResult ? 'Close' : 'Cancel' }}
                </button>

                <div class="flex items-center space-x-2">
                    <button
                        v-if="closedResult"
                        type="button"
                        @click="handleViewPrintXReport"
                        class="px-4 py-2 bg-slate-800 hover:bg-slate-900 text-white font-bold text-xs rounded-xl shadow-xs transition-all flex items-center space-x-1.5"
                    >
                        <span>🖨️ View & Print X-Report</span>
                    </button>

                    <button
                        v-if="!closedResult"
                        type="button"
                        @click="handleCloseShift"
                        :disabled="submitting || loadingSummary || actualCash === ''"
                        class="px-5 py-2.5 bg-amber-600 hover:bg-amber-700 text-white font-bold text-xs rounded-xl shadow-md active:scale-95 transition-all disabled:opacity-50 flex items-center space-x-1.5"
                    >
                        <span v-if="submitting" class="animate-spin mr-1">⌛</span>
                        <span>End Shift & Reconcile Till</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
