<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { Head, useForm, Link, router } from '@inertiajs/vue3';
import DataTable from '@/Components/DataTable.vue';
import InventorySidebar from '@/Components/InventorySidebar.vue';
import TradeDocumentFormModal from '../Components/TradeDocumentFormModal.vue';

const props = defineProps({
    purchaseInvoices: Array,
    branches: Array,
    suppliers: Array,
    approvedGrns: Array,
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
const editingPi = ref(null);
const isGrnLinked = ref(false);

const form = useForm({
    branch_id: '',
    supplier_id: '',
    reference_id: '',
    date: new Date().toISOString().split('T')[0],
    due_date: new Date().toISOString().split('T')[0],
    notes: '',
    discount_amount: 0,
    tax_amount: 0,
    shipping_amount: 0,
    items: [
        { item_id: '', ref_qty: 0, quantity: 1, unit_id: '', unit_price: 0, discount_amount: 0, tax_amount: 0, _item_name: '' }
    ]
});

const addItem = () => {
    form.items.push({ item_id: '', grn_qty: 0, quantity: 1, unit_id: '', unit_price: 0, discount_amount: 0, tax_amount: 0, _item_name: '' });
};

const removeItem = (index) => {
    form.items.splice(index, 1);
};

const filteredGrns = computed(() => {
    if (!form.branch_id) return [];
    return props.approvedGrns.filter(grn => grn.branch_id === form.branch_id);
});

watch(isGrnLinked, (val) => {
    if (!val) {
        form.reference_id = '';
        form.items = [{ item_id: '', grn_qty: 0, quantity: 1, unit_id: '', unit_price: 0, discount_amount: 0, tax_amount: 0, _item_name: '' }];
    }
});

const onGrnSelect = () => {
    if (form.reference_id) {
        const grn = props.approvedGrns.find(g => g.id === form.reference_id);
        if (grn && grn.items) {
            form.supplier_id = grn.supplier_id || '';
            form.items = grn.items.map(i => {
                return {
                    item_id: i.item_id,
                    ref_qty: parseFloat(i.quantity),
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
        form.items = [{ item_id: '', ref_qty: 0, quantity: 1, unit_id: '', unit_price: 0, discount_amount: 0, tax_amount: 0, _item_name: '' }];
    }
};

const openModal = (pi = null) => {
    if (pi) {
        editingPi.value = pi;
        form.clearErrors();
        form.branch_id = pi.branch_id;
        form.supplier_id = pi.supplier_id;
        form.reference_id = pi.reference_id || '';
        isGrnLinked.value = !!pi.reference_id;
        form.date = pi.date;
        form.due_date = pi.due_date || '';
        form.notes = pi.notes || '';
        form.discount_amount = parseFloat(pi.discount_amount) || 0;
        form.tax_amount = parseFloat(pi.tax_amount) || 0;
        form.shipping_amount = parseFloat(pi.shipping_amount) || 0;
        
        let grnItems = [];
        if (pi.reference_id) {
            const grn = props.approvedGrns.find(g => g.id === pi.reference_id);
            if (grn) grnItems = grn.items;
        }

        form.items = pi.items.map(i => {
            let refQty = 0;
            if (grnItems.length > 0) {
                const grItem = grnItems.find(gi => gi.item_id === i.item_id);
                if (grItem) refQty = parseFloat(grItem.quantity);
            }
            return {
                item_id: i.item_id,
                ref_qty: refQty,
                quantity: parseFloat(i.quantity),
                unit_id: i.unit_id,
                unit_price: parseFloat(i.unit_price),
                discount_amount: parseFloat(i.discount_amount),
                tax_amount: parseFloat(i.tax_amount),
                _item_name: i.item ? i.item.name : 'Unknown Item'
            };
        });
    } else {
        editingPi.value = null;
        form.clearErrors();
        form.reset();
        isGrnLinked.value = false; // Default to manual entry
        form.branch_id = '';
        form.supplier_id = '';
        form.reference_id = '';
        form.date = new Date().toISOString().split('T')[0];
        form.due_date = new Date().toISOString().split('T')[0];
        form.notes = '';
        form.discount_amount = 0;
        form.tax_amount = 0;
        form.shipping_amount = 0;
        form.items = [{ item_id: '', grn_qty: 0, quantity: 1, unit_id: '', unit_price: 0, discount_amount: 0, tax_amount: 0, _item_name: '' }];
    }
    isModalOpen.value = true;
};

const submitForm = () => {
    if (editingPi.value) {
        form.put(route('trade.purchase-invoices.update', editingPi.value.id), {
            onSuccess: () => {
                isModalOpen.value = false;
                editingPi.value = null;
            }
        });
    } else {
        form.post(route('trade.purchase-invoices.store'), {
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

const changeStatus = (pi, status) => {
    if (confirm(`Are you sure you want to change status to ${status.replace('_', ' ').toUpperCase()}?`)) {
        router.patch(route('trade.purchase-invoices.status', pi.id), { status }, {
            preserveScroll: true
        });
    }
};

const deletePi = (pi) => {
    if (confirm('Are you sure you want to delete this PI?')) {
        router.delete(route('trade.purchase-invoices.destroy', pi.id), {
            preserveScroll: true
        });
    }
};

const columns = computed(() => [
    { key: 'document_number', label: 'PI Number' },
    { key: 'reference_id', label: 'GRN Reference' },
    { key: 'branch_id', label: 'Branch' },
    { key: 'supplier_id', label: 'Supplier' },
    { key: 'date', label: 'Invoice Date' },
    { key: 'due_date', label: 'Due Date' },
    { key: 'total_amount', label: 'Total Amount' },
    { key: 'status', label: 'Status' }
]);

// Total computed property moved to TradeDocumentFormModal
</script>

<template>
    <Head title="Purchase Invoices" />

    <div class="min-h-screen flex flex-col transition-colors duration-300 bg-slate-50 dark:bg-slate-900">
        <div class="w-full mx-auto px-4 sm:px-6 lg:px-8 mt-6">
            <header class="dashboard-card px-6 py-4 flex justify-between items-center mb-6 border-t-4 border-t-cyan-500">
                <div class="flex items-center gap-4">
                    <h1 class="text-xl font-semibold text-indigo-500 dark:text-[#06b6d4]">Purchase Invoices</h1>
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
                    <div class="dashboard-card p-6 border-t-4 border-t-cyan-500">
                        <DataTable title="Purchase Invoices" :data="purchaseInvoices" :columns="columns">
                            <template #actions>
                                <button @click="openModal()" class="pos-btn-primary !py-2 !px-4 !rounded-lg text-sm">
                                    + Create PI
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
                                <span v-if="item.good_received_note" class="font-bold text-slate-700 dark:text-slate-300">
                                    {{ item.good_received_note.document_number }}
                                </span>
                                <span v-else class="text-slate-400 text-xs italic">Unlinked</span>
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
                                        'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/50 dark:text-indigo-400': item.status === 'approved',
                                        'bg-green-100 text-green-700 dark:bg-green-900/50 dark:text-green-400': item.status === 'paid',
                                        'bg-red-100 text-red-700 dark:bg-red-900/50 dark:text-red-400': item.status === 'cancelled'
                                      }">
                                    {{ item.status.replace('_', ' ').toUpperCase() }}
                                </span>
                            </template>
                            <template #row-actions="{ item }">
                                <button v-if="item.status === 'draft'" @click="openModal(item)" class="text-indigo-600 hover:text-indigo-800 dark:text-[#06b6d4] dark:hover:text-cyan-300 mr-3 text-xs font-bold transition-colors">Edit</button>
                                <button v-if="item.status === 'draft'" @click="changeStatus(item, 'approved')" class="text-blue-600 hover:text-blue-800 mr-3 text-xs font-bold transition-colors">Approve</button>
                                <button v-if="item.status === 'approved'" @click="changeStatus(item, 'paid')" class="text-green-600 hover:text-green-800 mr-3 text-xs font-bold transition-colors">Mark Paid</button>
                                <button v-if="['draft', 'approved'].includes(item.status)" @click="changeStatus(item, 'cancelled')" class="text-red-600 hover:text-red-800 mr-3 text-xs font-bold transition-colors">Cancel</button>
                                <button v-if="item.status === 'draft'" @click="deletePi(item)" class="text-red-500 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300 text-xs font-bold transition-colors mr-3">Delete</button>
                                <a :href="route('trade.purchase-invoices.print', item.id)" target="_blank" class="text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-300 text-xs font-bold transition-colors">Print</a>
                            </template>
                        </DataTable>
                    </div>
                </main>
            </div>
            
            <TradeDocumentFormModal
                :is-open="isModalOpen"
                :is-editing="!!editingPi"
                title="Purchase Invoice"
                :form="form"
                :branches="branches"
                :suppliers="suppliers"
                :items="items"
                :units="units"
                :has-supplier="true"
                :has-reference="true"
                :reference-options="filteredGrns"
                reference-label="GRN"
                v-model:isReferenceLinked="isGrnLinked"
                :has-date="true"
                date-label="Invoice Date"
                :has-due-date="true"
                :has-global-adjustments="true"
                :has-item-discount-percent="true"
                :has-item-tax-percent="true"
                :has-item-unit-price="true"
                :has-reference-qty="true"
                reference-qty-label="GRN Qty"
                theme-class="border-t-4 border-t-cyan-500"
                @close="isModalOpen = false"
                @submit="submitForm"
                @reference-select="onGrnSelect"
                @item-select="onItemSelect"
            />

        </div>
    </div>
</template>
