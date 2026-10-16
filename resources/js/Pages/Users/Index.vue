<script setup>
import { ref, computed, watch } from 'vue';
import { useForm, router, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import UsersSidebar from '@/Components/UsersSidebar.vue';
import DataTable from '@/Components/DataTable.vue';
import SelectInput from '@/Components/SelectInput.vue';

const props = defineProps({
    users: Array,
    branches: Array,
});

const activeTab = ref(new URLSearchParams(window.location.search).get('tab') || 'users');

watch(activeTab, (newTab) => {
    if (newTab && typeof window !== 'undefined') {
        const url = new URL(window.location.href);
        url.searchParams.set('tab', newTab);
        window.history.replaceState({}, '', url.toString());
    }
});

const tabs = [
    { id: 'users', label: 'Users' },
];

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
    <AppLayout title="User Management">
        <template #sidebar>
            <UsersSidebar v-model:activeTab="activeTab" />
        </template>

        <DataTable :title="tabs.find(t => t.id === activeTab)?.label || 'Users'" :columns="columns" :data="props.users" @edit="openModal" @delete="deleteItem">
            <template #actions>
                <button @click="openModal()" class="pos-btn-primary !py-2 !px-4 !rounded-lg text-sm">
                    + Add New
                </button>
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

        <template #modals>
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
                                    <SelectInput 
                                        v-model="form[field.key]" 
                                        :options="field.options" 
                                        valueKey="value"
                                        labelKey="label"
                                        :placeholder="'Select ' + field.label" 
                                        :searchPlaceholder="'Search ' + field.label.toLowerCase() + '...'"
                                    />
                                </template>

                                <template v-else-if="field.type === 'multiselect'">
                                    <div class="relative z-50">
                                        <SelectInput 
                                            v-model="form[field.key]" 
                                            :options="field.options" 
                                            valueKey="value"
                                            labelKey="label"
                                            :multiple="true"
                                            :placeholder="'Select ' + field.label" 
                                            :searchPlaceholder="'Search ' + field.label.toLowerCase() + '...'"
                                        />
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
    </AppLayout>
</template>
