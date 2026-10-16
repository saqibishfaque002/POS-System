<script setup>
import { ref } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import InventoryLayout from '@/Layouts/InventoryLayout.vue';
import DataTable from '@/Components/DataTable.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SelectInput from '@/Components/SelectInput.vue';

const props = defineProps({
    suppliers: {
        type: Array,
        required: true
    },
    ingredientItems: {
        type: Array,
        default: () => []
    }
});

const isModalOpen = ref(false);
const editingItem = ref(null);

const form = useForm({
    name: '',
    contact_person: '',
    phone: '',
    email: '',
    address: '',
    is_active: true,
    items: []
});

const openModal = (item = null) => {
    editingItem.value = item;
    if (item) {
        form.name = item.name;
        form.contact_person = item.contact_person;
        form.phone = item.phone;
        form.email = item.email;
        form.address = item.address;
        form.is_active = !!item.is_active;
        form.items = item.items ? item.items.map(i => i.id) : [];
    } else {
        form.reset();
        form.is_active = true;
        form.items = [];
    }
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
        form.put(route('trade.suppliers.update', editingItem.value.id), {
            onSuccess: () => closeModal(),
        });
    } else {
        form.post(route('trade.suppliers.store'), {
            onSuccess: () => closeModal(),
        });
    }
};

const deleteSupplier = (item) => {
    if (confirm('Are you sure you want to delete this supplier?')) {
        router.delete(route('trade.suppliers.destroy', item.id));
    }
};

const columns = [
    { key: 'name', label: 'Company Name' },
    { key: 'contact_person', label: 'Contact Person' },
    { key: 'phone', label: 'Phone' },
    { key: 'email', label: 'Email' },
    { key: 'address', label: 'Address' },
    { key: 'is_active', label: 'Status' }
];

const actions = [
    {
        label: 'Edit',
        icon: '<svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z" /></svg>',
        handler: (item) => openModal(item),
        class: 'text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300'
    },
    {
        label: 'Delete',
        icon: '<svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" /></svg>',
        handler: (item) => deleteSupplier(item),
        class: 'text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300'
    }
];
</script>

<template>
    <InventoryLayout title="Suppliers">
        <DataTable :data="suppliers" :columns="columns" title="Suppliers" @edit="openModal" @delete="deleteSupplier">
            <template #actions>
                <button @click="openModal()" class="pos-btn-primary !py-2 !px-4 !rounded-lg text-sm">
                    + Add New
                </button>
            </template>
        </DataTable>

        <!-- Supplier Form Modal -->
        <div v-if="isModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-sm">
            <div class="dashboard-card w-full max-w-3xl p-6 max-h-[90vh] overflow-y-auto">
                <h2 class="text-xl font-bold mb-6 text-slate-800 dark:text-white">
                    {{ editingItem ? 'Edit Supplier' : 'Add New Supplier' }}
                </h2>
                
                <form @submit.prevent="submitForm" class="flex flex-col">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                        <!-- Name / Company Name -->
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Company Name</label>
                            <input type="text" v-model="form.name" class="pos-input w-full" required placeholder="e.g. Acme Corp">
                            <div v-if="form.errors.name" class="text-red-500 text-xs mt-1">{{ form.errors.name }}</div>
                        </div>

                        <!-- Contact Person -->
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Contact Person</label>
                            <input type="text" v-model="form.contact_person" class="pos-input w-full" placeholder="e.g. John Doe">
                            <div v-if="form.errors.contact_person" class="text-red-500 text-xs mt-1">{{ form.errors.contact_person }}</div>
                        </div>

                        <!-- Phone -->
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Phone Number</label>
                            <input type="text" v-model="form.phone" class="pos-input w-full" placeholder="e.g. +1 234 567 890">
                            <div v-if="form.errors.phone" class="text-red-500 text-xs mt-1">{{ form.errors.phone }}</div>
                        </div>

                        <!-- Email -->
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Email Address</label>
                            <input type="email" v-model="form.email" class="pos-input w-full" placeholder="e.g. info@acme.com">
                            <div v-if="form.errors.email" class="text-red-500 text-xs mt-1">{{ form.errors.email }}</div>
                        </div>

                        <!-- Address -->
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Address</label>
                            <textarea v-model="form.address" class="pos-input w-full" rows="3" placeholder="Full business address..."></textarea>
                            <div v-if="form.errors.address" class="text-red-500 text-xs mt-1">{{ form.errors.address }}</div>
                        </div>

                        <!-- Items (Multi Select) -->
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Linked Ingredient Items</label>
                            <SelectInput 
                                v-model="form.items" 
                                :options="ingredientItems" 
                                :multiple="true"
                                placeholder="Select ingredient items..." 
                                searchPlaceholder="Search items by name or SKU..."
                            />
                            <div v-if="form.errors.items" class="text-red-500 text-xs mt-1">{{ form.errors.items }}</div>
                        </div>

                        <!-- Active Status -->
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Status</label>
                            <label class="relative inline-flex items-center cursor-pointer mt-1">
                                <input type="checkbox" v-model="form.is_active" class="sr-only peer">
                                <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer dark:bg-slate-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-slate-600 peer-checked:bg-indigo-500 dark:peer-checked:bg-[#06b6d4]"></div>
                                <span class="ml-3 text-sm font-medium text-slate-600 dark:text-slate-400">Active</span>
                            </label>
                        </div>
                    </div>
                    
                    <div class="flex justify-end gap-3 mt-4 pt-4 border-t border-slate-100 dark:border-slate-800">
                        <button type="button" @click="closeModal" class="px-4 py-2 text-sm font-bold text-slate-600 dark:text-slate-400 hover:text-slate-800 dark:hover:text-white transition-colors">Cancel</button>
                        <button type="submit" class="pos-btn-primary !py-2 !px-4" :disabled="form.processing">
                            {{ editingItem ? 'Save Changes' : 'Create Supplier' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </InventoryLayout>
</template>
