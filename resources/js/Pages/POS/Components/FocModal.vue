<script setup>
import { ref, watch } from 'vue';

const props = defineProps({
    isOpen: Boolean,
    subtotal: {
        type: Number,
        default: 0
    },
    submitting: Boolean,
});

const emit = defineEmits(['close', 'confirmFoc']);

const reasonPreset = ref('Management VIP Guest');
const customRemarks = ref('');

const presets = [
    'Management VIP Guest',
    'Customer Complaint Redress',
    'Staff Meal / Shift Allowance',
    'Food Quality / Spill Replacement',
    'Corporate Tasting / PR Review',
    'Owner / Director Discretion',
];

watch(() => props.isOpen, (val) => {
    if (val) {
        reasonPreset.value = 'Management VIP Guest';
        customRemarks.value = '';
    }
});

const handleConfirm = () => {
    let finalReason = reasonPreset.value;
    if (customRemarks.value.trim()) {
        finalReason += ` - ${customRemarks.value.trim()}`;
    }
    emit('confirmFoc', finalReason);
};

const formatPrice = (val) => {
    return Number(val || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
};
</script>

<template>
    <div
        v-if="isOpen"
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm animate-fade-in"
    >
        <div class="bg-white dark:bg-slate-900 rounded-2xl shadow-2xl border border-slate-200 dark:border-slate-800 w-full max-w-md overflow-hidden flex flex-col">
            <!-- Modal Header -->
            <div class="px-5 py-4 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between bg-purple-50 dark:bg-purple-950/40">
                <div class="flex items-center space-x-2.5">
                    <div class="w-8 h-8 rounded-xl bg-purple-100 dark:bg-purple-900/60 text-purple-700 dark:text-purple-300 flex items-center justify-center text-sm font-black">
                        🎁
                    </div>
                    <div>
                        <h3 class="text-sm font-black text-slate-800 dark:text-slate-100">Free of Charge (FOC)</h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400">Process complimentary order (100% discount)</p>
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

            <!-- Modal Body -->
            <div class="p-5 space-y-4">
                <div class="p-3 bg-amber-50 dark:bg-amber-950/30 border border-amber-200 dark:border-amber-800 rounded-xl text-xs text-amber-800 dark:text-amber-300 flex items-start space-x-2">
                    <span class="text-base leading-none">ℹ️</span>
                    <div>
                        This order (Rs. {{ formatPrice(subtotal) }}) will be marked as <strong>Completed (Rs. 0 Payable)</strong> and ingredients will be deducted from the inventory ledger.
                    </div>
                </div>

                <!-- Reason Preset Dropdown -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                        Select Authorized Reason <span class="text-rose-500">*</span>
                    </label>
                    <select
                        v-model="reasonPreset"
                        class="w-full text-xs font-semibold bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3 py-2 text-slate-800 dark:text-slate-100 focus:ring-2 focus:ring-purple-500"
                    >
                        <option v-for="p in presets" :key="p" :value="p">{{ p }}</option>
                    </select>
                </div>

                <!-- Additional Remarks / Manager Reference -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                        Additional Notes / Manager Name (Optional)
                    </label>
                    <textarea
                        v-model="customRemarks"
                        rows="2"
                        placeholder="e.g. Authorized by Shift Manager Ahmed..."
                        class="w-full text-xs bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl p-2.5 text-slate-800 dark:text-slate-100 placeholder-slate-400 focus:ring-2 focus:ring-purple-500"
                    ></textarea>
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="px-5 py-3 border-t border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-900 flex items-center justify-between">
                <button
                    type="button"
                    @click="emit('close')"
                    class="text-xs font-bold text-slate-600 hover:text-slate-800 dark:text-slate-400 dark:hover:text-slate-200 px-3 py-2"
                >
                    Cancel
                </button>
                <button
                    type="button"
                    @click="handleConfirm"
                    :disabled="submitting || !reasonPreset"
                    class="px-5 py-2.5 bg-purple-600 hover:bg-purple-700 text-white font-bold text-xs rounded-xl shadow-md active:scale-95 transition-all disabled:opacity-50 disabled:cursor-not-allowed flex items-center space-x-1.5"
                >
                    <span v-if="submitting" class="animate-spin mr-1">⌛</span>
                    <span>Confirm FOC Order</span>
                </button>
            </div>
        </div>
    </div>
</template>
