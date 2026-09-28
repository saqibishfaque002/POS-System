<script setup>
import { computed, ref, watch } from 'vue';

const props = defineProps({
    isOpen: Boolean,
    isEditing: Boolean,
    title: String,
    form: Object,
    branches: Array,
    suppliers: Array,
    items: Array,
    units: Array,
    
    // Config options
    hasSupplier: { type: Boolean, default: true },
    hasReference: { type: Boolean, default: false },
    referenceOptions: { type: Array, default: () => [] },
    referenceLabel: { type: String, default: 'Reference' },
    isReferenceLinked: { type: Boolean, default: false },
    
    hasDate: { type: Boolean, default: true },
    dateLabel: { type: String, default: 'Date' },
    hasDueDate: { type: Boolean, default: false },
    hasDeliveryDate: { type: Boolean, default: false },
    hasRequiredDate: { type: Boolean, default: false },
    hasManualInvoiceNumber: { type: Boolean, default: false },
    
    hasGlobalAdjustments: { type: Boolean, default: false },
    hasItemDiscount: { type: Boolean, default: false },
    hasItemDiscountPercent: { type: Boolean, default: false },
    hasItemTax: { type: Boolean, default: false },
    hasItemTaxPercent: { type: Boolean, default: false },
    hasItemUnitPrice: { type: Boolean, default: true },
    hasReferenceQty: { type: Boolean, default: false },
    referenceQtyLabel: { type: String, default: 'Ref Qty' },
    hasConversionUnit: { type: Boolean, default: false }
});

const emit = defineEmits(['close', 'submit', 'update:isReferenceLinked', 'reference-select', 'item-select']);

const getValidUnits = (itemId) => {
    if (!itemId) return props.units;
    const ingredient = props.items.find(i => i.id === itemId);
    if (!ingredient) return props.units;
    
    // POS mechanism: restrict to Default (purchase) and Base (consumption) units
    return props.units.filter(u => u.id === ingredient.purchase_unit_id || u.id === ingredient.consumption_unit_id);
};

const handleItemSelect = (item) => {
    const ingredient = props.items.find(i => i.id === item.item_id);
    if (ingredient) {
        // Auto-select Default Purchase Unit
        item.unit_id = ingredient.purchase_unit_id;
        if (props.hasItemUnitPrice) {
            item.unit_price = parseFloat(ingredient.base_price) || 0;
        }
    }
    emit('item-select', item);
};

const itemSubtotal = computed(() => {
    return props.form.items.reduce((sum, item) => {
        const qty = parseFloat(item.quantity) || 0;
        const rate = parseFloat(item.unit_price) || 0;
        
        let lineAmount = qty * rate;
        
        if (props.hasItemDiscount || props.hasItemDiscountPercent) {
            lineAmount -= (parseFloat(item.discount_amount) || 0);
        }
        
        if (props.hasItemTax || props.hasItemTaxPercent) {
            lineAmount += (parseFloat(item.tax_amount) || 0);
        }
        
        return sum + lineAmount;
    }, 0);
});

const formTotal = computed(() => {
    let total = itemSubtotal.value;
    
    if (props.hasGlobalAdjustments) {
        total = total - (parseFloat(props.form.discount_amount) || 0) + (parseFloat(props.form.tax_amount) || 0) + (parseFloat(props.form.shipping_amount) || 0);
    }
    
    return total;
});

const globalDiscountPercent = ref(0);
const globalTaxPercent = ref(0);

const updateGlobalAmounts = () => {
    if (!props.hasGlobalAdjustments) return;
    const sub = itemSubtotal.value;
    
    const dp = parseFloat(globalDiscountPercent.value) || 0;
    props.form.discount_amount = parseFloat((sub * (dp / 100)).toFixed(2));
    
    const subAfterDiscount = sub - (parseFloat(props.form.discount_amount) || 0);
    const tp = parseFloat(globalTaxPercent.value) || 0;
    props.form.tax_amount = parseFloat((subAfterDiscount * (tp / 100)).toFixed(2));
};

watch(itemSubtotal, () => {
    updateGlobalAmounts();
});

watch(() => props.isOpen, (isOpen) => {
    if (isOpen && props.hasGlobalAdjustments) {
        if (props.isEditing) {
            const sub = itemSubtotal.value;
            if (sub > 0) {
                globalDiscountPercent.value = parseFloat((((parseFloat(props.form.discount_amount) || 0) / sub) * 100).toFixed(2));
                const subAfterDisc = sub - (parseFloat(props.form.discount_amount) || 0);
                if (subAfterDisc > 0) {
                    globalTaxPercent.value = parseFloat((((parseFloat(props.form.tax_amount) || 0) / subAfterDisc) * 100).toFixed(2));
                } else {
                    globalTaxPercent.value = 0;
                }
            } else {
                globalDiscountPercent.value = 0;
                globalTaxPercent.value = 0;
            }
        } else {
            globalDiscountPercent.value = 0;
            globalTaxPercent.value = 0;
        }
    }
});

const addItem = () => {
    props.form.items.push({ 
        item_id: '', 
        ref_qty: 0, 
        quantity: 1, 
        unit_id: '', 
        unit_price: 0, 
        discount_amount: 0, 
        discount_percent: 0,
        tax_amount: 0, 
        tax_percent: 0 
    });
};

const updateItemAmounts = (item) => {
    const qty = parseFloat(item.quantity) || 0;
    const price = parseFloat(item.unit_price) || 0;
    
    if (props.hasItemDiscountPercent) {
        const dp = parseFloat(item.discount_percent) || 0;
        item.discount_amount = qty * price * (dp / 100);
    }
    
    if (props.hasItemTaxPercent) {
        const tp = parseFloat(item.tax_percent) || 0;
        const sub = (qty * price) - (parseFloat(item.discount_amount) || 0);
        item.tax_amount = sub * (tp / 100);
    }
};

const removeItem = (index) => {
    props.form.items.splice(index, 1);
};

const getConversionText = (item) => {
    if (!item.item_id || !item.unit_id) return '-';
    const ingredient = props.items.find(i => i.id === item.item_id);
    const selectedUnit = props.units.find(u => u.id === item.unit_id);

    if (ingredient && ingredient.consumption_unit && selectedUnit) {
        let qty = 1;
        if (selectedUnit.id === ingredient.purchase_unit_id) {
            qty = parseFloat(ingredient.conversion_qty || 1);
        } else if (selectedUnit.id === ingredient.consumption_unit_id) {
            qty = 1;
        } else {
            qty = parseFloat(ingredient.conversion_qty || 1);
        }
        
        const selName = selectedUnit.short_code || selectedUnit.name;
        const conName = ingredient.consumption_unit.short_code || ingredient.consumption_unit.name;
        return `1 ${selName} = ${qty} ${conName}`;
    }
    return '-';
};
</script>

<template>
    <div v-if="isOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-sm">
        <div class="dashboard-card w-full max-w-[95vw] lg:max-w-7xl p-6 max-h-[90vh] overflow-y-auto">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-xl font-bold text-slate-800 dark:text-white">{{ isEditing ? 'Edit' : 'New' }} {{ title }}</h2>
                <div v-if="hasReference" class="flex items-center gap-3">
                    <span class="text-sm font-medium text-slate-700 dark:text-slate-300">Link {{ referenceLabel }}?</span>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" :checked="isReferenceLinked" @change="emit('update:isReferenceLinked', $event.target.checked)" :disabled="isEditing" class="sr-only peer">
                        <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-indigo-300 dark:peer-focus:ring-indigo-800 rounded-full peer dark:bg-slate-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-slate-600 peer-checked:bg-indigo-600"></div>
                        <span class="ml-2 text-sm font-medium text-slate-900 dark:text-slate-300">{{ isReferenceLinked ? 'Yes' : 'No' }}</span>
                    </label>
                </div>
            </div>
            
            <form @submit.prevent="emit('submit')" class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                    
                    <!-- Branch -->
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Branch</label>
                        <select v-model="form.branch_id" class="pos-input w-full" required>
                            <option value="" disabled>Select Branch</option>
                            <option v-for="branch in branches" :key="branch.id" :value="branch.id">{{ branch.name }}</option>
                        </select>
                    </div>
                    
                    <div v-if="hasReference && isReferenceLinked">
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Select {{ referenceLabel }}</label>
                        <select v-model="form.reference_id" @change="emit('reference-select')" class="pos-input w-full" :disabled="!form.branch_id" required>
                            <option value="">-- Select {{ referenceLabel }} --</option>
                            <option v-for="opt in referenceOptions" :key="opt.id" :value="opt.id">{{ opt.document_number }} - {{ opt.date }}</option>
                        </select>
                        <p v-if="!form.branch_id" class="text-xs text-orange-500 mt-1">Select branch first</p>
                    </div>
                    
                    <!-- Dates -->
                    <div v-if="hasDate">
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">{{ dateLabel }}</label>
                        <input type="date" v-model="form.date" class="pos-input w-full" required>
                    </div>

                    <div v-if="hasDueDate">
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Due Date</label>
                        <input type="date" v-model="form.due_date" class="pos-input w-full">
                    </div>

                    <div v-if="hasDeliveryDate">
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Delivery Date</label>
                        <input type="date" v-model="form.delivery_date" class="pos-input w-full">
                    </div>

                    <div v-if="hasRequiredDate">
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Required Date</label>
                        <input type="date" v-model="form.required_date" class="pos-input w-full">
                    </div>

                    <div v-if="hasManualInvoiceNumber">
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Manual Invoice No</label>
                        <input type="text" v-model="form.manual_invoice_number" class="pos-input w-full">
                    </div>

                    <!-- Supplier -->
                    <div v-if="hasSupplier">
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Supplier</label>
                        <select v-model="form.supplier_id" class="pos-input w-full" required>
                            <option value="" disabled>Select Supplier</option>
                            <option v-for="sup in suppliers" :key="sup.id" :value="sup.id">{{ sup.name }}</option>
                        </select>
                    </div>
                </div>

                <!-- Items Table -->
                <div class="mb-6 bg-slate-50 dark:bg-slate-900/50 rounded-xl p-4 border border-slate-200 dark:border-slate-700">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="font-semibold text-slate-800 dark:text-white">Items {{ isReferenceLinked ? `(From ${referenceLabel})` : '' }}</h3>
                        <button v-if="!isReferenceLinked" type="button" @click="addItem" class="pos-btn-secondary !py-1 !px-3 text-xs">
                            + Add Item
                        </button>
                    </div>
                    <div class="max-h-[350px] overflow-y-auto pr-2 custom-scrollbar">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="text-[10px] uppercase tracking-wider text-slate-500 dark:text-slate-400 border-b border-slate-200 dark:border-slate-700 sticky top-0 bg-slate-50 dark:bg-slate-900/50 backdrop-blur-sm z-10">
                                    <th class="pb-3 w-48 text-center">Ingredient Item</th>
                                    <th v-if="hasReferenceQty && isReferenceLinked" class="pb-3 w-20 text-center">{{ referenceQtyLabel }}</th>
                                    <th class="pb-3 w-24 text-center">Quantity</th>
                                    <th class="pb-3 w-24 text-center">Unit</th>
                                    <th v-if="hasItemUnitPrice" class="pb-3 w-24 text-center">Rate</th>
                                    <th v-if="hasItemDiscount" class="pb-3 w-24 text-center">Disc ($)</th>
                                    <th v-if="hasItemDiscountPercent" class="pb-3 w-36 text-center">Disc (%)</th>
                                    <th v-if="hasItemTax" class="pb-3 w-24 text-center">Tax ($)</th>
                                    <th v-if="hasItemTaxPercent" class="pb-3 w-36 text-center">Tax (%)</th>
                                    <th v-if="hasItemUnitPrice" class="pb-3 w-32 text-center">Amount</th>
                                    <th v-if="hasConversionUnit" class="pb-3 w-32 pl-3 text-center">Conv Unit</th>
                                    <th class="pb-3 w-8"></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-if="isReferenceLinked && form.items.length === 0">
                                    <td colspan="10" class="py-6 text-center text-slate-500 italic">Select a {{ referenceLabel }} first to load items.</td>
                                </tr>
                                <tr v-for="(item, index) in form.items" :key="index" class="border-b border-slate-100 dark:border-slate-800 last:border-0">
                                    <td class="py-3 pr-2">
                                        <select v-if="isReferenceLinked" v-model="item.item_id" class="pos-input w-full !py-1.5 !px-2 text-sm disabled:opacity-50" disabled required>
                                            <option v-for="i in items" :key="i.id" :value="i.id">{{ i.name }}</option>
                                        </select>
                                        <select v-else v-model="item.item_id" @change="handleItemSelect(item)" class="pos-input w-full !py-1.5 !px-2 text-sm" required>
                                            <option value="" disabled>Select Item</option>
                                            <option v-for="i in items" :key="i.id" :value="i.id">{{ i.name }}</option>
                                        </select>
                                    </td>
                                    <td v-if="hasReferenceQty && isReferenceLinked" class="py-3 pr-2 text-center text-sm font-bold text-slate-500">
                                        {{ item.ref_qty }}
                                    </td>
                                    <td class="py-3 pr-2">
                                        <input type="number" step="0.01" v-model="item.quantity" @input="updateItemAmounts(item)" class="pos-input w-full !py-1.5 !px-2 text-right text-sm" required>
                                    </td>
                                    <td class="py-3 pr-2">
                                        <select v-model="item.unit_id" class="pos-input w-full !py-1.5 !px-2 text-sm" :disabled="isReferenceLinked" required>
                                            <option value="" disabled>Unit</option>
                                            <option v-for="u in getValidUnits(item.item_id)" :key="u.id" :value="u.id">{{ u.name }}</option>
                                        </select>
                                    </td>
                                    <td v-if="hasItemUnitPrice" class="py-3 pr-2">
                                        <input type="number" step="0.01" v-model="item.unit_price" @input="updateItemAmounts(item)" class="pos-input w-full !py-1.5 !px-2 text-right text-sm" required>
                                    </td>
                                    <td v-if="hasItemDiscount" class="py-3 pr-2">
                                        <input type="number" step="0.01" v-model="item.discount_amount" @input="updateItemAmounts(item)" class="pos-input w-full !py-1.5 !px-2 text-right text-sm text-red-500">
                                    </td>
                                    <td v-if="hasItemDiscountPercent" class="py-3 pr-2">
                                        <div class="flex items-center gap-1 border border-slate-200 dark:border-slate-700 rounded-md bg-white dark:bg-slate-800 px-2">
                                            <input type="number" step="0.01" v-model="item.discount_percent" @input="updateItemAmounts(item)" class="w-16 bg-transparent border-none text-right text-sm text-red-500 focus:ring-0 p-1" placeholder="%">
                                            <span class="text-[10px] font-bold text-slate-400 border-l border-slate-200 dark:border-slate-700 pl-1 min-w-[35px] text-right">${{ (item.discount_amount || 0).toFixed(2) }}</span>
                                        </div>
                                    </td>
                                    <td v-if="hasItemTax" class="py-3 pr-2">
                                        <input type="number" step="0.01" v-model="item.tax_amount" @input="updateItemAmounts(item)" class="pos-input w-full !py-1.5 !px-2 text-right text-sm text-indigo-500">
                                    </td>
                                    <td v-if="hasItemTaxPercent" class="py-3 pr-2">
                                        <div class="flex items-center gap-1 border border-slate-200 dark:border-slate-700 rounded-md bg-white dark:bg-slate-800 px-2">
                                            <input type="number" step="0.01" v-model="item.tax_percent" @input="updateItemAmounts(item)" class="w-16 bg-transparent border-none text-right text-sm text-indigo-500 focus:ring-0 p-1" placeholder="%">
                                            <span class="text-[10px] font-bold text-slate-400 border-l border-slate-200 dark:border-slate-700 pl-1 min-w-[35px] text-right">${{ (item.tax_amount || 0).toFixed(2) }}</span>
                                        </div>
                                    </td>
                                    <td v-if="hasItemUnitPrice" class="py-3 pr-2 text-sm text-slate-700 dark:text-slate-300 font-bold text-center">
                                        {{ (((parseFloat(item.quantity) || 0) * (parseFloat(item.unit_price) || 0)) - (parseFloat(item.discount_amount) || 0) + (parseFloat(item.tax_amount) || 0)).toFixed(2) }}
                                    </td>
                                    <td v-if="hasConversionUnit" class="py-3 text-xs font-bold text-slate-500 dark:text-slate-400 pl-3 text-center">
                                        {{ getConversionText(item) }}
                                    </td>
                                    <td class="py-3 text-right">
                                        <button v-if="!isReferenceLinked" type="button" @click="removeItem(index)" class="text-red-400 hover:text-red-600 transition-colors">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                                <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" />
                                            </svg>
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="flex flex-col md:flex-row justify-between gap-4">
                    <div class="w-full md:w-1/2">
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Notes</label>
                        <textarea v-model="form.notes" class="pos-input w-full" rows="3"></textarea>
                    </div>
                    <div v-if="hasItemUnitPrice" class="w-full md:w-1/3 flex flex-col justify-end gap-2">
                        <div v-if="hasGlobalAdjustments" class="flex justify-between items-center text-sm font-bold text-slate-600 dark:text-slate-400">
                            <span>Global Discount:</span>
                            <div class="flex items-center gap-1 border border-slate-200 dark:border-slate-700 rounded-md bg-white dark:bg-slate-800 px-2 h-8">
                                <input type="number" step="0.01" v-model="globalDiscountPercent" @input="updateGlobalAmounts" class="w-16 bg-transparent border-none text-right text-sm text-red-500 focus:ring-0 p-0" placeholder="%">
                                <span class="text-xs font-bold text-slate-400 border-l border-slate-200 dark:border-slate-700 pl-2 min-w-[50px] text-right">${{ (parseFloat(form.discount_amount) || 0).toFixed(2) }}</span>
                            </div>
                        </div>
                        <div v-if="hasGlobalAdjustments" class="flex justify-between items-center text-sm font-bold text-slate-600 dark:text-slate-400">
                            <span>Global Tax:</span>
                            <div class="flex items-center gap-1 border border-slate-200 dark:border-slate-700 rounded-md bg-white dark:bg-slate-800 px-2 h-8">
                                <input type="number" step="0.01" v-model="globalTaxPercent" @input="updateGlobalAmounts" class="w-16 bg-transparent border-none text-right text-sm text-indigo-500 focus:ring-0 p-0" placeholder="%">
                                <span class="text-xs font-bold text-slate-400 border-l border-slate-200 dark:border-slate-700 pl-2 min-w-[50px] text-right">${{ (parseFloat(form.tax_amount) || 0).toFixed(2) }}</span>
                            </div>
                        </div>
                        <div v-if="hasGlobalAdjustments" class="flex justify-between items-center text-sm font-bold text-slate-600 dark:text-slate-400">
                            <span>Shipping ($):</span>
                            <input type="number" step="0.01" v-model="form.shipping_amount" class="pos-input w-[124px] !py-1 !px-2 text-right text-sm text-indigo-500 h-8">
                        </div>
                        <div class="bg-slate-100 dark:bg-slate-800 p-4 rounded-xl border border-slate-200 dark:border-slate-700 mt-2">
                            <div class="flex justify-between items-center text-lg font-black text-slate-800 dark:text-white">
                                <span>Total Payable:</span>
                                <span class="text-indigo-600 dark:text-[#06b6d4]">${{ formTotal.toFixed(2) }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex justify-end gap-3 mt-8">
                    <button type="button" @click="emit('close')" class="pos-btn-secondary !px-6 mr-3">Cancel</button>
                    <button type="submit" class="pos-btn-primary !px-6" :disabled="form.processing">{{ isEditing ? 'Update' : 'Submit' }}</button>
                </div>
            </form>
        </div>
    </div>
</template>
