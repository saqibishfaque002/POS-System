<script setup>
import { computed } from 'vue';
import SelectInput from '@/Components/SelectInput.vue';

const props = defineProps({
    isOpen: Boolean,
    editingItem: Object,
    activeTab: String,
    formFields: Array,
    form: Object,
    tabs: Array
});

const emit = defineEmits(['close', 'submit']);

const submitForm = () => {
    emit('submit');
};

const closeModal = () => {
    emit('close');
};

const activeTabLabel = computed(() => {
    const tab = props.tabs.find(t => t.id === props.activeTab);
    return tab ? tab.label.slice(0, -1) : ''; // Remove plural 's'
});
</script>

<template>
    <div v-if="isOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-sm">
        <div :class="['dashboard-card w-full p-6 max-h-[90vh] overflow-y-auto transition-all duration-300', (activeTab === 'ingredientItems' || activeTab === 'ingredientUnits') ? 'max-w-3xl' : 'max-w-lg']">
            <h2 class="text-xl font-bold mb-6 text-slate-800 dark:text-white">
                {{ editingItem ? 'Edit' : 'Create' }} {{ activeTabLabel }}
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
                            <SelectInput 
                                v-model="form[field.key]" 
                                :options="field.options" 
                                valueKey="value"
                                labelKey="label"
                                placeholder="Select an option" 
                                searchPlaceholder="Search option..."
                            />
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
