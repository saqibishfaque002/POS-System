<script setup>
import { ref, watch } from 'vue';
import axios from 'axios';

const props = defineProps({
    isOpen: Boolean,
    branchId: [Number, String],
    branchName: String,
    activeBusinessDay: Object,
    user: Object,
});

const emit = defineEmits(['close', 'shiftStarted', 'openBusinessDayPrompt']);

const openingFloat = ref(0);
const notes = ref('');
const submitting = ref(false);
const errorMessage = ref('');

const floatPresets = [0, 500, 1000, 2000, 5000];

watch(() => props.isOpen, (val) => {
    if (val) {
        openingFloat.value = 0;
        notes.value = '';
        errorMessage.value = '';
    }
});

const handleStartShift = async () => {
    if (!props.branchId) {
        errorMessage.value = 'Please select a branch first.';
        return;
    }

    if (!props.activeBusinessDay) {
        errorMessage.value = 'No business day is open for this branch. Please open the business day first.';
        return;
    }

    submitting.value = true;
    errorMessage.value = '';

    try {
        const response = await axios.post(route('pos.shifts.start'), {
            branch_id: props.branchId,
            opening_float: Number(openingFloat.value || 0),
            notes: notes.value,
        });

        if (response.data.success) {
            emit('shiftStarted', response.data.shift);
            emit('close');
        } else {
            errorMessage.value = response.data.message || 'Failed to start shift.';
        }
    } catch (err) {
        errorMessage.value = err.response?.data?.message || 'Error occurred while starting shift.';
    } finally {
        submitting.value = false;
    }
};
</script>

<template>
    <div
        v-if="isOpen"
        class="fixed inset-0 z-50 flex items-center justify-center p-2.5 sm:p-4 bg-slate-900/60 backdrop-blur-sm animate-fade-in"
    >
        <div class="bg-white dark:bg-slate-900 rounded-2xl shadow-2xl border border-slate-200 dark:border-slate-800 w-full max-w-md overflow-hidden flex flex-col max-h-[90vh]">
            <!-- Header -->
            <div class="px-4 sm:px-5 py-3 sm:py-4 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between bg-emerald-50 dark:bg-emerald-950/40 shrink-0">
                <div class="flex items-center space-x-2.5">
                    <div class="w-8 h-8 rounded-xl bg-emerald-100 dark:bg-emerald-900/60 text-emerald-700 dark:text-emerald-300 flex items-center justify-center text-sm font-black shrink-0">
                        🟢
                    </div>
                    <div>
                        <h3 class="text-xs sm:text-sm font-black text-slate-800 dark:text-slate-100">Start Cashier Shift</h3>
                        <p class="text-[11px] text-slate-500 dark:text-slate-400">
                            Cashier: <strong class="text-slate-700 dark:text-slate-200">{{ user?.name }}</strong> &bull; {{ branchName }}
                        </p>
                    </div>
                </div>
                <button
                    type="button"
                    @click="emit('close')"
                    class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 p-1.5 rounded-lg text-lg leading-none transition-colors cursor-pointer"
                >
                    ✕
                </button>
            </div>

            <!-- Body -->
            <div class="p-4 sm:p-5 space-y-3.5 sm:space-y-4 overflow-y-auto flex-1">
                <div
                    v-if="errorMessage"
                    class="p-3 bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-800 rounded-xl text-xs text-rose-700 dark:text-rose-300 flex items-start space-x-2"
                >
                    <span>⚠️</span>
                    <div>{{ errorMessage }}</div>
                </div>

                <!-- Business Day Check Warning -->
                <div
                    v-if="!activeBusinessDay"
                    class="p-3 bg-amber-50 dark:bg-amber-950/30 border border-amber-200 dark:border-amber-800 rounded-xl text-xs text-amber-800 dark:text-amber-300 space-y-2"
                >
                    <div class="flex items-center space-x-1.5 font-bold">
                        <span>⚠️</span>
                        <span>Business Day Not Open</span>
                    </div>
                    <p>
                        A business day must be opened for this branch before cashiers can start their shifts and punch orders.
                    </p>
                    <button
                        type="button"
                        @click="emit('openBusinessDayPrompt')"
                        class="px-3 py-1.5 bg-amber-600 hover:bg-amber-700 text-white rounded-lg font-bold text-xs shadow-xs"
                    >
                        Open Business Day Now
                    </button>
                </div>

                <div v-else class="p-2.5 bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700 rounded-xl text-xs text-slate-600 dark:text-slate-300 flex justify-between items-center">
                    <span>Accounting Date:</span>
                    <span class="font-bold text-indigo-600 dark:text-cyan-400">
                        {{ activeBusinessDay.business_date_formatted || activeBusinessDay.business_date }}
                    </span>
                </div>

                <!-- Opening Cash Float Input -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                        Opening Cash Float (Initial Drawer Cash) <span class="text-rose-500">*</span>
                    </label>
                    <div class="relative">
                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-xs font-bold text-slate-400">Rs.</span>
                        <input
                            type="number"
                            min="0"
                            step="any"
                            v-model="openingFloat"
                            placeholder="0.00"
                            class="w-full text-base font-black pl-10 pr-3 py-2 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl text-slate-800 dark:text-slate-100 focus:ring-2 focus:ring-emerald-500"
                        />
                    </div>

                    <!-- Quick Preset Buttons -->
                    <div class="flex items-center space-x-1.5 mt-2">
                        <span class="text-[10px] text-slate-400 uppercase font-bold mr-1">Quick:</span>
                        <button
                            v-for="amt in floatPresets"
                            :key="amt"
                            type="button"
                            @click="openingFloat = amt"
                            :class="[
                                'px-2 py-1 text-xs font-bold rounded-lg border transition-all',
                                openingFloat == amt
                                    ? 'bg-emerald-600 text-white border-emerald-600'
                                    : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 border-slate-200 dark:border-slate-700 hover:bg-slate-200'
                            ]"
                        >
                            Rs. {{ amt }}
                        </button>
                    </div>
                </div>

                <!-- Notes / Remarks -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                        Shift Notes (Optional)
                    </label>
                    <textarea
                        v-model="notes"
                        rows="2"
                        placeholder="e.g. Counter 1, terminal #2, morning shift..."
                        class="w-full text-xs bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl p-2.5 text-slate-800 dark:text-slate-100 placeholder-slate-400 focus:ring-2 focus:ring-emerald-500"
                    ></textarea>
                </div>
            </div>

            <!-- Footer -->
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
                    @click="handleStartShift"
                    :disabled="submitting || !activeBusinessDay || openingFloat < 0"
                    class="pos-btn-primary !py-2 !px-4 text-xs font-bold flex items-center space-x-1.5 disabled:opacity-50"
                >
                    <span v-if="submitting" class="animate-spin mr-1">⌛</span>
                    <span>Start Shift & Open Till</span>
                </button>
            </div>
        </div>
    </div>
</template>
