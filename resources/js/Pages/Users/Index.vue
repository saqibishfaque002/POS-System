<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { Head, Link, useForm, router, usePage } from '@inertiajs/vue3';
import DataTable from '@/Components/DataTable.vue';

const props = defineProps({
    users: Array,
    branches: Array,
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

const activeTab = ref('users');

const tabs = [
    { id: 'users', label: 'Users' },
];

const expandedCategories = ref({
    staff: true,
});

const columns = computed(() => {
    return [
        { key: 'id', label: 'ID' },
        { key: 'name', label: 'Name' },
        { key: 'phone', label: 'Phone' },
        { key: 'role', label: 'Role' },
        { key: 'branches', label: 'Branches' },
        { key: 'is_active', label: 'Active' },
    ];
});

// Modal State
const isModalOpen = ref(false);
const editingItem = ref(null);
const branchDropdownOpen = ref(false);
let form = useForm({});

const formFields = computed(() => {
    return [
        { key: 'name', label: 'Name', type: 'text' },
        { key: 'email', label: 'Email', type: 'email' },
        { key: 'password', label: 'Password', type: 'password', required: !editingItem.value },
        { key: 'password_confirmation', label: 'Confirm Password', type: 'password', required: !editingItem.value },
        { key: 'phone', label: 'Phone', type: 'text', required: false },
        { key: 'address', label: 'Address', type: 'textarea', required: false },
        { 
            key: 'role', 
            label: 'Role', 
            type: 'select', 
            options: [
                { value: 'admin', label: 'Admin' },
                { value: 'manager', label: 'Manager' },
                { value: 'multi branch manager', label: 'Multi Branch Manager' },
                { value: 'cashier', label: 'Cashier' },
                { value: 'user', label: 'User' },
            ]
        },
        { 
            key: 'branch_ids', 
            label: 'Assigned Branches', 
            type: 'multiselect', 
            options: props.branches.map(b => ({ value: b.id, label: b.name + ' (' + b.company?.name + ')' })) 
        },
        { key: 'is_active', label: 'Active', type: 'checkbox' },
    ];
});

const openModal = (item = null) => {
    editingItem.value = item;
    const initialData = {};
    formFields.value.forEach(field => {
        if (field.type === 'checkbox') {
            initialData[field.key] = item ? !!item[field.key] : true;
        } else if (field.type === 'multiselect') {
            initialData[field.key] = item ? (item.branches ? item.branches.map(b => b.id) : []) : [];
        } else if (field.key === 'role') {
            initialData[field.key] = item ? item.role : 'user';
        } else {
            initialData[field.key] = item && !field.key.includes('password') ? item[field.key] : '';
        }
    });
    form = useForm(initialData);
    isModalOpen.value = true;
};

const closeModal = () => {
    isModalOpen.value = false;
    branchDropdownOpen.value = false;
    form.reset();
};

const submitForm = () => {
    if (editingItem.value) {
        form.put(route(`users.update`, editingItem.value.id), {
            onSuccess: () => closeModal(),
        });
    } else {
        form.post(route(`users.store`), {
            onSuccess: () => closeModal(),
        });
    }
};

const deleteItem = (item) => {
    if (usePage().props.auth.user.id === item.id) {
        alert("You cannot delete your own account.");
        return;
    }
    if (confirm('Are you sure you want to delete this user?')) {
        router.delete(route(`users.destroy`, item.id), {
            onError: (errors) => {
                if (errors.message) alert(errors.message);
            }
        });
    }
};
</script>

<template>
    <Head title="User Management" />
    <div class="min-h-screen flex flex-col transition-colors duration-300">
        
        <div class="w-full mx-auto px-4 sm:px-6 lg:px-8 mt-6 pb-20">
            <!-- Header -->
            <header class="dashboard-card px-6 py-4 flex justify-between items-center mb-6">
                <div class="flex items-center gap-4">
                    <h1 class="text-xl font-semibold text-indigo-500 dark:text-[#06b6d4]">User Management</h1>
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
                    <div class="dashboard-card p-4 space-y-6">
                        
                        <div>
                            <button type="button" class="w-full flex justify-between items-center text-xs font-black tracking-wider text-slate-400 dark:text-slate-500 uppercase mb-2 px-2 focus:outline-none cursor-default">
                                <span>Staff & Security</span>
                            </button>
                            <div v-show="expandedCategories.staff" class="space-y-1">
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
                    
                    <DataTable :title="tabs.find(t => t.id === activeTab)?.label" :columns="columns" :data="props.users" @edit="openModal" @delete="deleteItem">
                        <template #actions>
                            <button @click="openModal()" class="pos-btn-primary !py-2 !px-4 !rounded-lg text-sm">
                                + Add New
                            </button>
                        </template>

                        <template #cell-is_active="{item}">
                            <span :class="item.is_active ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-400 px-2.5 py-0.5 rounded-full text-xs font-bold' : 'bg-slate-100 text-slate-800 dark:bg-slate-700/50 dark:text-slate-400 px-2.5 py-0.5 rounded-full text-xs font-bold'">
                                {{ item.is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </template>
                        
                        <template #cell-branches="{item}">
                            <div class="flex flex-wrap gap-1">
                                <span v-for="branch in item.branches" :key="branch.id" class="px-2 py-0.5 text-[10px] bg-slate-100 dark:bg-slate-700 rounded-md font-bold text-slate-600 dark:text-slate-300">
                                    {{ branch.name }}
                                </span>
                            </div>
                        </template>
                        
                        <template #cell-role="{item}">
                            <span class="capitalize text-slate-600 dark:text-slate-300 font-bold text-sm">{{ item.role }}</span>
                        </template>
                    </DataTable>
                </main>
            </div>
        </div>
    </div>

    <!-- Modal Form -->
    <div v-if="isModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-sm">
        <div class="dashboard-card w-full max-w-2xl p-6 max-h-[90vh] overflow-y-auto">
            <h2 class="text-xl font-bold mb-6 text-slate-800 dark:text-white">
                {{ editingItem ? 'Edit User' : 'Create User' }}
            </h2>
            <form @submit.prevent="submitForm" class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div v-for="field in formFields" :key="field.key" :class="[field.type === 'textarea' || field.type === 'multiselect' ? 'md:col-span-2' : '']">
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">{{ field.label }}</label>
                        
                        <template v-if="field.type === 'checkbox'">
                            <label class="relative inline-flex items-center cursor-pointer mt-1">
                                <input type="checkbox" v-model="form[field.key]" class="sr-only peer">
                                <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer dark:bg-slate-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-slate-600 peer-checked:bg-indigo-500 dark:peer-checked:bg-[#06b6d4]"></div>
                            </label>
                        </template>

                        <template v-else-if="field.type === 'textarea'">
                            <textarea v-model="form[field.key]" class="pos-input w-full min-h-[80px]" :required="field.required !== false"></textarea>
                        </template>

                        <template v-else-if="field.type === 'select'">
                            <select v-model="form[field.key]" class="pos-input w-full" :required="field.required !== false">
                                <option value="">Select {{ field.label }}</option>
                                <option v-for="opt in field.options" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
                            </select>
                        </template>

                        <template v-else-if="field.type === 'multiselect'">
                            <div class="relative">
                                <button type="button" @click="branchDropdownOpen = !branchDropdownOpen" class="pos-input w-full flex justify-between items-center text-left bg-white dark:bg-slate-900">
                                    <span class="truncate text-slate-600 dark:text-slate-300">
                                        {{ form[field.key].length ? form[field.key].length + ' Branches Selected' : 'Select Branches' }}
                                    </span>
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                    </svg>
                                </button>
                                
                                <div v-show="branchDropdownOpen" class="absolute z-10 w-full mt-1 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl shadow-lg max-h-48 overflow-y-auto">
                                    <div class="p-2 flex flex-col gap-1">
                                        <label v-for="opt in field.options" :key="opt.value" class="flex items-center gap-3 p-2 hover:bg-slate-50 dark:hover:bg-slate-700/50 rounded-lg cursor-pointer group">
                                            <div class="relative flex items-center">
                                                <input type="checkbox" :value="opt.value" v-model="form[field.key]" class="peer sr-only" />
                                                <div class="w-5 h-5 rounded border border-slate-300 dark:border-slate-600 peer-checked:bg-indigo-500 dark:peer-checked:bg-[#06b6d4] peer-checked:border-transparent flex items-center justify-center transition-colors">
                                                    <svg class="w-3 h-3 text-white opacity-0 peer-checked:opacity-100 transition-opacity" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                                                    </svg>
                                                </div>
                                            </div>
                                            <span class="text-sm text-slate-600 dark:text-slate-300 group-hover:text-slate-800 dark:group-hover:text-white transition-colors">{{ opt.label }}</span>
                                        </label>
                                    </div>
                                </div>
                                <!-- Overlay to close dropdown when clicking outside -->
                                <div v-if="branchDropdownOpen" @click="branchDropdownOpen = false" class="fixed inset-0 z-0"></div>
                            </div>
                        </template>
                        
                        <template v-else>
                            <input :type="field.type" v-model="form[field.key]" class="pos-input w-full" :required="field.required !== false">
                        </template>

                        <div v-if="form.errors[field.key]" class="text-red-500 text-xs mt-1">{{ form.errors[field.key] }}</div>
                    </div>
                </div>
                
                <div class="flex justify-end gap-3 mt-8 pt-4 border-t border-slate-100 dark:border-slate-800">
                    <button type="button" @click="closeModal" class="px-4 py-2 text-sm font-bold text-slate-600 dark:text-slate-400 hover:text-slate-800 dark:hover:text-white transition-colors">Cancel</button>
                    <button type="submit" class="pos-btn-primary !py-2 !px-4" :disabled="form.processing">
                        {{ editingItem ? 'Save Changes' : 'Create' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>
