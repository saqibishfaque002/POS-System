<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import DataTable from '@/Components/DataTable.vue';

const props = defineProps({
    countries: Array,
    provinces: Array,
    cities: Array,
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
    }
});

const activeTab = ref('countries');
const tabs = [
    { id: 'countries', label: 'Countries' },
    { id: 'provinces', label: 'Provinces' },
    { id: 'cities', label: 'Cities' },
];

const tabData = computed(() => {
    switch (activeTab.value) {
        case 'countries': return props.countries;
        case 'provinces': return props.provinces;
        case 'cities': return props.cities;
        default: return [];
    }
});

const columns = computed(() => {
    switch (activeTab.value) {
        case 'countries': return [
            { key: 'id', label: 'ID' },
            { key: 'name', label: 'Name' },
            { key: 'is_active', label: 'Active' },
        ];
        case 'provinces': return [
            { key: 'id', label: 'ID' },
            { key: 'country_id', label: 'Country' },
            { key: 'name', label: 'Name' },
            { key: 'is_active', label: 'Active' },
        ];
        case 'cities': return [
            { key: 'id', label: 'ID' },
            { key: 'province_id', label: 'Province' },
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
        case 'countries': return [
            { key: 'name', label: 'Name', type: 'text' },
            { key: 'is_active', label: 'Active', type: 'checkbox' },
        ];
        case 'provinces': return [
            { key: 'country_id', label: 'Country', type: 'select', options: props.countries.map(g => ({value: g.id, label: g.name})) },
            { key: 'name', label: 'Name', type: 'text' },
            { key: 'is_active', label: 'Active', type: 'checkbox' },
        ];
        case 'cities': return [
            { key: 'country_id', label: 'Country (Filter)', type: 'select', options: props.countries.map(g => ({value: g.id, label: g.name})), virtual: true },
            { 
                key: 'province_id', 
                label: 'Province', 
                type: 'select', 
                dynamicOptions: (f) => props.provinces.filter(p => p.country_id === f.country_id).map(p => ({value: p.id, label: p.name}))
            },
            { key: 'name', label: 'City Name', type: 'text' },
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
        } else if (field.key === 'country_id' && activeTab.value === 'cities') {
            initialData[field.key] = item ? item.province?.country_id : '';
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
        case 'countries': return 'countries';
        case 'provinces': return 'provinces';
        case 'cities': return 'cities';
    }
};

const submitForm = () => {
    const prefix = getRoutePrefix();
    if (editingItem.value) {
        form.put(route(`locations.${prefix}.update`, editingItem.value.id), {
            onSuccess: () => closeModal(),
        });
    } else {
        form.post(route(`locations.${prefix}.store`), {
            onSuccess: () => closeModal(),
        });
    }
};

const deleteItem = (item) => {
    if (confirm('Are you sure you want to delete this item?')) {
        const prefix = getRoutePrefix();
        router.delete(route(`locations.${prefix}.destroy`, item.id));
    }
};
</script>

<template>
    <Head title="System Settings" />
    <div class="min-h-screen flex flex-col transition-colors duration-300">
        
        <div class="w-full mx-auto px-4 sm:px-6 lg:px-8 mt-6">
            <!-- Header -->
            <header class="dashboard-card px-6 py-4 flex justify-between items-center mb-6">
                <div class="flex items-center gap-4">
                    <h1 class="text-xl font-semibold text-indigo-500 dark:text-[#06b6d4]">System Settings</h1>
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
                <aside class="w-full md:w-64 shrink-0">
                    <div class="dashboard-card p-4 space-y-4">
                        
                        <div>
                            <h3 class="text-xs font-black tracking-wider text-slate-400 dark:text-slate-500 uppercase mb-2 px-2">Manage Restaurant</h3>
                            <div class="space-y-1">
                                <button v-for="tab in tabs" :key="tab.id" @click="activeTab = tab.id" 
                                    :class="[
                                        'w-full text-left px-4 py-2.5 rounded-xl transition-colors text-sm font-bold',
                                        activeTab === tab.id ? 'bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-[#06b6d4]' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800'
                                    ]">
                                    {{ tab.label }}
                                </button>
                            </div>
                        </div>

                    </div>
                </aside>

                <!-- Content -->
                <main class="flex-1">
                    <DataTable :title="tabs.find(t => t.id === activeTab).label" :columns="columns" :data="tabData" @edit="openModal" @delete="deleteItem">
                        <template #actions>
                            <button @click="openModal()" class="pos-btn-primary !py-2 !px-4 !rounded-lg text-sm">
                                + Add New
                            </button>
                        </template>

                        <template #cell-country_id="{item}">
                            {{ item.country?.name || item.country_id }}
                        </template>
                        <template #cell-province_id="{item}">
                            {{ item.province?.name || item.province_id }}
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
        <div class="dashboard-card w-full max-w-lg p-6 max-h-[90vh] overflow-y-auto">
            <h2 class="text-xl font-bold mb-6 text-slate-800 dark:text-white">
                {{ editingItem ? 'Edit' : 'Create' }} {{ tabs.find(t => t.id === activeTab).label.slice(0, -1) }}
            </h2>
            <form @submit.prevent="submitForm" class="space-y-4">
                <div v-for="field in formFields" :key="field.key">
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">{{ field.label }}</label>
                    
                    <template v-if="field.type === 'checkbox'">
                        <label class="relative inline-flex items-center cursor-pointer mt-1">
                            <input type="checkbox" v-model="form[field.key]" class="sr-only peer">
                            <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer dark:bg-slate-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-slate-600 peer-checked:bg-indigo-500 dark:peer-checked:bg-[#06b6d4]"></div>
                        </label>
                    </template>
                    
                    <template v-else-if="field.type === 'select'">
                        <select v-model="form[field.key]" class="pos-input w-full" :required="!field.virtual">
                            <option value="" disabled>Select an option</option>
                            <option v-for="opt in (field.dynamicOptions ? field.dynamicOptions(form) : field.options)" :key="opt.value" :value="opt.value">
                                {{ opt.label }}
                            </option>
                        </select>
                    </template>
                    
                    <template v-else>
                        <input :type="field.type" v-model="form[field.key]" class="pos-input w-full" :required="field.required !== false">
                    </template>

                    <div v-if="form.errors[field.key]" class="text-red-500 text-xs mt-1">{{ form.errors[field.key] }}</div>
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
