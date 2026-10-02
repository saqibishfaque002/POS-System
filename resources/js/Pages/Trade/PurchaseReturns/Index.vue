<script setup>
import { ref, computed, watch } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import InventoryLayout from '@/Layouts/InventoryLayout.vue';
import DataTable from '@/Components/DataTable.vue';
import TradeDocumentFormModal from '../Components/TradeDocumentFormModal.vue';

const props = defineProps({
    purchaseReturns: Array,
    branches: Array,
    suppliers: Array,
    approvedPis: Array,
    items: Array,
    units: Array,
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
        { item_id: '', ref_qty: 0, quantity: 1, unit_id: '', unit_price: 0, discount_amount: 0, tax_amount: 0, _item_name: '' }
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
            let refQty = 0;
            if (piItems.length > 0) {
                const piItem = piItems.find(pii => pii.item_id === i.item_id);
                if (piItem) refQty = parseFloat(piItem.quantity);
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

// Total moved to Modal
</script>

<template>
    <InventoryLayout title="Purchase Returns" headerColorClass="border-t-rose-500">
        <div class="dashboard-card p-6 border-t-4 border-t-rose-500">
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
        
        <template #modals>
            <TradeDocumentFormModal
                :is-open="isModalOpen"
                :is-editing="!!editingPr"
                title="Purchase Return"
                :form="form"
                :branches="branches"
                :suppliers="suppliers"
                :items="items"
                :units="units"
                :has-supplier="true"
                :has-reference="true"
                :reference-options="filteredPis"
                reference-label="PI"
                v-model:isReferenceLinked="isPiLinked"
                :has-date="true"
                date-label="Return Date"
                :has-global-adjustments="true"
                :has-item-discount-percent="true"
                :has-item-tax-percent="true"
                :has-item-unit-price="true"
                :has-reference-qty="true"
                reference-qty-label="PI Qty"
                theme-class="border-t-4 border-t-orange-500"
                @close="isModalOpen = false"
                @submit="submitForm"
                @reference-select="onPiSelect"
                @item-select="onItemSelect"
            />
        </template>
    </InventoryLayout>
</template>
