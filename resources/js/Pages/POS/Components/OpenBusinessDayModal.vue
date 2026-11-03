<script setup>
import { ref, watch } from 'vue';
import axios from 'axios';

const props = defineProps({
    isOpen: Boolean,
    branchId: [Number, String],
    branchName: String,
});

const emit = defineEmits(['close', 'dayOpened']);

const todayStr = new Date().toISOString().slice(0, 10);
const todayFormatted = new Intl.DateTimeFormat('en-US', { 
    weekday: 'long', 
    day: '2-digit', 
    month: 'short', 
    year: 'numeric' 
}).format(new Date());
const businessDate = ref(todayStr);
const notes = ref('');
const submitting = ref(false);
const errorMessage = ref('');

watch(() => props.isOpen, (val) => {
    if (val) {
        businessDate.value = new Date().toISOString().slice(0, 10);
        notes.value = '';
        errorMessage.value = '';
    }
});

const handleOpenDay = async () => {
    if (!props.branchId) {
        errorMessage.value = 'Please select a branch first.';
        return;
    }
    if (!businessDate.value) {
        errorMessage.value = 'Please select a valid business date.';
        return;
    }

    submitting.value = true;
    errorMessage.value = '';

    try {
        const response = await axios.post(route('pos.business-day.open'), {
            branch_id: props.branchId,
            business_date: businessDate.value,
            notes: notes.value,
        });

        if (response.data.success) {
            emit('dayOpened', response.data.business_day);
            emit('close');
        } else {
            errorMessage.value = response.data.message || 'Failed to open business day.';
        }
    } catch (err) {
        errorMessage.value = err.response?.data?.message || 'Error occurred while opening business day.';
    } finally {
        submitting.value = false;
    }
};
</script>

<template>
    <div
        v-if="isOpen"
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm animate-fade-in"
    >
        <div class="bg-white dark:bg-slate-900 rounded-2xl shadow-2xl border border-slate-200 dark:border-slate-800 w-full max-w-md overflow-hidden flex flex-col">
            <!-- Modal Header -->
            <div class="px-5 py-4 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between bg-indigo-50 dark:bg-cyan-950/40">
                <div class="flex items-center space-x-2.5">
                    <div class="w-8 h-8 rounded-xl bg-indigo-100 dark:bg-cyan-900/60 text-indigo-700 dark:text-cyan-300 flex items-center justify-center text-sm font-black">
                        📅
                    </div>
                    <div>
                        <h3 class="text-sm font-black text-slate-800 dark:text-slate-100">Open Business Day</h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400">{{ branchName || 'Branch' }}</p>
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
                <div
                    v-if="errorMessage"
                    class="p-3 bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-800 rounded-xl text-xs text-rose-700 dark:text-rose-300 flex items-start space-x-2"
                >
                    <span>⚠️</span>
                    <div>{{ errorMessage }}</div>
                </div>

                <div class="p-3 bg-blue-50 dark:bg-blue-950/30 border border-blue-200 dark:border-blue-800 rounded-xl text-xs text-blue-800 dark:text-blue-300 flex items-start space-x-2">
                    <span class="text-base leading-none">ℹ️</span>
                    <div>
                        Opening the business day initiates the branch accounting day. All cashier shifts and punched orders will be assigned to this business date.
                    </div>
                </div>

                <!-- Business Date Display (Locked to Today) -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                        Accounting Business Date <span class="text-rose-500">*</span>
                    </label>
                    <div class="bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 rounded-xl p-3 flex items-center justify-between">
                        <div class="flex items-center space-x-2.5">
                            <div class="w-8 h-8 rounded-lg bg-indigo-100 dark:bg-cyan-950 text-indigo-600 dark:text-cyan-400 flex items-center justify-center text-sm font-bold">
                                📅
                            </div>
                            <div>
                                <div class="text-[10px] uppercase font-bold text-slate-400 dark:text-slate-500 tracking-wider">Business Date</div>
                                <div class="text-xs font-black text-slate-800 dark:text-slate-100">{{ todayFormatted }}</div>
                            </div>
                        </div>
                        <div class="flex items-center space-x-1 px-2.5 py-1 rounded-lg bg-slate-200/60 dark:bg-slate-700/60 text-slate-700 dark:text-slate-300 text-[11px] font-bold">
                            <span>🔒</span>
                            <span>Locked to Today</span>
                        </div>
                    </div>
                    <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-1.5">
                        The business day is automatically locked to the current calendar date to prevent backdating or premature order punching.
                    </p>
                </div>

                <!-- Notes / Remarks -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                        Opening Notes (Optional)
                    </label>
                    <textarea
                        v-model="notes"
                        rows="2"
                        placeholder="e.g. Regular operations open, morning manager on duty..."
                        class="w-full text-xs bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl p-2.5 text-slate-800 dark:text-slate-100 placeholder-slate-400 focus:ring-2 focus:ring-indigo-500"
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
                    @click="handleOpenDay"
                    :disabled="submitting || !businessDate"
                    class="pos-btn-primary !py-2 !px-4 text-xs font-bold flex items-center space-x-1.5 disabled:opacity-50"
                >
                    <span v-if="submitting" class="animate-spin mr-1">⌛</span>
                    <span>Open Business Day</span>
                </button>
            </div>
        </div>
    </div>
</template>
