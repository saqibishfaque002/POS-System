<script setup>
import { computed, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';

const props = defineProps({
    isOpen: Boolean,
    production: Object,
    recipes: Array,
    branches: Array,
});

const emit = defineEmits(['close', 'submit']);

const form = useForm({
    id: null,
    branch_id: '',
    production_type: 'raw_material',
    recipe_id: '',
    target_yield_qty: 1,
    notes: '',
    items: [],
    total_cost: 0,
});

watch(() => props.isOpen, (newVal) => {
    if (newVal) {
        if (props.production) {
            form.id = props.production.id;
            form.branch_id = props.production.branch_id;
            form.production_type = props.production.production_type || 'raw_material';
            form.recipe_id = props.production.recipe_id;
            form.target_yield_qty = props.production.target_yield_qty;
            form.notes = props.production.notes || '';
            form.items = props.production.items.map(item => ({
                id: item.id,
                ingredient_item_id: item.ingredient_item_id,
                expected_qty: item.expected_qty,
                actual_qty: item.actual_qty,
                unit_id: item.unit_id,
                unit_cost: item.unit_cost,
                total_cost: item.total_cost,
                _ingredient_name: item.ingredient_item?.name || 'Unknown',
                _unit_code: item.unit?.short_code || 'Unit'
            }));
            form.total_cost = props.production.total_cost;
        } else {
            form.reset();
            form.clearErrors();
        }
    }
});

const filteredRecipes = computed(() => {
    return props.recipes.filter(r => {
        if (form.production_type === 'raw_material') {
            return r.recipeable_type === 'App\\Models\\IngredientItem';
        } else if (form.production_type === 'food_item') {
            return r.recipeable_type === 'App\\Models\\FoodItem';
        }
        return false;
    });
});

const selectedRecipe = computed(() => {
    return props.recipes.find(r => r.id === form.recipe_id);
});

watch(() => form.production_type, () => {
    if (!props.production) {
        form.recipe_id = '';
        form.items = [];
        form.total_cost = 0;
    }
});

// Auto BOM Explosion
watch([() => form.recipe_id, () => form.target_yield_qty], ([newRecipeId, newTargetQty]) => {
    // Only auto-fill if creating a new production, not when editing
    if (!props.production && newRecipeId && selectedRecipe.value) {
        const recipeYield = parseFloat(selectedRecipe.value.yield_quantity || 1);
        const multiplier = parseFloat(newTargetQty || 0) / recipeYield;

        form.items = selectedRecipe.value.ingredients.map(ing => {
            const ingredientItem = ing.ingredient_item;
            const expectedQty = parseFloat(ing.quantity) * multiplier;
            
            // Calculate base cost
            let unitCost = 0;
            if (ingredientItem) {
                const convQty = parseFloat(ingredientItem.conversion_qty || 1);
                const consmpQty = parseFloat(ingredientItem.consumption_qty || 1);
                const basePrice = parseFloat(ingredientItem.base_price || 0);
                unitCost = basePrice / (convQty / consmpQty);
            }
            
            return {
                ingredient_item_id: ing.ingredient_item_id,
                expected_qty: expectedQty.toFixed(4),
                actual_qty: expectedQty.toFixed(4), // default actual to expected
                unit_id: ing.unit_id || (ingredientItem ? ingredientItem.consumption_unit_id : null),
                unit_cost: unitCost.toFixed(2),
                total_cost: (expectedQty * unitCost).toFixed(2),
                _ingredient_name: ingredientItem ? ingredientItem.name : 'Unknown',
                _unit_code: ing.unit ? ing.unit.short_code : 'Unit'
            };
        });
        
        calculateTotalCost();
    }
});

const calculateTotalCost = () => {
    let sum = 0;
    form.items.forEach(item => {
        item.total_cost = (parseFloat(item.actual_qty || 0) * parseFloat(item.unit_cost || 0)).toFixed(2);
        sum += parseFloat(item.total_cost);
    });
    form.total_cost = sum.toFixed(2);
};

const submitForm = () => {
    if (form.id) {
        form.put(route('production.update', form.id), {
            onSuccess: () => emit('close')
        });
    } else {
        form.post(route('production.store'), {
            onSuccess: () => emit('close')
        });
    }
};

const closeModal = () => {
    emit('close');
};
</script>

<template>
    <div v-if="isOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm transition-all duration-300">
        <div class="bg-white dark:bg-slate-900 w-full max-w-4xl rounded-2xl shadow-2xl flex flex-col max-h-[90vh] border border-slate-200 dark:border-slate-700 overflow-hidden">
            <!-- Header -->
            <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-800 flex justify-between items-center bg-slate-50 dark:bg-slate-800/50">
                <h2 class="text-xl font-bold text-slate-800 dark:text-white flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 002-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                    </svg>
                    {{ production ? 'Edit Production Batch' : 'New Production Batch' }}
                </h2>
                <button @click="closeModal" class="text-slate-400 hover:text-rose-500 transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <form @submit.prevent="submitForm" class="flex-1 overflow-y-auto">
                <div class="p-6">
                    <!-- Top Section: What to produce -->
                    <div class="mb-6 bg-slate-50 dark:bg-slate-800/30 p-4 rounded-xl border border-slate-200 dark:border-slate-700">
                        <InputLabel value="Production For" class="mb-3" />
                        <div class="flex gap-4">
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="radio" v-model="form.production_type" value="raw_material" class="text-indigo-600 focus:ring-indigo-500" :disabled="!!production" />
                                <span class="text-slate-700 dark:text-slate-300 font-medium">Raw Material (Sub-Recipe)</span>
                            </label>
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="radio" v-model="form.production_type" value="food_item" class="text-indigo-600 focus:ring-indigo-500" :disabled="!!production" />
                                <span class="text-slate-700 dark:text-slate-300 font-medium">Food Item (Menu Item)</span>
                            </label>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                        <div>
                            <InputLabel for="branch_id" value="Branch" />
                            <select id="branch_id" v-model="form.branch_id" class="pos-input w-full mt-1" required>
                                <option value="" disabled>Select Branch</option>
                                <option v-for="branch in branches" :key="branch.id" :value="branch.id">{{ branch.name }}</option>
                            </select>
                            <InputError :message="form.errors.branch_id" class="mt-2" />
                        </div>
                        
                        <div>
                            <InputLabel for="recipe_id" value="Recipe / Product to Prepare" />
                            <select id="recipe_id" v-model="form.recipe_id" class="pos-input w-full mt-1" required :disabled="filteredRecipes.length === 0">
                                <option value="" disabled>Select Recipe</option>
                                <option v-for="recipe in filteredRecipes" :key="recipe.id" :value="recipe.id">
                                    {{ recipe.name }} (Yield: {{ recipe.yield_quantity }})
                                </option>
                            </select>
                            <InputError :message="form.errors.recipe_id" class="mt-2" />
                            <p v-if="filteredRecipes.length === 0" class="text-rose-500 text-xs mt-1 font-medium">
                                No recipes found for the selected type. Please add a recipe first.
                            </p>
                        </div>

                        <div>
                            <InputLabel for="target_yield_qty" value="Target Yield Quantity" />
                            <div class="relative mt-1">
                                <TextInput id="target_yield_qty" type="number" step="0.01" min="0.01" v-model="form.target_yield_qty" class="w-full pr-12 text-lg font-bold text-indigo-700 dark:text-indigo-400" required />
                                <span class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 font-medium">Qty</span>
                            </div>
                            <InputError :message="form.errors.target_yield_qty" class="mt-2" />
                        </div>
                    </div>

                    <!-- Middle Section: Auto-filled BOM -->
                    <div v-if="form.items.length > 0" class="mb-8">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-bold text-slate-700 dark:text-slate-300">Required Ingredients (BOM)</h3>
                            <span class="px-3 py-1 bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400 text-xs font-bold rounded-full border border-green-200 dark:border-green-800">
                                Auto-Calculated
                            </span>
                        </div>
                        
                        <div class="bg-slate-50 dark:bg-slate-800/50 rounded-xl border border-slate-200 dark:border-slate-700 overflow-hidden">
                            <table class="w-full text-sm text-left">
                                <thead class="bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 font-semibold uppercase text-xs">
                                    <tr>
                                        <th class="px-4 py-3">Ingredient</th>
                                        <th class="px-4 py-3 text-right">Expected Qty</th>
                                        <th class="px-4 py-3 text-right w-32">Actual Qty</th>
                                        <th class="px-4 py-3 text-right">Unit Cost</th>
                                        <th class="px-4 py-3 text-right">Total Cost</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                                    <tr v-for="(item, index) in form.items" :key="index" class="hover:bg-white dark:hover:bg-slate-800 transition-colors">
                                        <td class="px-4 py-3 font-medium text-slate-700 dark:text-slate-300">
                                            {{ item._ingredient_name }}
                                        </td>
                                        <td class="px-4 py-3 text-right text-slate-500">
                                            {{ item.expected_qty }} {{ item._unit_code }}
                                        </td>
                                        <td class="px-4 py-3 text-right">
                                            <input type="number" step="0.0001" min="0" v-model="item.actual_qty" @input="calculateTotalCost" class="pos-input w-24 text-right py-1 px-2 !text-sm" />
                                        </td>
                                        <td class="px-4 py-3 text-right text-slate-500">
                                            ${{ item.unit_cost }}
                                        </td>
                                        <td class="px-4 py-3 text-right font-bold text-slate-700 dark:text-slate-300">
                                            ${{ item.total_cost }}
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <InputError :message="form.errors.items" class="mt-2" />
                    </div>
                    <div v-else-if="form.recipe_id" class="mb-8 p-8 text-center bg-slate-50 dark:bg-slate-800/50 rounded-xl border border-dashed border-slate-300 dark:border-slate-700">
                        <p class="text-slate-500 dark:text-slate-400">Loading recipe ingredients...</p>
                    </div>

                    <!-- Bottom Section: Notes & Total Cost -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <InputLabel for="notes" value="Production Notes (Optional)" />
                            <textarea id="notes" v-model="form.notes" rows="3" class="pos-input w-full mt-1" placeholder="e.g. Added extra water due to dry flour..."></textarea>
                        </div>
                        <div class="flex items-end justify-end">
                            <div class="bg-indigo-50 dark:bg-indigo-900/30 border border-indigo-100 dark:border-indigo-800 p-4 rounded-xl text-right w-full md:w-2/3">
                                <span class="block text-sm font-bold text-indigo-600 dark:text-indigo-400 mb-1">Total Estimated Cost</span>
                                <span class="block text-3xl font-black text-indigo-700 dark:text-indigo-300">${{ form.total_cost }}</span>
                                <span v-if="form.target_yield_qty > 0" class="block text-xs font-medium text-indigo-500/70 dark:text-indigo-400/70 mt-1">
                                    ~ ${{ (form.total_cost / form.target_yield_qty).toFixed(2) }} per unit
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Footer -->
                <div class="px-6 py-4 bg-slate-50 dark:bg-slate-800/80 border-t border-slate-200 dark:border-slate-700 flex justify-end gap-3 sticky bottom-0">
                    <button type="button" @click="closeModal" class="px-5 py-2.5 text-sm font-bold text-slate-600 dark:text-slate-400 hover:bg-slate-200 dark:hover:bg-slate-700 rounded-xl transition-colors">
                        Cancel
                    </button>
                    <button type="submit" class="pos-btn-primary" :disabled="form.processing || form.items.length === 0">
                        <span v-if="form.processing">Saving...</span>
                        <span v-else>{{ production ? 'Update Batch' : 'Create Production Batch' }}</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>
