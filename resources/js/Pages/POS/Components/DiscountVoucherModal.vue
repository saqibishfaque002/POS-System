<script setup>
import { ref, watch } from 'vue';
import axios from 'axios';

const props = defineProps({
    isOpen: Boolean,
    branchId: [Number, String],
    orderTypeId: [Number, String],
    cart: {
        type: Array,
        default: () => []
    },
    subtotal: {
        type: Number,
        default: 0
    },
    appliedDiscount: Object,
});

const emit = defineEmits(['close', 'applyDiscount', 'removeDiscount']);

const voucherCode = ref('');
const validatingVoucher = ref(false);
const voucherError = ref('');
const voucherSuccess = ref('');

const loadingDeals = ref(false);
const availableDeals = ref([]);

const fetchAvailableDeals = async () => {
    if (!props.isOpen) return;
    loadingDeals.value = true;
    try {
        const response = await axios.post(route('restaurant.discounts.available'), {
            branch_id: props.branchId,
            order_type_id: props.orderTypeId,
            subtotal: props.subtotal,
            cart_subtotal: props.subtotal,
        });
        availableDeals.value = response.data.available_discounts || response.data.discounts || [];
    } catch (e) {
        console.warn('Could not fetch deals:', e);
    } finally {
        loadingDeals.value = false;
    }
};

watch(() => props.isOpen, (newVal) => {
    if (newVal) {
        voucherCode.value = '';
        voucherError.value = '';
        voucherSuccess.value = '';
        fetchAvailableDeals();
    }
});

const handleApplyVoucher = async () => {
    if (!voucherCode.value.trim()) {
        voucherError.value = 'Please enter a voucher code.';
        return;
    }

    validatingVoucher.value = true;
    voucherError.value = '';
    voucherSuccess.value = '';

    try {
        const response = await axios.post(route('restaurant.discounts.validate_voucher'), {
            code: voucherCode.value.trim().toUpperCase(),
            branch_id: props.branchId,
            order_type_id: props.orderTypeId,
            subtotal: props.subtotal,
            cart_subtotal: props.subtotal,
            items: props.cart,
        });

        if (response.data.valid && response.data.discount) {
            voucherSuccess.value = response.data.message || 'Voucher applied successfully!';
            emit('applyDiscount', response.data.discount);
            setTimeout(() => {
                emit('close');
            }, 500);
        } else {
            voucherError.value = response.data.message || 'Invalid voucher code.';
        }
    } catch (e) {
        voucherError.value = e.response?.data?.message || 'Failed to validate voucher code.';
    } finally {
        validatingVoucher.value = false;
    }
};

const selectDeal = (deal) => {
    emit('applyDiscount', deal);
    emit('close');
};

const handleRemove = () => {
    emit('removeDiscount');
};
</script>

<template>
    <div
        v-if="isOpen"
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm animate-fade-in"
    >
        <div class="bg-white dark:bg-slate-900 rounded-2xl shadow-2xl border border-slate-200 dark:border-slate-800 w-full max-w-lg overflow-hidden flex flex-col max-h-[85vh]">
            <!-- Modal Header -->
            <div class="px-5 py-4 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between bg-slate-50/80 dark:bg-slate-800/60">
                <div class="flex items-center space-x-2.5">
                    <div class="w-8 h-8 rounded-xl bg-indigo-100 dark:bg-indigo-950/60 text-indigo-600 dark:text-cyan-400 flex items-center justify-center font-black">
                        %
                    </div>
                    <div>
                        <h3 class="text-sm font-black text-slate-800 dark:text-slate-100">Discounts & Vouchers</h3>
                        <p class="text-xs text-slate-400">Apply promotional deals or enter a voucher code</p>
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

            <!-- Modal Content -->
            <div class="p-5 overflow-y-auto space-y-5">
                <!-- Currently Active Discount Banner -->
                <div
                    v-if="appliedDiscount"
                    class="p-3 bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800 rounded-xl flex items-center justify-between"
                >
                    <div class="flex items-center space-x-2">
                        <span class="text-emerald-600 dark:text-emerald-400 font-black text-sm">✓</span>
                        <div>
                            <div class="text-xs font-bold text-emerald-900 dark:text-emerald-200">
                                Applied: {{ appliedDiscount.name }}
                            </div>
                            <div class="text-[11px] text-emerald-700 dark:text-emerald-400">
                                {{ appliedDiscount.type === 'percentage' ? `${appliedDiscount.value}% Off` : `Rs. ${appliedDiscount.value} Off` }}
                                <span v-if="appliedDiscount.code" class="font-mono ml-1">({{ appliedDiscount.code }})</span>
                            </div>
                        </div>
                    </div>
                    <button
                        type="button"
                        @click="handleRemove"
                        class="text-xs font-bold text-rose-600 hover:text-rose-800 dark:text-rose-400 px-2.5 py-1 rounded-lg bg-white dark:bg-slate-900 border border-rose-200 dark:border-rose-900 hover:bg-rose-50 transition-colors"
                    >
                        Remove
                    </button>
                </div>

                <!-- Section 1: Enter Voucher Code -->
                <div class="bg-slate-50 dark:bg-slate-800/50 p-3.5 rounded-xl border border-slate-200 dark:border-slate-800 space-y-2">
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300">
                        Voucher / Coupon Code
                    </label>
                    <div class="flex items-center space-x-2">
                        <div class="relative flex-1">
                            <input
                                type="text"
                                v-model="voucherCode"
                                @keyup.enter="handleApplyVoucher"
                                placeholder="e.g. WELCOME10, FLAT100"
                                uppercase
                                class="w-full text-xs font-mono uppercase font-bold tracking-wider bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-xl px-3 py-2 text-slate-900 dark:text-white placeholder-slate-400 focus:ring-2 focus:ring-indigo-500"
                            />
                        </div>
                        <button
                            type="button"
                            @click="handleApplyVoucher"
                            :disabled="validatingVoucher || !voucherCode.trim()"
                            class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs rounded-xl transition-all shadow-sm active:scale-95 disabled:opacity-40 disabled:cursor-not-allowed flex items-center space-x-1"
                        >
                            <span v-if="validatingVoucher" class="animate-spin mr-1">⌛</span>
                            <span>Apply</span>
                        </button>
                    </div>

                    <!-- Voucher Feedback Messages -->
                    <p v-if="voucherError" class="text-xs font-semibold text-rose-600 dark:text-rose-400 mt-1">
                        ⚠️ {{ voucherError }}
                    </p>
                    <p v-if="voucherSuccess" class="text-xs font-semibold text-emerald-600 dark:text-emerald-400 mt-1">
                        ✓ {{ voucherSuccess }}
                    </p>
                </div>

                <!-- Section 2: Available Store Deals -->
                <div class="space-y-2.5">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-black uppercase tracking-wider text-slate-700 dark:text-slate-300">
                            Available Store Deals
                        </span>
                        <span v-if="loadingDeals" class="text-xs text-slate-400">Loading...</span>
                    </div>

                    <div v-if="availableDeals.length === 0 && !loadingDeals" class="p-4 text-center text-xs text-slate-400 border border-dashed border-slate-200 dark:border-slate-800 rounded-xl">
                        No general promotions currently active for this order type and subtotal.
                    </div>

                    <div v-else class="space-y-2">
                        <div
                            v-for="deal in availableDeals"
                            :key="deal.id"
                            class="p-3 bg-white dark:bg-slate-800/80 border rounded-xl flex items-center justify-between transition-all hover:border-indigo-400 dark:hover:border-cyan-500"
                            :class="appliedDiscount?.id === deal.id ? 'border-emerald-500 bg-emerald-50/20' : 'border-slate-200 dark:border-slate-700'"
                        >
                            <div>
                                <div class="flex items-center space-x-2">
                                    <span class="text-xs font-black text-slate-800 dark:text-slate-100">
                                        {{ deal.name }}
                                    </span>
                                    <span class="text-[10px] font-bold px-1.5 py-0.2 rounded bg-indigo-100 dark:bg-cyan-950 text-indigo-700 dark:text-cyan-400">
                                        {{ deal.type === 'percentage' ? `${deal.value}% Off` : `Rs. ${deal.value} Off` }}
                                    </span>
                                </div>
                                <div class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5 space-x-2">
                                    <span v-if="deal.min_order_amount > 0">Min Order: Rs. {{ deal.min_order_amount }}</span>
                                    <span v-if="deal.max_discount_cap > 0">• Cap: Rs. {{ deal.max_discount_cap }}</span>
                                    <span v-if="deal.code" class="font-mono text-indigo-600 dark:text-cyan-400">• Code: {{ deal.code }}</span>
                                </div>
                            </div>

                            <div>
                                <button
                                    v-if="appliedDiscount?.id === deal.id"
                                    type="button"
                                    disabled
                                    class="text-xs font-bold text-emerald-600 bg-emerald-100 dark:bg-emerald-950/60 px-3 py-1.5 rounded-lg cursor-default"
                                >
                                    Applied
                                </button>
                                <button
                                    v-else
                                    type="button"
                                    @click="selectDeal(deal)"
                                    class="text-xs font-bold text-white bg-slate-800 hover:bg-indigo-600 dark:bg-slate-700 dark:hover:bg-cyan-600 px-3 py-1.5 rounded-lg transition-colors shadow-sm"
                                >
                                    Select
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div class="px-5 py-3 border-t border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-900 flex justify-end">
                <button
                    type="button"
                    @click="emit('close')"
                    class="text-xs font-bold text-slate-600 hover:text-slate-800 dark:text-slate-400 dark:hover:text-slate-200 px-4 py-2"
                >
                    Close
                </button>
            </div>
        </div>
    </div>
</template>
