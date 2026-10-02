<script setup>
import { ref, computed } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import InventoryLayout from '@/Layouts/InventoryLayout.vue';
import DataTable from '@/Components/DataTable.vue';
import TradeDocumentFormModal from '../Components/TradeDocumentFormModal.vue';

const props = defineProps({
    purchaseRequests: Array,
    branches: Array,
    items: Array,
    units: Array,
});


const isModalOpen = ref(false);
const editingPr = ref(null);

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

const openModal = (pr = null) => {
    if (pr) {
        editingPr.value = pr;
        form.branch_id = pr.branch_id;
        form.date = pr.date;
        form.notes = pr.notes || '';
        form.items = pr.items.map(i => ({
            item_id: i.item_id,
            quantity: parseFloat(i.quantity),
            unit_id: i.unit_id
        }));
    } else {
        editingPr.value = null;
        form.reset();
        form.branch_id = '';
        form.date = new Date().toISOString().split('T')[0];
        form.notes = '';
        form.items = [{ item_id: '', quantity: 1, unit_id: '' }];
    }
    isModalOpen.value = true;
};

const submitForm = () => {
    if (editingPr.value) {
        form.put(route('trade.purchase-requests.update', editingPr.value.id), {
            onSuccess: () => {
                isModalOpen.value = false;
                editingPr.value = null;
            }
        });
    } else {
        form.post(route('trade.purchase-requests.store'), {
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
    { key: 'branch_id', label: 'Branch' },
    { key: 'date', label: 'Date' },
    { key: 'status', label: 'Status' }
]);
</script>

<template>
    <InventoryLayout title="Purchase Requests" headerColorClass="border-t-yellow-500">
        <div class="dashboard-card p-6 border-t-4 border-t-yellow-500">
            <DataTable title="Purchase Requests" :data="purchaseRequests" :columns="columns">
                            <template #actions>
                                <button @click="openModal()" class="pos-btn-primary !py-2 !px-4 !rounded-lg text-sm">
                                    + Create PR
                                </button>
                            </template>
                            <template #cell-branch_id="{ item }">
                                {{ item.branch?.name }}
                            </template>
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
                                <button v-if="item.status === 'draft'" @click="openModal(item)" class="text-indigo-600 hover:text-indigo-800 dark:text-[#06b6d4] dark:hover:text-cyan-300 mr-3 text-xs font-bold transition-colors">Edit</button>
                                <button v-if="item.status === 'draft'" @click="changeStatus(item, 'pending_approval')" class="text-yellow-600 hover:text-yellow-800 mr-3 text-xs font-bold transition-colors">Submit</button>
                                <button v-if="item.status === 'pending_approval'" @click="changeStatus(item, 'approved')" class="text-green-600 hover:text-green-800 mr-3 text-xs font-bold transition-colors">Approve</button>
                                <button v-if="item.status === 'pending_approval'" @click="changeStatus(item, 'rejected')" class="text-red-600 hover:text-red-800 mr-3 text-xs font-bold transition-colors">Reject</button>
                                <button v-if="item.status === 'draft'" @click="deletePr(item)" class="text-red-500 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300 text-xs font-bold transition-colors mr-3">Delete</button>
                                <a :href="route('trade.purchase-requests.print', item.id)" target="_blank" class="text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-300 text-xs font-bold transition-colors">Print</a>
                            </template>
            </DataTable>
        </div>
        
        <template #modals>
            <TradeDocumentFormModal
                :is-open="isModalOpen"
                :is-editing="!!editingPr"
                title="Purchase Request"
                :form="form"
                :branches="branches"
                :items="items"
                :units="units"
                :has-date="true"
                date-label="Date"
                theme-class="border-t-4 border-t-yellow-500"
                @close="isModalOpen = false"
                @submit="submitForm"
                @item-select="onItemSelect"
            />
        </template>
    </InventoryLayout>
</template>
