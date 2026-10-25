<script setup>
import { ref, computed, watch } from 'vue';
import SelectInput from '@/Components/SelectInput.vue';

const props = defineProps({
    isOpen: Boolean,
    grandTotal: {
        type: Number,
        default: 0
    },
    paymentTypes: {
        type: Array,
        default: () => []
    },
    submitting: Boolean,
});

const emit = defineEmits(['close', 'confirmPayment', 'cardBinEntered']);

const activePaymentTypeId = ref(null);
const splitRows = ref([]);
const isSplitMode = ref(false);
const cardBin = ref('');
const cardLastFour = ref('');
const cardBank = ref('');
const transactionRef = ref('');
const cashTendered = ref(0);

watch(() => props.isOpen, (open) => {
    if (open) {
        // Default to Cash payment type
        const cashPt = props.paymentTypes.find(pt => pt.name.toLowerCase().includes('cash')) || props.paymentTypes[0];
        activePaymentTypeId.value = cashPt ? cashPt.id : null;
        cashTendered.value = props.grandTotal;
        cardBin.value = '';
        cardLastFour.value = '';
        cardBank.value = '';
        transactionRef.value = '';
        isSplitMode.value = false;
        splitRows.value = [
            { payment_type_id: activePaymentTypeId.value, amount: props.grandTotal }
        ];
    }
}, { immediate: true });

watch(cardBin, (val) => {
    if (val && val.length >= 6) {
        emit('cardBinEntered', val.substring(0, 6));
    }
});

const activePaymentType = computed(() => {
    return props.paymentTypes.find(pt => pt.id == activePaymentTypeId.value);
});

const isCardPayment = computed(() => {
    const name = activePaymentType.value?.name?.toLowerCase() || '';
    return name.includes('card') || name.includes('credit') || name.includes('debit');
});

const isCashPayment = computed(() => {
    return activePaymentType.value?.name?.toLowerCase().includes('cash');
});

const changeAmount = computed(() => {
    if (!isCashPayment.value) return 0;
    return Math.max(0, cashTendered.value - props.grandTotal);
});

const totalSplitPaid = computed(() => {
    return splitRows.value.reduce((sum, r) => sum + Number(r.amount || 0), 0);
});

const remainingAmount = computed(() => {
    return Math.max(0, props.grandTotal - totalSplitPaid.value);
});

const addSplitRow = () => {
    const unselectedPt = props.paymentTypes.find(pt => !splitRows.value.some(r => r.payment_type_id == pt.id)) || props.paymentTypes[0];
    splitRows.value.push({
        payment_type_id: unselectedPt?.id || activePaymentTypeId.value,
        amount: remainingAmount.value,
    });
};

const removeSplitRow = (index) => {
    splitRows.value.splice(index, 1);
};

const setQuickTender = (amt) => {
    cashTendered.value = amt;
};

const handleConfirm = () => {
    let paymentsPayload = [];

    if (isSplitMode.value) {
        paymentsPayload = splitRows.value.map(r => ({
            payment_type_id: r.payment_type_id,
            amount: Number(r.amount || 0),
            tendered: Number(r.amount || 0),
            change_returned: 0,
        }));
    } else {
        paymentsPayload = [
            {
                payment_type_id: activePaymentTypeId.value,
                amount: props.grandTotal,
                tendered: isCashPayment.value ? Number(cashTendered.value) : props.grandTotal,
                change_returned: isCashPayment.value ? changeAmount.value : 0,
                transaction_reference: transactionRef.value.trim() || null,
                card_last_four: cardLastFour.value.trim() || null,
                card_bank: cardBank.value.trim() || null,
            }
        ];
    }

    emit('confirmPayment', {
        payments: paymentsPayload,
        card_bin: cardBin.value.trim() || null,
        tendered_amount: isCashPayment.value ? Number(cashTendered.value) : props.grandTotal,
        change_amount: isCashPayment.value ? changeAmount.value : 0,
    });
};

const formatPrice = (val) => {
    return Number(val || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
};
</script>

<template>
    <div v-if="isOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs select-none">
        <div class="bg-white dark:bg-slate-900 w-full max-w-xl rounded-2xl shadow-2xl border border-slate-200 dark:border-slate-800 overflow-hidden flex flex-col max-h-[90vh] animate-in fade-in zoom-in-95 duration-150">
            <!-- Modal Header -->
            <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between bg-slate-50/70 dark:bg-slate-800/40">
                <div class="flex items-center space-x-2">
                    <div class="w-8 h-8 rounded-lg bg-emerald-50 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400 flex items-center justify-center font-bold">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-sm font-black text-slate-900 dark:text-white">
                            Settle & Complete Bill
                        </h3>
                        <p class="text-[11px] text-slate-500 dark:text-slate-400">
                            Select payment method and confirm transaction
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

            <!-- Total Banner -->
            <div class="px-6 py-4 bg-emerald-500/10 dark:bg-emerald-950/30 border-b border-emerald-500/20 flex items-center justify-between">
                <div>
                    <span class="text-[10px] font-black uppercase text-emerald-700 dark:text-emerald-300 tracking-wider">
                        Total Amount Due
                    </span>
                    <h2 class="text-2xl font-black text-emerald-600 dark:text-emerald-400">
                        <span class="text-sm font-bold mr-1">Rs.</span>
                        <span>{{ formatPrice(grandTotal) }}</span>
                    </h2>
                </div>
                <!-- Split toggle -->
                <button
                    type="button"
                    @click="isSplitMode = !isSplitMode"
                    :class="[
                        'px-3 py-1.5 text-xs font-bold rounded-lg border transition-all',
                        isSplitMode
                            ? 'bg-indigo-600 text-white border-indigo-600'
                            : 'bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-200 border-slate-300 dark:border-slate-700 hover:border-indigo-400'
                    ]"
                >
                    {{ isSplitMode ? '✓ Split Mode Active' : '+ Split Payment' }}
                </button>
            </div>

            <!-- Body -->
            <div class="p-6 overflow-y-auto space-y-5 flex-1">
                <!-- Standard Single Payment Mode -->
                <div v-if="!isSplitMode" class="space-y-4">
                    <!-- Payment Methods Grid -->
                    <div>
                        <label class="text-xs font-black uppercase text-slate-700 dark:text-slate-300 tracking-wider block mb-2">
                            Select Payment Method
                        </label>
                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-2.5">
                            <button
                                v-for="pt in paymentTypes"
                                :key="pt.id"
                                type="button"
                                @click="activePaymentTypeId = pt.id"
                                :class="[
                                    'p-3 rounded-xl border text-left font-bold text-xs transition-all relative flex items-center justify-between',
                                    activePaymentTypeId == pt.id
                                        ? 'border-emerald-600 bg-emerald-50 dark:bg-emerald-950/40 dark:border-emerald-500 text-emerald-900 dark:text-emerald-200 shadow-xs'
                                        : 'border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800/60 text-slate-700 dark:text-slate-300 hover:border-slate-300'
                                ]"
                            >
                                <span>{{ pt.name }}</span>
                                <span v-if="activePaymentTypeId == pt.id" class="w-2 h-2 rounded-full bg-emerald-600 dark:bg-emerald-400"></span>
                            </button>
                        </div>
                    </div>

                    <!-- Cash Specific: Quick Cash Tender & Change -->
                    <div v-if="isCashPayment" class="p-4 bg-slate-50 dark:bg-slate-800/50 rounded-2xl border border-slate-200 dark:border-slate-800 space-y-3">
                        <div class="flex items-center justify-between">
                            <label class="text-xs font-bold text-slate-700 dark:text-slate-300">
                                Cash Received (Tendered)
                            </label>
                            <input
                                type="number"
                                v-model.number="cashTendered"
                                step="any"
                                class="w-36 text-right font-black text-sm bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-lg px-2.5 py-1.5 text-slate-900 dark:text-white focus:ring-2 focus:ring-emerald-500/20"
                            />
                        </div>

                        <!-- Quick Cash Buttons -->
                        <div class="flex items-center space-x-2">
                            <button
                                type="button"
                                @click="setQuickTender(grandTotal)"
                                class="flex-1 py-1 px-2 rounded-lg bg-white dark:bg-slate-700 border border-slate-200 dark:border-slate-600 text-[11px] font-bold text-slate-700 dark:text-slate-200 hover:bg-slate-100"
                            >
                                Exact
                            </button>
                            <button
                                v-for="amt in [500, 1000, 5000]"
                                :key="amt"
                                type="button"
                                @click="setQuickTender(amt)"
                                class="flex-1 py-1 px-2 rounded-lg bg-white dark:bg-slate-700 border border-slate-200 dark:border-slate-600 text-[11px] font-bold text-slate-700 dark:text-slate-200 hover:bg-slate-100"
                            >
                                {{ amt }}
                            </button>
                        </div>

                        <!-- Change Returned -->
                        <div class="pt-2 border-t border-slate-200 dark:border-slate-700 flex items-center justify-between">
                            <span class="text-xs font-bold text-slate-500 dark:text-slate-400">Change to Return</span>
                            <span :class="['text-sm font-black', changeAmount >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-500']">
                                Rs. {{ formatPrice(changeAmount) }}
                            </span>
                        </div>
                    </div>

                    <!-- Card Specific: BIN & Bank Details -->
                    <div v-if="isCardPayment" class="p-4 bg-slate-50 dark:bg-slate-800/50 rounded-2xl border border-slate-200 dark:border-slate-800 space-y-3">
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="text-[11px] font-bold text-slate-700 dark:text-slate-300 block mb-1">
                                    Card BIN (First 6 Digits)
                                </label>
                                <input
                                    type="text"
                                    maxlength="6"
                                    v-model="cardBin"
                                    placeholder="e.g. 421412"
                                    class="w-full text-xs bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-lg px-2.5 py-1.5 text-slate-900 dark:text-white"
                                />
                                <span class="text-[9px] text-slate-400 mt-0.5 block">Auto checks bank card discount</span>
                            </div>
                            <div>
                                <label class="text-[11px] font-bold text-slate-700 dark:text-slate-300 block mb-1">
                                    Last 4 Digits
                                </label>
                                <input
                                    type="text"
                                    maxlength="4"
                                    v-model="cardLastFour"
                                    placeholder="e.g. 9876"
                                    class="w-full text-xs bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-lg px-2.5 py-1.5 text-slate-900 dark:text-white"
                                />
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="text-[11px] font-bold text-slate-700 dark:text-slate-300 block mb-1">
                                    Bank Name
                                </label>
                                <input
                                    type="text"
                                    v-model="cardBank"
                                    placeholder="e.g. HBL, Meezan, Alfalah"
                                    class="w-full text-xs bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-lg px-2.5 py-1.5 text-slate-900 dark:text-white"
                                />
                            </div>
                            <div>
                                <label class="text-[11px] font-bold text-slate-700 dark:text-slate-300 block mb-1">
                                    Transaction Ref / Slip #
                                </label>
                                <input
                                    type="text"
                                    v-model="transactionRef"
                                    placeholder="Slip / Auth Code"
                                    class="w-full text-xs bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-lg px-2.5 py-1.5 text-slate-900 dark:text-white"
                                />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Split Payment Mode -->
                <div v-else class="space-y-3">
                    <div class="flex items-center justify-between">
                        <label class="text-xs font-black uppercase text-slate-700 dark:text-slate-300 tracking-wider">
                            Split Payment Rows
                        </label>
                        <button
                            type="button"
                            @click="addSplitRow"
                            class="text-xs font-bold text-indigo-600 dark:text-cyan-400 hover:underline"
                        >
                            + Add Method
                        </button>
                    </div>

                    <div v-for="(row, rIndex) in splitRows" :key="rIndex" class="flex items-center space-x-2 bg-slate-50 dark:bg-slate-800 p-2.5 rounded-xl border border-slate-200 dark:border-slate-700">
                        <div class="flex-1">
                            <SelectInput
                                v-model="row.payment_type_id"
                                :options="paymentTypes"
                                valueKey="id"
                                labelKey="name"
                                size="sm"
                                placeholder="Select payment method"
                            />
                        </div>
                        <input
                            type="number"
                            v-model.number="row.amount"
                            step="any"
                            placeholder="Amount"
                            class="w-28 text-right font-black text-xs bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-lg py-1 px-2 text-slate-900 dark:text-white"
                        />
                        <button
                            v-if="splitRows.length > 1"
                            type="button"
                            @click="removeSplitRow(rIndex)"
                            class="text-slate-400 hover:text-rose-500 p-1"
                        >
                            ✕
                        </button>
                    </div>

                    <div class="p-3 bg-slate-100 dark:bg-slate-800 rounded-xl flex items-center justify-between text-xs font-bold">
                        <span>Total Split Entered:</span>
                        <span :class="totalSplitPaid >= grandTotal ? 'text-emerald-600' : 'text-rose-500'">
                            Rs. {{ formatPrice(totalSplitPaid) }} / Rs. {{ formatPrice(grandTotal) }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div class="px-6 py-4 border-t border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 flex items-center justify-between">
                <button
                    type="button"
                    @click="emit('close')"
                    class="px-4 py-2 text-xs font-bold text-slate-600 dark:text-slate-400 hover:text-slate-800"
                >
                    Cancel
                </button>
                <button
                    type="button"
                    @click="handleConfirm"
                    :disabled="submitting || (isSplitMode && totalSplitPaid < grandTotal) || (isCashPayment && cashTendered < grandTotal)"
                    class="px-6 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-black text-xs shadow-lg active:scale-95 transition-all disabled:opacity-40 disabled:cursor-not-allowed flex items-center space-x-2"
                >
                    <span>{{ submitting ? 'Processing...' : 'Complete & Print Receipt' }}</span>
                </button>
            </div>
        </div>
    </div>
</template>
