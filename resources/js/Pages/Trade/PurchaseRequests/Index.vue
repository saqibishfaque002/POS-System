<script setup>
import { ref, computed } from 'vue';
import { Head, useForm, Link } from '@inertiajs/vue3';
import DashboardLayout from '@/Pages/Dashboard.vue'; // We can just use a simple layout or rely on Dashboard
import DataTable from '@/Components/DataTable.vue';
import InventorySidebar from '@/Components/InventorySidebar.vue';

const props = defineProps({
    purchaseRequests: Array,
    branches: Array,
    items: Array,
});

const isModalOpen = ref(false);
const form = useForm({
    branch_id: '',
    date: new Date().toISOString().split('T')[0],
    notes: '',
    items: [
        { item_id: '', quantity: 1, unit_price: 0 }
    ]
});

const addItem = () => {
    form.items.push({ item_id: '', quantity: 1, unit_price: 0 });
};

const removeItem = (index) => {
    form.items.splice(index, 1);
};

const openModal = () => {
    form.reset();
    isModalOpen.value = true;
};

const submitForm = () => {
    form.post(route('trade.purchase-requests.store'), {
        onSuccess: () => {
            isModalOpen.value = false;
        }
    });
};

const columns = computed(() => [
    { key: 'document_number', label: 'PR Number' },
    { key: 'branch.name', label: 'Branch' },
    { key: 'date', label: 'Date' },
    { key: 'total_amount', label: 'Total Amount' },
    { key: 'status', label: 'Status' }
]);
</script>

<template>
    <Head title="Purchase Requests" />

    <div class="min-h-screen flex flex-col transition-colors duration-300 bg-slate-50 dark:bg-slate-900">
        <div class="w-full mx-auto px-4 sm:px-6 lg:px-8 mt-6">
            <!-- Header -->
            <header class="dashboard-card px-6 py-4 flex justify-between items-center mb-6">
                <div class="flex items-center gap-4">
                    <h1 class="text-xl font-semibold text-indigo-500 dark:text-[#06b6d4]">Purchase Requests</h1>
                    <div class="hidden md:flex items-center gap-3 ml-4 border-l pl-4 border-slate-200 dark:border-slate-700">
                        <Link :href="route('dashboard')" class="text-sm font-bold text-slate-600 hover:text-indigo-600 dark:text-slate-400 dark:hover:text-[#06b6d4] transition-colors flex items-center gap-1">
                            &larr; Back to Dashboard
                        </Link>
                    </div>
                </div>
            </header>

            <div class="flex flex-col md:flex-row gap-6">
                <!-- Sidebar Tabs (Shared) -->
                <InventorySidebar />

                <!-- Content -->
                <main class="flex-1">
                    <div class="dashboard-card p-6">
                        <div class="flex justify-between items-center mb-6">
                            <h2 class="text-lg font-bold text-slate-800 dark:text-white">Purchase Requests</h2>
                            <button @click="openModal" class="pos-btn-primary !py-2 !px-4 !rounded-lg text-sm">
                                + Create PR
                            </button>
                        </div>
                        <DataTable :data="purchaseRequests" :columns="columns" />
                    </div>
                </main>
            </div>
            
            <!-- Modal Form -->
            <div v-if="isModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-sm">
                <div class="dashboard-card w-full max-w-4xl p-6 max-h-[90vh] overflow-y-auto">
                    <h2 class="text-xl font-bold mb-6 text-slate-800 dark:text-white">New Purchase Request</h2>
                    
                    <form @submit.prevent="submitForm" class="space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Branch</label>
                                <select v-model="form.branch_id" class="pos-input w-full" required>
                                    <option value="" disabled>Select Branch</option>
                                    <option v-for="branch in branches" :key="branch.id" :value="branch.id">{{ branch.name }}</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Date</label>
                                <input type="date" v-model="form.date" class="pos-input w-full" required>
                            </div>
                        </div>

                        <!-- Items Table -->
                        <div class="mb-6 bg-slate-50 dark:bg-slate-900/50 rounded-xl p-4 border border-slate-200 dark:border-slate-700">
                            <div class="flex justify-between items-center mb-4">
                                <h3 class="font-semibold text-slate-800 dark:text-white">Request Items</h3>
                                <button type="button" @click="addItem" class="text-xs bg-indigo-100 text-indigo-700 dark:bg-indigo-900/50 dark:text-indigo-400 px-3 py-1.5 rounded-lg font-bold hover:bg-indigo-200 dark:hover:bg-indigo-800 transition-colors">
                                    + Add Item
                                </button>
                            </div>
                            <table class="w-full text-left border-collapse">
                                <thead>
                                    <tr class="text-xs uppercase tracking-wider text-slate-500 dark:text-slate-400 border-b border-slate-200 dark:border-slate-700">
                                        <th class="pb-3 w-1/2">Food Item</th>
                                        <th class="pb-3 w-1/6">Quantity</th>
                                        <th class="pb-3 w-1/6">Unit Price</th>
                                        <th class="pb-3 w-1/6">Subtotal</th>
                                        <th class="pb-3 w-8"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="(item, index) in form.items" :key="index" class="border-b border-slate-100 dark:border-slate-800 last:border-0">
                                        <td class="py-3 pr-3">
                                            <select v-model="item.item_id" class="pos-input w-full !py-1.5" required>
                                                <option value="" disabled>Select Item</option>
                                                <option v-for="fi in items" :key="fi.id" :value="fi.id">{{ fi.name }}</option>
                                            </select>
                                        </td>
                                        <td class="py-3 pr-3">
                                            <input type="number" step="0.01" v-model="item.quantity" class="pos-input w-full !py-1.5 text-center" required>
                                        </td>
                                        <td class="py-3 pr-3">
                                            <input type="number" step="0.01" v-model="item.unit_price" class="pos-input w-full !py-1.5 text-right" required>
                                        </td>
                                        <td class="py-3 text-sm text-slate-700 dark:text-slate-300 font-semibold text-right">
                                            ${{ (item.quantity * item.unit_price).toFixed(2) }}
                                        </td>
                                        <td class="py-3 text-right">
                                            <button type="button" @click="removeItem(index)" class="text-red-400 hover:text-red-600 transition-colors">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                                    <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" />
                                                </svg>
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="mb-6">
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Notes</label>
                            <textarea v-model="form.notes" class="pos-input w-full" rows="2"></textarea>
                        </div>

                        <div class="flex justify-end gap-3 mt-8">
                            <button type="button" @click="isModalOpen = false" class="px-4 py-2 text-sm font-bold text-slate-600 dark:text-slate-400 hover:text-slate-800 dark:hover:text-white transition-colors">Cancel</button>
                            <button type="submit" class="pos-btn-primary !py-2 !px-4" :disabled="form.processing">
                                Submit Request
                            </button>
                        </div>
                    </form>
                </div>
            </div>

        </div>
    </div>
</template>
