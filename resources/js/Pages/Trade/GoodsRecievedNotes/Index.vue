<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { Head, useForm, Link, router } from '@inertiajs/vue3';
import DataTable from '@/Components/DataTable.vue';
import InventorySidebar from '@/Components/InventorySidebar.vue';

const props = defineProps({
    goodsRecievedNotes: Array,
    branches: Array,
    suppliers: Array,
    items: Array,
    units: Array,
    approvedPos: Array,
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
const editingGrn = ref(null);
const isPoLinked = ref(false);

const form = useForm({
    branch_id: '',
    supplier_id: '',
    reference_id: '',
    date: new Date().toISOString().split('T')[0],
    notes: '',
    items: [
        { item_id: '', po_qty: 0, quantity: 1, unit_id: '', unit_price: 0, tax_percent: 0, tax_amount: 0 }
    ]
});

const addItem = () => {
    form.items.push({ item_id: '', po_qty: 0, quantity: 1, unit_id: '', unit_price: 0, tax_percent: 0, tax_amount: 0 });
};

const removeItem = (index) => {
    form.items.splice(index, 1);
};

const filteredPos = computed(() => {
    if (!form.branch_id) return [];
    return props.approvedPos.filter(pr => pr.branch_id === form.branch_id);
});

watch(isPoLinked, (val) => {
    if (!val) {
        form.reference_id = '';
        // Clear PR quantities if unlinked
        form.items.forEach(item => {
            item.po_qty = 0;
        });
    }
});

const onPrSelect = () => {
    if (form.reference_id) {
        const pr = props.approvedPos.find(p => p.id === form.reference_id);
        if (pr && pr.items) {
            form.items = pr.items.map(i => {
                const ingredientItem = props.items.find(ing => ing.id === i.item_id);
                return {
                    item_id: i.item_id,
                    po_qty: parseFloat(i.quantity),
                    quantity: parseFloat(i.quantity),
                    unit_id: i.unit_id,
                    unit_price: ingredientItem ? parseFloat(ingredientItem.base_price) || 0 : 0,
                    tax_percent: 0,
                    tax_amount: 0
                };
            });
        }
    } else {
        form.items = [{ item_id: '', po_qty: 0, quantity: 1, unit_id: '', unit_price: 0, tax_percent: 0, tax_amount: 0 }];
    }
};

const calculateTaxAndTotal = (item) => {
    const qty = parseFloat(item.quantity) || 0;
    const rate = parseFloat(item.unit_price) || 0;
    const taxPercent = parseFloat(item.tax_percent) || 0;
    
    const amount = qty * rate;
    const taxAmount = amount * (taxPercent / 100);
    
    item.tax_amount = taxAmount;
};

const getConversionText = (item) => {
    if (!item.item_id || !item.unit_id) return '-';
    const ingredient = props.items.find(i => i.id === item.item_id);
    const selectedUnit = props.units.find(u => u.id === item.unit_id);

    if (ingredient && ingredient.consumption_unit && selectedUnit) {
        let qty = 1;
        if (selectedUnit.id === ingredient.purchase_unit_id) {
            qty = parseFloat(ingredient.conversion_qty || 1);
        } else if (selectedUnit.id === ingredient.consumption_unit_id) {
            qty = 1;
        } else {
            qty = parseFloat(ingredient.conversion_qty || 1);
        }
        
        const selName = selectedUnit.short_code || selectedUnit.name;
        const conName = ingredient.consumption_unit.short_code || ingredient.consumption_unit.name;
        return `1 ${selName} = ${qty} ${conName}`;
    }
    return '-';
};

const openModal = (po = null) => {
    if (po) {
        editingGrn.value = po;
        form.clearErrors();
        form.branch_id = po.branch_id;
        form.supplier_id = po.supplier_id;
        form.reference_id = po.reference_id || '';
        isPoLinked.value = !!po.reference_id;
        form.date = po.date;
        form.notes = po.notes || '';
        
        let prItems = [];
        if (po.reference_id) {
            const pr = props.approvedPos.find(p => p.id === po.reference_id);
            if (pr) prItems = pr.items;
        }

        form.items = po.items.map(i => {
            let poQty = 0;
            if (prItems.length > 0) {
                const prItem = prItems.find(pi => pi.item_id === i.item_id);
                if (prItem) poQty = parseFloat(prItem.quantity);
            }
            return {
                item_id: i.item_id,
                po_qty: poQty,
                quantity: parseFloat(i.quantity),
                unit_id: i.unit_id,
                unit_price: parseFloat(i.unit_price),
                tax_percent: parseFloat(i.tax_percent),
                tax_amount: parseFloat(i.tax_amount)
            };
        });
    } else {
        editingGrn.value = null;
        form.clearErrors();
        form.reset();
        isPoLinked.value = false;
        form.branch_id = '';
        form.supplier_id = '';
        form.reference_id = '';
        form.date = new Date().toISOString().split('T')[0];
        form.notes = '';
        form.items = [{ item_id: '', po_qty: 0, quantity: 1, unit_id: '', unit_price: 0, tax_percent: 0, tax_amount: 0 }];
    }
    isModalOpen.value = true;
};

const submitForm = () => {
    if (editingGrn.value) {
        form.put(route('trade.good-received-notes.update', editingGrn.value.id), {
            onSuccess: () => {
                isModalOpen.value = false;
                editingGrn.value = null;
            }
        });
    } else {
        form.post(route('trade.good-received-notes.store'), {
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
        calculateTaxAndTotal(item);
    }
};

const changeStatus = (grn, status) => {
    if (confirm(`Are you sure you want to change status to ${status.replace('_', ' ').toUpperCase()}?`)) {
        router.patch(route('trade.good-received-notes.status', grn.id), { status }, {
            preserveScroll: true
        });
    }
};

const deleteGrn = (grn) => {
    if (confirm('Are you sure you want to delete this GRN?')) {
        router.delete(route('trade.good-received-notes.destroy', grn.id), {
            preserveScroll: true
        });
    }
};

const columns = computed(() => [
    { key: 'document_number', label: 'GRN Number' },
    { key: 'branch_id', label: 'Branch' },
    { key: 'supplier_id', label: 'Supplier' },
    { key: 'date', label: 'Date' },
    { key: 'total_amount', label: 'Total Amount' },
    { key: 'status', label: 'Status' }
]);

const formTotal = computed(() => {
    return form.items.reduce((sum, item) => {
        const qty = parseFloat(item.quantity) || 0;
        const rate = parseFloat(item.unit_price) || 0;
        const tax = parseFloat(item.tax_amount) || 0;
        return sum + (qty * rate) + tax;
    }, 0);
});
</script>

<template>
    <Head title="Good Revieved Notes" />

    <div class="min-h-screen flex flex-col transition-colors duration-300 bg-slate-50 dark:bg-slate-900">
        <div class="w-full mx-auto px-4 sm:px-6 lg:px-8 mt-6">
            <header class="dashboard-card px-6 py-4 flex justify-between items-center mb-6">
                <div class="flex items-center gap-4">
                    <h1 class="text-xl font-semibold text-indigo-500 dark:text-[#06b6d4]">Good Revieved Notes</h1>
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
                        <DataTable title="Good Revieved Notes" :data="goodsRecievedNotes" :columns="columns">
                            <template #actions>
                                <button @click="openModal()" class="pos-btn-primary !py-2 !px-4 !rounded-lg text-sm">
                                    + Create GRN
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
                            <template #cell-total_amount="{ item }">
                                <span class="font-bold text-slate-700 dark:text-slate-300">
                                    ${{ parseFloat(item.total_amount).toFixed(2) }}
                                </span>
                            </template>
                            <template #cell-status="{ item }">
                                <span class="px-2 py-1 rounded-full text-xs font-bold"
                                      :class="{
                                        'bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-300': item.status === 'draft',
                                        'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/50 dark:text-indigo-400': item.status === 'issued',
                                        'bg-green-100 text-green-700 dark:bg-green-900/50 dark:text-green-400': item.status === 'completed',
                                        'bg-red-100 text-red-700 dark:bg-red-900/50 dark:text-red-400': item.status === 'cancelled'
                                      }">
                                    {{ item.status.replace('_', ' ').toUpperCase() }}
                                </span>
                            </template>
                            <template #row-actions="{ item }">
                                <button v-if="item.status === 'draft'" @click="openModal(item)" class="text-indigo-600 hover:text-indigo-800 dark:text-[#06b6d4] dark:hover:text-cyan-300 mr-3 text-xs font-bold transition-colors">Edit</button>
                                <button v-if="item.status === 'draft'" @click="changeStatus(item, 'issued')" class="text-blue-600 hover:text-blue-800 mr-3 text-xs font-bold transition-colors">Issue</button>
                                <button v-if="item.status === 'issued'" @click="changeStatus(item, 'completed')" class="text-green-600 hover:text-green-800 mr-3 text-xs font-bold transition-colors">Complete</button>
                                <button v-if="item.status === 'issued'" @click="changeStatus(item, 'cancelled')" class="text-red-600 hover:text-red-800 mr-3 text-xs font-bold transition-colors">Cancel</button>
                                <button v-if="item.status === 'draft'" @click="deleteGrn(item)" class="text-red-500 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300 text-xs font-bold transition-colors mr-3">Delete</button>
                                <a :href="route('trade.good-received-notes.print', item.id)" target="_blank" class="text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-300 text-xs font-bold transition-colors">Print</a>
                            </template>
                        </DataTable>
                    </div>
                </main>
            </div>
            
            <!-- Modal Form -->
            <div v-if="isModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-sm">
                <div class="dashboard-card w-full max-w-[95vw] lg:max-w-7xl p-6 max-h-[90vh] overflow-y-auto">
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="text-xl font-bold text-slate-800 dark:text-white">{{ editingGrn ? 'Edit' : 'New' }} Good Revieved Notes</h2>
                        <div class="flex items-center gap-3">
                            <span class="text-sm font-medium text-slate-700 dark:text-slate-300">Link PO?</span>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" v-model="isPoLinked" class="sr-only peer">
                                <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-indigo-300 dark:peer-focus:ring-indigo-800 rounded-full peer dark:bg-slate-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-slate-600 peer-checked:bg-indigo-600"></div>
                                <span class="ml-2 text-sm font-medium text-slate-900 dark:text-slate-300">{{ isPoLinked ? 'Yes' : 'No' }}</span>
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
                            
                            <div v-if="isPoLinked">
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Select PO</label>
                                <select v-model="form.reference_id" @change="onPrSelect" class="pos-input w-full" :disabled="!form.branch_id">
                                    <option value="">-- Select PO --</option>
                                    <option v-for="po in filteredPos" :key="po.id" :value="po.id">{{ po.document_number }}</option>
                                </select>
                                <p v-if="!form.branch_id" class="text-xs text-orange-500 mt-1">Select branch first</p>
                            </div>
                            

                            <!-- Date (Disabled) -->
                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Date</label>
                                <input type="date" v-model="form.date" class="pos-input w-full disabled:opacity-50 disabled:cursor-not-allowed" disabled required>
                            </div>

                            <!-- Delivery Date -->
                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Manual Invoice No</label>
                                <input type="text" v-model="form.manual_invoice_number" class="pos-input w-full">
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
                                <h3 class="font-semibold text-slate-800 dark:text-white">GRN Items</h3>
                                <button type="button" @click="addItem" class="text-xs bg-indigo-100 text-indigo-700 dark:bg-indigo-900/50 dark:text-indigo-400 px-3 py-1.5 rounded-lg font-bold hover:bg-indigo-200 dark:hover:bg-indigo-800 transition-colors">
                                    + Add Item
                                </button>
                            </div>
                            <div class="max-h-[350px] overflow-y-auto pr-2 custom-scrollbar">
                                <table class="w-full text-left border-collapse">
                                    <thead>
                                        <tr class="text-[10px] uppercase tracking-wider text-slate-500 dark:text-slate-400 border-b border-slate-200 dark:border-slate-700 sticky top-0 bg-slate-50 dark:bg-slate-900/50 backdrop-blur-sm z-10">
                                            <th class="pb-3 w-48 text-center">Ingredient Item</th>
                                            <th v-if="isPoLinked" class="pb-3 w-20 text-center">PO Qty</th>
                                            <th class="pb-3 w-24 text-center">GRN Qty</th>
                                            <th class="pb-3 w-24 text-center">Unit</th>
                                            <th class="pb-3 w-24 text-center">Rate</th>
                                            <th class="pb-3 w-24 text-center">Amount</th>
                                            <!-- <th class="pb-3 w-24 text-center">Tax %</th>
                                            <th class="pb-3 w-24 text-center">Tax Amt</th> -->
                                            <!-- <th class="pb-3 w-24 text-center">Total</th> -->
                                            <!-- <th class="pb-3 w-32 pl-3 text-center">Conv Unit</th> -->
                                            <th class="pb-3 w-8"></th>
                                        </tr>
                                    </thead>
                                <tbody>
                                    <tr v-for="(item, index) in form.items" :key="index" class="border-b border-slate-100 dark:border-slate-800 last:border-0">
                                        <td class="py-3 pr-2">
                                            <select v-model="item.item_id" @change="onItemSelect(item)" class="pos-input w-full !py-1.5 !px-2 text-sm" required>
                                                <option value="" disabled>Select Item</option>
                                                <option v-for="fi in items" :key="fi.id" :value="fi.id">{{ fi.name }}</option>
                                            </select>
                                        </td>
                                        <td v-if="isPoLinked" class="py-3 pr-2 text-center text-sm font-bold text-slate-500">
                                            {{ item.po_qty }}
                                        </td>
                                        <td class="py-3 pr-2">
                                            <input type="number" step="0.01" v-model="item.quantity" @input="calculateTaxAndTotal(item)" class="pos-input w-full !py-1.5 !px-2 text-right text-sm" required>
                                        </td>
                                        <td class="py-3 pr-2">
                                            <select v-model="item.unit_id" class="pos-input w-full !py-1.5 !px-2 text-sm" required>
                                                <option value="" disabled>Unit</option>
                                                <option v-for="unit in units" :key="unit.id" :value="unit.id">{{ unit.name }}</option>
                                            </select>
                                        </td>
                                        <td class="py-3 pr-2">
                                            <input type="number" step="0.01" v-model="item.unit_price" @input="calculateTaxAndTotal(item)" class="pos-input w-full !py-1.5 !px-2 text-right text-sm" required>
                                        </td>
                                        <td class="py-3 pr-2 text-sm text-slate-700 dark:text-slate-300 font-bold text-center">
                                            {{ ((parseFloat(item.quantity) || 0) * (parseFloat(item.unit_price) || 0)).toFixed(2) }}
                                        </td>
                                        <!-- <td class="py-3 pr-2">
                                            <input type="number" step="0.01" v-model="item.tax_percent" @input="calculateTaxAndTotal(item)" class="pos-input w-full !py-1.5 !px-2 text-right text-sm" required>
                                        </td>
                                        <td class="py-3 pr-2">
                                            <input type="number" step="0.01" :value="item.tax_amount" class="pos-input w-full !py-1.5 !px-2 text-right text-sm disabled:opacity-50" disabled>
                                        </td> -->
                                        <!-- <td class="py-3 pr-2 text-sm text-indigo-600 dark:text-[#06b6d4] font-black text-center">
                                            {{ (((parseFloat(item.quantity) || 0) * (parseFloat(item.unit_price) || 0)) + (parseFloat(item.tax_amount) || 0)).toFixed(2) }}
                                        </td> -->
                                        <!-- <td class="py-3 text-xs font-bold text-slate-500 dark:text-slate-400 pl-3 text-center">
                                            {{ getConversionText(item) }}
                                        </td> -->
                                        <td class="py-3 text-right">
                                            <button type="button" @click="removeItem(index)" class="text-red-400 hover:text-red-600 transition-colors">
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
                            <div class="w-full md:w-1/3 flex flex-col justify-end">
                                <div class="bg-slate-100 dark:bg-slate-800 p-4 rounded-xl border border-slate-200 dark:border-slate-700">
                                    <div class="flex justify-between items-center text-lg font-bold text-slate-800 dark:text-white">
                                        <span>Total Amount:</span>
                                        <span class="text-indigo-600 dark:text-[#06b6d4]">${{ formTotal.toFixed(2) }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="flex justify-end gap-3 mt-8">
                            <button type="button" @click="isModalOpen = false" class="pos-btn-secondary !px-6 mr-3">Cancel</button>
                            <button type="submit" class="pos-btn-primary !px-6" :disabled="form.processing">{{ editingGrn ? 'Update GRN' : 'Submit GRN' }}</button>
                        </div>
                    </form>
                </div>
            </div>

        </div>
    </div>
</template>
