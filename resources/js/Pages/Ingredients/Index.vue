<script setup>
import { ref, computed, watch } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import DataTable from '@/Components/DataTable.vue';
import InventorySidebar from '@/Components/InventorySidebar.vue';
import IngredientsFormModal from './Components/IngredientsFormModal.vue';

const props = defineProps({
    ingredientUnits: Array,
    ingredientGroups: Array,
    ingredientItems: Array,
});

const activeTab = ref(new URLSearchParams(window.location.search).get('tab') || 'ingredientUnits');

watch(activeTab, (newTab) => {
    if (newTab && typeof window !== 'undefined') {
        const url = new URL(window.location.href);
        url.searchParams.set('tab', newTab);
        window.history.replaceState({}, '', url.toString());
    }
});

const tabs = [
    { id: 'ingredientUnits', label: 'Ingredient Units' },
    { id: 'ingredientGroups', label: 'Ingredient Groups' },
    { id: 'ingredientItems', label: 'Ingredient Items' },
];

const tabData = computed(() => {
    switch (activeTab.value) {
        case 'ingredientUnits': return props.ingredientUnits;
        case 'ingredientGroups': return props.ingredientGroups;
        case 'ingredientItems': return props.ingredientItems;
        default: return [];
    }
});

const columns = computed(() => {
    switch (activeTab.value) {
        case 'ingredientUnits': return [
            { key: 'id', label: 'ID' },
            { key: 'name', label: 'Name' },
            { key: 'short_code', label: 'Short Code' },
            { key: 'is_active', label: 'Active' },
        ];
        case 'ingredientGroups': return [
            { key: 'id', label: 'ID' },
            { key: 'name', label: 'Name' },
            { key: 'description', label: 'Description' },
            { key: 'is_active', label: 'Active' },
        ];
        case 'ingredientItems': return [
            { key: 'id', label: 'ID' },
            { key: 'ingredient_group_id', label: 'Group' },
            { key: 'name', label: 'Name' },
            { key: 'purchase_unit_id', label: 'Purchase Unit' },
            { key: 'conversion_qty', label: 'Conver. Qty' },
            { key: 'consumption_unit_id', label: 'Consumption Unit' },
            { key: 'consumption_qty', label: 'Consmp. Qty' },
            { key: 'reorder_level', label: 'Reorder Level' },
            { key: 'base_price', label: 'Base Price' },
            { key: 'is_active', label: 'Active' },
        ];
        default: return [];
    }
});

// Modal State
const isModalOpen = ref(false);
const editingItem = ref(null);
let form = useForm({});

const formFields = computed(() => {
    switch (activeTab.value) {
        case 'ingredientUnits': return [
            { key: 'name', label: 'Unit Name', type: 'text' },
            { key: 'short_code', label: 'Short Code (e.g. Kg, Pcs)', type: 'text', required: false },
            { key: 'is_active', label: 'Active', type: 'checkbox' },
        ];
        case 'ingredientGroups': return [
            { key: 'name', label: 'Group Name', type: 'text' },
            { key: 'description', label: 'Description', type: 'text', required: false },
            { key: 'is_active', label: 'Active', type: 'checkbox' },
        ];
        case 'ingredientItems': return [
            { key: 'ingredient_group_id', label: 'Ingredient Group', type: 'select', options: props.ingredientGroups.map(g => ({value: g.id, label: g.name})) },
            { key: 'name', label: 'Item Name', type: 'text' },
            { key: 'purchase_unit_id', label: 'Purchase Unit', type: 'select', options: props.ingredientUnits.map(u => ({value: u.id, label: u.name})) },
            { key: 'conversion_qty', label: 'Conver. Qty', type: 'number' },
            { key: 'consumption_unit_id', label: 'Consumption Unit', type: 'select', options: props.ingredientUnits.map(u => ({value: u.id, label: u.name})) },
            { key: 'consumption_qty', label: 'Consmp. Qty', type: 'number' },
            { key: 'reorder_level', label: 'Reorder Level (Consumption Unit)', type: 'number' },
            { key: 'base_price', label: 'Base Price', type: 'number' },
            { key: 'description', label: 'Description', type: 'text', required: false },
            { key: 'is_active', label: 'Active', type: 'checkbox' },
        ];
        default: return [];
    }
});

const openModal = (item = null) => {
    editingItem.value = item;
    const initialData = {};
    formFields.value.forEach(field => {
        if (field.type === 'checkbox') {
            initialData[field.key] = item ? !!item[field.key] : true;
        } else if (field.key === 'conversion_qty' || field.key === 'consumption_qty') {
            initialData[field.key] = item ? item[field.key] : 1;
        } else if (field.key === 'reorder_level') {
            initialData[field.key] = item ? item[field.key] : 0;
        } else {
            initialData[field.key] = item ? item[field.key] : '';
        }
    });
    form = useForm(initialData);
    isModalOpen.value = true;
};

const closeModal = () => {
    isModalOpen.value = false;
    form.reset();
};

const getRoutePrefix = () => {
    switch(activeTab.value) {
        case 'ingredientUnits': return 'ingredient-units';
        case 'ingredientGroups': return 'ingredient-groups';
        case 'ingredientItems': return 'ingredient-items';
    }
};

const submitForm = () => {
    const prefix = getRoutePrefix();
    if (editingItem.value) {
        form.put(route(`ingredients.${prefix}.update`, editingItem.value.id), {
            onSuccess: () => closeModal(),
        });
    } else {
        form.post(route(`ingredients.${prefix}.store`), {
            onSuccess: () => closeModal(),
        });
    }
};

const deleteItem = (item) => {
    if (confirm('Are you sure you want to delete this item?')) {
        const prefix = getRoutePrefix();
        router.delete(route(`ingredients.${prefix}.destroy`, item.id));
    }
};
</script>

<template>
    <AppLayout title="Ingredients Management">
        <template #sidebar>
            <InventorySidebar v-model:activeTab="activeTab" />
        </template>
        
        <DataTable :title="tabs.find(t => t.id === activeTab)?.label" :columns="columns" :data="tabData" @edit="openModal" @delete="deleteItem">
            <template #actions>
                <button @click="openModal()" class="pos-btn-primary !py-2 !px-4 !rounded-lg text-sm">
                    + Add New
                </button>
            </template>

            <template #cell-ingredient_group_id="{item}">
                {{ item.group?.name || item.ingredient_group_id }}
            </template>
            <template #cell-purchase_unit_id="{item}">
                {{ item.purchase_unit?.name || item.purchase_unit_id }}
            </template>
            <template #cell-consumption_unit_id="{item}">
                {{ item.consumption_unit?.name || item.consumption_unit_id }}
            </template>
            <template #cell-base_price="{item}">
                ${{ parseFloat(item.base_price).toFixed(2) }}
            </template>
        </DataTable>

        <template #modals>
            <IngredientsFormModal 
                :is-open="isModalOpen"
                :editing-item="editingItem"
                :active-tab="activeTab"
                :form-fields="formFields"
                :form="form"
                :tabs="tabs"
                @close="closeModal"
                @submit="submitForm"
            />
        </template>
    </AppLayout>
</template>
