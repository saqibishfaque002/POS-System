<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { Head, useForm, Link, router } from '@inertiajs/vue3';
import DataTable from '@/Components/DataTable.vue';
import InventorySidebar from '@/Components/InventorySidebar.vue';

const props = defineProps({
    saleReturns: Array,
    branches: Array,
    items: Array, // FoodItems
    paidSis: Array, // Paid Sale Invoices to link against
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
const editingSrtn = ref(null);
const isLinked = ref(false);

const form = useForm({
    branch_id: '',
    reference_id: '',
    customer_name: '',
    customer_phone: '',
    date: new Date().toISOString().split('T')[0],
    notes: '',
    discount_amount: 0,
    tax_amount: 0,
    shipping_amount: 0,
    items: [
        { item_id: '', quantity: 1, unit_price: 0, discount_amount: 0, discount_percent: 0, tax_amount: 0, tax_percent: 0, _item_name: '' }
    ]
});

const globalDiscountPercent = ref(0);
const globalTaxPercent = ref(0);

const addItem = () => {
    form.items.push({ item_id: '', quantity: 1, unit_price: 0, discount_amount: 0, discount_percent: 0, tax_amount: 0, tax_percent: 0, _item_name: '' });
};

const removeItem = (index) => {
    form.items.splice(index, 1);
};

const filteredSis = computed(() => {
    if (!form.branch_id) return [];
    return props.paidSis.filter(si => si.branch_id === form.branch_id);
});

const onSiSelect = () => {
    if (!form.reference_id) return;
    const si = props.paidSis.find(s => s.id === form.reference_id);
    if (si) {
        form.branch_id = si.branch_id;
        form.customer_name = si.customer_name || '';
        form.customer_phone = si.customer_phone || '';
        form.discount_amount = parseFloat(si.discount_amount) || 0;
        form.tax_amount = parseFloat(si.tax_amount) || 0;
        form.shipping_amount = parseFloat(si.shipping_amount) || 0;
        form.items = si.items.map(i => {
            const qty = parseFloat(i.quantity);
            const rate = parseFloat(i.unit_price);
            const discAmt = parseFloat(i.discount_amount) || 0;
            const taxAmt = parseFloat(i.tax_amount) || 0;
            
            const discPct = qty * rate > 0 ? (discAmt / (qty * rate) * 100) : 0;
            const subAfterDisc = (qty * rate) - discAmt;
            const taxPct = subAfterDisc > 0 ? (taxAmt / subAfterDisc * 100) : 0;
            
            return {
                item_id: i.item_id,
                quantity: qty,
                unit_price: rate,
                discount_amount: discAmt,
                discount_percent: parseFloat(discPct.toFixed(2)),
                tax_amount: taxAmt,
                tax_percent: parseFloat(taxPct.toFixed(2)),
                _item_name: i.item ? i.item.name : 'Unknown Item'
            };
        });
        
        setTimeout(() => {
            const sub = itemSubtotal.value;
            if (sub > 0) {
                globalDiscountPercent.value = parseFloat(((form.discount_amount / sub) * 100).toFixed(2));
                const subAfterDisc = sub - form.discount_amount;
                if (subAfterDisc > 0) {
                    globalTaxPercent.value = parseFloat(((form.tax_amount / subAfterDisc) * 100).toFixed(2));
                } else {
                    globalTaxPercent.value = 0;
                }
            } else {
                globalDiscountPercent.value = 0;
                globalTaxPercent.value = 0;
            }
        }, 50);
    }
};

watch(isLinked, (val) => {
    if (!val) {
        form.reference_id = '';
        form.items = [{ item_id: '', quantity: 1, unit_price: 0, discount_amount: 0, discount_percent: 0, tax_amount: 0, tax_percent: 0, _item_name: '' }];
    }
});

const openModal = (srtn = null) => {
    if (srtn) {
        editingSrtn.value = srtn;
        form.clearErrors();
        form.branch_id = srtn.branch_id;
        form.reference_id = srtn.reference_id || '';
        form.customer_name = srtn.customer_name || '';
        form.customer_phone = srtn.customer_phone || '';
        form.date = srtn.date;
        form.notes = srtn.notes || '';
        form.discount_amount = parseFloat(srtn.discount_amount) || 0;
        form.tax_amount = parseFloat(srtn.tax_amount) || 0;
        form.shipping_amount = parseFloat(srtn.shipping_amount) || 0;
        
        form.items = srtn.items.map(i => {
            const qty = parseFloat(i.quantity);
            const rate = parseFloat(i.unit_price);
            const discAmt = parseFloat(i.discount_amount) || 0;
            const taxAmt = parseFloat(i.tax_amount) || 0;
            
            const discPct = qty * rate > 0 ? (discAmt / (qty * rate) * 100) : 0;
            const subAfterDisc = (qty * rate) - discAmt;
            const taxPct = subAfterDisc > 0 ? (taxAmt / subAfterDisc * 100) : 0;
            
            return {
                item_id: i.item_id,
                quantity: qty,
                unit_price: rate,
                discount_amount: discAmt,
                discount_percent: parseFloat(discPct.toFixed(2)),
                tax_amount: taxAmt,
                tax_percent: parseFloat(taxPct.toFixed(2)),
                _item_name: i.item ? i.item.name : 'Unknown Item'
            };
        });
        isLinked.value = !!srtn.reference_id;
        
        setTimeout(() => {
            const sub = itemSubtotal.value;
            if (sub > 0) {
                globalDiscountPercent.value = parseFloat(((form.discount_amount / sub) * 100).toFixed(2));
                const subAfterDisc = sub - form.discount_amount;
                if (subAfterDisc > 0) {
                    globalTaxPercent.value = parseFloat(((form.tax_amount / subAfterDisc) * 100).toFixed(2));
                } else {
                    globalTaxPercent.value = 0;
                }
            } else {
                globalDiscountPercent.value = 0;
                globalTaxPercent.value = 0;
            }
        }, 50);
    } else {
        editingSrtn.value = null;
        form.clearErrors();
        form.reset();
        form.branch_id = '';
        form.reference_id = '';
        form.customer_name = '';
        form.customer_phone = '';
        form.date = new Date().toISOString().split('T')[0];
        form.notes = '';
        form.discount_amount = 0;
        form.tax_amount = 0;
        form.shipping_amount = 0;
        globalDiscountPercent.value = 0;
        globalTaxPercent.value = 0;
        form.items = [{ item_id: '', quantity: 1, unit_price: 0, discount_amount: 0, discount_percent: 0, tax_amount: 0, tax_percent: 0, _item_name: '' }];
        isLinked.value = false;
    }
    isModalOpen.value = true;
};

const submitForm = () => {
    if (editingSrtn.value) {
        form.put(route('trade.sale-returns.update', editingSrtn.value.id), {
            onSuccess: () => {
                isModalOpen.value = false;
                editingSrtn.value = null;
            }
        });
    } else {
        form.post(route('trade.sale-returns.store'), {
            onSuccess: () => {
                isModalOpen.value = false;
            }
        });
    }
};

const onItemSelect = (item) => {
    const foodItem = props.items.find(i => i.id === item.item_id);
    if (foodItem) {
        item.unit_price = parseFloat(foodItem.base_price) || 0;
    }
};

const changeStatus = (srtn, status) => {
    if (confirm(`Are you sure you want to change status to ${status.replace('_', ' ').toUpperCase()}?`)) {
        router.patch(route('trade.sale-returns.status', srtn.id), { status }, {
            preserveScroll: true
        });
    }
};

const deleteSrtn = (srtn) => {
    if (confirm('Are you sure you want to delete this Sale Return?')) {
        router.delete(route('trade.sale-returns.destroy', srtn.id), {
            preserveScroll: true
        });
    }
};

const columns = computed(() => [
    { key: 'document_number', label: 'SRTN Number' },
    { key: 'branch_id', label: 'Branch' },
    { key: 'reference_id', label: 'Linked SI' },
    { key: 'customer_name', label: 'Customer' },
    { key: 'date', label: 'Return Date' },
    { key: 'total_amount', label: 'Total Refund' },
    { key: 'status', label: 'Status' }
]);

const updateItemAmounts = (item) => {
    const qty = parseFloat(item.quantity) || 0;
    const price = parseFloat(item.unit_price) || 0;
    
    const dp = parseFloat(item.discount_percent) || 0;
    item.discount_amount = qty * price * (dp / 100);
    
    const tp = parseFloat(item.tax_percent) || 0;
    const sub = (qty * price) - item.discount_amount;
    item.tax_amount = sub * (tp / 100);
};

const itemSubtotal = computed(() => {
    return form.items.reduce((sum, item) => {
        const qty = parseFloat(item.quantity) || 0;
        const rate = parseFloat(item.unit_price) || 0;
        const disc = parseFloat(item.discount_amount) || 0;
        const tax = parseFloat(item.tax_amount) || 0;
        return sum + (qty * rate) - disc + tax;
    }, 0);
});

const updateGlobalAmounts = () => {
    const sub = itemSubtotal.value;
    
    const dp = parseFloat(globalDiscountPercent.value) || 0;
    form.discount_amount = parseFloat((sub * (dp / 100)).toFixed(2));
    
    const subAfterDiscount = sub - (parseFloat(form.discount_amount) || 0);
    const tp = parseFloat(globalTaxPercent.value) || 0;
    form.tax_amount = parseFloat((subAfterDiscount * (tp / 100)).toFixed(2));
};

watch(itemSubtotal, () => {
    updateGlobalAmounts();
});

const formTotal = computed(() => {
    return itemSubtotal.value - (parseFloat(form.discount_amount) || 0) + (parseFloat(form.tax_amount) || 0) + (parseFloat(form.shipping_amount) || 0);
});
</script>

<template>
    <Head title="Sale Returns" />

    <div class="min-h-screen flex flex-col transition-colors duration-300 bg-slate-50 dark:bg-slate-900">
        <div class="w-full mx-auto px-4 sm:px-6 lg:px-8 mt-6">
            <header class="dashboard-card px-6 py-4 flex justify-between items-center mb-6 border-t-4 border-t-rose-500">
                <div class="flex items-center gap-4">
                    <h1 class="text-xl font-semibold text-rose-500 dark:text-rose-400">Sale Returns</h1>
                    <div class="hidden md:flex items-center gap-3 ml-4 border-l pl-4 border-slate-200 dark:border-slate-700">
                        <Link :href="route('dashboard')" class="text-sm font-bold text-slate-600 hover:text-rose-600 dark:text-slate-400 dark:hover:text-rose-400 transition-colors flex items-center gap-1">
                            &larr; Back to Dashboard
                        </Link>
                    </div>
                </div>
                
                <button @click="toggleTheme" class="p-2 rounded-full hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors">
                    <svg v-if="!isDark" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-slate-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                    </svg>
                    <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-rose-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
                    </svg>
                </button>
            </header>

            <div class="flex flex-col md:flex-row gap-6">
                <InventorySidebar />

                <main class="flex-1">
                    <div class="dashboard-card p-6 border-t-4 border-t-rose-500">
                        <DataTable title="Sale Returns" :data="saleReturns" :columns="columns">
                            <template #actions>
                                <button @click="openModal()" class="pos-btn-primary !py-2 !px-4 !rounded-lg text-sm !bg-rose-600 hover:!bg-rose-700 dark:!bg-rose-600 dark:hover:!bg-rose-500">
                                    + Create Return
                                </button>
                            </template>
                            <template #cell-branch_id="{ item }">
                                {{ item.branch?.name }}
                            </template>
                            <template #cell-reference_id="{ item }">
                                <span v-if="item.sale_invoice" class="text-xs font-bold text-slate-500">
                                    {{ item.sale_invoice.document_number }}
                                </span>
                                <span v-else class="text-xs text-slate-400 italic">Unlinked</span>
                            </template>
                            <template #cell-customer_name="{ item }">
                                <div class="flex flex-col">
                                    <span class="font-bold text-slate-700 dark:text-slate-300">
                                        {{ item.customer_name || 'Walk-in Customer' }}
                                    </span>
                                    <span v-if="item.customer_phone" class="text-xs text-slate-500">
                                        {{ item.customer_phone }}
                                    </span>
                                </div>
                            </template>
                            <template #cell-total_amount="{ item }">
                                <span class="font-bold text-rose-600 dark:text-rose-400">
                                    ${{ parseFloat(item.total_amount).toFixed(2) }}
                                </span>
                            </template>
                            <template #cell-status="{ item }">
                                <span class="px-2 py-1 rounded-full text-xs font-bold"
                                      :class="{
                                        'bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-300': item.status === 'draft',
                                        'bg-blue-100 text-blue-700 dark:bg-blue-900/50 dark:text-blue-400': item.status === 'approved',
                                        'bg-rose-100 text-rose-700 dark:bg-rose-900/50 dark:text-rose-400': item.status === 'completed',
                                        'bg-red-100 text-red-700 dark:bg-red-900/50 dark:text-red-400': item.status === 'cancelled'
                                      }">
                                    {{ item.status.replace('_', ' ').toUpperCase() }}
                                </span>
                            </template>
                            <template #row-actions="{ item }">
                                <button v-if="item.status === 'draft'" @click="openModal(item)" class="text-rose-600 hover:text-rose-800 dark:text-rose-400 dark:hover:text-rose-300 mr-3 text-xs font-bold transition-colors">Edit</button>
                                <button v-if="item.status === 'draft'" @click="changeStatus(item, 'approved')" class="text-blue-600 hover:text-blue-800 mr-3 text-xs font-bold transition-colors">Approve</button>
                                <button v-if="item.status === 'approved'" @click="changeStatus(item, 'completed')" class="text-rose-600 hover:text-rose-800 mr-3 text-xs font-bold transition-colors">Complete</button>
                                <button v-if="['draft', 'approved'].includes(item.status)" @click="changeStatus(item, 'cancelled')" class="text-red-600 hover:text-red-800 mr-3 text-xs font-bold transition-colors">Cancel</button>
                                <button v-if="item.status === 'draft'" @click="deleteSrtn(item)" class="text-red-500 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300 text-xs font-bold transition-colors mr-3">Delete</button>
                                <a :href="route('trade.sale-returns.print', item.id)" target="_blank" class="text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-300 text-xs font-bold transition-colors">Print</a>
                            </template>
                        </DataTable>
                    </div>
                </main>
            </div>
            
            <!-- Modal Form -->
            <div v-if="isModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-sm">
                <div class="dashboard-card w-full max-w-[95vw] lg:max-w-7xl p-6 max-h-[90vh] overflow-y-auto border-t-4 border-t-rose-500">
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="text-xl font-bold text-slate-800 dark:text-white">{{ editingSrtn ? 'Edit' : 'New' }} Sale Return</h2>
                        <div class="flex items-center gap-3">
                            <span class="text-sm font-medium text-slate-700 dark:text-slate-300">Link SI?</span>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" v-model="isLinked" :disabled="!!editingSrtn" class="sr-only peer">
                                <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-rose-300 dark:peer-focus:ring-rose-800 rounded-full peer dark:bg-slate-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-slate-600 peer-checked:bg-rose-600"></div>
                                <span class="ml-2 text-sm font-medium text-slate-900 dark:text-slate-300">{{ isLinked ? 'Yes' : 'No' }}</span>
                            </label>
                        </div>
                    </div>
                    
                    <form @submit.prevent="submitForm" class="space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-5 gap-4 mb-6">
                            
                            <!-- Branch -->
                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Branch</label>
                                <select v-model="form.branch_id" class="pos-input w-full" required>
                                    <option value="" disabled>Select Branch</option>
                                    <option v-for="branch in branches" :key="branch.id" :value="branch.id">{{ branch.name }}</option>
                                </select>
                            </div>

                            <!-- Link SI -->
                            <div v-if="isLinked">
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Select Sale Invoice</label>
                                <select v-model="form.reference_id" @change="onSiSelect" class="pos-input w-full" :disabled="!form.branch_id" :required="isLinked">
                                    <option value="" disabled>Choose Invoice</option>
                                    <option v-for="si in filteredSis" :key="si.id" :value="si.id">{{ si.document_number }} - {{ si.date }}</option>
                                </select>
                                <p v-if="!form.branch_id" class="text-xs text-rose-500 mt-1">Select branch first</p>
                            </div>
                            
                            <!-- Date (Return Date) -->
                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Return Date</label>
                                <input type="date" v-model="form.date" class="pos-input w-full" required>
                            </div>

                            <!-- Customer Name -->
                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Customer Name (Optional)</label>
                                <input type="text" v-model="form.customer_name" class="pos-input w-full" placeholder="Walk-in Customer">
                            </div>

                            <!-- Customer Phone -->
                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Customer Phone (Optional)</label>
                                <input type="text" v-model="form.customer_phone" class="pos-input w-full" placeholder="+1234567890">
                            </div>
                        </div>

                        <!-- Items Table -->
                        <div class="mb-6 bg-slate-50 dark:bg-slate-900/50 rounded-xl p-4 border border-slate-200 dark:border-slate-700">
                            <div class="flex justify-between items-center mb-4">
                                <h3 class="font-semibold text-slate-800 dark:text-white">Return Items</h3>
                                <button v-if="!isLinked" type="button" @click="addItem" class="pos-btn-secondary !py-1 !px-3 text-xs">
                                    + Add Item
                                </button>
                            </div>
                            <div class="max-h-[350px] overflow-y-auto pr-2 custom-scrollbar">
                                <table class="w-full text-left border-collapse">
                                    <thead>
                                        <tr class="text-[10px] uppercase tracking-wider text-slate-500 dark:text-slate-400 border-b border-slate-200 dark:border-slate-700 sticky top-0 bg-slate-50 dark:bg-slate-900/50 backdrop-blur-sm z-10">
                                            <th class="pb-3 w-64 text-center">Food Item</th>
                                            <th class="pb-3 w-24 text-center">Qty</th>
                                            <th class="pb-3 w-32 text-center">Rate</th>
                                            <th class="pb-3 w-36 text-center">Disc (%)</th>
                                            <th class="pb-3 w-36 text-center">Tax (%)</th>
                                            <th class="pb-3 w-32 text-center">Amount</th>
                                            <th class="pb-3 w-8"></th>
                                        </tr>
                                    </thead>
                                <tbody>
                                    <tr v-for="(item, index) in form.items" :key="index" class="border-b border-slate-100 dark:border-slate-800 last:border-0">
                                        <td class="py-3 pr-2">
                                            <select v-if="isLinked" v-model="item.item_id" class="pos-input w-full !py-1.5 !px-2 text-sm disabled:opacity-50" disabled required>
                                                <option v-for="i in items" :key="i.id" :value="i.id">{{ i.name }}</option>
                                            </select>
                                            <select v-else v-model="item.item_id" @change="onItemSelect(item)" class="pos-input w-full !py-1.5 !px-2 text-sm" required>
                                                <option value="" disabled>Select Item</option>
                                                <option v-for="i in items" :key="i.id" :value="i.id">{{ i.name }}</option>
                                            </select>
                                        </td>
                                        <td class="py-3 pr-2">
                                            <input type="number" step="0.01" v-model="item.quantity" @input="updateItemAmounts(item)" class="pos-input w-full !py-1.5 !px-2 text-right text-sm" required>
                                        </td>
                                        <td class="py-3 pr-2">
                                            <input type="number" step="0.01" v-model="item.unit_price" @input="updateItemAmounts(item)" class="pos-input w-full !py-1.5 !px-2 text-right text-sm" required>
                                        </td>
                                        <td class="py-3 pr-2">
                                            <div class="flex items-center gap-1 border border-slate-200 dark:border-slate-700 rounded-md bg-white dark:bg-slate-800 px-2">
                                                <input type="number" step="0.01" v-model="item.discount_percent" @input="updateItemAmounts(item)" class="w-16 bg-transparent border-none text-right text-sm text-rose-500 focus:ring-0 p-1" placeholder="%">
                                                <span class="text-[10px] font-bold text-slate-400 border-l border-slate-200 dark:border-slate-700 pl-1 min-w-[35px] text-right">${{ (item.discount_amount || 0).toFixed(2) }}</span>
                                            </div>
                                        </td>
                                        <td class="py-3 pr-2">
                                            <div class="flex items-center gap-1 border border-slate-200 dark:border-slate-700 rounded-md bg-white dark:bg-slate-800 px-2">
                                                <input type="number" step="0.01" v-model="item.tax_percent" @input="updateItemAmounts(item)" class="w-16 bg-transparent border-none text-right text-sm text-emerald-500 focus:ring-0 p-1" placeholder="%">
                                                <span class="text-[10px] font-bold text-slate-400 border-l border-slate-200 dark:border-slate-700 pl-1 min-w-[35px] text-right">${{ (item.tax_amount || 0).toFixed(2) }}</span>
                                            </div>
                                        </td>
                                        <td class="py-3 pr-2 text-sm text-slate-700 dark:text-slate-300 font-bold text-center">
                                            {{ (((parseFloat(item.quantity) || 0) * (parseFloat(item.unit_price) || 0)) - (parseFloat(item.discount_amount) || 0) + (parseFloat(item.tax_amount) || 0)).toFixed(2) }}
                                        </td>
                                        <td class="py-3 text-right">
                                            <button v-if="!isLinked" type="button" @click="removeItem(index)" class="text-red-400 hover:text-red-600 transition-colors">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                                    <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" />
                                                </svg>
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                            </div>
                        </div>

                        <div class="flex flex-col md:flex-row justify-between gap-4">
                            <div class="w-full md:w-1/2">
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Notes</label>
                                <textarea v-model="form.notes" class="pos-input w-full" rows="3"></textarea>
                            </div>
                            <div class="w-full md:w-1/3 flex flex-col justify-end gap-2">
                                <div class="flex justify-between items-center text-sm font-bold text-slate-600 dark:text-slate-400">
                                    <span>Global Discount:</span>
                                    <div class="flex items-center gap-1 border border-slate-200 dark:border-slate-700 rounded-md bg-white dark:bg-slate-800 px-2 h-8">
                                        <input type="number" step="0.01" v-model="globalDiscountPercent" @input="updateGlobalAmounts" class="w-16 bg-transparent border-none text-right text-sm text-rose-500 focus:ring-0 p-0" placeholder="%">
                                        <span class="text-xs font-bold text-slate-400 border-l border-slate-200 dark:border-slate-700 pl-2 min-w-[50px] text-right">${{ (parseFloat(form.discount_amount) || 0).toFixed(2) }}</span>
                                    </div>
                                </div>
                                <div class="flex justify-between items-center text-sm font-bold text-slate-600 dark:text-slate-400">
                                    <span>Global Tax:</span>
                                    <div class="flex items-center gap-1 border border-slate-200 dark:border-slate-700 rounded-md bg-white dark:bg-slate-800 px-2 h-8">
                                        <input type="number" step="0.01" v-model="globalTaxPercent" @input="updateGlobalAmounts" class="w-16 bg-transparent border-none text-right text-sm text-emerald-500 focus:ring-0 p-0" placeholder="%">
                                        <span class="text-xs font-bold text-slate-400 border-l border-slate-200 dark:border-slate-700 pl-2 min-w-[50px] text-right">${{ (parseFloat(form.tax_amount) || 0).toFixed(2) }}</span>
                                    </div>
                                </div>
                                <div class="flex justify-between items-center text-sm font-bold text-slate-600 dark:text-slate-400">
                                    <span>Shipping ($):</span>
                                    <input type="number" step="0.01" v-model="form.shipping_amount" class="pos-input w-24 !py-1 !px-2 text-right text-sm text-emerald-500">
                                </div>
                                <div class="bg-rose-50 dark:bg-rose-900/20 p-4 rounded-xl border border-rose-200 dark:border-rose-800 mt-2">
                                    <div class="flex justify-between items-center text-lg font-black text-slate-800 dark:text-white">
                                        <span>Total Refund:</span>
                                        <span class="text-rose-600 dark:text-rose-400">${{ formTotal.toFixed(2) }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="flex justify-end gap-3 mt-8">
                            <button type="button" @click="isModalOpen = false" class="pos-btn-secondary !px-6 mr-3">Cancel</button>
                            <button type="submit" class="pos-btn-primary !px-6 !bg-rose-600 hover:!bg-rose-700 dark:!bg-rose-600 dark:hover:!bg-rose-500" :disabled="form.processing">{{ editingSrtn ? 'Update Return' : 'Submit Return' }}</button>
                        </div>
                    </form>
                </div>
            </div>

        </div>
    </div>
</template>
