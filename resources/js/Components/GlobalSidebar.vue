<script setup>
import { ref, onMounted, watch } from 'vue';
import { Link, router } from '@inertiajs/vue3';

const props = defineProps({
    activeTab: {
        type: String,
        default: ''
    }
});

const emit = defineEmits(['update:activeTab']);

const expandedCategories = ref({
    catalog: false,
    ingredients: false,
    purchase: false,
});

onMounted(() => {
    const saved = localStorage.getItem('inventorySidebarState');
    if (saved) {
        expandedCategories.value = JSON.parse(saved);
        // Force accordion constraint in case old state had multiple true
        let foundOpen = false;
        Object.keys(expandedCategories.value).forEach(k => {
            if (expandedCategories.value[k]) {
                if (foundOpen) expandedCategories.value[k] = false;
                else foundOpen = true;
            }
        });
    } else {
        if (route().current('trade.*')) {
            expandedCategories.value.purchase = true;
        } else if (route().current('ingredients.*')) {
            expandedCategories.value.ingredients = true;
        } else {
            expandedCategories.value.catalog = true;
        }
    }
});

watch(expandedCategories, (newVal) => {
    localStorage.setItem('inventorySidebarState', JSON.stringify(newVal));
}, { deep: true });

const toggleCategory = (cat) => {
    const isOpening = !expandedCategories.value[cat];
    if (isOpening) {
        Object.keys(expandedCategories.value).forEach(k => expandedCategories.value[k] = false);
    }
    expandedCategories.value[cat] = isOpening;
};

const tabs = [
    { id: 'foodGroups', label: 'Food Groups', route: 'catalog.index' },
    { id: 'foodItems', label: 'Food Items', route: 'catalog.index' },
    { id: 'propertyGroups', label: 'Property Groups', route: 'catalog.index' },
    { id: 'propertyItems', label: 'Property Items', route: 'catalog.index' },
    { id: 'globalVariations', label: 'Global Variations', route: 'catalog.index' },
];
const ingredientTabs = [
    { id: 'ingredientUnits', label: 'Ingredient Units', route: 'ingredients.index' },
    { id: 'ingredientGroups', label: 'Ingredient Groups', route: 'ingredients.index' },
    { id: 'ingredientItems', label: 'Ingredient Items', route: 'ingredients.index' },
];
</script>

<template>
    <aside class="w-full md:w-64 shrink-0">
        <div class="dashboard-card p-4 space-y-6">
            <!-- Catalog Management -->
            <div>
                <button @click="toggleCategory('catalog')" type="button" class="w-full flex justify-between items-center text-xs font-black tracking-wider text-slate-400 dark:text-slate-500 uppercase mb-2 px-2 focus:outline-none">
                    <span>Catalog Management</span>
                    <svg :class="{'rotate-180': expandedCategories.catalog}" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 transition-transform duration-200" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                    </svg>
                </button>
                <div v-show="expandedCategories.catalog" class="space-y-1">
                    <template v-if="route().current('catalog.index')">
                        <button v-for="tab in tabs" :key="tab.id" @click="tab.route === 'catalog.index' ? emit('update:activeTab', tab.id) : router.get(route(tab.route))" 
                            :class="[
                                'w-full text-left px-4 py-2.5 rounded-xl transition-colors text-sm font-bold',
                                activeTab === tab.id ? 'bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-[#06b6d4]' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800'
                            ]">
                            {{ tab.label }}
                        </button>
                    </template>
                    <template v-else>
                        <!-- If on a Trade page or Ingredients page, clicking Catalog tabs should redirect to Catalog -->
                        <Link v-for="tab in tabs" :key="'link-'+tab.id" :href="route(tab.route, {tab: tab.id})" class="block w-full text-left px-4 py-2.5 rounded-xl transition-colors text-sm font-bold text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800">
                            {{ tab.label }}
                        </Link>
                    </template>
                    <Link :href="route('catalog.menus.index')"
                        :class="[
                            'block w-full text-left px-4 py-2.5 rounded-xl transition-colors text-sm font-bold mt-1 border-t border-slate-100 dark:border-slate-800 pt-3',
                            route().current('catalog.menus.*') ? 'bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-[#06b6d4]' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800'
                        ]">
                        Menu Management
                    </Link>
                </div>
            </div>

            <!-- Ingredients Management -->
            <div>
                <button @click="toggleCategory('ingredients')" type="button" class="w-full flex justify-between items-center text-xs font-black tracking-wider text-slate-400 dark:text-slate-500 uppercase mb-2 px-2 focus:outline-none">
                    <span>Manage Ingredients</span>
                    <svg :class="{'rotate-180': expandedCategories.ingredients}" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 transition-transform duration-200" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                    </svg>
                </button>
                <div v-show="expandedCategories.ingredients" class="space-y-1">
                    <template v-if="route().current('ingredients.index')">
                        <button v-for="tab in ingredientTabs" :key="tab.id" @click="emit('update:activeTab', tab.id)" 
                            :class="[
                                'w-full text-left px-4 py-2.5 rounded-xl transition-colors text-sm font-bold',
                                activeTab === tab.id ? 'bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-[#06b6d4]' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800'
                            ]">
                            {{ tab.label }}
                        </button>
                    </template>
                    <template v-else>
                        <Link v-for="tab in ingredientTabs" :key="'link-'+tab.id" :href="route(tab.route, {tab: tab.id})" class="block w-full text-left px-4 py-2.5 rounded-xl transition-colors text-sm font-bold text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800">
                            {{ tab.label }}
                        </Link>
                    </template>
                    <Link :href="route('recipes.index')"
                        :class="[
                            'block w-full text-left px-4 py-2.5 rounded-xl transition-colors text-sm font-bold',
                            route().current('recipes.*') ? 'bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-[#06b6d4]' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800'
                        ]">
                        Recipes & BOM
                    </Link>
                    <Link :href="route('production.index')"
                        :class="[
                            'block w-full text-left px-4 py-2.5 rounded-xl transition-colors text-sm font-bold',
                            route().current('production.*') ? 'bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-[#06b6d4]' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800'
                        ]">
                        Kitchen Production
                    </Link>
                </div>
            </div>

            <!-- Manage Purchase -->
            <div>
                <button @click="toggleCategory('purchase')" type="button" class="w-full flex justify-between items-center text-xs font-black tracking-wider text-slate-400 dark:text-slate-500 uppercase mb-2 px-2 focus:outline-none">
                    <span>Manage Purchase</span>
                    <svg :class="{'rotate-180': expandedCategories.purchase}" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 transition-transform duration-200" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                    </svg>
                </button>
                <div v-show="expandedCategories.purchase" class="space-y-1">
                    <Link :href="route('trade.purchase-requests.index')"
                        :class="[
                            'block w-full text-left px-4 py-2.5 rounded-xl transition-colors text-sm font-bold',
                            route().current('trade.purchase-requests.index') ? 'bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-[#06b6d4]' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800'
                        ]">
                        Purchase Request
                    </Link>
                    <Link :href="route('trade.purchase-orders.index')" 
                        :class="[
                            'block w-full text-left px-4 py-2.5 rounded-xl transition-colors text-sm font-bold',
                            route().current('trade.purchase-orders.index') ? 'bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-[#06b6d4]' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800'
                        ]">
                        Purchase Order
                    </Link>
                    <Link :href="route('trade.good-received-notes.index')" 
                        :class="[
                            'block w-full text-left px-4 py-2.5 rounded-xl transition-colors text-sm font-bold',
                            route().current('trade.good-received-notes.index') ? 'bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-[#06b6d4]' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800'
                        ]">
                        Good Received Note
                    </Link>
                    <Link :href="route('trade.purchase-invoices.index')" 
                        :class="[
                            'block w-full text-left px-4 py-2.5 rounded-xl transition-colors text-sm font-bold',
                            route().current('trade.purchase-invoices.index') ? 'bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-[#06b6d4]' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800'
                        ]">
                        Purchase Invoice
                    </Link>
                    <Link :href="route('trade.purchase-returns.index')" 
                        :class="[
                            'block w-full text-left px-4 py-2.5 rounded-xl transition-colors text-sm font-bold',
                            route().current('trade.purchase-returns.index') ? 'bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-[#06b6d4]' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800'
                        ]">
                        Purchase Return
                    </Link>
                    <Link :href="route('trade.sale-invoices.index')" 
                        :class="[
                            'block w-full text-left px-4 py-2.5 rounded-xl transition-colors text-sm font-bold',
                            route().current('trade.sale-invoices.index') ? 'bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-[#06b6d4]' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800'
                        ]">
                        Sale Invoice
                    </Link>
                    <Link :href="route('trade.sale-returns.index')" 
                        :class="[
                            'block w-full text-left px-4 py-2.5 rounded-xl transition-colors text-sm font-bold',
                            route().current('trade.sale-returns.index') ? 'bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-[#06b6d4]' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800'
                        ]">
                        Sale Return
                    </Link>
                </div>
            </div>
        </div>
    </aside>
</template>
