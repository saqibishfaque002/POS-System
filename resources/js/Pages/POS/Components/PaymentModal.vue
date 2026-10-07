<script setup>
import { ref, computed, watch } from 'vue';

const props = defineProps({
    show: Boolean,
    totalAmount: Number
});

const emit = defineEmits(['close', 'confirm']);

const tenderedAmount = ref('');
const paymentMethod = ref('cash');

watch(() => props.show, (newVal) => {
    if (newVal) {
        tenderedAmount.value = props.totalAmount.toFixed(2);
        paymentMethod.value = 'cash';
    }
});

const setTendered = (val) => {
    tenderedAmount.value = val.toString();
};

const addTendered = (val) => {
    const current = parseFloat(tenderedAmount.value || 0);
    tenderedAmount.value = (current + val).toFixed(2);
};

const clearTendered = () => {
    tenderedAmount.value = '';
};

const changeDue = computed(() => {
    const tendered = parseFloat(tenderedAmount.value || 0);
    const diff = tendered - props.totalAmount;
    return diff > 0 ? diff : 0;
});

const canConfirm = computed(() => {
    const tendered = parseFloat(tenderedAmount.value || 0);
    return tendered >= props.totalAmount;
});

const confirm = () => {
    if (canConfirm.value) {
        emit('confirm', {
            method: paymentMethod.value,
            tendered: parseFloat(tenderedAmount.value || 0),
            change: changeDue.value
        });
    }
};
</script>
<template>
    <div v-if="show" class="fixed inset-0 z-[100] flex items-center justify-center bg-black/60 backdrop-blur-sm p-4">
        <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-2xl w-full max-w-xl overflow-hidden flex flex-col">
            <div class="p-6 border-b border-slate-100 dark:border-slate-700 flex justify-between items-center bg-slate-50 dark:bg-slate-900/50">
                <h3 class="text-xl font-bold text-slate-800 dark:text-white">Payment Checkout</h3>
                <button @click="emit('close')" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            
            <div class="p-6 grid grid-cols-2 gap-8">
                <!-- Left: Payment details -->
                <div class="space-y-6">
                    <div>
                        <label class="block text-sm font-medium text-slate-500 dark:text-slate-400 mb-2">Total Amount Due</label>
                        <div class="text-4xl font-bold text-slate-800 dark:text-white tabular-nums">${{ totalAmount.toFixed(2) }}</div>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-slate-500 dark:text-slate-400 mb-2">Payment Method</label>
                        <div class="grid grid-cols-2 gap-3">
                            <button 
                                @click="paymentMethod = 'cash'" 
                                class="py-3 rounded-lg font-medium border-2 transition-colors flex items-center justify-center gap-2"
                                :class="paymentMethod === 'cash' ? 'border-indigo-600 text-indigo-700 bg-indigo-50 dark:border-indigo-500 dark:text-indigo-300 dark:bg-indigo-900/30' : 'border-slate-200 text-slate-600 hover:border-slate-300 dark:border-slate-700 dark:text-slate-300'"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" /></svg>
                                Cash
                            </button>
                            <button 
                                @click="paymentMethod = 'card'" 
                                class="py-3 rounded-lg font-medium border-2 transition-colors flex items-center justify-center gap-2"
                                :class="paymentMethod === 'card' ? 'border-indigo-600 text-indigo-700 bg-indigo-50 dark:border-indigo-500 dark:text-indigo-300 dark:bg-indigo-900/30' : 'border-slate-200 text-slate-600 hover:border-slate-300 dark:border-slate-700 dark:text-slate-300'"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" /></svg>
                                Card
                            </button>
                        </div>
                    </div>
                    
                    <div class="pt-4 border-t border-slate-100 dark:border-slate-700">
                        <label class="block text-sm font-medium text-slate-500 dark:text-slate-400 mb-2">Change Due</label>
                        <div class="text-3xl font-bold text-green-600 dark:text-green-400 tabular-nums">${{ changeDue.toFixed(2) }}</div>
                    </div>
                </div>
                
                <!-- Right: Tendered Input -->
                <div class="bg-slate-50 dark:bg-slate-900/50 p-4 rounded-xl border border-slate-100 dark:border-slate-700">
                    <label class="block text-sm font-medium text-slate-500 dark:text-slate-400 mb-2">Amount Tendered</label>
                    <input 
                        type="number" 
                        v-model="tenderedAmount" 
                        class="w-full text-2xl p-3 border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 rounded-lg focus:ring-indigo-500 focus:border-indigo-500 dark:text-white"
                    >
                    
                    <div class="grid grid-cols-2 gap-2 mt-4">
                        <button @click="setTendered(totalAmount)" class="p-2 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-600 rounded shadow-sm hover:bg-slate-50 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 font-medium text-sm">Exact Cash</button>
                        <button @click="clearTendered" class="p-2 bg-red-50 dark:bg-red-900/20 border border-red-100 dark:border-red-800 rounded shadow-sm hover:bg-red-100 dark:hover:bg-red-900/40 text-red-600 dark:text-red-400 font-medium text-sm">Clear</button>
                        
                        <button @click="addTendered(5)" class="p-2 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-600 rounded shadow-sm hover:bg-slate-50 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 font-medium text-sm">+$5</button>
                        <button @click="addTendered(10)" class="p-2 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-600 rounded shadow-sm hover:bg-slate-50 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 font-medium text-sm">+$10</button>
                        <button @click="addTendered(20)" class="p-2 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-600 rounded shadow-sm hover:bg-slate-50 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 font-medium text-sm">+$20</button>
                        <button @click="addTendered(50)" class="p-2 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-600 rounded shadow-sm hover:bg-slate-50 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 font-medium text-sm">+$50</button>
                        <button @click="addTendered(100)" class="p-2 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-600 rounded shadow-sm hover:bg-slate-50 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 font-medium text-sm col-span-2">+$100</button>
                    </div>
                </div>
            </div>
            
            <div class="p-6 border-t border-slate-100 dark:border-slate-700 bg-slate-50 dark:bg-slate-900 flex justify-end gap-3">
                <button 
                    @click="emit('close')" 
                    class="px-6 py-3 rounded-lg font-medium text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700 transition-colors"
                >
                    Cancel
                </button>
                <button 
                    @click="confirm" 
                    :disabled="!canConfirm"
                    class="px-8 py-3 rounded-lg font-bold text-white shadow-lg transition-all"
                    :class="canConfirm ? 'bg-indigo-600 hover:bg-indigo-700 hover:shadow-indigo-500/30' : 'bg-slate-400 cursor-not-allowed opacity-50'"
                >
                    Confirm Payment
                </button>
            </div>
        </div>
    </div>
</template>
