<script setup>
import { ref } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import InventorySidebar from '@/Components/InventorySidebar.vue';
import DataTable from '@/Components/DataTable.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import SelectInput from '@/Components/SelectInput.vue';

const props = defineProps({
    customers: {
        type: Array,
        required: true,
    },
    cities: {
        type: Array,
        default: () => [],
    },
});

const isModalOpen = ref(false);
const editingItem = ref(null);

const customerTypeOptions = [
    { value: 'regular', label: 'Regular Customer' },
    { value: 'vip', label: 'VIP Customer' },
    { value: 'corporate', label: 'Corporate' },
    { value: 'walk_in', label: 'Walk-in' },
];

const form = useForm({
    name: '',
    phone: '',
    email: '',
    customer_type: 'regular',
    address: '',
    city_id: '',
    credit_limit: 0,
    opening_balance: 0,
    is_active: true,
    notes: '',
});

const openCreateModal = () => {
    editingItem.value = null;
    form.reset();
    form.clearErrors();
    form.customer_type = 'regular';
    form.is_active = true;
    form.credit_limit = 0;
    form.opening_balance = 0;
    isModalOpen.value = true;
};

const openEditModal = (item) => {
    editingItem.value = item;
    form.reset();
    form.clearErrors();
    form.name = item.name || '';
    form.phone = item.phone || '';
    form.email = item.email || '';
    form.customer_type = item.customer_type || 'regular';
    form.address = item.address || '';
    form.city_id = item.city_id || '';
    form.credit_limit = item.credit_limit || 0;
    form.opening_balance = item.opening_balance || 0;
    form.is_active = !!item.is_active;
    form.notes = item.notes || '';
    isModalOpen.value = true;
};

const closeModal = () => {
    isModalOpen.value = false;
    form.reset();
    form.clearErrors();
    editingItem.value = null;
};

const submitForm = () => {
    if (editingItem.value) {
        form.put(route('trade.customers.update', editingItem.value.id), {
            preserveScroll: true,
            onSuccess: () => closeModal(),
        });
    } else {
        form.post(route('trade.customers.store'), {
            preserveScroll: true,
            onSuccess: () => closeModal(),
        });
    }
};

const deleteItem = (item) => {
    if (confirm(`Are you sure you want to delete customer "${item.name}"?`)) {
        router.delete(route('trade.customers.destroy', item.id), {
            preserveScroll: true,
        });
    }
};

const columns = [
    { key: 'name', label: 'Customer Name' },
    { key: 'phone', label: 'Phone' },
    { key: 'customer_type', label: 'Type' },
    { key: 'city_id', label: 'City' },
    { key: 'credit_limit', label: 'Credit Limit' },
    { key: 'is_active', label: 'Status' },
];
</script>

<template>
    <AppLayout title="Customers (Sales & Trade)">
        <template #sidebar>
            <InventorySidebar />
        </template>

        <div class="space-y-6">
            <DataTable
                title="Customers"
                :data="customers"
                :columns="columns"
            >
                <template #actions>
                    <button @click="openCreateModal" class="pos-btn-primary !py-2 !px-4 text-sm !rounded-lg">
                        + Add New Customer
                    </button>
                </template>

                <template #cell-name="{ item }">
                    <div>
                        <div class="font-semibold text-slate-800 dark:text-white">{{ item.name }}</div>
                        <div v-if="item.email" class="text-xs text-slate-500 dark:text-slate-400">{{ item.email }}</div>
                    </div>
                </template>

                <template #cell-phone="{ item }">
                    <span class="font-mono text-sm text-slate-700 dark:text-slate-300">
                        {{ item.phone || '—' }}
                    </span>
                </template>

                <template #cell-customer_type="{ item }">
                    <span 
                        class="px-2.5 py-0.5 rounded-full text-xs font-semibold capitalize"
                        :class="{
                            'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-300': item.customer_type === 'vip',
                            'bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-300': item.customer_type === 'corporate',
                            'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300': item.customer_type === 'regular',
                            'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300': item.customer_type === 'walk_in' || !item.customer_type,
                        }"
                    >
                        {{ (item.customer_type || 'regular').replace('_', ' ') }}
                    </span>
                </template>

                <template #cell-city_id="{ item }">
                    <span class="text-sm text-slate-600 dark:text-slate-300">
                        {{ item.city ? item.city.name : '—' }}
                    </span>
                </template>

                <template #cell-credit_limit="{ item }">
                    <span class="font-medium text-slate-700 dark:text-slate-300">
                        ${{ Number(item.credit_limit || 0).toLocaleString(undefined, { minimumFractionDigits: 2 }) }}
                    </span>
                </template>

                <template #cell-is_active="{ item }">
                    <StatusBadge :value="item.is_active" />
                </template>

                <template #row-actions="{ item }">
                    <div class="flex items-center justify-end gap-2">
                        <button
                            @click="openEditModal(item)"
                            class="p-1.5 text-indigo-600 hover:text-indigo-800 dark:text-cyan-400 dark:hover:text-cyan-300 hover:bg-slate-100 dark:hover:bg-slate-800 rounded transition-colors"
                            title="Edit"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                            </svg>
                        </button>
                        <button
                            @click="deleteItem(item)"
                            class="p-1.5 text-rose-500 hover:text-rose-700 hover:bg-rose-50 dark:hover:bg-rose-950/30 rounded transition-colors"
                            title="Delete"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                        </button>
                    </div>
                </template>
            </DataTable>
        </div>

        <!-- Customer Form Modal -->
        <div v-if="isModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-sm">
            <div class="dashboard-card w-full max-w-2xl p-6 max-h-[90vh] overflow-y-auto">
                <h2 class="text-xl font-bold mb-6 text-slate-800 dark:text-white">
                    {{ editingItem ? 'Edit Customer' : 'Add New Customer' }}
                </h2>

                <form @submit.prevent="submitForm" class="space-y-4">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                                Customer Name <span class="text-rose-500">*</span>
                            </label>
                            <input
                                type="text"
                                v-model="form.name"
                                placeholder="e.g. John Doe"
                                class="pos-input w-full"
                                required
                            />
                            <div v-if="form.errors.name" class="text-rose-500 text-xs mt-1">{{ form.errors.name }}</div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                                Phone Number <span class="text-rose-500">*</span>
                            </label>
                            <input
                                type="text"
                                v-model="form.phone"
                                placeholder="e.g. +1 234 567 8900"
                                class="pos-input w-full"
                                required
                            />
                            <div v-if="form.errors.phone" class="text-rose-500 text-xs mt-1">{{ form.errors.phone }}</div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                                Email Address
                            </label>
                            <input
                                type="email"
                                v-model="form.email"
                                placeholder="e.g. john@example.com"
                                class="pos-input w-full"
                            />
                            <div v-if="form.errors.email" class="text-rose-500 text-xs mt-1">{{ form.errors.email }}</div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                                Customer Type
                            </label>
                            <SelectInput
                                v-model="form.customer_type"
                                :options="customerTypeOptions"
                                valueKey="value"
                                labelKey="label"
                                placeholder="Select customer type"
                            />
                            <div v-if="form.errors.customer_type" class="text-rose-500 text-xs mt-1">{{ form.errors.customer_type }}</div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                                City
                            </label>
                            <SelectInput
                                v-model="form.city_id"
                                :options="cities"
                                valueKey="id"
                                labelKey="name"
                                placeholder="Select City"
                                searchPlaceholder="Search city..."
                            />
                            <div v-if="form.errors.city_id" class="text-rose-500 text-xs mt-1">{{ form.errors.city_id }}</div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                                Credit Limit ($)
                            </label>
                            <input
                                type="number"
                                step="0.01"
                                min="0"
                                v-model="form.credit_limit"
                                placeholder="0.00"
                                class="pos-input w-full"
                            />
                            <div v-if="form.errors.credit_limit" class="text-rose-500 text-xs mt-1">{{ form.errors.credit_limit }}</div>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                            Delivery / Street Address
                        </label>
                        <textarea
                            v-model="form.address"
                            rows="2"
                            placeholder="Full address for delivery or billing..."
                            class="pos-input w-full"
                        ></textarea>
                        <div v-if="form.errors.address" class="text-rose-500 text-xs mt-1">{{ form.errors.address }}</div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 items-center">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                                Opening Balance ($)
                            </label>
                            <input
                                type="number"
                                step="0.01"
                                v-model="form.opening_balance"
                                placeholder="0.00"
                                class="pos-input w-full"
                            />
                            <div v-if="form.errors.opening_balance" class="text-rose-500 text-xs mt-1">{{ form.errors.opening_balance }}</div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                                Active Status
                            </label>
                            <label class="relative inline-flex items-center cursor-pointer mt-1">
                                <input type="checkbox" v-model="form.is_active" class="sr-only peer">
                                <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer dark:bg-slate-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-slate-600 peer-checked:bg-indigo-500 dark:peer-checked:bg-[#06b6d4]"></div>
                                <span class="ml-3 text-sm text-slate-600 dark:text-slate-400">
                                    {{ form.is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </label>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                            Notes / Preferences
                        </label>
                        <textarea
                            v-model="form.notes"
                            rows="2"
                            placeholder="Customer preferences, dietary remarks, etc..."
                            class="pos-input w-full"
                        ></textarea>
                        <div v-if="form.errors.notes" class="text-rose-500 text-xs mt-1">{{ form.errors.notes }}</div>
                    </div>

                    <div class="flex justify-end gap-3 mt-8 pt-4 border-t border-slate-100 dark:border-slate-800">
                        <button
                            type="button"
                            @click="closeModal"
                            class="px-4 py-2 text-sm font-bold text-slate-600 dark:text-slate-400 hover:text-slate-800 dark:hover:text-white transition-colors"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            class="pos-btn-primary !py-2 !px-4"
                            :disabled="form.processing"
                        >
                            {{ editingItem ? 'Save Changes' : 'Create Customer' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>
