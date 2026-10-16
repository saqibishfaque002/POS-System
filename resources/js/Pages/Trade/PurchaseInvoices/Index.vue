<script setup>
import { ref, computed, watch } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import InventoryLayout from '@/Layouts/InventoryLayout.vue';
import DataTable from '@/Components/DataTable.vue';
import TradeDocumentFormModal from '../Components/TradeDocumentFormModal.vue';

const props = defineProps({
    purchaseInvoices: Array,
    branches: Array,
    suppliers: Array,
    approvedGrns: Array,
    items: Array,
    units: Array,
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
    <InventoryLayout title="Purchase Invoices">
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
            <template #row-actions="{ item }">
                <button v-if="item.status === 'draft'" @click="openModal(item)" class="text-indigo-600 hover:text-indigo-800 dark:text-[#06b6d4] dark:hover:text-cyan-300 mr-3 text-xs font-bold transition-colors">Edit</button>
                <button v-if="item.status === 'draft'" @click="changeStatus(item, 'approved')" class="text-blue-600 hover:text-blue-800 mr-3 text-xs font-bold transition-colors">Approve</button>
                <button v-if="item.status === 'approved'" @click="changeStatus(item, 'paid')" class="text-green-600 hover:text-green-800 mr-3 text-xs font-bold transition-colors">Mark Paid</button>
                <button v-if="['draft', 'approved'].includes(item.status)" @click="changeStatus(item, 'cancelled')" class="text-red-600 hover:text-red-800 mr-3 text-xs font-bold transition-colors">Cancel</button>
                <button v-if="item.status === 'draft'" @click="deletePi(item)" class="text-red-500 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300 text-xs font-bold transition-colors mr-3">Delete</button>
                <a :href="route('trade.purchase-invoices.print', item.id)" target="_blank" class="text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-300 text-xs font-bold transition-colors">Print</a>
            </template>
        </DataTable>
        
        <template #modals>
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
                @close="isModalOpen = false"
                @submit="submitForm"
                @reference-select="onGrnSelect"
                @item-select="onItemSelect"
            />
        </template>
    </InventoryLayout>
</template>
