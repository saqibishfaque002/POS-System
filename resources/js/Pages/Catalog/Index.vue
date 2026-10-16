<script setup>
import { ref, computed, watch } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import DataTable from '@/Components/DataTable.vue';
import InventorySidebar from '@/Components/InventorySidebar.vue';
import CatalogFormModal from './Components/CatalogFormModal.vue';

const props = defineProps({
    foodGroups: Array,
    foodItems: Array,
    propertyGroups: Array,
    propertyItems: Array,
    globalVariations: Array,
});

const activeTab = ref(new URLSearchParams(window.location.search).get('tab') || 'foodGroups');

watch(activeTab, (newTab) => {
    if (newTab && typeof window !== 'undefined') {
        const url = new URL(window.location.href);
        url.searchParams.set('tab', newTab);
        window.history.replaceState({}, '', url.toString());
    }
});

const tabs = [
    { id: 'foodGroups', label: 'Food Groups' },
    { id: 'foodItems', label: 'Food Items' },
    { id: 'propertyGroups', label: 'Property Groups' },
    { id: 'propertyItems', label: 'Property Items' },
    { id: 'globalVariations', label: 'Global Variations' },
];

const tabData = computed(() => {
    switch (activeTab.value) {
        case 'foodGroups': return props.foodGroups;
        case 'foodItems': return props.foodItems;
        case 'propertyGroups': return props.propertyGroups;
        case 'propertyItems': return props.propertyItems;
        case 'globalVariations': return props.globalVariations;
        default: return [];
    }
});

const columns = computed(() => {
    switch (activeTab.value) {
        case 'foodGroups': return [
            { key: 'id', label: 'ID' },
            { key: 'name', label: 'Name' },
            { key: 'description', label: 'Description' },
            { key: 'is_active', label: 'Active' },
        ];
        case 'foodItems': return [
            { key: 'id', label: 'ID' },
            { key: 'food_group_id', label: 'Group' },
            { key: 'name', label: 'Name' },
            { key: 'base_price', label: 'Base Price' },
            { key: 'is_active', label: 'Active' },
        ];
        case 'propertyGroups': return [
            { key: 'id', label: 'ID' },
            { key: 'name', label: 'Name' },
            { key: 'is_multiple', label: 'Is Multiple' },
            { key: 'is_active', label: 'Active' },
        ];
        case 'propertyItems': return [
            { key: 'id', label: 'ID' },
            { key: 'property_group_id', label: 'Group' },
            { key: 'name', label: 'Name' },
            { key: 'extra_price', label: 'Extra Price' },
            { key: 'is_active', label: 'Active' },
        ];
        case 'globalVariations': return [
            { key: 'id', label: 'ID' },
            { key: 'name', label: 'Name' },
            { key: 'is_active', label: 'Active' },
        ];
        default: return [];
    }
});

const isModalOpen = ref(false);
const editingItem = ref(null);
let form = useForm({});

const formFields = computed(() => {
    switch (activeTab.value) {
        case 'foodGroups': return [
            { key: 'name', label: 'Name', type: 'text' },
            { key: 'description', label: 'Description', type: 'text', required: false },
            { key: 'is_active', label: 'Active', type: 'checkbox' },
        ];
        case 'foodItems': return [
            { key: 'food_group_id', label: 'Food Group', type: 'select', options: props.foodGroups.map(g => ({value: g.id, label: g.name})) },
            { key: 'name', label: 'Name', type: 'text' },
            { key: 'has_variations', label: 'Item Has Variations?', type: 'checkbox' },
            { key: 'base_price', label: 'Base Price', type: 'number', showIf: (form) => !form.has_variations },
            { key: 'custom_variations', label: 'Variations', type: 'custom_variations', showIf: (form) => form.has_variations },
            { key: 'description', label: 'Description', type: 'text', required: false },
            { key: 'is_active', label: 'Active', type: 'checkbox' },
        ];
        case 'propertyGroups': return [
            { key: 'name', label: 'Name', type: 'text' },
            { key: 'is_multiple', label: 'Multiple Choice', type: 'checkbox' },
            { key: 'is_active', label: 'Active', type: 'checkbox' },
        ];
        case 'propertyItems': return [
            { key: 'property_group_id', label: 'Property Group', type: 'select', options: props.propertyGroups.map(g => ({value: g.id, label: g.name})) },
            { key: 'name', label: 'Name (e.g. Extra Sauce)', type: 'text' },
            { key: 'extra_price', label: 'Extra Price', type: 'number' },
            { key: 'is_active', label: 'Active', type: 'checkbox' },
        ];
        case 'globalVariations': return [
            { key: 'name', label: 'Name (e.g. Large)', type: 'text' },
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
            if (field.key === 'has_variations') {
                initialData[field.key] = item && item.variations && item.variations.length > 0;
            } else {
                initialData[field.key] = item ? !!item[field.key] : true;
            }
        } else if (field.type === 'custom_variations') {
            initialData['variations'] = item && item.variations && item.variations.length > 0 
                ? item.variations.map(v => ({ id: v.id, variation_id: v.variation_id, price: v.price, sku: v.sku })) 
                : [{ variation_id: '', price: 0, sku: '' }];
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
        case 'foodGroups': return 'food-groups';
        case 'foodItems': return 'food-items';
        case 'propertyGroups': return 'property-groups';
        case 'propertyItems': return 'property-items';
        case 'globalVariations': return 'variations';
    }
};

const submitForm = () => {
    const prefix = getRoutePrefix();
    if (editingItem.value) {
        form.put(route(`catalog.${prefix}.update`, editingItem.value.id), {
            onSuccess: () => closeModal(),
        });
    } else {
        form.post(route(`catalog.${prefix}.store`), {
            onSuccess: () => closeModal(),
        });
    }
};

const deleteItem = (item) => {
    if (confirm('Are you sure you want to delete this item?')) {
        const prefix = getRoutePrefix();
        router.delete(route(`catalog.${prefix}.destroy`, item.id));
    }
};
</script>

<template>
    <AppLayout title="Inventory Management">
        <template #sidebar>
            <InventorySidebar v-model:activeTab="activeTab" />
        </template>
        
        <DataTable :title="tabs.find(t => t.id === activeTab)?.label || 'Catalog'" :columns="columns" :data="tabData" @edit="openModal" @delete="deleteItem">
            <template #actions>
                <button @click="openModal()" class="pos-btn-primary !py-2 !px-4 !rounded-lg text-sm">
                    + Add New
                </button>
            </template>

            <template #cell-food_group_id="{item}">
                {{ item.food_group?.name || item.food_group_id }}
            </template>
            <template #cell-property_group_id="{item}">
                {{ item.property_group?.name || item.property_group_id }}
            </template>
            <template #cell-food_item_id="{item}">
                {{ item.food_item?.name || item.food_item_id }}
            </template>
            <template #cell-base_price="{item}">
                ${{ parseFloat(item.base_price || 0).toFixed(2) }}
            </template>
            <template #cell-extra_price="{item}">
                ${{ parseFloat(item.extra_price || 0).toFixed(2) }}
            </template>
            <template #cell-price="{item}">
                ${{ parseFloat(item.price || 0).toFixed(2) }}
            </template>
        </DataTable>

        <template #modals>
            <CatalogFormModal 
                :is-open="isModalOpen"
                :editing-item="editingItem"
                :active-tab="activeTab"
                :form-fields="formFields"
                :form="form"
                :tabs="tabs"
                :global-variations="globalVariations"
                @close="closeModal"
                @submit="submitForm"
            />
        </template>
    </AppLayout>
</template>
