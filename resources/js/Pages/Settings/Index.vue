<script setup>
import { ref, computed, watch } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import SettingsSidebar from '@/Components/SettingsSidebar.vue';
import DataTable from '@/Components/DataTable.vue';
import SelectInput from '@/Components/SelectInput.vue';

const props = defineProps({
    countries: Array,
    provinces: Array,
    cities: Array,
    companies: Array,
    branches: Array,
    floors: Array,
    tables: Array,
    settings: Object,
    paymentTypes: Array,
    orderTypes: {
        type: Array,
        default: () => [],
    },
    parties: {
        type: Array,
        default: () => [],
    },
    discounts: {
        type: Array,
        default: () => [],
    },
    bankDiscounts: {
        type: Array,
        default: () => [],
    },
    foodGroups: {
        type: Array,
        default: () => [],
    },
    foodItems: {
        type: Array,
        default: () => [],
    },
});

const activeTab = ref(new URLSearchParams(window.location.search).get('tab') || 'storeProfile');

watch(activeTab, (newTab) => {
    if (newTab && typeof window !== 'undefined') {
        const url = new URL(window.location.href);
        url.searchParams.set('tab', newTab);
        window.history.replaceState({}, '', url.toString());
    }
});

const geographicTabs = [
    { id: 'countries', label: 'Countries' },
    { id: 'provinces', label: 'Provinces' },
    { id: 'cities', label: 'Cities' },
];

const restaurantTabs = [
    { id: 'companies', label: 'Companies' },
    { id: 'branches', label: 'Branches' },
    { id: 'floors', label: 'Floors' },
    { id: 'tables', label: 'Tables' },
    { id: 'orderTypes', label: 'Order Types' },
    { id: 'parties', label: 'Parties (POS)' },
];

const settingTabs = [
    { id: 'storeProfile', label: 'Store Profile' },
    { id: 'posSettings', label: 'POS Settings' },
];

const financialTabs = [
    { id: 'paymentTypes', label: 'Payment Types' },
    { id: 'discounts', label: 'Discounts & Deals' },
    { id: 'bankDiscounts', label: 'Bank Card Discounts' },
];

const currentTabLabel = computed(() => {
    const all = geographicTabs.concat(restaurantTabs).concat(financialTabs);
    const found = all.find(t => t.id === activeTab.value);
    return found ? found.label : 'Item';
});

const tabData = computed(() => {
    switch (activeTab.value) {
        case 'countries': return props.countries;
        case 'provinces': return props.provinces;
        case 'cities': return props.cities;
        case 'companies': return props.companies;
        case 'branches': return props.branches;
        case 'floors': return props.floors;
        case 'tables': return props.tables;
        case 'paymentTypes': return props.paymentTypes;
        case 'orderTypes': return props.orderTypes;
        case 'parties': return props.parties;
        case 'discounts': return props.discounts;
        case 'bankDiscounts': return props.bankDiscounts;
        default: return [];
    }
});

const columns = computed(() => {
    switch (activeTab.value) {
        case 'countries': return [
            { key: 'id', label: 'ID' },
            { key: 'name', label: 'Name' },
            { key: 'is_active', label: 'Active' },
        ];
        case 'provinces': return [
            { key: 'id', label: 'ID' },
            { key: 'country_id', label: 'Country' },
            { key: 'name', label: 'Name' },
            { key: 'is_active', label: 'Active' },
        ];
        case 'cities': return [
            { key: 'id', label: 'ID' },
            { key: 'province_id', label: 'Province' },
            { key: 'name', label: 'Name' },
            { key: 'is_active', label: 'Active' },
        ];
        case 'companies': return [
            { key: 'id', label: 'ID' },
            { key: 'name', label: 'Name' },
            { key: 'is_active', label: 'Active' },
        ];
        case 'branches': return [
            { key: 'id', label: 'ID' },
            { key: 'company_id', label: 'Company' },
            { key: 'city_id', label: 'City' },
            { key: 'name', label: 'Name' },
            { key: 'is_active', label: 'Active' },
        ];
        case 'floors': return [
            { key: 'id', label: 'ID' },
            { key: 'branch_id', label: 'Branch' },
            { key: 'name', label: 'Name' },
            { key: 'is_active', label: 'Active' },
        ];
        case 'tables': return [
            { key: 'id', label: 'ID' },
            { key: 'floor_id', label: 'Floor' },
            { key: 'name', label: 'Name' },
            { key: 'capacity', label: 'Capacity' },
            { key: 'is_active', label: 'Active' },
        ];
        case 'paymentTypes': return [
            { key: 'id', label: 'ID' },
            { key: 'name', label: 'Name' },
            { key: 'is_active', label: 'Active' },
        ];
        case 'orderTypes': return [
            { key: 'id', label: 'ID' },
            { key: 'name', label: 'Name' },
            { key: 'code', label: 'Code' },
            { key: 'service_charge_percent', label: 'Service Charge (%)' },
            { key: 'is_active', label: 'Active' },
        ];
        case 'parties': return [
            { key: 'id', label: 'ID' },
            { key: 'name', label: 'Party Name' },
            { key: 'party_type', label: 'Type' },
            { key: 'order_types', label: 'Linked Order Types (POS)' },
            { key: 'payment_types', label: 'Payment Methods' },
            { key: 'phone', label: 'Phone' },
            { key: 'city_id', label: 'City' },
            { key: 'credit_limit', label: 'Credit Limit' },
            { key: 'is_active', label: 'Active' },
        ];
        case 'discounts': return [
            { key: 'id', label: 'ID' },
            { key: 'name', label: 'Discount Name' },
            { key: 'value', label: 'Value / Type' },
            { key: 'scope', label: 'Scope' },
            { key: 'sharing', label: 'Sharing' },
            { key: 'timing', label: 'Tax & Addon' },
            { key: 'branches', label: 'Branches' },
            { key: 'order_types', label: 'Order Types' },
            { key: 'schedule', label: 'Schedule' },
            { key: 'requires_manager_approval', label: 'Manager PIN' },
            { key: 'is_active', label: 'Status' },
        ];
        case 'bankDiscounts': return [
            { key: 'id', label: 'ID' },
            { key: 'bank_name', label: 'Bank' },
            { key: 'name', label: 'Campaign / Deal' },
            { key: 'card_numbers', label: 'Card BINs / Numbers' },
            { key: 'value', label: 'Discount %' },
            { key: 'sharing', label: 'Sharing (Rest. / Bank)' },
            { key: 'is_active', label: 'Status' },
        ];
        default: return [];
    }
});

// Location Modal State
const isModalOpen = ref(false);
const editingItem = ref(null);
let form = useForm({});

const formFields = computed(() => {
    switch (activeTab.value) {
        case 'countries': return [
            { key: 'name', label: 'Name', type: 'text' },
            { key: 'is_active', label: 'Active', type: 'checkbox' },
        ];
        case 'provinces': return [
            { key: 'country_id', label: 'Country', type: 'select', options: props.countries.map(g => ({value: g.id, label: g.name})) },
            { key: 'name', label: 'Name', type: 'text' },
            { key: 'is_active', label: 'Active', type: 'checkbox' },
        ];
        case 'cities': return [
            { key: 'country_id', label: 'Country (Filter)', type: 'select', options: props.countries.map(g => ({value: g.id, label: g.name})), virtual: true },
            { 
                key: 'province_id', 
                label: 'Province', 
                type: 'select', 
                dynamicOptions: (f) => props.provinces.filter(p => p.country_id === f.country_id).map(p => ({value: p.id, label: p.name}))
            },
            { key: 'name', label: 'City Name', type: 'text' },
            { key: 'is_active', label: 'Active', type: 'checkbox' },
        ];
        case 'companies': return [
            { key: 'name', label: 'Name', type: 'text' },
            { key: 'is_active', label: 'Active', type: 'checkbox' },
        ];
        case 'branches': return [
            { key: 'company_id', label: 'Company', type: 'select', options: props.companies.map(c => ({value: c.id, label: c.name})) },
            { 
                key: 'city_id', 
                label: 'City', 
                type: 'select', 
                options: props.cities.map(p => ({value: p.id, label: p.name + (p.province ? ' (' + p.province.name + ')' : '')}))
            },
            { key: 'name', label: 'Branch Name', type: 'text' },
            { key: 'address', label: 'Address', type: 'text', required: false },
            { key: 'phone', label: 'Phone', type: 'text', required: false },
            { key: 'is_active', label: 'Active', type: 'checkbox' },
        ];
        case 'floors': return [
            { key: 'company_id', label: 'Company (Filter)', type: 'select', options: props.companies.map(c => ({value: c.id, label: c.name})), virtual: true },
            { 
                key: 'branch_id', 
                label: 'Branch', 
                type: 'select', 
                dynamicOptions: (f) => props.branches.filter(b => b.company_id == f.company_id).map(b => ({value: b.id, label: b.name}))
            },
            { key: 'name', label: 'Floor Name', type: 'text' },
            { key: 'is_active', label: 'Active', type: 'checkbox' },
        ];
        case 'tables': return [
            { key: 'company_id', label: 'Company (Filter)', type: 'select', options: props.companies.map(c => ({value: c.id, label: c.name})), virtual: true },
            { 
                key: 'branch_id', 
                label: 'Branch (Filter)', 
                type: 'select', 
                virtual: true,
                dynamicOptions: (f) => props.branches.filter(b => b.company_id == f.company_id).map(b => ({value: b.id, label: b.name}))
            },
            { 
                key: 'floor_id', 
                label: 'Floor', 
                type: 'select', 
                dynamicOptions: (f) => props.floors.filter(fl => fl.branch_id == f.branch_id).map(fl => ({value: fl.id, label: fl.name}))
            },
            { key: 'name', label: 'Table Name', type: 'text' },
            { key: 'capacity', label: 'Capacity (Seats)', type: 'number' },
            { key: 'is_active', label: 'Active', type: 'checkbox' },
        ];
        case 'paymentTypes': return [
            { key: 'name', label: 'Payment Method Name', type: 'text' },
            { key: 'is_active', label: 'Active', type: 'checkbox' },
        ];
        case 'orderTypes': return [
            { key: 'name', label: 'Order Type Name', type: 'text' },
            { key: 'code', label: 'Code (e.g. dine_in, delivery)', type: 'text', required: false },
            { key: 'service_charge_percent', label: 'Service Charge (%)', type: 'number', step: '0.01', required: false },
            { key: 'notes', label: 'Notes', type: 'textarea', required: false },
            { key: 'is_active', label: 'Active', type: 'checkbox' },
        ];
        case 'parties': return [
            { key: 'name', label: 'Party / Company Name', type: 'text' },
            { 
                key: 'party_type', 
                label: 'Party Type', 
                type: 'select', 
                options: [
                    { value: 'delivery_partner', label: 'Delivery Partner / Aggregator (e.g. Foodpanda, Careem)' },
                    { value: 'corporate', label: 'Corporate / B2B Client' },
                    { value: 'customer', label: 'Customer' },
                    { value: 'vendor', label: 'Vendor' },
                    { value: 'supplier', label: 'Supplier' },
                    { value: 'other', label: 'Other' },
                ] 
            },
            {
                key: 'order_type_ids',
                label: 'Linked Order Types (POS)',
                type: 'select',
                multiple: true,
                placeholder: 'Select linked order types...',
                options: (props.orderTypes || []).map(ot => ({ value: ot.id, label: ot.name })),
                required: false,
            },
            {
                key: 'payment_type_ids',
                label: 'Accepted Payment Methods / Types (POS)',
                type: 'select',
                multiple: true,
                placeholder: 'Select accepted payment methods...',
                options: (props.paymentTypes || []).map(pt => ({ value: pt.id, label: pt.name })),
                required: false,
            },
            { key: 'contact_person', label: 'Contact Person', type: 'text', required: false },
            { key: 'phone', label: 'Phone', type: 'text', required: false },
            { key: 'email', label: 'Email', type: 'email', required: false },
            { key: 'tax_number', label: 'Tax / NTN Number', type: 'text', required: false },
            { 
                key: 'city_id', 
                label: 'City', 
                type: 'select', 
                options: (props.cities || []).map(p => ({ value: p.id, label: p.name + (p.province ? ' (' + p.province.name + ')' : '') })),
                required: false,
            },
            { key: 'credit_limit', label: 'Credit Limit', type: 'number', step: '0.01', required: false },
            { key: 'opening_balance', label: 'Opening Balance', type: 'number', step: '0.01', required: false },
            { key: 'address', label: 'Address', type: 'text', required: false },
            { key: 'notes', label: 'Notes', type: 'textarea', required: false },
            { key: 'is_active', label: 'Active', type: 'checkbox' },
        ];
        case 'discounts': return [
            { key: 'name', label: 'Discount / Deal Name (e.g. Staff Discount)', type: 'text' },
            { key: 'code', label: 'Short Code / Promo Slug (Optional, e.g. STAFF15)', type: 'text', required: false },
            { 
                key: 'type', 
                label: 'Discount Type', 
                type: 'select', 
                options: [
                    { value: 'percentage', label: 'Percentage (%) Off' },
                    { value: 'fixed', label: 'Fixed Amount ($ / PKR) Off' },
                ] 
            },
            { key: 'value', label: 'Discount Value (e.g. 15 for 15% or 200 for $200)', type: 'number', step: '0.01' },
            { 
                key: 'scope', 
                label: 'Applicability Scope', 
                type: 'select', 
                options: [
                    { value: 'cart', label: 'Whole Order (Subtotal)' },
                    { value: 'category', label: 'Specific Categories (Food Groups)' },
                    { value: 'item', label: 'Specific Food Items' },
                ] 
            },
            { 
                key: 'food_group_ids', 
                label: 'Eligible Categories (Food Groups)', 
                type: 'select', 
                multiple: true, 
                placeholder: 'Select food groups...',
                options: (props.foodGroups || []).map(g => ({ value: g.id, label: g.name })),
                showIf: (form) => form.scope === 'category',
                required: false
            },
            { 
                key: 'food_item_ids', 
                label: 'Eligible Food Items', 
                type: 'select', 
                multiple: true, 
                placeholder: 'Select food items...',
                options: (props.foodItems || []).map(i => ({ value: i.id, label: i.name })),
                showIf: (form) => form.scope === 'item',
                required: false
            },
            { key: 'min_order_amount', label: 'Minimum Bill / Order Amount', type: 'number', step: '0.01', required: false },
            { key: 'max_discount_amount', label: 'Maximum Discount Cap (Limit)', type: 'number', step: '0.01', required: false },
            { 
                key: 'party_id', 
                label: 'Co-Funding Partner / Party (Optional)', 
                type: 'select', 
                placeholder: 'Select partner / bank if co-funded...',
                options: (props.parties || []).map(p => ({ value: p.id, label: p.name })),
                required: false
            },
            { key: 'merchant_share_percentage', label: 'Restaurant / Merchant Share (%)', type: 'number', step: '0.01', placeholder: 'e.g. 100 or 30', required: false },
            { key: 'partner_share_percentage', label: 'Partner / Bank Share (%)', type: 'number', step: '0.01', placeholder: 'e.g. 0 or 70', required: false },
            { 
                key: 'apply_tax_timing', 
                label: 'Tax Calculation Timing', 
                type: 'select', 
                options: [
                    { value: 'before_tax', label: 'Discount Before Tax (GST on discounted net total)' },
                    { value: 'after_tax', label: 'Discount After Tax (GST on full subtotal)' },
                ] 
            },
            { 
                key: 'apply_addon_timing', 
                label: 'Addon Discount Applicability', 
                type: 'select', 
                options: [
                    { value: 'with_addon', label: 'Discount With Addons (Item + addons)' },
                    { value: 'without_addon', label: 'Discount Without Addons (Base item only)' },
                ] 
            },
            { key: 'valid_from', label: 'Valid From (Date & Time)', type: 'datetime-local', required: false },
            { key: 'valid_to', label: 'Valid Until (Date & Time)', type: 'datetime-local', required: false },
            { 
                key: 'branch_ids', 
                label: 'Eligible Branches (Leave empty for all branches)', 
                type: 'select', 
                multiple: true, 
                placeholder: 'All branches or select specific...',
                options: (props.branches || []).map(b => ({ value: b.id, label: b.name })),
                required: false
            },
            { 
                key: 'order_type_ids', 
                label: 'Eligible Order Types (Leave empty for all order types)', 
                type: 'select', 
                multiple: true, 
                placeholder: 'All order types or select specific...',
                options: (props.orderTypes || []).map(ot => ({ value: ot.id, label: ot.name })),
                required: false
            },
            { key: 'description', label: 'Terms & Conditions / Description', type: 'textarea', required: false },
            { key: 'requires_manager_approval', label: 'Requires Manager PIN / Approval', type: 'checkbox' },
            { key: 'is_active', label: 'Active', type: 'checkbox' },
        ];
        case 'bankDiscounts': return [
            { key: 'bank_name', label: 'Bank Name (e.g. HBL, Bank Alfalah, Meezan Bank)', type: 'text' },
            { key: 'name', label: 'Campaign / Deal Name (e.g. HBL Dining Delight 20% Off)', type: 'text' },
            { 
                key: 'card_type', 
                label: 'Card Tier / Type', 
                type: 'select', 
                options: [
                    { value: 'All Cards', label: 'All Cards (Debit & Credit)' },
                    { value: 'Credit Cards', label: 'Credit Cards Only' },
                    { value: 'Debit Cards', label: 'Debit Cards Only' },
                    { value: 'Platinum / Titanium', label: 'Platinum / Titanium Only' },
                    { value: 'World Elite / Infinite', label: 'World Elite / Infinite Only' },
                    { value: 'Gold / Classic', label: 'Gold / Classic Only' },
                ] 
            },
            { 
                key: 'card_numbers', 
                label: 'Eligible Card Numbers / BIN Prefixes', 
                type: 'textarea', 
                placeholder: 'Enter 6-digit BIN prefixes or card numbers separated by commas or new lines (e.g. 421456, 524163, 4000123456789012)' 
            },
            { 
                key: 'party_id', 
                label: 'Linked Bank Party (for Ledger)', 
                type: 'select', 
                placeholder: 'Select bank party...',
                options: (props.parties || []).map(p => ({ value: p.id, label: p.name })),
                required: false 
            },
            { key: 'value', label: 'Discount Percentage (%) (e.g. 20 for 20%)', type: 'number', step: '0.01' },
            { key: 'max_discount_amount', label: 'Maximum Discount Cap ($ / PKR) (e.g. 1000)', type: 'number', step: '0.01', required: false },
            { key: 'min_order_amount', label: 'Minimum Bill / Order Amount ($ / PKR)', type: 'number', step: '0.01', required: false },
            { key: 'merchant_share_percentage', label: 'Restaurant / Merchant Share (%) (e.g. 30)', type: 'number', step: '0.01', required: false },
            { key: 'partner_share_percentage', label: 'Bank / Partner Share (%) (e.g. 70)', type: 'number', step: '0.01', required: false },
            { key: 'is_active', label: 'Active', type: 'checkbox' },
        ];
        default: return [];
    }
});

const openModal = (item = null) => {
    editingItem.value = item;
    const initialData = {};
    formFields.value.forEach(field => {
        if (field.key === 'order_type_ids') {
            initialData[field.key] = item && item.order_types ? item.order_types.map(ot => ot.id) : [];
        } else if (field.key === 'payment_type_ids') {
            initialData[field.key] = item && item.payment_types ? item.payment_types.map(pt => pt.id) : [];
        } else if (field.key === 'branch_ids') {
            initialData[field.key] = item && item.branches ? item.branches.map(b => b.id) : [];
        } else if (field.key === 'food_group_ids') {
            initialData[field.key] = item && item.food_groups ? item.food_groups.map(fg => fg.id) : [];
        } else if (field.key === 'food_item_ids') {
            initialData[field.key] = item && item.food_items ? item.food_items.map(fi => fi.id) : [];
        } else if (field.type === 'datetime-local') {
            if (item && item[field.key]) {
                const d = new Date(item[field.key]);
                if (!isNaN(d.getTime())) {
                    const pad = (n) => String(n).padStart(2, '0');
                    initialData[field.key] = `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}T${pad(d.getHours())}:${pad(d.getMinutes())}`;
                } else {
                    initialData[field.key] = '';
                }
            } else {
                initialData[field.key] = '';
            }
        } else if (field.key === 'party_type') {
            initialData[field.key] = item ? (item.party_type || 'delivery_partner') : 'delivery_partner';
        } else if (field.key === 'type' && (activeTab.value === 'discounts' || activeTab.value === 'bankDiscounts')) {
            initialData[field.key] = item ? item.type : 'percentage';
        } else if (field.key === 'scope' && (activeTab.value === 'discounts' || activeTab.value === 'bankDiscounts')) {
            initialData[field.key] = item ? item.scope : 'cart';
        } else if (field.key === 'merchant_share_percentage') {
            initialData[field.key] = item ? (item.merchant_share_percentage ?? (activeTab.value === 'bankDiscounts' ? 30 : 100)) : (activeTab.value === 'bankDiscounts' ? 30 : 100);
        } else if (field.key === 'partner_share_percentage') {
            initialData[field.key] = item ? (item.partner_share_percentage ?? (activeTab.value === 'bankDiscounts' ? 70 : 0)) : (activeTab.value === 'bankDiscounts' ? 70 : 0);
        } else if (field.key === 'apply_tax_timing') {
            initialData[field.key] = item ? (item.apply_tax_timing || 'before_tax') : 'before_tax';
        } else if (field.key === 'apply_addon_timing') {
            initialData[field.key] = item ? (item.apply_addon_timing || 'with_addon') : 'with_addon';
        } else if (field.type === 'checkbox') {
            if (field.key === 'requires_manager_approval') {
                initialData[field.key] = item ? !!item[field.key] : false;
            } else {
                initialData[field.key] = item ? !!item[field.key] : true;
            }
        } else if (field.virtual || field.key.endsWith('_id')) {
            if (item) {
                // Pre-fill virtuals for nested hierarchy
                if (activeTab.value === 'cities') {
                    if (field.key === 'country_id') initialData[field.key] = item.province?.country_id;
                    else initialData[field.key] = item[field.key];
                }
                else if (activeTab.value === 'branches') {
                    if (field.key === 'country_id') initialData[field.key] = item.city?.province?.country_id;
                    else if (field.key === 'province_id') initialData[field.key] = item.city?.province_id;
                    else initialData[field.key] = item[field.key];
                }
                else if (activeTab.value === 'floors') {
                    if (field.key === 'company_id') initialData[field.key] = item.branch?.company_id;
                    else initialData[field.key] = item[field.key];
                }
                else if (activeTab.value === 'tables') {
                    if (field.key === 'company_id') initialData[field.key] = item.floor?.branch?.company_id;
                    else if (field.key === 'branch_id') initialData[field.key] = item.floor?.branch_id;
                    else initialData[field.key] = item[field.key];
                }
                else initialData[field.key] = item[field.key];
            } else {
                initialData[field.key] = field.key === 'capacity' ? 4 : '';
            }
        } else {
            initialData[field.key] = item ? item[field.key] : (field.key === 'capacity' ? 4 : (field.key === 'credit_limit' || field.key === 'opening_balance' || field.key === 'service_charge_percent' ? 0 : ''));
        }
    });

    if (activeTab.value === 'bankDiscounts') {
        initialData.is_bank_discount = true;
        initialData.type = 'percentage';
        initialData.scope = 'cart';
        if (!item) {
            initialData.merchant_share_percentage = 30;
            initialData.partner_share_percentage = 70;
        }
    } else if (activeTab.value === 'discounts') {
        initialData.is_bank_discount = false;
        if (!item) {
            initialData.merchant_share_percentage = 100;
            initialData.partner_share_percentage = 0;
            initialData.party_id = null;
        }
        initialData.bank_name = null;
        initialData.card_type = null;
        initialData.card_numbers = null;
    }

    if (activeTab.value === 'branches') {
        const existingPivots = item?.payment_types || [];
        initialData.payment_types = (props.paymentTypes || []).map(pt => {
            const existing = existingPivots.find(ep => ep.id === pt.id);
            return {
                id: pt.id,
                name: pt.name,
                selected: !!existing,
                tax_percentage: existing ? (parseFloat(existing.pivot?.tax_percentage) || 0) : 0,
            };
        });
    }

    form = useForm(initialData);
    isModalOpen.value = true;
};

const closeModal = () => {
    isModalOpen.value = false;
    form.reset();
};

const getRouteGroupAndPrefix = () => {
    switch(activeTab.value) {
        case 'countries': return { group: 'locations', prefix: 'countries' };
        case 'provinces': return { group: 'locations', prefix: 'provinces' };
        case 'cities': return { group: 'locations', prefix: 'cities' };
        case 'companies': return { group: 'restaurant', prefix: 'companies' };
        case 'branches': return { group: 'restaurant', prefix: 'branches' };
        case 'floors': return { group: 'restaurant', prefix: 'floors' };
        case 'tables': return { group: 'restaurant', prefix: 'tables' };
        case 'paymentTypes': return { group: 'restaurant', prefix: 'payment-types' };
        case 'orderTypes': return { group: 'restaurant', prefix: 'order-types' };
        case 'parties': return { group: 'restaurant', prefix: 'parties' };
        case 'discounts': return { group: 'restaurant', prefix: 'discounts' };
        case 'bankDiscounts': return { group: 'restaurant', prefix: 'discounts' };
    }
};

const submitForm = () => {
    const { group, prefix } = getRouteGroupAndPrefix();
    if (editingItem.value) {
        form.put(route(`${group}.${prefix}.update`, editingItem.value.id), {
            onSuccess: () => closeModal(),
        });
    } else {
        form.post(route(`${group}.${prefix}.store`), {
            onSuccess: () => closeModal(),
        });
    }
};

const deleteItem = (item) => {
    if (confirm('Are you sure you want to delete this item?')) {
        const { group, prefix } = getRouteGroupAndPrefix();
        router.delete(route(`${group}.${prefix}.destroy`, item.id));
    }
};

// Settings Forms
const generalForm = useForm({
    store_name: props.settings.store_name || '',
    store_location: props.settings.store_location || '',
    logo: null,
    favicon: null,
});

const posForm = useForm({
    tax_rate: props.settings.tax_rate || 0,
    print_mode: props.settings.print_mode || 'browser',
    auto_fetch_supplier_items: props.settings.auto_fetch_supplier_items == '1' || props.settings.auto_fetch_supplier_items == 'true' || false,
});

const submitGeneralSettings = () => {
    generalForm.post(route('settings.group.update', { group: 'general' }), {
        preserveScroll: true,
        onSuccess: () => {
            // clear file inputs after success so we don't accidentally re-upload
            generalForm.logo = null;
            generalForm.favicon = null;
        }
    });
};

const submitPosSettings = () => {
    posForm.post(route('settings.group.update', { group: 'pos' }), {
        preserveScroll: true,
    });
};
</script>

<template>
    <AppLayout title="System Settings">
        <template #sidebar>
            <SettingsSidebar v-model:activeTab="activeTab" />
        </template>
                    
                    <!-- Store Profile Tab -->
                    <div v-if="activeTab === 'storeProfile'" class="dashboard-card p-8">
                        <h2 class="text-xl font-bold text-slate-800 dark:text-white mb-6">Store Profile</h2>
                        
                        <form @submit.prevent="submitGeneralSettings" class="space-y-6 max-w-2xl">
                            <div>
                                <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-2">Store Name</label>
                                <input type="text" v-model="generalForm.store_name" class="pos-input w-full" placeholder="e.g. My Awesome Restaurant">
                                <div v-if="generalForm.errors.store_name" class="text-red-500 text-xs mt-1">{{ generalForm.errors.store_name }}</div>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-2">Primary Location (Address)</label>
                                <textarea v-model="generalForm.store_location" rows="3" class="pos-input w-full" placeholder="Full address..."></textarea>
                                <div v-if="generalForm.errors.store_location" class="text-red-500 text-xs mt-1">{{ generalForm.errors.store_location }}</div>
                            </div>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-2">Store Logo</label>
                                    <div v-if="props.settings.logo" class="mb-3 p-2 border border-slate-200 dark:border-slate-700 rounded-lg inline-block bg-white">
                                        <img :src="'/storage/' + props.settings.logo" class="h-16 object-contain" alt="Store Logo">
                                    </div>
                                    <input type="file" @input="generalForm.logo = $event.target.files[0]" class="block w-full text-sm text-slate-500 dark:text-slate-400 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 dark:file:bg-indigo-900/30 dark:file:text-[#06b6d4]">
                                    <p class="text-xs text-slate-500 mt-2">Upload a new logo to replace the current one. Leave empty to keep existing.</p>
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-2">Favicon</label>
                                    <div v-if="props.settings.favicon" class="mb-3 p-2 border border-slate-200 dark:border-slate-700 rounded-lg inline-block bg-white">
                                        <img :src="'/storage/' + props.settings.favicon" class="h-8 object-contain" alt="Favicon">
                                    </div>
                                    <input type="file" @input="generalForm.favicon = $event.target.files[0]" class="block w-full text-sm text-slate-500 dark:text-slate-400 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 dark:file:bg-indigo-900/30 dark:file:text-[#06b6d4]">
                                    <p class="text-xs text-slate-500 mt-2">Upload a new favicon (.ico or .png). Leave empty to keep existing.</p>
                                </div>
                            </div>
                            
                            <div class="pt-4 flex justify-end border-t border-slate-100 dark:border-slate-700/50">
                                <button type="submit" class="pos-btn-primary !py-2.5 !px-6" :disabled="generalForm.processing">
                                    Save Changes
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- POS Settings Tab -->
                    <div v-else-if="activeTab === 'posSettings'" class="dashboard-card p-8">
                        <h2 class="text-xl font-bold text-slate-800 dark:text-white mb-6">POS Settings</h2>
                        
                        <form @submit.prevent="submitPosSettings" class="space-y-6 max-w-2xl">
                            <div>
                                <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-2">Global Tax Rate (%)</label>
                                <input type="number" step="0.01" v-model="posForm.tax_rate" class="pos-input w-full md:w-1/2" placeholder="e.g. 5.00">
                                <p class="text-xs text-slate-500 mt-2">This tax rate will be applied to all POS orders automatically.</p>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-2">Print Mode</label>
                                <div class="w-full md:w-1/2">
                                    <SelectInput 
                                        v-model="posForm.print_mode" 
                                        :options="[{id: 'browser', name: 'Browser Print (Opens Print Dialog)'}, {id: 'pdf', name: 'PDF Download (Saves as PDF)'}]" 
                                        placeholder="Select Print Mode" 
                                    />
                                </div>
                                <p class="text-xs text-slate-500 mt-2">Choose how documents (like PR, PO) are printed across the system.</p>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-2">Auto-Fetch Supplier Items</label>
                                <label class="relative inline-flex items-center cursor-pointer mt-1">
                                    <input type="checkbox" v-model="posForm.auto_fetch_supplier_items" class="sr-only peer">
                                    <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer dark:bg-slate-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-slate-600 peer-checked:bg-indigo-500 dark:peer-checked:bg-[#06b6d4]"></div>
                                </label>
                                <p class="text-xs text-slate-500 mt-2">When enabled, selecting a supplier in trade documents will automatically load their linked ingredient items.</p>
                            </div>
                            
                            <div class="pt-4 flex justify-end border-t border-slate-100 dark:border-slate-700/50">
                                <button type="submit" class="pos-btn-primary !py-2.5 !px-6" :disabled="posForm.processing">
                                    Save Changes
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Location & Restaurant DataTables -->
                    <DataTable v-else :title="currentTabLabel" :columns="columns" :data="tabData" @edit="openModal" @delete="deleteItem">
                        <template #actions>
                            <button @click="openModal()" class="pos-btn-primary !py-2 !px-4 !rounded-lg text-sm">
                                + Add New
                            </button>
                        </template>

                        <template #row-actions="{item}">
                            <div class="flex items-center justify-end gap-3">
                                <button @click="openModal(item)" class="text-indigo-600 hover:text-indigo-900 dark:text-[#06b6d4] dark:hover:text-cyan-300 transition-colors">Edit</button>
                                <button @click="deleteItem(item)" class="text-red-500 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300 transition-colors">Delete</button>
                            </div>
                        </template>

                        <template #cell-country_id="{item}">
                            {{ item.country?.name || item.country_id }}
                        </template>
                        <template #cell-province_id="{item}">
                            {{ item.province?.name || item.province_id }}
                        </template>
                        <template #cell-company_id="{item}">
                            {{ item.company?.name || item.company_id }}
                        </template>
                        <template #cell-city_id="{item}">
                            {{ item.city?.name || item.city_id }}
                        </template>
                        <template #cell-branch_id="{item}">
                            {{ item.branch?.name || item.branch_id }}
                        </template>
                        <template #cell-floor_id="{item}">
                            {{ item.floor?.name || item.floor_id }}
                        </template>
                        <template #cell-service_charge_percent="{item}">
                            {{ item.service_charge_percent !== null && item.service_charge_percent !== undefined ? Number(item.service_charge_percent) + '%' : '0%' }}
                        </template>
                        <template #cell-credit_limit="{item}">
                            ${{ Number(item.credit_limit || 0).toLocaleString(undefined, { minimumFractionDigits: 2 }) }}
                        </template>
                        <template #cell-party_type="{item}">
                            <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold capitalize bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-300">
                                {{ (item.party_type || '—').replace('_', ' ') }}
                            </span>
                        </template>
                        <template #cell-order_types="{item}">
                            <div class="flex flex-wrap gap-1 max-w-xs">
                                <span 
                                    v-for="ot in item.order_types" 
                                    :key="ot.id" 
                                    class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-indigo-50 text-indigo-700 dark:bg-indigo-950/60 dark:text-cyan-300 border border-indigo-200/60 dark:border-indigo-900/40"
                                >
                                    {{ ot.name }}
                                </span>
                                <span v-if="!item.order_types || item.order_types.length === 0" class="text-xs text-slate-400 italic">
                                    All Types
                                </span>
                            </div>
                        </template>
                        <template #cell-payment_types="{item}">
                            <div class="flex flex-wrap gap-1 max-w-xs">
                                <span 
                                    v-for="pt in item.payment_types" 
                                    :key="pt.id" 
                                    class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] font-bold bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300 border border-emerald-200/60 dark:border-emerald-800/40"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-2.5 h-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                                    </svg>
                                    {{ pt.name }}
                                </span>
                                <span v-if="!item.payment_types || item.payment_types.length === 0" class="text-xs text-slate-400 italic">
                                    All / None
                                </span>
                            </div>
                        </template>
                        <template #cell-bank_name="{item}">
                            <div v-if="activeTab === 'bankDiscounts'">
                                <div class="font-bold text-slate-800 dark:text-white flex items-center gap-1.5">
                                    <svg class="w-4 h-4 text-indigo-500 dark:text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" /></svg>
                                    {{ item.bank_name || 'Bank' }}
                                </div>
                                <span v-if="item.card_type" class="px-1.5 py-0.2 rounded text-[10px] font-medium bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400">
                                    {{ item.card_type }}
                                </span>
                            </div>
                            <span v-else>{{ item.bank_name }}</span>
                        </template>
                        <template #cell-card_numbers="{item}">
                            <div v-if="activeTab === 'bankDiscounts'" class="max-w-[200px]">
                                <span class="font-mono text-xs font-semibold text-indigo-600 dark:text-cyan-400 truncate block" :title="item.card_numbers">
                                    {{ item.card_numbers }}
                                </span>
                            </div>
                            <span v-else>{{ item.card_numbers }}</span>
                        </template>
                        <template #cell-value="{item}">
                            <div v-if="activeTab === 'discounts' || activeTab === 'bankDiscounts'">
                                <span class="font-bold text-indigo-600 dark:text-cyan-400">
                                    {{ item.type === 'percentage' ? Number(item.value) + '%' : '$' + Number(item.value).toFixed(2) }}
                                </span>
                                <div v-if="item.max_discount_amount" class="text-[10px] text-slate-400">
                                    Cap: ${{ Number(item.max_discount_amount).toFixed(2) }}
                                </div>
                            </div>
                            <span v-else>{{ item.value }}</span>
                        </template>
                        <template #cell-scope="{item}">
                            <span v-if="activeTab === 'discounts'" class="px-2 py-0.5 rounded-full text-xs font-semibold capitalize bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300">
                                {{ item.scope === 'cart' ? 'Whole Order' : (item.scope === 'category' ? 'Category (' + (item.food_groups?.length || 0) + ')' : 'Items (' + (item.food_items?.length || 0) + ')') }}
                            </span>
                            <span v-else>{{ item.scope }}</span>
                        </template>
                        <template #cell-sharing="{item}">
                            <div v-if="activeTab === 'bankDiscounts'" class="text-xs">
                                <div class="font-semibold text-slate-700 dark:text-slate-300">
                                    Rest: {{ Number(item.merchant_share_percentage ?? 30) }}%
                                </div>
                                <div class="text-[11px] font-bold text-indigo-600 dark:text-cyan-400">
                                    {{ item.party?.name || 'Bank' }}: {{ Number(item.partner_share_percentage ?? 70) }}%
                                </div>
                            </div>
                        </template>
                        <template #cell-timing="{item}">
                            <div v-if="activeTab === 'discounts' || activeTab === 'bankDiscounts'" class="flex flex-col gap-1 text-[11px]">
                                <span :class="item.apply_tax_timing === 'before_tax' ? 'text-emerald-700 bg-emerald-50 dark:bg-emerald-950/40 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800' : 'text-blue-700 bg-blue-50 dark:bg-blue-950/40 dark:text-blue-400 border border-blue-200 dark:border-blue-800'" class="px-1.5 py-0.5 rounded text-[10px] font-bold w-fit">
                                    {{ item.apply_tax_timing === 'before_tax' ? 'Before Tax' : 'After Tax' }}
                                </span>
                                <span :class="item.apply_addon_timing === 'with_addon' ? 'text-purple-700 bg-purple-50 dark:bg-purple-950/40 dark:text-purple-400 border border-purple-200 dark:border-purple-800' : 'text-slate-600 bg-slate-100 dark:bg-slate-800 dark:text-slate-400 border border-slate-200 dark:border-slate-700'" class="px-1.5 py-0.5 rounded text-[10px] font-bold w-fit">
                                    {{ item.apply_addon_timing === 'with_addon' ? 'With Addons' : 'Base Only' }}
                                </span>
                            </div>
                        </template>
                        <template #cell-schedule="{item}">
                            <div v-if="activeTab === 'discounts' || activeTab === 'bankDiscounts'" class="text-xs">
                                <div v-if="item.valid_from || item.valid_to" class="text-[11px] leading-tight">
                                    <div class="text-slate-600 dark:text-slate-300"><span class="text-slate-400 text-[10px]">From:</span> {{ item.valid_from ? new Date(item.valid_from).toLocaleDateString() + ' ' + new Date(item.valid_from).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'}) : 'Always' }}</div>
                                    <div class="text-slate-600 dark:text-slate-300"><span class="text-slate-400 text-[10px]">To:</span> {{ item.valid_to ? new Date(item.valid_to).toLocaleDateString() + ' ' + new Date(item.valid_to).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'}) : 'Never' }}</div>
                                </div>
                                <div v-else-if="item.start_time && item.end_time" class="font-semibold text-emerald-600 dark:text-emerald-400 flex items-center gap-1">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                    {{ item.start_time.substring(0, 5) }} - {{ item.end_time.substring(0, 5) }}
                                </div>
                                <div v-else class="text-slate-400 italic text-[11px]">Always Active</div>
                            </div>
                            <span v-else>{{ item.schedule }}</span>
                        </template>
                        <template #cell-requires_manager_approval="{item}">
                            <span :class="item.requires_manager_approval ? 'px-2 py-0.5 rounded-full text-[11px] font-bold bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-400' : 'text-slate-400 text-xs'">
                                {{ item.requires_manager_approval ? 'Required (PIN)' : 'No' }}
                            </span>
                        </template>
                        <template #cell-branches="{item}">
                            <div class="flex flex-wrap gap-1 max-w-xs">
                                <span 
                                    v-for="b in item.branches" 
                                    :key="b.id" 
                                    class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-indigo-50 text-indigo-700 dark:bg-indigo-950/60 dark:text-cyan-300 border border-indigo-200/60 dark:border-indigo-900/40"
                                >
                                    {{ b.name }}
                                </span>
                                <span v-if="!item.branches || item.branches.length === 0" class="text-xs text-slate-400 italic">
                                    All Branches
                                </span>
                            </div>
                        </template>
                        <template #cell-is_active="{item}">
                            <span :class="item.is_active ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-400 px-2.5 py-0.5 rounded-full text-xs font-bold' : 'bg-slate-100 text-slate-800 dark:bg-slate-700/50 dark:text-slate-400 px-2.5 py-0.5 rounded-full text-xs font-bold'">
                                {{ item.is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </template>
                    </DataTable>

        <template #modals>
            <!-- Modal Form (Locations) -->
            <div v-if="isModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-sm">
        <div class="dashboard-card w-full max-w-3xl p-6 max-h-[90vh] overflow-y-auto">
            <h2 class="text-xl font-bold mb-6 text-slate-800 dark:text-white">
                {{ editingItem ? 'Edit' : 'Create' }} {{ currentTabLabel }}
            </h2>
            <form @submit.prevent="submitForm" class="flex flex-col">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                    <div v-for="field in formFields" :key="field.key" v-show="field.showIf === undefined || field.showIf(form)" :class="{'md:col-span-2': field.type === 'textarea' || field.multiple}">
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
                            :options="field.dynamicOptions ? field.dynamicOptions(form) : field.options" 
                            :multiple="!!field.multiple"
                            valueKey="value"
                            labelKey="label"
                            :placeholder="field.placeholder || (field.multiple ? 'Select options...' : 'Select an option')" 
                            searchPlaceholder="Search option..."
                            :disabled="field.disabled !== undefined ? field.disabled : (!field.virtual && !field.multiple && (field.dynamicOptions ? field.dynamicOptions(form).length === 0 : (field.options && field.options.length === 0)))"
                        />
                    </template>
                    
                    <template v-else-if="field.type === 'textarea'">
                        <textarea v-model="form[field.key]" rows="2" class="pos-input w-full" :required="field.required !== false"></textarea>
                    </template>

                    <template v-else>
                        <input :type="field.type" :step="field.step" v-model="form[field.key]" class="pos-input w-full" :required="field.required !== false">
                    </template>

                    <div v-if="form.errors[field.key]" class="text-red-500 text-xs mt-1">{{ form.errors[field.key] }}</div>
                </div>
                </div>

                <!-- Branch Payment Methods & Taxes Section -->
                <div v-if="activeTab === 'branches'" class="border-t border-slate-100 dark:border-slate-800 pt-5 mt-2 mb-2">
                    <div class="mb-4">
                        <h3 class="text-sm font-bold text-slate-800 dark:text-white flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-indigo-500 dark:text-[#06b6d4]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                            </svg>
                            Accepted Payment Methods & Tax (%)
                        </h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                            Select payment types accepted by this branch and specify their branch-specific tax percentage.
                        </p>
                    </div>

                    <div v-if="!form.payment_types || form.payment_types.length === 0" class="text-xs text-slate-400 italic py-4 bg-slate-50 dark:bg-slate-800/40 rounded-xl px-4 text-center">
                        No payment methods found. Create them first in the Payment Types tab.
                    </div>

                    <div v-else class="grid grid-cols-1 sm:grid-cols-2 gap-3 max-h-60 overflow-y-auto pr-1">
                        <div 
                            v-for="pt in form.payment_types" 
                            :key="pt.id"
                            class="flex items-center justify-between p-3 rounded-xl border transition-all"
                            :class="pt.selected 
                                ? 'border-indigo-200 dark:border-cyan-800/60 bg-indigo-50/30 dark:bg-cyan-950/20' 
                                : 'border-slate-100 dark:border-slate-800/80 bg-slate-50/50 dark:bg-slate-800/20 opacity-70'"
                        >
                            <div class="flex items-center gap-2.5">
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" v-model="pt.selected" class="sr-only peer">
                                    <div class="w-9 h-5 bg-slate-200 peer-focus:outline-none rounded-full peer dark:bg-slate-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all dark:border-slate-600 peer-checked:bg-indigo-600 dark:peer-checked:bg-[#06b6d4]"></div>
                                </label>
                                <span class="text-sm font-semibold text-slate-700 dark:text-slate-200">{{ pt.name }}</span>
                            </div>

                            <div v-if="pt.selected" class="flex items-center gap-1.5">
                                <label class="text-xs text-slate-500 dark:text-slate-400">Tax %</label>
                                <input 
                                    type="number" 
                                    step="0.01" 
                                    min="0" 
                                    v-model.number="pt.tax_percentage" 
                                    class="pos-input !py-1 text-xs w-20 text-right" 
                                    placeholder="0.00"
                                    required
                                />
                            </div>
                            <div v-else class="text-xs text-slate-400 italic">
                                Disabled
                            </div>
                        </div>
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
    </AppLayout>
</template>
