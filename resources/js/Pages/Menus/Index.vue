<script setup>
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import InventorySidebar from '@/Components/InventorySidebar.vue';
import DataTable from '@/Components/DataTable.vue';
import MenuFormModal from './MenuFormModal.vue';

const props = defineProps({
    menus: Array,
    branches: Array,
    foodItems: Array,
});

const isFormModalOpen = ref(false);
const editingMenu = ref(null);

const openCreateModal = () => {
    editingMenu.value = null;
    isFormModalOpen.value = true;
};

const openEditModal = (menu) => {
    editingMenu.value = menu;
    isFormModalOpen.value = true;
};

const deleteMenu = (id) => {
    if (confirm('Are you sure you want to delete this menu?')) {
        router.delete(route('catalog.menus.destroy', id), {
            preserveScroll: true,
        });
    }
};

const columns = [
    { key: 'name', label: 'Menu Name' },
    { key: 'description', label: 'Description' },
    { key: 'is_active', label: 'Status' },
    { key: 'tax_percentage', label: 'Tax %' },
    { key: 'discount_percentage', label: 'Discount %' },
    { key: 'branches', label: 'Assigned Branches' }
];
</script>

<template>
    <AppLayout title="Menu Management" headerColorClass="border-t-indigo-500">
        <template #sidebar>
            <InventorySidebar />
        </template>

        <div class="dashboard-card p-6 border-t-4 border-t-indigo-500">
            <DataTable 
                title="Menus"
                :data="menus" 
                :columns="columns"
                searchPlaceholder="Search menus..."
            >
                <template #actions>
                    <button @click="openCreateModal" class="pos-btn-primary !py-2 !px-4 !rounded-lg text-sm">
                        + Add New
                    </button>
                </template>
                
                <template #cell-is_active="{item}">
                    <span v-if="item.is_active" class="px-2 py-1 bg-emerald-100 text-emerald-700 rounded-md text-xs font-bold">Active</span>
                    <span v-else class="px-2 py-1 bg-rose-100 text-rose-700 rounded-md text-xs font-bold">Inactive</span>
                </template>
                <template #cell-tax_percentage="{item}">
                    {{ item.tax_percentage }}%
                </template>
                <template #cell-discount_percentage="{item}">
                    {{ item.discount_percentage }}%
                </template>
                <template #cell-branches="{item}">
                    <template v-if="!item.branches || item.branches.length === 0">
                        <span class="text-slate-400 italic text-xs">Global / None</span>
                    </template>
                    <template v-else>
                        <span v-for="b in item.branches" :key="b.id" class="px-2 py-0.5 mr-1 bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 rounded text-xs">
                            {{ b.name }}
                        </span>
                    </template>
                </template>

                <template #row-actions="{ item }">
                    <div class="flex items-center gap-2 justify-end">
                        <button @click="openEditModal(item)" class="p-2 text-emerald-500 hover:bg-emerald-50 dark:hover:bg-emerald-900/30 rounded-lg transition-colors tooltip-trigger" title="Edit Menu Settings">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                        </button>
                        <button @click="deleteMenu(item.id)" class="p-2 text-rose-500 hover:bg-rose-50 dark:hover:bg-rose-900/30 rounded-lg transition-colors tooltip-trigger" title="Delete Menu">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                        </button>
                    </div>
                </template>
            </DataTable>
        </div>

        <template #modals>
            <MenuFormModal
                :show="isFormModalOpen"
                :menu="editingMenu"
                :branches="branches"
                :foodItems="foodItems"
                :is-editing="!!editingMenu"
                @close="isFormModalOpen = false"
            />
        </template>
    </AppLayout>
</template>
