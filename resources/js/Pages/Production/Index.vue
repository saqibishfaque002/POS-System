<script setup>
import { ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import DataTable from '@/Components/DataTable.vue';
import ProductionFormModal from './Components/ProductionFormModal.vue';
import CompleteProductionModal from './Components/CompleteProductionModal.vue';

const props = defineProps({
    productions: Array,
    recipes: Array,
    branches: Array,
});

const isModalOpen = ref(false);
const isCompleteModalOpen = ref(false);
const editingProduction = ref(null);
const productionToComplete = ref(null);

const columns = [
    { key: 'document_number', label: 'Document No.' },
    { key: 'branch', label: 'Branch', format: (val) => val?.name || 'N/A' },
    { key: 'recipe', label: 'Recipe/Product', format: (val) => val?.name || 'N/A' },
    { key: 'target_yield_qty', label: 'Target Qty' },
    { key: 'actual_yield_qty', label: 'Actual Qty', format: (val) => val ? val : '-' },
    { key: 'wastage_qty', label: 'Wastage', format: (val) => val > 0 ? `<span class="text-rose-500 font-bold">${val}</span>` : '-' },
    { key: 'total_cost', label: 'Total Cost', format: (val) => `$${parseFloat(val).toFixed(2)}` },
    { key: 'status', label: 'Status' }
];

const openCreateModal = () => {
    editingProduction.value = null;
    isModalOpen.value = true;
};

const editProduction = (production) => {
    editingProduction.value = production;
    isModalOpen.value = true;
};

const deleteProduction = (production) => {
    if (confirm('Are you sure you want to delete this production record?')) {
        router.delete(route('production.destroy', production.id));
    }
};

const markAsCompleted = (production) => {
    productionToComplete.value = production;
    isCompleteModalOpen.value = true;
};
</script>

<template>
    <Head title="Kitchen Production" />

    <AppLayout title="Kitchen Production" headerColorClass="border-t-indigo-500">
        <div class="dashboard-card p-6 border-t-4 border-t-indigo-500">
            <DataTable
                :data="productions"
                :columns="columns"
                title="Production Batches"
            >
                <template #actions>
                    <button @click="openCreateModal" class="pos-btn-primary !py-2 !px-4 !rounded-lg text-sm flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        New Production Batch
                    </button>
                </template>
                <template #cell-branch="{ item }">
                    {{ item.branch?.name || 'N/A' }}
                </template>
                <template #cell-recipe="{ item }">
                    <span class="font-bold text-slate-700 dark:text-slate-300">{{ item.recipe?.recipeable?.name || 'N/A' }}</span>
                </template>
                <template #cell-actual_yield_qty="{ item }">
                    {{ item.actual_yield_qty ? item.actual_yield_qty : '-' }}
                </template>
                <template #cell-wastage_qty="{ item }">
                    <span v-if="item.wastage_qty > 0" class="px-2 py-1 bg-rose-100 text-rose-700 dark:bg-rose-900/50 dark:text-rose-400 rounded-lg text-xs font-bold">{{ item.wastage_qty }}</span>
                    <span v-else class="text-slate-400">-</span>
                </template>
                <template #cell-total_cost="{ item }">
                    <span class="font-bold text-slate-700 dark:text-slate-300">${{ parseFloat(item.total_cost).toFixed(2) }}</span>
                </template>
                <template #cell-status="{ item }">
                    <span class="px-2 py-1 rounded-full text-xs font-bold"
                          :class="{
                            'bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-300': item.status === 'draft',
                            'bg-blue-100 text-blue-700 dark:bg-blue-900/50 dark:text-blue-400': item.status === 'in_progress',
                            'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-400': item.status === 'completed',
                            'bg-red-100 text-red-700 dark:bg-red-900/50 dark:text-red-400': item.status === 'cancelled'
                          }">
                        {{ item.status.replace('_', ' ').toUpperCase() }}
                    </span>
                </template>
                <template #row-actions="{ item }">
                    <button v-if="item.status === 'draft' || item.status === 'in_progress'" @click="markAsCompleted(item)" class="text-emerald-600 hover:text-emerald-800 dark:text-emerald-400 dark:hover:text-emerald-300 mr-3 text-xs font-bold transition-colors" title="Mark as Completed">Complete</button>
                    <button v-if="item.status !== 'completed'" @click="editProduction(item)" class="text-indigo-600 hover:text-indigo-800 dark:text-[#06b6d4] dark:hover:text-cyan-300 mr-3 text-xs font-bold transition-colors">Edit</button>
                    <button v-if="item.status !== 'completed'" @click="deleteProduction(item)" class="text-red-500 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300 text-xs font-bold transition-colors">Delete</button>
                </template>
            </DataTable>
        </div>

        <ProductionFormModal 
            :is-open="isModalOpen"
            :production="editingProduction"
            :recipes="recipes"
            :branches="branches"
            @close="isModalOpen = false"
            @submit="isModalOpen = false"
        />

        <CompleteProductionModal
            :is-open="isCompleteModalOpen"
            :production="productionToComplete"
            @close="isCompleteModalOpen = false"
            @completed="isCompleteModalOpen = false"
        />
    </AppLayout>
</template>
