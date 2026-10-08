<script setup>
import { ref, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import SelectInput from '@/Components/SelectInput.vue';

const props = defineProps({
    show: Boolean,
    menu: {
        type: Object,
        default: () => ({})
    },
    branches: {
        type: Array,
        default: () => []
    },
    foodItems: {
        type: Array,
        default: () => []
    },
    isEditing: Boolean,
});

const emit = defineEmits(['close']);

const form = useForm({
    name: '',
    description: '',
    is_active: true,
    tax_percentage: 0,
    discount_percentage: 0,
    branch_ids: [],
    item_ids: [],
});

watch(() => props.show, (newVal) => {
    if (newVal) {
        if (props.isEditing && props.menu) {
            form.name = props.menu.name;
            form.description = props.menu.description || '';
            form.is_active = props.menu.is_active;
            form.tax_percentage = props.menu.tax_percentage || 0;
            form.discount_percentage = props.menu.discount_percentage || 0;
            form.branch_ids = props.menu.branches ? props.menu.branches.map(b => b.id) : [];
            form.item_ids = props.menu.items ? props.menu.items.map(i => i.id) : [];
        } else {
            form.reset();
        }
        form.clearErrors();
    }
});

const submit = () => {
    if (props.isEditing) {
        form.put(route('catalog.menus.update', props.menu.id), {
            onSuccess: () => closeModal(),
            preserveScroll: true,
        });
    } else {
        form.post(route('catalog.menus.store'), {
            onSuccess: () => closeModal(),
            preserveScroll: true,
        });
    }
};

const closeModal = () => {
    emit('close');
};
</script>

<template>
    <div v-if="show" class="fixed inset-0 z-[100] flex items-center justify-center overflow-y-auto overflow-x-hidden bg-slate-900/50 backdrop-blur-sm p-4 md:p-0">
        <div class="relative w-full max-w-2xl bg-white dark:bg-slate-900 rounded-3xl shadow-2xl flex flex-col max-h-[90vh]">
            
            <!-- Header -->
            <div class="flex-shrink-0 flex items-center justify-between p-6 border-b border-slate-100 dark:border-slate-800">
                <h3 class="text-xl font-black text-slate-800 dark:text-white">
                    {{ isEditing ? 'Edit Menu' : 'Create New Menu' }}
                </h3>
                <button @click="closeModal" class="p-2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-xl transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>

            <!-- Body -->
            <div class="flex-1 overflow-y-auto p-6 custom-scrollbar">
                <form @submit.prevent="submit" class="space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="md:col-span-2 space-y-2">
                            <label class="text-sm font-bold text-slate-700 dark:text-slate-300">Menu Name</label>
                            <input v-model="form.name" type="text" class="pos-input" placeholder="e.g. FoodPanda Menu" required />
                            <div v-if="form.errors.name" class="text-red-500 text-xs">{{ form.errors.name }}</div>
                        </div>

                        <div class="md:col-span-2 space-y-2">
                            <label class="text-sm font-bold text-slate-700 dark:text-slate-300">Description (Optional)</label>
                            <textarea v-model="form.description" class="pos-input" rows="2" placeholder="Menu details..."></textarea>
                            <div v-if="form.errors.description" class="text-red-500 text-xs">{{ form.errors.description }}</div>
                        </div>

                        <div class="md:col-span-2 space-y-2 relative z-50">
                            <label class="text-sm font-bold text-slate-700 dark:text-slate-300">Available Branches</label>
                            <SelectInput v-model="form.branch_ids" :options="branches" :multiple="true" placeholder="Select branches..." />
                            <div v-if="form.errors.branch_ids" class="text-red-500 text-xs">{{ form.errors.branch_ids }}</div>
                            <p class="text-xs text-slate-500 mt-1">Select the branches where this menu will be available.</p>
                        </div>
                        
                        <div class="md:col-span-2 space-y-2 relative z-40">
                            <label class="text-sm font-bold text-slate-700 dark:text-slate-300">Food Items</label>
                            <SelectInput v-model="form.item_ids" :options="foodItems" :multiple="true" placeholder="Select food items..." />
                            <div v-if="form.errors.item_ids" class="text-red-500 text-xs">{{ form.errors.item_ids }}</div>
                            <p class="text-xs text-slate-500 mt-1">Select the food items to include in this menu. They will use their default base prices.</p>
                        </div>

                        <div class="space-y-2">
                            <label class="text-sm font-bold text-slate-700 dark:text-slate-300">Tax Percentage (%)</label>
                            <input v-model="form.tax_percentage" type="number" step="0.01" min="0" max="100" class="pos-input w-full" />
                            <div v-if="form.errors.tax_percentage" class="text-red-500 text-xs">{{ form.errors.tax_percentage }}</div>
                        </div>

                        <div class="space-y-2">
                            <label class="text-sm font-bold text-slate-700 dark:text-slate-300">Discount Percentage (%)</label>
                            <input v-model="form.discount_percentage" type="number" step="0.01" min="0" max="100" class="pos-input w-full" />
                            <div v-if="form.errors.discount_percentage" class="text-red-500 text-xs">{{ form.errors.discount_percentage }}</div>
                        </div>
                        
                        <div class="md:col-span-2 mt-2">
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" v-model="form.is_active" class="sr-only peer">
                                <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer dark:bg-slate-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-slate-600 peer-checked:bg-indigo-500 dark:peer-checked:bg-[#06b6d4]"></div>
                                <span class="ml-3 text-sm font-medium text-slate-700 dark:text-slate-300">Active Menu</span>
                            </label>
                        </div>
                    </div>
                    
                    <div class="flex justify-end gap-3 mt-8">
                        <button type="button" @click="closeModal" class="px-4 py-2 text-sm font-bold text-slate-600 dark:text-slate-400 hover:text-slate-800 dark:hover:text-white transition-colors">
                            Cancel
                        </button>
                        <button type="button" @click="submit" :disabled="form.processing" class="pos-btn-primary !py-2 !px-4">
                            {{ form.processing ? 'Saving...' : (isEditing ? 'Save Changes' : 'Create') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>
