<script setup>
import { ref, computed, watch } from 'vue';

const props = defineProps({
    isOpen: Boolean,
    item: Object,
    propertyGroups: {
        type: Array,
        default: () => []
    },
});

const emit = defineEmits(['close', 'addToCart']);

const selectedVariationId = ref(null);
const selectedProperties = ref([]);
const quantity = ref(1);
const itemNotes = ref('');

watch(() => props.item, (newItem) => {
    if (newItem) {
        quantity.value = 1;
        itemNotes.value = '';
        selectedProperties.value = [];
        
        // Auto-select first variation if item has variations
        if (newItem.variations && newItem.variations.length > 0) {
            selectedVariationId.value = newItem.variations[0].variation_id;
        } else {
            selectedVariationId.value = null;
        }
    }
}, { immediate: true });

const selectedVariation = computed(() => {
    if (!props.item?.variations || !selectedVariationId.value) return null;
    return props.item.variations.find(v => v.variation_id == selectedVariationId.value);
});

const unitPrice = computed(() => {
    let base = 0;
    if (selectedVariation.value) {
        base = Number(selectedVariation.value.price || 0);
    } else {
        base = Number(props.item?.effective_price ?? props.item?.base_price ?? 0);
    }

    const addonsTotal = selectedProperties.value.reduce((sum, p) => sum + Number(p.extra_price || 0), 0);
    return base + addonsTotal;
});

const totalPrice = computed(() => {
    return unitPrice.value * quantity.value;
});

const toggleProperty = (item, isMultiple) => {
    const existsIndex = selectedProperties.value.findIndex(p => p.property_item_id === item.id);
    if (existsIndex > -1) {
        selectedProperties.value.splice(existsIndex, 1);
    } else {
        if (!isMultiple) {
            // Remove any other item from the same group
            selectedProperties.value = selectedProperties.value.filter(p => p.property_group_id !== item.property_group_id);
        }
        selectedProperties.value.push({
            property_group_id: item.property_group_id,
            property_item_id: item.id,
            property_name: item.name,
            extra_price: Number(item.extra_price || 0),
            quantity: 1,
        });
    }
};

const isPropertySelected = (propertyItemId) => {
    return selectedProperties.value.some(p => p.property_item_id === propertyItemId);
};

const handleConfirm = () => {
    emit('addToCart', {
        food_item_id: props.item.id,
        name: props.item.name,
        food_group_id: props.item.food_group_id,
        variation_id: selectedVariation.value?.variation_id || null,
        variation_name: selectedVariation.value?.name || null,
        unit_price: unitPrice.value,
        quantity: quantity.value,
        total_price: totalPrice.value,
        notes: itemNotes.value.trim() || null,
        properties: [...selectedProperties.value],
    });
    emit('close');
};

const formatPrice = (val) => {
    return Number(val || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
};
</script>

<template>
    <div v-if="isOpen && item" class="fixed inset-0 z-50 flex items-center justify-center p-2.5 sm:p-4 bg-slate-900/60 backdrop-blur-xs select-none">
        <div class="bg-white dark:bg-slate-900 w-full max-w-lg rounded-2xl shadow-2xl border border-slate-200 dark:border-slate-800 overflow-hidden flex flex-col max-h-[90vh] animate-in fade-in zoom-in-95 duration-150">
            <!-- Modal Header -->
            <div class="px-4 sm:px-5 py-3 sm:py-4 border-b border-slate-200 dark:border-slate-800 flex items-start justify-between bg-slate-50/70 dark:bg-slate-800/40">
                <div>
                    <span class="text-[10px] font-bold uppercase tracking-wider text-indigo-600 dark:text-cyan-400 bg-indigo-50 dark:bg-cyan-950/60 px-2 py-0.5 rounded">
                        {{ item.food_group_name || 'Customize Item' }}
                    </span>
                    <h3 class="text-sm sm:text-base font-black text-slate-900 dark:text-white mt-1">
                        {{ item.name }}
                    </h3>
                    <p v-if="item.description" class="text-xs text-slate-500 dark:text-slate-400 mt-0.5 line-clamp-1">
                        {{ item.description }}
                    </p>
                </div>
                <button
                    type="button"
                    @click="emit('close')"
                    class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 p-1 rounded-lg cursor-pointer"
                >
                    ✕
                </button>
            </div>

            <!-- Modal Body (Scrollable) -->
            <div class="p-3.5 sm:p-5 overflow-y-auto space-y-4 sm:space-y-5 flex-1">
                <!-- Variations Section (if item has variations) -->
                <div v-if="item.variations && item.variations.length > 0">
                    <label class="text-xs font-black uppercase text-slate-700 dark:text-slate-300 tracking-wider block mb-2">
                        Select Size / Variation <span class="text-rose-500">*</span>
                    </label>
                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-2.5">
                        <button
                            v-for="v in item.variations"
                            :key="v.id"
                            type="button"
                            @click="selectedVariationId = v.variation_id"
                            :class="[
                                'p-3 rounded-xl border text-left transition-all relative flex flex-col justify-between',
                                selectedVariationId == v.variation_id
                                    ? 'border-indigo-600 bg-indigo-50/80 dark:bg-cyan-950/40 dark:border-cyan-500 shadow-xs'
                                    : 'border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800/60 hover:border-slate-300'
                            ]"
                        >
                            <span class="text-xs font-black text-slate-800 dark:text-slate-100">
                                {{ v.name }}
                            </span>
                            <span class="text-xs font-bold text-indigo-600 dark:text-cyan-400 mt-1">
                                Rs. {{ formatPrice(v.price) }}
                            </span>
                            <span
                                v-if="selectedVariationId == v.variation_id"
                                class="absolute top-2 right-2 w-2 h-2 rounded-full bg-indigo-600 dark:bg-cyan-500"
                            ></span>
                        </button>
                    </div>
                </div>

                <!-- Addons / Property Groups Section -->
                <div v-if="propertyGroups && propertyGroups.length > 0" class="space-y-4">
                    <div v-for="group in propertyGroups" :key="group.id" class="border-t border-slate-100 dark:border-slate-800/80 pt-3">
                        <div class="flex items-center justify-between mb-2">
                            <label class="text-xs font-black uppercase text-slate-700 dark:text-slate-300 tracking-wider">
                                {{ group.name }}
                            </label>
                            <span class="text-[10px] text-slate-400">
                                {{ group.is_multiple ? 'Choose multiple' : 'Choose one' }}
                            </span>
                        </div>

                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
                            <button
                                v-for="propItem in group.property_items"
                                :key="propItem.id"
                                type="button"
                                @click="toggleProperty(propItem, group.is_multiple)"
                                :class="[
                                    'p-2.5 rounded-xl border text-left transition-all text-xs flex items-center justify-between',
                                    isPropertySelected(propItem.id)
                                        ? 'border-indigo-500 bg-indigo-50/80 dark:bg-cyan-950/40 dark:border-cyan-500 text-indigo-900 dark:text-cyan-200 font-bold'
                                        : 'border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800/40 text-slate-700 dark:text-slate-300 hover:border-slate-300'
                                ]"
                            >
                                <span>{{ propItem.name }}</span>
                                <span class="text-[10px] text-slate-500 dark:text-slate-400 font-semibold">
                                    +Rs. {{ formatPrice(propItem.extra_price) }}
                                </span>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Special Instructions / Notes -->
                <div class="border-t border-slate-100 dark:border-slate-800/80 pt-3">
                    <label class="text-xs font-black uppercase text-slate-700 dark:text-slate-300 tracking-wider block mb-1.5">
                        Cooking Notes / Instructions
                    </label>
                    <input
                        type="text"
                        v-model="itemNotes"
                        placeholder="e.g. Less spicy, Extra ketchup, No mayonnaise..."
                        class="w-full text-xs bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl px-3 py-2 text-slate-800 dark:text-slate-100 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500"
                    />
                </div>

                <!-- Quantity Stepper -->
                <div class="border-t border-slate-100 dark:border-slate-800/80 pt-3 flex items-center justify-between">
                    <span class="text-xs font-black uppercase text-slate-700 dark:text-slate-300 tracking-wider">
                        Quantity
                    </span>
                    <div class="flex items-center space-x-2 bg-slate-100 dark:bg-slate-800 p-1 rounded-xl border border-slate-200 dark:border-slate-700">
                        <button
                            type="button"
                            @click="quantity = Math.max(1, quantity - 1)"
                            class="w-8 h-8 rounded-lg bg-white dark:bg-slate-700 text-slate-800 dark:text-slate-200 flex items-center justify-center font-black shadow-xs hover:bg-slate-200 transition-colors"
                        >
                            -
                        </button>
                        <span class="w-8 text-center font-black text-sm text-slate-900 dark:text-white">
                            {{ quantity }}
                        </span>
                        <button
                            type="button"
                            @click="quantity++"
                            class="w-8 h-8 rounded-lg bg-white dark:bg-slate-700 text-slate-800 dark:text-slate-200 flex items-center justify-center font-black shadow-xs hover:bg-slate-200 transition-colors"
                        >
                            +
                        </button>
                    </div>
                </div>
            </div>

            <!-- Modal Footer: Live Price & Add Button -->
            <div class="p-4 border-t border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-800/50 flex items-center justify-between">
                <div>
                    <span class="text-[10px] uppercase font-bold text-slate-400">Total Price</span>
                    <p class="text-base font-black text-slate-900 dark:text-white">
                        <span class="text-xs font-bold text-slate-500 mr-0.5">Rs.</span>
                        <span>{{ formatPrice(totalPrice) }}</span>
                    </p>
                </div>
                <div class="flex items-center space-x-2">
                    <button
                        type="button"
                        @click="emit('close')"
                        class="px-4 py-2 text-xs font-bold text-slate-600 dark:text-slate-400 hover:text-slate-800 dark:hover:text-slate-200"
                    >
                        Cancel
                    </button>
                    <button
                        type="button"
                        @click="handleConfirm"
                        class="px-5 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-black text-xs shadow-md active:scale-95 transition-all dark:bg-cyan-600 dark:hover:bg-cyan-700 flex items-center space-x-1.5"
                    >
                        <span>Add to Order</span>
                        <span>• Rs. {{ formatPrice(totalPrice) }}</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
