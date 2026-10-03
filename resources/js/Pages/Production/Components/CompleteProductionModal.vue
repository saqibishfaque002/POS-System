<script setup>
import { computed } from 'vue';
import { useForm } from '@inertiajs/vue3';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';

const props = defineProps({
    isOpen: Boolean,
    production: Object,
});

const emit = defineEmits(['close', 'completed']);

const form = useForm({
    status: 'completed',
    actual_yield_qty: props.production?.target_yield_qty || 1,
    wastage_qty: 0,
});

// Update form when modal opens
import { watch } from 'vue';
watch(() => props.isOpen, (newVal) => {
    if (newVal && props.production) {
        form.actual_yield_qty = props.production.target_yield_qty;
        form.wastage_qty = 0;
        form.clearErrors();
    }
});

const calculatedUnitCost = computed(() => {
    if (!props.production) return 0;
    const actual = parseFloat(form.actual_yield_qty) || 0;
    if (actual <= 0) return 0;
    return (parseFloat(props.production.total_cost) / actual).toFixed(2);
});

const submitForm = () => {
    form.patch(route('production.status', props.production.id), {
        onSuccess: () => {
            emit('completed');
            emit('close');
        }
    });
};

const closeModal = () => {
    emit('close');
};
</script>

<template>
    <div v-if="isOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm transition-all duration-300">
        <div class="bg-white dark:bg-slate-900 w-full max-w-lg rounded-2xl shadow-2xl flex flex-col border border-slate-200 dark:border-slate-700 overflow-hidden">
            <!-- Header -->
            <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-800 flex justify-between items-center bg-slate-50 dark:bg-slate-800/50">
                <h2 class="text-xl font-bold text-slate-800 dark:text-white flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    Complete Production Batch
                </h2>
                <button @click="closeModal" class="text-slate-400 hover:text-rose-500 transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <form @submit.prevent="submitForm">
                <div class="p-6">
                    <p class="text-sm text-slate-600 dark:text-slate-400 mb-6">
                        You are about to mark batch <strong class="text-slate-800 dark:text-white">{{ production?.document_number }}</strong> as completed. Please enter the final yield quantities.
                    </p>

                    <div class="space-y-5">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <InputLabel value="Target Yield" />
                                <div class="mt-1 px-3 py-2 bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400 font-bold rounded-lg border border-slate-200 dark:border-slate-700">
                                    {{ production?.target_yield_qty }}
                                </div>
                            </div>
                            <div>
                                <InputLabel value="Total Cost (BOM)" />
                                <div class="mt-1 px-3 py-2 bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 font-bold rounded-lg border border-indigo-100 dark:border-indigo-800">
                                    ${{ production?.total_cost }}
                                </div>
                            </div>
                        </div>

                        <div>
                            <InputLabel for="actual_yield_qty" value="Actual Usable Yield" />
                            <TextInput id="actual_yield_qty" type="number" step="0.01" min="0.01" v-model="form.actual_yield_qty" class="w-full mt-1 text-lg font-bold" required />
                            <p class="text-xs text-slate-500 mt-1">The quantity that will be added to your inventory.</p>
                            <InputError :message="form.errors.actual_yield_qty" class="mt-1" />
                        </div>

                        <div>
                            <InputLabel for="wastage_qty" value="Wastage Quantity" />
                            <TextInput id="wastage_qty" type="number" step="0.01" min="0" v-model="form.wastage_qty" class="w-full mt-1 text-lg text-rose-600 dark:text-rose-400 font-bold" required />
                            <p class="text-xs text-slate-500 mt-1">Quantity lost or wasted during production (cost is absorbed by actual yield).</p>
                            <InputError :message="form.errors.wastage_qty" class="mt-1" />
                        </div>

                        <div class="mt-6 bg-slate-50 dark:bg-slate-800 p-4 rounded-xl border border-slate-200 dark:border-slate-700">
                            <div class="flex justify-between items-center">
                                <span class="font-medium text-slate-600 dark:text-slate-400">Final Cost Per Unit:</span>
                                <span class="text-2xl font-black text-slate-800 dark:text-white">${{ calculatedUnitCost }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="px-6 py-4 bg-slate-50 dark:bg-slate-800/80 border-t border-slate-200 dark:border-slate-700 flex justify-end gap-3">
                    <button type="button" @click="closeModal" class="px-5 py-2.5 text-sm font-bold text-slate-600 dark:text-slate-400 hover:bg-slate-200 dark:hover:bg-slate-700 rounded-xl transition-colors">
                        Cancel
                    </button>
                    <button type="submit" class="pos-btn-primary bg-green-500 hover:bg-green-600 focus:ring-green-500" :disabled="form.processing">
                        <span v-if="form.processing">Saving...</span>
                        <span v-else>Complete Production</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>
