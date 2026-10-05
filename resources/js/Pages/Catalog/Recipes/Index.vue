<script setup>
import { ref, computed, watch } from 'vue';
import { useForm, Link } from '@inertiajs/vue3';
import InventoryLayout from '@/Layouts/InventoryLayout.vue';
import DataTable from '@/Components/DataTable.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import SelectInput from '@/Components/SelectInput.vue';

const props = defineProps({
    recipes: Array,
    foodItems: Array,
    propertyItems: Array,
    ingredientItems: Array,
    units: Array,
    orderTypes: Array,
});


const columns = [
    { key: 'item_name', label: 'Item Name' },
    { key: 'type', label: 'Type' },
    { key: 'total_cost', label: 'Total Cost' },
    { key: 'ingredients_count', label: 'Ingredients' },
    { key: 'status', label: 'Status' },
];

const isModalOpen = ref(false);
const editingRecipe = ref(null);
const activeTab = ref('standard'); // 'standard' for FoodItem, 'property' for PropertyItem, 'sub' for IngredientItem

const form = useForm({
    selected_base_item_id: '',
    recipeable_id: '',
    recipeable_type: 'App\\Models\\FoodItem',
    total_cost: 0,
    yield_quantity: 1,
    instructions: '',
    status: 'active',
    items: [],
});

const openModal = (recipe = null) => {
    editingRecipe.value = recipe;
    if (recipe) {
        form.recipeable_id = recipe.recipeable_id;
        form.recipeable_type = recipe.recipeable_type;
        form.total_cost = recipe.total_cost;
        form.yield_quantity = recipe.yield_quantity || 1;
        form.instructions = recipe.instructions || '';
        form.status = recipe.status;
        form.items = recipe.ingredients.map(ing => ({
            ingredient_item_id: ing.ingredient_item_id,
            quantity: ing.quantity,
            unit_id: ing.unit_id,
            order_type_ids: ing.order_types ? ing.order_types.map(ot => ot.id) : [],
            cost: 0 // Ideally fetched from item
        }));
        if (recipe.recipeable_type.includes('FoodItemVariation')) {
            activeTab.value = 'standard';
            const foodItem = props.foodItems.find(f => f.variations.some(v => v.id === parseInt(recipe.recipeable_id)));
            if (foodItem) {
                form.selected_base_item_id = foodItem.id;
            }
        } else if (recipe.recipeable_type.includes('FoodItem')) {
            activeTab.value = 'standard';
            form.selected_base_item_id = recipe.recipeable_id;
        } else if (recipe.recipeable_type.includes('PropertyItem')) {
            activeTab.value = 'property';
        } else {
            activeTab.value = 'sub';
        }
    } else {
        form.reset();
        form.selected_base_item_id = '';
        form.items = [];
    }
    isModalOpen.value = true;
};

const selectedFoodItem = computed(() => {
    if (!form.selected_base_item_id) return null;
    return props.foodItems.find(i => i.id === parseInt(form.selected_base_item_id));
});

watch(() => form.selected_base_item_id, (newVal) => {
    if (activeTab.value !== 'standard' || editingRecipe.value) return;
    const item = selectedFoodItem.value;
    if (item && item.variations && item.variations.length > 0) {
        form.recipeable_id = '';
        form.recipeable_type = 'App\\Models\\FoodItemVariation';
    } else {
        form.recipeable_id = newVal;
        form.recipeable_type = 'App\\Models\\FoodItem';
    }
});

const closeModal = () => {
    isModalOpen.value = false;
    form.reset();
    form.clearErrors();
};

const addIngredient = () => {
    form.items.push({
        ingredient_item_id: '',
        quantity: 1,
        unit_id: '',
        order_type_ids: [],
    });
};

const toggleOrderType = (item, orderTypeId) => {
    const index = item.order_type_ids.indexOf(orderTypeId);
    if (index > -1) {
        item.order_type_ids.splice(index, 1);
    } else {
        item.order_type_ids.push(orderTypeId);
    }
};

const removeIngredient = (index) => {
    form.items.splice(index, 1);
    calculateTotalCost();
};

const onIngredientChange = (index) => {
    const ingId = form.items[index].ingredient_item_id;
    const item = props.ingredientItems.find(i => i.id === parseInt(ingId));
    if (item) {
        form.items[index].unit_id = item.consumption_unit_id || item.purchase_unit_id;
    }
    calculateTotalCost();
};

const calculateTotalCost = () => {
    let total = 0;
    form.items.forEach((ing, index) => {
        const item = props.ingredientItems.find(i => i.id === parseInt(ing.ingredient_item_id));
        if (item) {
            const conversionQty = parseFloat(item.conversion_qty || 1);
            const consumptionQty = parseFloat(item.consumption_qty || 1);
            const basePrice = parseFloat(item.base_price || 0);
            
            // Cost per 1 consumption unit
            const costPerConsumptionUnit = basePrice / (conversionQty / consumptionQty);
            
            const qty = parseFloat(ing.quantity || 0);
            const lineCost = costPerConsumptionUnit * qty;
            
            total += lineCost;
            form.items[index].cost = lineCost.toFixed(2);
        }
    });
    form.total_cost = total.toFixed(2);
};

const saveRecipe = () => {
    if (activeTab.value === 'standard') {
        form.yield_quantity = 1;
        if (selectedFoodItem.value && selectedFoodItem.value.variations && selectedFoodItem.value.variations.length > 0) {
            form.recipeable_type = 'App\\Models\\FoodItemVariation';
        } else {
            form.recipeable_type = 'App\\Models\\FoodItem';
            form.recipeable_id = form.selected_base_item_id;
        }
    } else if (activeTab.value === 'property') {
        form.recipeable_type = 'App\\Models\\PropertyItem';
        form.yield_quantity = 1;
    } else {
        form.recipeable_type = 'App\\Models\\IngredientItem';
    }
    
    if (editingRecipe.value) {
        form.put(route('recipes.update', editingRecipe.value.id), {
            onSuccess: () => closeModal(),
        });
    } else {
        form.post(route('recipes.store'), {
            onSuccess: () => closeModal(),
        });
    }
};

const switchTab = (tab) => {
    activeTab.value = tab;
    form.recipeable_id = '';
    form.selected_base_item_id = '';
};

const deleteRecipe = (recipe) => {
    if (confirm('Are you sure you want to delete this recipe?')) {
        router.delete(route('recipes.destroy', recipe.id), {
            preserveScroll: true
        });
    }
};
</script>

<template>
    <InventoryLayout title="Recipes & BOM" headerColorClass="border-t-indigo-500">
        <div class="dashboard-card p-6 border-t-4 border-t-indigo-500">
            <DataTable title="Recipes & BOM" :data="recipes" :columns="columns" @edit="openModal" @delete="deleteRecipe">
                <template #actions>
                    <button @click="openModal()" class="pos-btn-primary !py-2 !px-4 !rounded-lg text-sm !bg-indigo-600 hover:!bg-indigo-700 dark:!bg-indigo-600 dark:hover:!bg-indigo-500">
                        + Create Recipe
                    </button>
                </template>
                            
                            <template #cell-item_name="{ item }">
                                <span class="font-bold text-slate-700 dark:text-slate-300">
                                    {{ item.recipeable?.food_item?.name ? `${item.recipeable.food_item.name} (${item.recipeable.variation?.name})` : (item.recipeable?.name || 'Unknown Item') }}
                                </span>
                            </template>

                            <template #cell-type="{ item }">
                                <span v-if="item.recipeable_type.includes('FoodItemVariation')" class="px-2 py-1 bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400 rounded text-xs font-bold">Variation Recipe</span>
                                <span v-else-if="item.recipeable_type.includes('FoodItem')" class="px-2 py-1 bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400 rounded text-xs font-bold">Food Recipe</span>
                                <span v-else-if="item.recipeable_type.includes('PropertyItem')" class="px-2 py-1 bg-indigo-100 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-400 rounded text-xs font-bold">Property Recipe</span>
                                <span v-else class="px-2 py-1 bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400 rounded text-xs font-bold">Sub-Recipe</span>
                            </template>

                            <template #cell-total_cost="{ item }">
                                <span class="font-bold text-slate-700 dark:text-slate-300">
                                    ${{ parseFloat(item.total_cost).toFixed(2) }}
                                </span>
                            </template>
                            
                            <template #cell-ingredients_count="{ item }">
                                <span class="px-2 py-1 bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300 rounded-full text-xs font-bold">
                                    {{ item.ingredients?.length || 0 }} Items
                                </span>
                            </template>

                            <template #cell-status="{ item }">
                                <span class="px-2 py-1 rounded-full text-xs font-bold"
                                    :class="item.status === 'active' ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400' : 'bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-400'">
                                    {{ item.status.toUpperCase() }}
                                </span>
                            </template>
                        </DataTable>
        </div>
        
        <template #modals>
            <div v-if="isModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-sm">
                <div class="dashboard-card w-full max-w-5xl p-6 max-h-[90vh] overflow-y-auto border-t-4 border-t-indigo-500">
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="text-xl font-bold text-slate-800 dark:text-white">{{ editingRecipe ? 'Edit' : 'New' }} Recipe / BOM</h2>
                        <button @click="closeModal" class="text-slate-400 hover:text-slate-500 focus:outline-none">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <form @submit.prevent="saveRecipe" class="space-y-6">
                        
                        <!-- Recipe Type Tabs (Only shown when creating new) -->
                        <div v-if="!editingRecipe" class="flex border-b border-slate-200 dark:border-slate-700 mb-6">
                            <button type="button" @click="switchTab('standard')" :class="['px-4 py-2 font-bold text-sm border-b-2 transition-colors', activeTab === 'standard' ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400' : 'border-transparent text-slate-500 hover:text-slate-700 dark:text-slate-400']">
                                Food Recipe
                            </button>
                            <button type="button" @click="switchTab('property')" :class="['px-4 py-2 font-bold text-sm border-b-2 transition-colors', activeTab === 'property' ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400' : 'border-transparent text-slate-500 hover:text-slate-700 dark:text-slate-400']">
                                Property Recipe
                            </button>
                            <button type="button" @click="switchTab('sub')" :class="['px-4 py-2 font-bold text-sm border-b-2 transition-colors', activeTab === 'sub' ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400' : 'border-transparent text-slate-500 hover:text-slate-700 dark:text-slate-400']">
                                Sub-Recipe
                            </button>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            
                            <!-- Left Column: Final Product Info -->
                            <div class="md:col-span-1 space-y-4">
                                <h3 class="text-lg font-bold text-slate-800 dark:text-white mb-2 pb-2 border-b border-slate-200 dark:border-slate-700">Target Item</h3>
                                
                                <div v-if="activeTab === 'standard'">
                                    <InputLabel for="selected_base_item_id" value="Select Food Item" />
                                    <SelectInput 
                                        v-model="form.selected_base_item_id" 
                                        :options="foodItems" 
                                        placeholder="Select an Item..." 
                                        searchPlaceholder="Search food item..."
                                        :disabled="editingRecipe"
                                    />
                                    <InputError :message="form.errors.recipeable_id" class="mt-2" />
                                </div>
                                
                                <div v-if="activeTab === 'standard' && selectedFoodItem && selectedFoodItem.variations && selectedFoodItem.variations.length > 0">
                                    <InputLabel for="variation_id" value="Select Variation" class="mt-4" />
                                    <SelectInput 
                                        v-model="form.recipeable_id" 
                                        :options="selectedFoodItem.variations.map(v => ({id: v.id, name: v.variation?.name || 'Variation'}))" 
                                        placeholder="Select Variation..." 
                                        searchPlaceholder="Search variation..."
                                        :disabled="editingRecipe"
                                    />
                                    <InputError :message="form.errors.recipeable_id" class="mt-2" />
                                </div>

                                <div v-if="activeTab !== 'standard'">
                                    <InputLabel for="recipeable_id" :value="activeTab === 'property' ? 'Select Property Item' : 'Select Ingredient (Sub-Recipe)'" />
                                    <SelectInput 
                                        v-model="form.recipeable_id" 
                                        :options="activeTab === 'property' ? propertyItems : ingredientItems" 
                                        placeholder="Select an Item..." 
                                        searchPlaceholder="Search item..."
                                        :disabled="editingRecipe"
                                    />
                                    <InputError :message="form.errors.recipeable_id" class="mt-2" />
                                </div>

                                <div v-if="activeTab === 'sub'">
                                    <InputLabel for="yield_quantity" value="Target Item Yield Quantity" />
                                    <TextInput id="yield_quantity" type="number" step="0.001" min="0.001" v-model="form.yield_quantity" class="w-full mt-1" required />
                                    <InputError :message="form.errors.yield_quantity" class="mt-2" />
                                </div>

                                <div>
                                    <InputLabel for="status" value="Status" />
                                    <SelectInput 
                                        v-model="form.status" 
                                        :options="[{id: 'active', name: 'Active'}, {id: 'inactive', name: 'Inactive'}]" 
                                        placeholder="Select Status"
                                    />
                                    <InputError :message="form.errors.status" class="mt-2" />
                                </div>
                                
                                <div>
                                    <InputLabel for="instructions" value="Prep Instructions" />
                                    <textarea id="instructions" v-model="form.instructions" rows="4" class="pos-input mt-1 block w-full"></textarea>
                                </div>

                                <div class="bg-indigo-50 dark:bg-indigo-900/30 p-4 rounded-xl border border-indigo-100 dark:border-indigo-800/50 mt-4">
                                    <span class="text-sm font-bold text-indigo-600 dark:text-indigo-400 block mb-1">Total Max Cost</span>
                                    <span class="text-3xl font-black text-indigo-700 dark:text-indigo-300">${{ form.total_cost }}</span>
                                </div>
                            </div>

                            <!-- Right Column: Ingredients Builder -->
                            <div class="md:col-span-2">
                                <div class="flex justify-between items-center mb-4 pb-2 border-b border-slate-200 dark:border-slate-700">
                                    <h3 class="text-lg font-bold text-slate-800 dark:text-white">Bill of Materials</h3>
                                    <button type="button" @click="addIngredient" class="text-sm font-bold text-indigo-600 hover:text-indigo-700 bg-indigo-50 hover:bg-indigo-100 px-3 py-1.5 rounded-lg transition-colors">
                                        + Add Ingredient
                                    </button>
                                </div>
                                
                                <InputError :message="form.errors.items" class="mb-4" />

                                <div class="space-y-3 max-h-[50vh] overflow-y-auto pr-2">
                                    <div v-if="form.items.length === 0" class="text-center py-8 text-slate-500 dark:text-slate-400 border-2 border-dashed border-slate-200 dark:border-slate-700 rounded-xl">
                                        No ingredients added yet. Click "+ Add Ingredient" to start.
                                    </div>
                                    
                                    <div v-for="(item, index) in form.items" :key="index" class="flex flex-col gap-2 bg-white dark:bg-slate-800 p-3 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700">
                                        <div class="flex gap-3 items-start">
                                            <div class="flex-1">
                                                <InputLabel :value="'Ingredient ' + (index + 1)" class="sr-only" />
                                                <SelectInput 
                                                    v-model="item.ingredient_item_id" 
                                                    @update:modelValue="onIngredientChange(index)" 
                                                    :options="ingredientItems" 
                                                    placeholder="Select Ingredient..." 
                                                    searchPlaceholder="Search ingredient..."
                                                />
                                            </div>
                                            
                                            <div class="w-24">
                                                <InputLabel value="Qty" class="sr-only" />
                                                <TextInput type="number" step="0.001" min="0" v-model="item.quantity" @input="calculateTotalCost" class="w-full text-sm py-1.5 text-right" placeholder="Qty" required />
                                            </div>
                                            
                                            <div class="w-32">
                                                <InputLabel value="Unit" class="sr-only" />
                                                <SelectInput 
                                                    v-model="item.unit_id" 
                                                    :options="units" 
                                                    labelKey="short_code"
                                                    placeholder="Unit..." 
                                                />
                                            </div>

                                            <div class="w-24 flex items-center justify-end px-2">
                                                <span class="text-sm font-bold text-slate-700 dark:text-slate-300">${{ item.cost || '0.00' }}</span>
                                            </div>

                                            <button type="button" @click="removeIngredient(index)" class="mt-1 p-1.5 text-rose-500 hover:bg-rose-50 rounded-lg transition-colors">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                                    <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" />
                                                </svg>
                                            </button>
                                        </div>
                                        <div class="flex items-center gap-2 mt-2 pt-2 border-t border-slate-100 dark:border-slate-700/50">
                                            <InputLabel value="Applicable For:" class="text-xs shrink-0 text-slate-500" />
                                            <div class="flex flex-wrap gap-2">
                                                <button 
                                                    v-for="ot in orderTypes" 
                                                    :key="ot.id" 
                                                    type="button"
                                                    @click="toggleOrderType(item, ot.id)"
                                                    :class="[
                                                        'px-3 py-1 text-xs font-semibold rounded-full border transition-all duration-200',
                                                        item.order_type_ids.includes(ot.id) 
                                                            ? 'bg-indigo-100 text-indigo-700 border-indigo-200 shadow-sm dark:bg-indigo-900/50 dark:text-indigo-300 dark:border-indigo-700/50' 
                                                            : 'bg-slate-50 text-slate-500 border-slate-200 hover:bg-slate-100 dark:bg-slate-800 dark:text-slate-400 dark:border-slate-700 dark:hover:bg-slate-700'
                                                    ]"
                                                >
                                                    {{ ot.name }}
                                                </button>
                                            </div>
                                            <span class="text-xs text-slate-400 italic ml-auto" v-if="item.order_type_ids.length === 0">
                                                * Applies to ALL order types
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                        </div>

                        <div class="flex justify-end gap-3 mt-8 pt-6 border-t border-slate-200 dark:border-slate-700">
                            <button type="button" @click="closeModal" class="px-6 py-2 rounded-lg text-sm font-bold text-slate-600 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700 transition-colors">
                                Cancel
                            </button>
                            <button type="submit" class="pos-btn-primary !py-2 !px-8 text-sm bg-indigo-600 hover:bg-indigo-700 !rounded-lg" :disabled="form.processing">
                                {{ editingRecipe ? 'Update Recipe' : 'Save Recipe' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </template>
    </InventoryLayout>
</template>
