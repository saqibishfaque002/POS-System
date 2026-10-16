<script setup>
import { ref, computed, watch } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import InventoryLayout from '@/Layouts/InventoryLayout.vue';
import DataTable from '@/Components/DataTable.vue';
import TradeDocumentFormModal from '../Components/TradeDocumentFormModal.vue';

const props = defineProps({
    goodsRecievedNotes: Array,
    branches: Array,
    suppliers: Array,
    items: Array,
    units: Array,
    approvedPos: Array,
});


const isModalOpen = ref(false);
const editingGrn = ref(null);
const isPoLinked = ref(false);

const form = useForm({
    branch_id: '',
    supplier_id: '',
    reference_id: '',
    date: new Date().toISOString().split('T')[0],
    manual_invoice_number: '',
    notes: '',
    items: [
        { item_id: '', ref_qty: 0, quantity: 1, unit_id: '', unit_price: 0, tax_percent: 0, tax_amount: 0 }
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
            item.ref_qty = 0;
        });
    }
});

const onPrSelect = () => {
    if (form.reference_id) {
        const pr = props.approvedPos.find(p => p.id === form.reference_id);
        if (pr && pr.items) {
            form.supplier_id = pr.supplier_id || '';
            form.items = pr.items.map(i => {
                const ingredientItem = props.items.find(ing => ing.id === i.item_id);
                return {
                    item_id: i.item_id,
                    ref_qty: parseFloat(i.quantity),
                    quantity: parseFloat(i.quantity),
                    unit_id: i.unit_id,
                    unit_price: ingredientItem ? parseFloat(ingredientItem.base_price) || 0 : 0,
                    tax_percent: 0,
                    tax_amount: 0
                };
            });
        }
    } else {
        form.items = [{ item_id: '', ref_qty: 0, quantity: 1, unit_id: '', unit_price: 0, tax_percent: 0, tax_amount: 0 }];
    }
};

// Conversions moved to Modal

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
        form.manual_invoice_number = po.manual_invoice_number || '';
        
        let prItems = [];
        if (po.reference_id) {
            const pr = props.approvedPos.find(p => p.id === po.reference_id);
            if (pr) prItems = pr.items;
        }

        form.items = po.items.map(i => {
            let refQty = 0;
            if (prItems.length > 0) {
                const prItem = prItems.find(pi => pi.item_id === i.item_id);
                if (prItem) refQty = parseFloat(prItem.quantity);
            }
            return {
                item_id: i.item_id,
                ref_qty: refQty,
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
        form.manual_invoice_number = '';
        form.notes = '';
        form.items = [{ item_id: '', ref_qty: 0, quantity: 1, unit_id: '', unit_price: 0, tax_percent: 0, tax_amount: 0 }];
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

// Total logic moved to Modal
</script>

<template>
    <InventoryLayout title="Good Received Notes">
        <DataTable title="Good Received Notes" :data="goodsRecievedNotes" :columns="columns">
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
            <template #row-actions="{ item }">
                <button v-if="item.status === 'draft'" @click="openModal(item)" class="text-indigo-600 hover:text-indigo-800 dark:text-[#06b6d4] dark:hover:text-cyan-300 mr-3 text-xs font-bold transition-colors">Edit</button>
                <button v-if="item.status === 'draft'" @click="changeStatus(item, 'issued')" class="text-blue-600 hover:text-blue-800 mr-3 text-xs font-bold transition-colors">Issue</button>
                <button v-if="item.status === 'issued'" @click="changeStatus(item, 'completed')" class="text-green-600 hover:text-green-800 mr-3 text-xs font-bold transition-colors">Complete</button>
                <button v-if="item.status === 'issued'" @click="changeStatus(item, 'cancelled')" class="text-red-600 hover:text-red-800 mr-3 text-xs font-bold transition-colors">Cancel</button>
                <button v-if="item.status === 'draft'" @click="deleteGrn(item)" class="text-red-500 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300 text-xs font-bold transition-colors mr-3">Delete</button>
                <a :href="route('trade.good-received-notes.print', item.id)" target="_blank" class="text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-300 text-xs font-bold transition-colors">Print</a>
            </template>
        </DataTable>
        
        <template #modals>
            <TradeDocumentFormModal
                :is-open="isModalOpen"
                :is-editing="!!editingGrn"
                title="Good Received Note"
                :form="form"
                :branches="branches"
                :suppliers="suppliers"
                :items="items"
                :units="units"
                :has-supplier="true"
                :has-reference="true"
                :reference-options="filteredPos"
                reference-label="PO"
                v-model:isReferenceLinked="isPoLinked"
                :has-date="true"
                date-label="Date"
                :has-manual-invoice-number="true"
                :has-item-unit-price="true"
                :has-reference-qty="true"
                reference-qty-label="PO Qty"
                @close="isModalOpen = false"
                @submit="submitForm"
                @reference-select="onPrSelect"
                @item-select="onItemSelect"
            />
        </template>
    </InventoryLayout>
</template>
