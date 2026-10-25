<script setup>
import { ref, computed } from 'vue';
import axios from 'axios';

const props = defineProps({
    isOpen: Boolean,
    customers: {
        type: Array,
        default: () => []
    },
    selectedCustomer: Object,
});

const emit = defineEmits(['close', 'selectCustomer', 'customerCreated']);

const searchQuery = ref('');
const isCreating = ref(false);
const saving = ref(false);
const errorMsg = ref('');

const newCustomer = ref({
    name: '',
    phone: '',
    address: '',
});

const filteredCustomers = computed(() => {
    if (!searchQuery.value.trim()) {
        return props.customers.slice(0, 15);
    }
    const q = searchQuery.value.toLowerCase().trim();
    return props.customers.filter(c => 
        (c.name && c.name.toLowerCase().includes(q)) ||
        (c.phone && c.phone.includes(q)) ||
        (c.address && c.address.toLowerCase().includes(q))
    );
});

const handleSaveCustomer = async () => {
    errorMsg.value = '';
    if (!newCustomer.value.name.trim() || !newCustomer.value.phone.trim()) {
        errorMsg.value = 'Name and Phone are required.';
        return;
    }

    saving.value = true;
    try {
        const response = await axios.post(route('pos.customers.quick'), newCustomer.value);
        if (response.data.success && response.data.customer) {
            emit('customerCreated', response.data.customer);
            emit('selectCustomer', response.data.customer);
            newCustomer.value = { name: '', phone: '', address: '' };
            isCreating.value = false;
            emit('close');
        }
    } catch (e) {
        errorMsg.value = e.response?.data?.message || 'Failed to save customer.';
    } finally {
        saving.value = false;
    }
};

const handleSelect = (customer) => {
    emit('selectCustomer', customer);
    emit('close');
};
</script>

<template>
    <div v-if="isOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs select-none">
        <div class="bg-white dark:bg-slate-900 w-full max-w-lg rounded-2xl shadow-2xl border border-slate-200 dark:border-slate-800 overflow-hidden flex flex-col max-h-[85vh] animate-in fade-in zoom-in-95 duration-150">
            <!-- Modal Header -->
            <div class="px-5 py-3.5 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between bg-slate-50/70 dark:bg-slate-800/40">
                <div class="flex items-center space-x-2">
                    <div class="w-8 h-8 rounded-lg bg-emerald-50 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400 flex items-center justify-center font-bold">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-sm font-black text-slate-900 dark:text-white">
                            Customer Directory
                        </h3>
                        <p class="text-[11px] text-slate-500 dark:text-slate-400">
                            Search existing customer or register a new one
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

            <!-- Toggle Switcher: Search vs Add New -->
            <div class="p-3 border-b border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 flex items-center space-x-2">
                <button
                    type="button"
                    @click="isCreating = false"
                    :class="[
                        'flex-1 py-1.5 text-xs font-bold rounded-lg transition-all',
                        !isCreating
                            ? 'bg-indigo-600 dark:bg-cyan-600 text-white shadow-xs'
                            : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-200'
                    ]"
                >
                    Search Customer
                </button>
                <button
                    type="button"
                    @click="isCreating = true"
                    :class="[
                        'flex-1 py-1.5 text-xs font-bold rounded-lg transition-all',
                        isCreating
                            ? 'bg-indigo-600 dark:bg-cyan-600 text-white shadow-xs'
                            : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-200'
                    ]"
                >
                    + New Customer
                </button>
            </div>

            <!-- Search Mode Content -->
            <div v-if="!isCreating" class="p-4 flex-1 overflow-y-auto space-y-3">
                <!-- Search Input -->
                <div class="relative">
                    <svg class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    <input
                        type="text"
                        v-model="searchQuery"
                        placeholder="Search by phone number or name..."
                        class="w-full pl-9 pr-4 py-2 text-xs bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-slate-900 dark:text-slate-100 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500"
                    />
                </div>

                <!-- Customer List -->
                <div class="space-y-1.5 max-h-72 overflow-y-auto">
                    <div
                        v-for="c in filteredCustomers"
                        :key="c.id"
                        @click="handleSelect(c)"
                        class="p-2.5 rounded-xl border border-slate-200 dark:border-slate-800 hover:border-emerald-400 dark:hover:border-emerald-500 bg-white dark:bg-slate-800/60 hover:bg-emerald-50/40 dark:hover:bg-emerald-950/20 cursor-pointer transition-all flex items-center justify-between"
                    >
                        <div>
                            <h4 class="text-xs font-black text-slate-800 dark:text-slate-100">
                                {{ c.name }}
                            </h4>
                            <p class="text-[11px] text-slate-500 dark:text-slate-400 flex items-center space-x-1 mt-0.5">
                                <span class="font-bold text-emerald-600 dark:text-emerald-400">{{ c.phone }}</span>
                                <span v-if="c.address" class="text-slate-400 truncate max-w-xs">• {{ c.address }}</span>
                            </p>
                        </div>
                        <span class="text-xs font-bold text-emerald-600 dark:text-emerald-400">
                            Select →
                        </span>
                    </div>

                    <div v-if="filteredCustomers.length === 0" class="py-8 text-center text-slate-400 text-xs">
                        No customer found matching "{{ searchQuery }}". Click "+ New Customer" above to add.
                    </div>
                </div>
            </div>

            <!-- Create Mode Form -->
            <div v-else class="p-5 flex-1 overflow-y-auto space-y-3.5">
                <div v-if="errorMsg" class="p-2.5 bg-rose-50 border border-rose-200 text-rose-700 rounded-lg text-xs font-semibold">
                    {{ errorMsg }}
                </div>

                <div>
                    <label class="text-xs font-bold text-slate-700 dark:text-slate-300 block mb-1">
                        Phone Number <span class="text-rose-500">*</span>
                    </label>
                    <input
                        type="text"
                        v-model="newCustomer.phone"
                        placeholder="e.g. 03001234567"
                        class="w-full text-xs bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl px-3 py-2 text-slate-900 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500"
                    />
                </div>

                <div>
                    <label class="text-xs font-bold text-slate-700 dark:text-slate-300 block mb-1">
                        Full Name <span class="text-rose-500">*</span>
                    </label>
                    <input
                        type="text"
                        v-model="newCustomer.name"
                        placeholder="Customer full name"
                        class="w-full text-xs bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl px-3 py-2 text-slate-900 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500"
                    />
                </div>

                <div>
                    <label class="text-xs font-bold text-slate-700 dark:text-slate-300 block mb-1">
                        Delivery Address
                    </label>
                    <textarea
                        v-model="newCustomer.address"
                        rows="2"
                        placeholder="House #, Street, Area, Landmark..."
                        class="w-full text-xs bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl px-3 py-2 text-slate-900 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500"
                    ></textarea>
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="px-5 py-3 border-t border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 flex items-center justify-between">
                <button
                    type="button"
                    @click="emit('close')"
                    class="px-4 py-1.5 text-xs font-bold text-slate-600 dark:text-slate-400 hover:text-slate-800"
                >
                    Cancel
                </button>

                <div v-if="isCreating">
                    <button
                        type="button"
                        @click="handleSaveCustomer"
                        :disabled="saving"
                        class="px-5 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-black text-xs shadow-md transition-all disabled:opacity-50"
                    >
                        {{ saving ? 'Saving...' : 'Save & Select' }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
