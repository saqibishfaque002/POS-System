<script setup>
import { ref, computed, nextTick, onMounted, onBeforeUnmount } from 'vue';
const props = defineProps({
    modelValue: {
        type: [Array, String, Number, Boolean],
        default: null
    },
    options: {
        type: Array,
        required: true
    },
    placeholder: {
        type: String,
        default: 'Select options...'
    },
    searchPlaceholder: {
        type: String,
        default: 'Search...'
    },
    valueKey: {
        type: String,
        default: 'id'
    },
    labelKey: {
        type: String,
        default: 'name'
    },
    multiple: {
        type: Boolean,
        default: false
    },
    disabled: {
        type: Boolean,
        default: false
    }
});

const emit = defineEmits(['update:modelValue']);

const isOpen = ref(false);
const searchQuery = ref('');
const dropdownRef = ref(null);
const searchInputRef = ref(null);
const dropdownStyle = ref({});

const updatePosition = () => {
    if (!dropdownRef.value || !isOpen.value) return;
    const rect = dropdownRef.value.getBoundingClientRect();
    
    // Check if there is enough space below, otherwise show above
    const spaceBelow = window.innerHeight - rect.bottom;
    const dropdownHeight = 256; // max-h-64 is 256px
    
    if (spaceBelow < dropdownHeight && rect.top > dropdownHeight) {
        // Show above
        dropdownStyle.value = {
            position: 'fixed',
            bottom: `${window.innerHeight - rect.top + 4}px`,
            left: `${rect.left}px`,
            width: `${rect.width}px`,
            zIndex: 999999,
        };
    } else {
        // Show below
        dropdownStyle.value = {
            position: 'fixed',
            top: `${rect.bottom + 4}px`,
            left: `${rect.left}px`,
            width: `${rect.width}px`,
            zIndex: 999999,
        };
    }
};

const isSelected = (val) => {
    if (props.multiple) {
        return Array.isArray(props.modelValue) && props.modelValue.includes(val);
    }
    return props.modelValue === val;
};

const selectedItems = computed(() => {
    if (props.multiple) {
        return props.options.filter(opt => Array.isArray(props.modelValue) && props.modelValue.includes(opt[props.valueKey]));
    } else {
        const item = props.options.find(opt => opt[props.valueKey] === props.modelValue);
        return item ? [item] : [];
    }
});

const filteredOptions = computed(() => {
    if (!searchQuery.value) return props.options;
    const query = searchQuery.value.toLowerCase();
    return props.options.filter(opt => {
        const label = String(opt[props.labelKey]).toLowerCase();
        const sku = opt.sku ? String(opt.sku).toLowerCase() : '';
        return label.includes(query) || sku.includes(query);
    });
});

const toggleDropdown = async () => {
    if (props.disabled) return;
    
    isOpen.value = !isOpen.value;
    if (isOpen.value) {
        searchQuery.value = '';
        updatePosition();
        window.addEventListener('scroll', updatePosition, true);
        window.addEventListener('resize', updatePosition);
        
        await nextTick();
        if (searchInputRef.value) {
            searchInputRef.value.focus();
        }
    } else {
        window.removeEventListener('scroll', updatePosition, true);
        window.removeEventListener('resize', updatePosition);
    }
};

const closeDropdown = (e) => {
    if (isOpen.value) {
        // Find if the click is inside the teleported dropdown or the trigger
        const isClickInsideTrigger = dropdownRef.value && dropdownRef.value.contains(e.target);
        
        // Let's rely on event bubbling inside the teleported component to avoid closing it
        // Or we can check if it has a specific class or id, but standard is:
        const dropdownElement = document.getElementById('select-dropdown-container');
        const isClickInsideDropdown = dropdownElement && dropdownElement.contains(e.target);
        
        if (!isClickInsideTrigger && !isClickInsideDropdown) {
            isOpen.value = false;
            window.removeEventListener('scroll', updatePosition, true);
            window.removeEventListener('resize', updatePosition);
        }
    }
};

onMounted(() => {
    document.addEventListener('click', closeDropdown);
});

onBeforeUnmount(() => {
    document.removeEventListener('click', closeDropdown);
    window.removeEventListener('scroll', updatePosition, true);
    window.removeEventListener('resize', updatePosition);
});

const selectOption = (opt) => {
    const val = opt[props.valueKey];
    
    if (props.multiple) {
        let newValues = Array.isArray(props.modelValue) ? [...props.modelValue] : [];
        if (newValues.includes(val)) {
            newValues = newValues.filter(v => v !== val);
        } else {
            newValues.push(val);
        }
        emit('update:modelValue', newValues);
        // Don't close for multiple
    } else {
        emit('update:modelValue', val);
        isOpen.value = false;
        window.removeEventListener('scroll', updatePosition, true);
        window.removeEventListener('resize', updatePosition);
    }
};

const removeOption = (val) => {
    if (props.disabled) return;
    if (props.multiple) {
        const newValues = (props.modelValue || []).filter(v => v !== val);
        emit('update:modelValue', newValues);
    } else {
        emit('update:modelValue', null);
    }
};
</script>

<template>
    <div class="relative w-full" ref="dropdownRef">
        <!-- Main Input Display -->
        <div 
            @click="toggleDropdown"
            class="pos-input w-full min-h-[42px] flex flex-wrap items-center gap-1.5 p-1.5 pr-8 transition-colors"
            :class="{
                'ring-2 ring-indigo-500 border-transparent': isOpen, 
                'cursor-pointer': !disabled, 
                'opacity-50 cursor-not-allowed bg-slate-50 dark:bg-slate-900': disabled
            }"
        >
            <div v-if="selectedItems.length === 0" class="text-slate-400 dark:text-slate-500 px-2 py-1 select-none text-sm">
                {{ placeholder }}
            </div>
            
            <!-- Multiple Selection Tags -->
            <template v-if="multiple">
                <div 
                    v-for="item in selectedItems" 
                    :key="item[valueKey]"
                    class="flex items-center gap-1 bg-indigo-100 dark:bg-indigo-900/40 text-indigo-700 dark:text-[#06b6d4] px-2 py-0.5 rounded-md text-sm border border-indigo-200 dark:border-indigo-800/50"
                >
                    <span class="max-w-[150px] truncate">{{ item[labelKey] }}</span>
                    <button 
                        v-if="!disabled"
                        type="button" 
                        @click.stop="removeOption(item[valueKey])"
                        class="hover:text-red-500 focus:outline-none flex items-center justify-center h-4 w-4 rounded-full hover:bg-indigo-200 dark:hover:bg-indigo-800/50 transition-colors"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                        </svg>
                    </button>
                </div>
            </template>

            <!-- Single Selection Text -->
            <template v-else>
                <div v-if="selectedItems.length > 0" class="px-2 py-1 text-sm text-slate-800 dark:text-slate-200 truncate flex-1">
                    {{ selectedItems[0][labelKey] }} <span v-if="selectedItems[0].sku" class="text-xs text-slate-400">({{ selectedItems[0].sku }})</span>
                </div>
            </template>
        </div>
        
        <!-- Chevron Icon -->
        <div class="absolute right-3 top-1/2 -translate-y-1/2 pointer-events-none text-slate-400">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 transition-transform duration-200" :class="{'rotate-180': isOpen}" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
            </svg>
        </div>

        <!-- Dropdown Menu -->
        <Teleport to="body">
            <transition 
                enter-active-class="transition ease-out duration-100" 
                enter-from-class="transform opacity-0 scale-95" 
                enter-to-class="transform opacity-100 scale-100" 
                leave-active-class="transition ease-in duration-75" 
                leave-from-class="transform opacity-100 scale-100" 
                leave-to-class="transform opacity-0 scale-95"
            >
                <div v-show="isOpen" id="select-dropdown-container" :style="dropdownStyle" class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg shadow-lg overflow-hidden flex flex-col max-h-64">
                    
                    <!-- Search Input -->
                <div class="p-2 border-b border-slate-100 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 sticky top-0 z-10">
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-2.5 flex items-center pointer-events-none">
                            <svg class="h-4 w-4 text-slate-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
                            </svg>
                        </div>
                        <input 
                            type="text" 
                            ref="searchInputRef"
                            v-model="searchQuery" 
                            @click.stop 
                            class="w-full pl-9 pr-3 py-1.5 text-sm bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-md focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 text-slate-700 dark:text-slate-300 placeholder-slate-400 transition-shadow"
                            :placeholder="searchPlaceholder"
                        >
                    </div>
                </div>

                <!-- Options List -->
                <div class="overflow-y-auto custom-scrollbar flex-1 p-1">
                    <div v-if="filteredOptions.length === 0" class="px-3 py-4 text-sm text-slate-500 text-center italic">
                        No results found
                    </div>
                    
                    <div 
                        v-for="opt in filteredOptions" 
                        :key="opt[valueKey]"
                        @click.stop="selectOption(opt)"
                        class="px-3 py-2 cursor-pointer rounded-md flex items-center gap-3 text-sm transition-colors mb-0.5"
                        :class="isSelected(opt[valueKey]) ? 'bg-indigo-50 dark:bg-indigo-900/30 text-indigo-700 dark:text-[#06b6d4] font-medium' : 'text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700'"
                    >
                        <!-- Checkbox indicator for multiple selection -->
                        <div v-if="multiple" class="w-4 h-4 rounded border flex items-center justify-center flex-shrink-0 transition-colors" :class="isSelected(opt[valueKey]) ? 'bg-indigo-500 border-indigo-500 text-white' : 'border-slate-300 dark:border-slate-500 bg-white dark:bg-slate-800'">
                            <svg v-if="isSelected(opt[valueKey])" xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        
                        <!-- Check icon for single selection -->
                        <div v-else class="w-4 h-4 flex items-center justify-center flex-shrink-0 text-indigo-500 dark:text-[#06b6d4]">
                            <svg v-if="isSelected(opt[valueKey])" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        
                        <span class="truncate">{{ opt[labelKey] }} <span v-if="opt.sku" class="text-xs text-slate-400 dark:text-slate-500 font-normal ml-1">({{ opt.sku }})</span></span>
                    </div>
                </div>
                </div>
            </transition>
        </Teleport>
    </div>
</template>
