<script setup>
import { computed } from 'vue';
import SelectInput from '@/Components/SelectInput.vue';

const props = defineProps({
    isOpen: Boolean,
    editingItem: Object,
    activeTab: String,
    formFields: Array,
    form: Object,
    tabs: Array,
    globalVariations: Array
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
    return tab ? tab.label : '';
});
</script>

<template>
    <div v-if="isOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-sm">
        <div class="dashboard-card w-full max-w-2xl p-6 max-h-[90vh] overflow-y-auto">
            <h2 class="text-xl font-bold mb-6 text-slate-800 dark:text-white">
                {{ editingItem ? 'Edit' : 'Create' }} {{ activeTabLabel }}
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
                        <SelectInput 
                            v-model="form[field.key]" 
                            :options="field.options" 
                            :multiple="!!field.multiple"
                            valueKey="value"
                            labelKey="label"
                            :placeholder="field.placeholder || (field.multiple ? 'Select options...' : 'Select an option')" 
                            searchPlaceholder="Search option..."
                        />
                    </template>
                    
                    <template v-else-if="field.type === 'custom_variations'">
                        <div v-if="form.has_variations" class="mt-4 border dark:border-slate-700 rounded-lg p-4 bg-slate-50 dark:bg-slate-800/50">
                            <div class="flex justify-between items-center mb-3">
                                <h3 class="text-sm font-semibold text-slate-700 dark:text-slate-300">Item Variations</h3>
                                <button type="button" @click="form.variations.push({ variation_id: '', price: 0, sku: '' })" class="text-xs bg-indigo-100 text-indigo-700 dark:bg-indigo-900/50 dark:text-indigo-400 px-2 py-1 rounded hover:bg-indigo-200 dark:hover:bg-indigo-900 transition-colors">
                                    + Add Row
                                </button>
                            </div>
                            
                            <div v-for="(v, index) in form.variations" :key="index" class="flex gap-2 mb-2 items-start">
                                <div class="flex-1">
                                    <SelectInput 
                                        v-model="v.variation_id" 
                                        :options="globalVariations" 
                                        placeholder="Select Variation" 
                                        searchPlaceholder="Search variation..."
                                    />
                                </div>
                                <div class="w-24">
                                    <input type="number" step="0.01" v-model="v.price" placeholder="Price" class="pos-input w-full !py-1.5 text-sm" required>
                                </div>
                                <div class="w-24">
                                    <input type="text" v-model="v.sku" placeholder="SKU" class="pos-input w-full !py-1.5 text-sm">
                                </div>
                                <button type="button" @click="form.variations.splice(index, 1)" class="p-1.5 text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 rounded transition-colors" title="Remove">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" />
                                    </svg>
                                </button>
                            </div>
                            <div v-if="form.variations.length === 0" class="text-xs text-slate-500 italic py-2">
                                No variations added. Click '+ Add Row'.
                            </div>
                        </div>
                    </template>
                    
                    <template v-else>
                        <input v-if="field.showIf === undefined || field.showIf(form)" :type="field.type" v-model="form[field.key]" :step="field.type==='number' ? '0.01' : null" class="pos-input w-full" :required="field.required !== false">
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
