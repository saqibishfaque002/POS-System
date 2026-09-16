<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import DataTable from '@/Components/DataTable.vue';
import InventorySidebar from '@/Components/InventorySidebar.vue';

const props = defineProps({
    ingredientUnits: Array,
    ingredientGroups: Array,
    ingredientItems: Array,
});

// Theme logic
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

const activeTab = ref(new URLSearchParams(window.location.search).get('tab') || 'ingredientUnits');

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
    <Head title="Ingredients Management" />
    <div class="min-h-screen flex flex-col transition-colors duration-300 bg-slate-50 dark:bg-slate-900">
        
        <div class="w-full mx-auto px-4 sm:px-6 lg:px-8 mt-6">
            <!-- Header -->
            <header class="dashboard-card px-6 py-4 flex justify-between items-center mb-6">
                <div class="flex items-center gap-4">
                    <h1 class="text-xl font-semibold text-indigo-500 dark:text-[#06b6d4]">Ingredients Management</h1>
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
                <!-- Sidebar Tabs -->
                <InventorySidebar v-model:activeTab="activeTab" />

                <!-- Content -->
                <main class="flex-1">
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
                        <template #cell-is_active="{item}">
                            <span :class="item.is_active ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-400 px-2.5 py-0.5 rounded-full text-xs font-bold' : 'bg-slate-100 text-slate-800 dark:bg-slate-700/50 dark:text-slate-400 px-2.5 py-0.5 rounded-full text-xs font-bold'">
                                {{ item.is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </template>
                    </DataTable>
                </main>
            </div>
        </div>
    </div>

    <!-- Modal Form -->
    <div v-if="isModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-sm">
        <div :class="['dashboard-card w-full p-6 max-h-[90vh] overflow-y-auto transition-all duration-300', (activeTab === 'ingredientItems' || activeTab === 'ingredientUnits') ? 'max-w-3xl' : 'max-w-lg']">
            <h2 class="text-xl font-bold mb-6 text-slate-800 dark:text-white">
                {{ editingItem ? 'Edit' : 'Create' }} {{ tabs.find(t => t.id === activeTab)?.label.slice(0, -1) }}
            </h2>
            <form @submit.prevent="submitForm">
                <div :class="(activeTab === 'ingredientItems' || activeTab === 'ingredientUnits') ? 'grid grid-cols-1 md:grid-cols-2 gap-4' : 'space-y-4'">
                    <div v-for="field in formFields" :key="field.key" :class="{'md:col-span-2': field.type === 'textarea' || field.key === 'description'}">
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">{{ field.label }}</label>
                        
                        <template v-if="field.type === 'checkbox'">
                            <label class="relative inline-flex items-center cursor-pointer mt-1">
                                <input type="checkbox" v-model="form[field.key]" class="sr-only peer">
                                <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer dark:bg-slate-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-slate-600 peer-checked:bg-indigo-500 dark:peer-checked:bg-[#06b6d4]"></div>
                            </label>
                        </template>
                        
                        <template v-else-if="field.type === 'select'">
                            <select v-model="form[field.key]" class="pos-input w-full" required>
                                <option value="" disabled>Select an option</option>
                                <option v-for="opt in field.options" :key="opt.value" :value="opt.value">
                                    {{ opt.label }}
                                </option>
                            </select>
                        </template>
                        
                        <template v-else>
                            <input :type="field.type" :step="field.type === 'number' ? '0.01' : null" v-model="form[field.key]" class="pos-input w-full" :required="field.required !== false">
                        </template>

                        <div v-if="form.errors[field.key]" class="text-red-500 text-xs mt-1">{{ form.errors[field.key] }}</div>
                    </div>
                </div>
                
                <div class="flex justify-end gap-3 mt-8">
                    <button type="button" @click="closeModal" class="px-4 py-2 text-sm font-bold text-slate-600 dark:text-slate-400 hover:text-slate-800 dark:hover:text-white transition-colors">Cancel</button>
                    <button type="submit" class="pos-btn-primary !py-2 !px-4" :disabled="form.processing">
                        {{ editingItem ? 'Save Changes' : 'Create' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>
