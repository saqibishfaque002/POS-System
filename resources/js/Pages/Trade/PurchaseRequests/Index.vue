<script setup>
import { ref, computed, onMounted } from 'vue';
import { Head, useForm, Link, router } from '@inertiajs/vue3';
import DashboardLayout from '@/Pages/Dashboard.vue'; // We can just use a simple layout or rely on Dashboard
import DataTable from '@/Components/DataTable.vue';
import InventorySidebar from '@/Components/InventorySidebar.vue';

const props = defineProps({
    purchaseRequests: Array,
    branches: Array,
    items: Array,
    units: Array,
});

const isDark = ref(false);

const toggleTheme = () => {
    isDark.value = !isDark.value;
    if (isDark.value) {
        document.documentElement.classList.add('dark');
        localStorage.setItem('theme', 'dark');
    } else {
        document.documentElement.classList.remove('dark');
        localStorage.setItem('theme', 'light');
    }
};

onMounted(() => {
    if (localStorage.theme === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
        isDark.value = true;
        document.documentElement.classList.add('dark');
    } else {
        isDark.value = false;
        document.documentElement.classList.remove('dark');
    }
});

const isModalOpen = ref(false);
const form = useForm({
    branch_id: '',
    date: new Date().toISOString().split('T')[0],
    notes: '',
    items: [
        { item_id: '', quantity: 1, unit_id: '' }
    ]
});

const addItem = () => {
    form.items.push({ item_id: '', quantity: 1, unit_id: '' });
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

const onItemSelect = (item) => {
    const ingredientItem = props.items.find(i => i.id === item.item_id);
    if (ingredientItem) {
        item.unit_id = ingredientItem.purchase_unit_id;
    }
};

const changeStatus = (pr, status) => {
    if (confirm(`Are you sure you want to change status to ${status.replace('_', ' ').toUpperCase()}?`)) {
        router.patch(route('trade.purchase-requests.status', pr.id), { status }, {
            preserveScroll: true
        });
    }
};

const deletePr = (pr) => {
    if (confirm('Are you sure you want to delete this Purchase Request?')) {
        router.delete(route('trade.purchase-requests.destroy', pr.id), {
            preserveScroll: true
        });
    }
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
                
                <button @click="toggleTheme" class="p-2 rounded-full hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors">
                    <svg v-if="!isDark" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-slate-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                    </svg>
                    <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-[#06b6d4]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
                    </svg>
                </button>
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
                        <DataTable :data="purchaseRequests" :columns="columns">
                            <template #cell-status="{ item }">
                                <span class="px-2 py-1 rounded-full text-xs font-bold"
                                      :class="{
                                        'bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-300': item.status === 'draft',
                                        'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/50 dark:text-yellow-400': item.status === 'pending_approval',
                                        'bg-green-100 text-green-700 dark:bg-green-900/50 dark:text-green-400': item.status === 'approved',
                                        'bg-red-100 text-red-700 dark:bg-red-900/50 dark:text-red-400': item.status === 'rejected'
                                      }">
                                    {{ item.status.replace('_', ' ').toUpperCase() }}
                                </span>
                            </template>
                            <template #row-actions="{ item }">
                                <button v-if="item.status === 'draft'" @click="changeStatus(item, 'pending_approval')" class="text-yellow-600 hover:text-yellow-800 mr-3 text-xs font-bold transition-colors">Submit</button>
                                <button v-if="item.status === 'pending_approval'" @click="changeStatus(item, 'approved')" class="text-green-600 hover:text-green-800 mr-3 text-xs font-bold transition-colors">Approve</button>
                                <button v-if="item.status === 'pending_approval'" @click="changeStatus(item, 'rejected')" class="text-red-600 hover:text-red-800 mr-3 text-xs font-bold transition-colors">Reject</button>
                                <button @click="deletePr(item)" class="text-red-500 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300 text-xs font-bold transition-colors">Delete</button>
                            </template>
                        </DataTable>
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
                            <div class="max-h-[300px] overflow-y-auto pr-2 custom-scrollbar">
                                <table class="w-full text-left border-collapse">
                                    <thead>
                                        <tr class="text-xs uppercase tracking-wider text-slate-500 dark:text-slate-400 border-b border-slate-200 dark:border-slate-700 sticky top-0 bg-slate-50 dark:bg-slate-900/50 backdrop-blur-sm z-10">
                                            <th class="pb-3 w-1/2">Ingredient Item</th>
                                            <th class="pb-3 w-1/4">Quantity</th>
                                            <th class="pb-3 w-1/4">Unit</th>
                                            <th class="pb-3 w-8"></th>
                                        </tr>
                                    </thead>
                                <tbody>
                                    <tr v-for="(item, index) in form.items" :key="index" class="border-b border-slate-100 dark:border-slate-800 last:border-0">
                                        <td class="py-3 pr-3">
                                            <select v-model="item.item_id" @change="onItemSelect(item)" class="pos-input w-full !py-1.5" required>
                                                <option value="" disabled>Select Item</option>
                                                <option v-for="fi in items" :key="fi.id" :value="fi.id">{{ fi.name }}</option>
                                            </select>
                                        </td>
                                        <td class="py-3 pr-3">
                                            <input type="number" step="0.01" v-model="item.quantity" class="pos-input w-full !py-1.5 text-center" required>
                                        </td>
                                        <td class="py-3 pr-3">
                                            <select v-model="item.unit_id" class="pos-input w-full !py-1.5" required>
                                                <option value="" disabled>Select Unit</option>
                                                <option v-for="unit in units" :key="unit.id" :value="unit.id">{{ unit.name }}</option>
                                            </select>
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
