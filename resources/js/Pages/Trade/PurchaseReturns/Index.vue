<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { Head, useForm, Link, router } from '@inertiajs/vue3';
import DataTable from '@/Components/DataTable.vue';
import InventorySidebar from '@/Components/InventorySidebar.vue';

const props = defineProps({
    purchaseReturns: Array,
    branches: Array,
    suppliers: Array,
    approvedPis: Array,
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
const editingPr = ref(null);
const isPiLinked = ref(false);

const form = useForm({
    branch_id: '',
    supplier_id: '',
    reference_id: '',
    date: new Date().toISOString().split('T')[0],
    notes: '',
    discount_amount: 0,
    tax_amount: 0,
    shipping_amount: 0,
    items: [
        { item_id: '', pi_qty: 0, quantity: 1, unit_id: '', unit_price: 0, discount_amount: 0, tax_amount: 0, _item_name: '' }
    ]
});

const addItem = () => {
    form.items.push({ item_id: '', pi_qty: 0, quantity: 1, unit_id: '', unit_price: 0, discount_amount: 0, tax_amount: 0, _item_name: '' });
};

const removeItem = (index) => {
    form.items.splice(index, 1);
};

const filteredPis = computed(() => {
    if (!form.branch_id) return [];
    return props.approvedPis.filter(pi => pi.branch_id === form.branch_id);
});

watch(isPiLinked, (val) => {
    if (!val) {
        form.reference_id = '';
        form.items = [{ item_id: '', pi_qty: 0, quantity: 1, unit_id: '', unit_price: 0, discount_amount: 0, tax_amount: 0, _item_name: '' }];
    }
});

const onPiSelect = () => {
    if (form.reference_id) {
        const pi = props.approvedPis.find(p => p.id === form.reference_id);
        if (pi && pi.items) {
            form.supplier_id = pi.supplier_id || '';
            form.items = pi.items.map(i => {
                return {
                    item_id: i.item_id,
                    pi_qty: parseFloat(i.quantity),
                    quantity: parseFloat(i.quantity),
                    unit_id: i.unit_id,
                    unit_price: parseFloat(i.unit_price) || 0,
                    discount_amount: 0,
                    tax_amount: 0,
                    _item_name: i.item ? i.item.name : ''
                };
            });
        }
    } else {
        form.items = [{ item_id: '', pi_qty: 0, quantity: 1, unit_id: '', unit_price: 0, discount_amount: 0, tax_amount: 0, _item_name: '' }];
    }
};

const openModal = (pr = null) => {
    if (pr) {
        editingPr.value = pr;
        form.clearErrors();
        form.branch_id = pr.branch_id;
        form.supplier_id = pr.supplier_id;
        form.reference_id = pr.reference_id || '';
        isPiLinked.value = !!pr.reference_id;
        form.date = pr.date;
        form.notes = pr.notes || '';
        form.discount_amount = parseFloat(pr.discount_amount) || 0;
        form.tax_amount = parseFloat(pr.tax_amount) || 0;
        form.shipping_amount = parseFloat(pr.shipping_amount) || 0;
        
        let piItems = [];
        if (pr.reference_id) {
            const pi = props.approvedPis.find(p => p.id === pr.reference_id);
            if (pi) piItems = pi.items;
        }

        form.items = pr.items.map(i => {
            let piQty = 0;
            if (piItems.length > 0) {
                const piItem = piItems.find(pii => pii.item_id === i.item_id);
                if (piItem) piQty = parseFloat(piItem.quantity);
            }
            return {
                item_id: i.item_id,
                pi_qty: piQty,
                quantity: parseFloat(i.quantity),
                unit_id: i.unit_id,
                unit_price: parseFloat(i.unit_price),
                discount_amount: parseFloat(i.discount_amount),
                tax_amount: parseFloat(i.tax_amount),
                _item_name: i.item ? i.item.name : 'Unknown Item'
            };
        });
    } else {
        editingPr.value = null;
        form.clearErrors();
        form.reset();
        isPiLinked.value = false; // Default to manual entry
        form.branch_id = '';
        form.supplier_id = '';
        form.reference_id = '';
        form.date = new Date().toISOString().split('T')[0];
        form.notes = '';
        form.discount_amount = 0;
        form.tax_amount = 0;
        form.shipping_amount = 0;
        form.items = [{ item_id: '', pi_qty: 0, quantity: 1, unit_id: '', unit_price: 0, discount_amount: 0, tax_amount: 0, _item_name: '' }];
    }
    isModalOpen.value = true;
};

const submitForm = () => {
    if (editingPr.value) {
        form.put(route('trade.purchase-returns.update', editingPr.value.id), {
            onSuccess: () => {
                isModalOpen.value = false;
                editingPr.value = null;
            }
        });
    } else {
        form.post(route('trade.purchase-returns.store'), {
            onSuccess: () => {
                isModalOpen.value = false;
            }
        });
    }
};

const onItemSelect = (item) => {
    const ingredientItem = props.items.find(i => i.id === item.item_id);
    if (ingredientItem) {
        item.unit_id = ingredientItem.purchase_unit_id;
        item.unit_price = parseFloat(ingredientItem.base_price) || 0;
    }
};

const changeStatus = (pr, status) => {
    let msg = `Are you sure you want to change status to ${status.replace('_', ' ').toUpperCase()}?`;
    if (status === 'completed') {
        msg += "\n\nWARNING: Marking as Completed will DEDUCT these items from the branch inventory.";
    }
    if (confirm(msg)) {
        router.patch(route('trade.purchase-returns.status', pr.id), { status }, {
            preserveScroll: true
        });
    }
};

const deletePr = (pr) => {
    if (confirm('Are you sure you want to delete this Purchase Return?')) {
        router.delete(route('trade.purchase-returns.destroy', pr.id), {
            preserveScroll: true
        });
    }
};

const columns = computed(() => [
    { key: 'document_number', label: 'PR Number' },
    { key: 'reference_id', label: 'PI Reference' },
    { key: 'branch_id', label: 'Branch' },
    { key: 'supplier_id', label: 'Supplier' },
    { key: 'date', label: 'Return Date' },
    { key: 'total_amount', label: 'Return Amount' },
    { key: 'status', label: 'Status' }
]);

const formTotal = computed(() => {
    let subtotal = form.items.reduce((sum, item) => {
        const qty = parseFloat(item.quantity) || 0;
        const rate = parseFloat(item.unit_price) || 0;
        const disc = parseFloat(item.discount_amount) || 0;
        const tax = parseFloat(item.tax_amount) || 0;
        return sum + (qty * rate) - disc + tax;
    }, 0);
    
    return subtotal - (parseFloat(form.discount_amount) || 0) + (parseFloat(form.tax_amount) || 0) + (parseFloat(form.shipping_amount) || 0);
});
</script>

<template>
    <Head title="Purchase Returns" />

    <div class="min-h-screen flex flex-col transition-colors duration-300 bg-slate-50 dark:bg-slate-900">
        <div class="w-full mx-auto px-4 sm:px-6 lg:px-8 mt-6">
            <header class="dashboard-card px-6 py-4 flex justify-between items-center mb-6">
                <div class="flex items-center gap-4">
                    <h1 class="text-xl font-semibold text-indigo-500 dark:text-[#06b6d4]">Purchase Returns</h1>
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
                <InventorySidebar />

                <main class="flex-1">
                    <div class="dashboard-card p-6 border-0 shadow-none bg-transparent">
                        <DataTable title="Purchase Returns" :data="purchaseReturns" :columns="columns">
                            <template #actions>
                                <button @click="openModal()" class="pos-btn-primary !py-2 !px-4 !rounded-lg text-sm">
                                    + Create Return
                                </button>
                            </template>
                            <template #cell-branch_id="{ item }">
                                {{ item.branch?.name }}
                            </template>
                            <template #cell-supplier_id="{ item }">
                                <span v-if="item.supplier" class="font-bold text-slate-700 dark:text-slate-300">
                                    {{ item.supplier.name }}
                                </span>
                            </template>
                            <template #cell-reference_id="{ item }">
                                <span v-if="item.purchase_invoice" class="font-bold text-slate-700 dark:text-slate-300">
                                    {{ item.purchase_invoice.document_number }}
                                </span>
                                <span v-else class="text-slate-400 text-xs italic">Unlinked</span>
                            </template>
                            <template #cell-total_amount="{ item }">
                                <span class="font-bold text-red-600 dark:text-red-400">
                                    -${{ parseFloat(item.total_amount).toFixed(2) }}
                                </span>
                            </template>
                            <template #cell-status="{ item }">
                                <span class="px-2 py-1 rounded-full text-xs font-bold"
                                      :class="{
                                        'bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-300': item.status === 'draft',
                                        'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/50 dark:text-indigo-400': item.status === 'approved',
                                        'bg-green-100 text-green-700 dark:bg-green-900/50 dark:text-green-400': item.status === 'completed',
                                        'bg-red-100 text-red-700 dark:bg-red-900/50 dark:text-red-400': item.status === 'cancelled'
                                      }">
                                    {{ item.status.replace('_', ' ').toUpperCase() }}
                                </span>
                            </template>
                            <template #row-actions="{ item }">
                                <button v-if="item.status === 'draft'" @click="openModal(item)" class="text-indigo-600 hover:text-indigo-800 dark:text-[#06b6d4] dark:hover:text-cyan-300 mr-3 text-xs font-bold transition-colors">Edit</button>
                                <button v-if="item.status === 'draft'" @click="changeStatus(item, 'approved')" class="text-blue-600 hover:text-blue-800 mr-3 text-xs font-bold transition-colors">Approve</button>
                                <button v-if="item.status === 'approved'" @click="changeStatus(item, 'completed')" class="text-green-600 hover:text-green-800 mr-3 text-xs font-bold transition-colors">Complete Return</button>
                                <button v-if="['draft', 'approved'].includes(item.status)" @click="changeStatus(item, 'cancelled')" class="text-red-600 hover:text-red-800 mr-3 text-xs font-bold transition-colors">Cancel</button>
                                <button v-if="item.status === 'draft'" @click="deletePr(item)" class="text-red-500 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300 text-xs font-bold transition-colors mr-3">Delete</button>
                                <a :href="route('trade.purchase-returns.print', item.id)" target="_blank" class="text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-300 text-xs font-bold transition-colors">Print</a>
                            </template>
                        </DataTable>
                    </div>
                </main>
            </div>
            
            <!-- Modal Form -->
            <div v-if="isModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-sm">
                <div class="dashboard-card w-full max-w-[95vw] lg:max-w-7xl p-6 max-h-[90vh] overflow-y-auto">
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="text-xl font-bold text-slate-800 dark:text-white">{{ editingPr ? 'Edit' : 'New' }} Purchase Return</h2>
                        <div class="flex items-center gap-3">
                            <span class="text-sm font-medium text-slate-700 dark:text-slate-300">Link PI?</span>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" v-model="isPiLinked" :disabled="!!editingPr" class="sr-only peer">
                                <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-indigo-300 dark:peer-focus:ring-indigo-800 rounded-full peer dark:bg-slate-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-slate-600 peer-checked:bg-indigo-600"></div>
                                <span class="ml-2 text-sm font-medium text-slate-900 dark:text-slate-300">{{ isPiLinked ? 'Yes' : 'No' }}</span>
                            </label>
                        </div>
                    </div>
                    
                    <form @submit.prevent="submitForm" class="space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                            
                            <!-- Branch -->
                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Branch</label>
                                <select v-model="form.branch_id" class="pos-input w-full" required>
                                    <option value="" disabled>Select Branch</option>
                                    <option v-for="branch in branches" :key="branch.id" :value="branch.id">{{ branch.name }}</option>
                                </select>
                            </div>
                            
                            <div v-if="isPiLinked">
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Select PI Reference</label>
                                <select v-model="form.reference_id" @change="onPiSelect" class="pos-input w-full" :disabled="!form.branch_id" :required="isPiLinked">
                                    <option value="">-- Select PI --</option>
                                    <option v-for="pi in filteredPis" :key="pi.id" :value="pi.id">{{ pi.document_number }} - {{ pi.date }}</option>
                                </select>
                                <p v-if="!form.branch_id" class="text-xs text-orange-500 mt-1">Select branch first</p>
                            </div>
                            
                            <!-- Date (Return Date) -->
                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Return Date</label>
                                <input type="date" v-model="form.date" class="pos-input w-full" required>
                            </div>

                            <!-- Supplier -->
                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Supplier</label>
                                <select v-model="form.supplier_id" class="pos-input w-full" required>
                                    <option value="" disabled>Select Supplier</option>
                                    <option v-for="sup in suppliers" :key="sup.id" :value="sup.id">{{ sup.name }}</option>
                                </select>
                            </div>
                        </div>

                        <!-- Items Table -->
                        <div class="mb-6 bg-slate-50 dark:bg-slate-900/50 rounded-xl p-4 border border-slate-200 dark:border-slate-700">
                            <div class="flex justify-between items-center mb-4">
                                <h3 class="font-semibold text-slate-800 dark:text-white">Return Items {{ isPiLinked ? '(From PI)' : '' }}</h3>
                                <button v-if="!isPiLinked" type="button" @click="addItem" class="pos-btn-secondary !py-1 !px-3 text-xs">
                                    + Add Item
                                </button>
                            </div>
                            <div class="max-h-[350px] overflow-y-auto pr-2 custom-scrollbar">
                                <table class="w-full text-left border-collapse">
                                    <thead>
                                        <tr class="text-[10px] uppercase tracking-wider text-slate-500 dark:text-slate-400 border-b border-slate-200 dark:border-slate-700 sticky top-0 bg-slate-50 dark:bg-slate-900/50 backdrop-blur-sm z-10">
                                            <th class="pb-3 w-48 text-center">Ingredient Item</th>
                                            <th v-if="isPiLinked" class="pb-3 w-20 text-center">PI Qty</th>
                                            <th class="pb-3 w-24 text-center">Return Qty</th>
                                            <th class="pb-3 w-24 text-center">Unit</th>
                                            <th class="pb-3 w-24 text-center">Rate</th>
                                            <th class="pb-3 w-24 text-center">Disc ($)</th>
                                            <th class="pb-3 w-24 text-center">Tax ($)</th>
                                            <th class="pb-3 w-32 text-center">Amount</th>
                                            <th class="pb-3 w-8"></th>
                                        </tr>
                                    </thead>
                                <tbody>
                                    <tr v-if="isPiLinked && form.items.length === 0">
                                        <td colspan="7" class="py-6 text-center text-slate-500 italic">Select a PI first to load items.</td>
                                    </tr>
                                    <tr v-for="(item, index) in form.items" :key="index" class="border-b border-slate-100 dark:border-slate-800 last:border-0">
                                        <td class="py-3 pr-2">
                                            <select v-if="isPiLinked" v-model="item.item_id" class="pos-input w-full !py-1.5 !px-2 text-sm disabled:opacity-50" disabled required>
                                                <option v-for="i in items" :key="i.id" :value="i.id">{{ i.name }}</option>
                                            </select>
                                            <select v-else v-model="item.item_id" @change="onItemSelect(item)" class="pos-input w-full !py-1.5 !px-2 text-sm" required>
                                                <option value="" disabled>Select Item</option>
                                                <option v-for="i in items" :key="i.id" :value="i.id">{{ i.name }}</option>
                                            </select>
                                        </td>
                                        <td v-if="isPiLinked" class="py-3 pr-2 text-center text-sm font-bold text-slate-500">
                                            {{ item.pi_qty }}
                                        </td>
                                        <td class="py-3 pr-2">
                                            <input type="number" step="0.01" v-model="item.quantity" class="pos-input w-full !py-1.5 !px-2 text-right text-sm border-red-300 dark:border-red-900 focus:ring-red-500" required>
                                        </td>
                                        <td class="py-3 pr-2">
                                            <select v-model="item.unit_id" class="pos-input w-full !py-1.5 !px-2 text-sm" :disabled="isPiLinked" required>
                                                <option value="" disabled>Unit</option>
                                                <option v-for="u in units" :key="u.id" :value="u.id">{{ u.name }}</option>
                                            </select>
                                        </td>
                                        <td class="py-3 pr-2">
                                            <input type="number" step="0.01" v-model="item.unit_price" class="pos-input w-full !py-1.5 !px-2 text-right text-sm" required>
                                        </td>
                                        <td class="py-3 pr-2">
                                            <input type="number" step="0.01" v-model="item.discount_amount" class="pos-input w-full !py-1.5 !px-2 text-right text-sm text-red-500">
                                        </td>
                                        <td class="py-3 pr-2">
                                            <input type="number" step="0.01" v-model="item.tax_amount" class="pos-input w-full !py-1.5 !px-2 text-right text-sm text-indigo-500">
                                        </td>
                                        <td class="py-3 pr-2 text-sm text-slate-700 dark:text-slate-300 font-bold text-center">
                                            {{ (((parseFloat(item.quantity) || 0) * (parseFloat(item.unit_price) || 0)) - (parseFloat(item.discount_amount) || 0) + (parseFloat(item.tax_amount) || 0)).toFixed(2) }}
                                        </td>
                                        <td class="py-3 text-right">
                                            <button v-if="!isPiLinked" type="button" @click="removeItem(index)" class="text-red-400 hover:text-red-600 transition-colors">
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
                                    <span>Global Discount ($):</span>
                                    <input type="number" step="0.01" v-model="form.discount_amount" class="pos-input w-24 !py-1 !px-2 text-right text-sm text-red-500">
                                </div>
                                <div class="flex justify-between items-center text-sm font-bold text-slate-600 dark:text-slate-400">
                                    <span>Global Tax ($):</span>
                                    <input type="number" step="0.01" v-model="form.tax_amount" class="pos-input w-24 !py-1 !px-2 text-right text-sm text-indigo-500">
                                </div>
                                <div class="flex justify-between items-center text-sm font-bold text-slate-600 dark:text-slate-400">
                                    <span>Shipping ($):</span>
                                    <input type="number" step="0.01" v-model="form.shipping_amount" class="pos-input w-24 !py-1 !px-2 text-right text-sm text-indigo-500">
                                </div>
                                <div class="bg-red-50 dark:bg-red-900/20 p-4 rounded-xl border border-red-200 dark:border-red-800 mt-2">
                                    <div class="flex justify-between items-center text-lg font-black text-slate-800 dark:text-white">
                                        <span>Total Return Credit:</span>
                                        <span class="text-red-600 dark:text-red-400">${{ formTotal.toFixed(2) }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="flex justify-end gap-3 mt-8">
                            <button type="button" @click="isModalOpen = false" class="pos-btn-secondary !px-6 mr-3">Cancel</button>
                            <button type="submit" class="pos-btn-primary !px-6" :disabled="form.processing">{{ editingPr ? 'Update Return' : 'Submit Return' }}</button>
                        </div>
                    </form>
                </div>
            </div>

        </div>
    </div>
</template>
