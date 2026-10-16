<script setup>
import { ref, computed, watch, onMounted } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';

const props = defineProps({
    activeTab: {
        type: String,
        default: ''
    }
});

const emit = defineEmits(['update:activeTab']);

const page = usePage();

// Track expansion state for categories
const expandedCategories = ref({
    catalog: false,
    ingredients: false,
    purchase: false,
});

// Category definitions with modern iconography and metadata
const categories = [
    {
        id: 'catalog',
        label: 'Catalog Management',
        shortLabel: 'Catalog',
        itemCount: 6,
        icon: 'layers',
    },
    {
        id: 'ingredients',
        label: 'Manage Ingredients',
        shortLabel: 'Ingredients',
        itemCount: 5,
        icon: 'beaker',
    },
    {
        id: 'purchase',
        label: 'Manage Purchase',
        shortLabel: 'Trade',
        itemCount: 8,
        icon: 'shopping-cart',
    }
];

// Catalog items (Tabs + Menus)
const catalogItems = [
    { id: 'foodGroups', label: 'Food Groups', route: 'catalog.index', type: 'tab', icon: 'folder' },
    { id: 'foodItems', label: 'Food Items', route: 'catalog.index', type: 'tab', icon: 'tag' },
    { id: 'propertyGroups', label: 'Property Groups', route: 'catalog.index', type: 'tab', icon: 'sliders' },
    { id: 'propertyItems', label: 'Property Items', route: 'catalog.index', type: 'tab', icon: 'checklist' },
    { id: 'globalVariations', label: 'Global Variations', route: 'catalog.index', type: 'tab', icon: 'sparkles' },
    { id: 'menus', label: 'Menu Management', route: 'catalog.menus.index', routePattern: 'catalog.menus.*', type: 'route', icon: 'book' },
];

// Ingredient items (Tabs + BOM + Production)
const ingredientItems = [
    { id: 'ingredientUnits', label: 'Ingredient Units', route: 'ingredients.index', type: 'tab', icon: 'scale' },
    { id: 'ingredientGroups', label: 'Ingredient Groups', route: 'ingredients.index', type: 'tab', icon: 'archive' },
    { id: 'ingredientItems', label: 'Ingredient Items', route: 'ingredients.index', type: 'tab', icon: 'cube' },
    { id: 'recipes', label: 'Recipes & BOM', route: 'recipes.index', routePattern: 'recipes.*', type: 'route', icon: 'clipboard' },
    { id: 'production', label: 'Kitchen Production', route: 'production.index', routePattern: 'production.*', type: 'route', icon: 'factory' },
];

// Purchase / Trade items
const tradeItems = [
    { id: 'suppliers', label: 'Suppliers', route: 'trade.suppliers.index', routePattern: 'trade.suppliers.*', type: 'route', icon: 'building' },
    { id: 'purchaseRequests', label: 'Purchase Request', route: 'trade.purchase-requests.index', routePattern: 'trade.purchase-requests.*', type: 'route', icon: 'document-text' },
    { id: 'purchaseOrders', label: 'Purchase Order', route: 'trade.purchase-orders.index', routePattern: 'trade.purchase-orders.*', type: 'route', icon: 'shopping-bag' },
    { id: 'goodReceivedNotes', label: 'Good Received Note', route: 'trade.good-received-notes.index', routePattern: 'trade.good-received-notes.*', type: 'route', icon: 'inbox' },
    { id: 'purchaseInvoices', label: 'Purchase Invoice', route: 'trade.purchase-invoices.index', routePattern: 'trade.purchase-invoices.*', type: 'route', icon: 'receipt' },
    { id: 'purchaseReturns', label: 'Purchase Return', route: 'trade.purchase-returns.index', routePattern: 'trade.purchase-returns.*', type: 'route', icon: 'arrow-uturn' },
    { id: 'saleInvoices', label: 'Sale Invoice', route: 'trade.sale-invoices.index', routePattern: 'trade.sale-invoices.*', type: 'route', icon: 'banknotes' },
    { id: 'saleReturns', label: 'Sale Return', route: 'trade.sale-returns.index', routePattern: 'trade.sale-returns.*', type: 'route', icon: 'arrow-path' },
];

// Detect current active section dynamically from current route
const detectActiveCategory = () => {
    try {
        if (route().current('trade.*')) {
            return 'purchase';
        }
        if (route().current('ingredients.*') || route().current('recipes.*') || route().current('production.*')) {
            return 'ingredients';
        }
        if (route().current('catalog.*')) {
            return 'catalog';
        }
    } catch (e) {
        // Fallback if route() helper is still initializing
    }
    return 'catalog';
};

const activeCategory = computed(() => detectActiveCategory());

const activeCategoryLabel = computed(() => {
    const found = categories.find(c => c.id === activeCategory.value);
    return found ? found.shortLabel : 'Inventory';
});

// Auto-expand the category of the active page dynamically
const syncActiveSection = () => {
    const currentCat = detectActiveCategory();
    if (currentCat) {
        expandedCategories.value[currentCat] = true;
    }
};

// Toggle accordion section
const toggleCategory = (catId) => {
    expandedCategories.value[catId] = !expandedCategories.value[catId];
};

// Check if a specific sub-item is active
const isItemActive = (item) => {
    if (item.type === 'route') {
        const pattern = item.routePattern || item.route;
        return route().current(pattern);
    }

    if (item.type === 'tab') {
        // If on Catalog page
        if (item.route === 'catalog.index' && route().current('catalog.index')) {
            const currentTab = props.activeTab || (new URLSearchParams(window.location.search).get('tab')) || 'foodGroups';
            return currentTab === item.id;
        }

        // If on Ingredients page
        if (item.route === 'ingredients.index' && route().current('ingredients.index')) {
            const currentTab = props.activeTab || (new URLSearchParams(window.location.search).get('tab')) || 'ingredientUnits';
            return currentTab === item.id;
        }
    }

    return false;
};

// Handle Tab Selection without reloading if already on the same page
const selectTab = (item) => {
    if (item.type === 'tab') {
        if (item.route === 'catalog.index' && route().current('catalog.index')) {
            emit('update:activeTab', item.id);
            const url = new URL(window.location.href);
            url.searchParams.set('tab', item.id);
            window.history.replaceState({}, '', url.toString());
            return;
        }

        if (item.route === 'ingredients.index' && route().current('ingredients.index')) {
            emit('update:activeTab', item.id);
            const url = new URL(window.location.href);
            url.searchParams.set('tab', item.id);
            window.history.replaceState({}, '', url.toString());
            return;
        }

        // If on a different page, navigate to the target route with the tab
        router.get(route(item.route, { tab: item.id }));
    }
};

// Watch for Inertia page changes: auto-expand the section of the visited page
watch(() => page.url, () => {
    syncActiveSection();
}, { immediate: true });

onMounted(() => {
    syncActiveSection();
});
</script>

<template>
    <aside class="w-full md:w-64 shrink-0 select-none">
        <div class="dashboard-card !p-3 space-y-3 border border-slate-200/90 dark:border-slate-800/90 rounded-2xl bg-white/95 dark:bg-slate-900/95 shadow-sm">
            
            <!-- Modern Module Brand Strip -->
            <div class="px-3 py-2.5 rounded-xl bg-slate-50/80 dark:bg-slate-800/50 border border-slate-100 dark:border-slate-800/80 flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <div class="w-7 h-7 rounded-lg bg-indigo-600/10 dark:bg-cyan-500/10 text-indigo-600 dark:text-[#06b6d4] flex items-center justify-center font-black">
                        <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-xs font-black tracking-wide text-slate-800 dark:text-slate-100 uppercase">Inventory Hub</h2>
                        <p class="text-[10px] text-slate-400 dark:text-slate-500 font-medium">Operations & Trade</p>
                    </div>
                </div>
                <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-cyan-400 border border-indigo-100 dark:border-indigo-900/40">
                    {{ activeCategoryLabel }}
                </span>
            </div>

            <!-- Category Accordions -->
            <div class="space-y-2">

                <!-- 1. Catalog Management -->
                <div class="rounded-xl border border-transparent transition-colors">
                    <button 
                        @click="toggleCategory('catalog')" 
                        type="button" 
                        class="w-full group flex justify-between items-center px-2.5 py-2 rounded-xl text-xs font-bold uppercase tracking-wider transition-all duration-200 focus:outline-none"
                        :class="[
                            activeCategory === 'catalog'
                                ? 'text-indigo-600 dark:text-[#06b6d4] bg-indigo-50/50 dark:bg-indigo-950/20'
                                : 'text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800/40'
                        ]"
                    >
                        <div class="flex items-center gap-2">
                            <div class="p-1 rounded-md transition-colors"
                                 :class="activeCategory === 'catalog' ? 'bg-indigo-100/70 dark:bg-cyan-900/40 text-indigo-600 dark:text-[#06b6d4]' : 'bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400 group-hover:text-indigo-600 dark:group-hover:text-[#06b6d4]'">
                                <svg class="w-3.5 h-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                                </svg>
                            </div>
                            <span class="text-[11px] font-black">Catalog</span>
                        </div>

                        <div class="flex items-center gap-1.5">
                            <span v-if="activeCategory === 'catalog'" class="w-1.5 h-1.5 rounded-full bg-indigo-500 dark:bg-cyan-400"></span>
                            <span class="text-[10px] font-semibold text-slate-400 dark:text-slate-500">{{ catalogItems.length }}</span>
                            <svg 
                                :class="{'rotate-180': expandedCategories.catalog}" 
                                class="h-3.5 w-3.5 text-slate-400 dark:text-slate-500 transition-transform duration-200" 
                                viewBox="0 0 20 20" 
                                fill="currentColor"
                            >
                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                            </svg>
                        </div>
                    </button>

                    <div v-show="expandedCategories.catalog" class="space-y-1 pt-1.5 pb-1">
                        <template v-for="item in catalogItems" :key="item.id">
                            <!-- In-page Tab Buttons for catalog.index -->
                            <button
                                v-if="item.type === 'tab' && route().current('catalog.index')"
                                type="button"
                                @click="selectTab(item)"
                                :class="[
                                    'group flex items-center justify-between w-full px-3 py-2 rounded-xl text-xs font-bold transition-all duration-150 relative text-left',
                                    isItemActive(item)
                                        ? 'bg-indigo-50/90 dark:bg-indigo-950/40 text-indigo-600 dark:text-[#06b6d4] shadow-xs'
                                        : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-800/50'
                                ]"
                            >
                                <span v-if="isItemActive(item)" class="absolute left-0 top-1/2 -translate-y-1/2 w-1 h-4 bg-indigo-600 dark:bg-[#06b6d4] rounded-r-full"></span>
                                <div class="flex items-center gap-2.5">
                                    <span class="w-1.5 h-1.5 rounded-full transition-colors"
                                          :class="isItemActive(item) ? 'bg-indigo-600 dark:bg-[#06b6d4]' : 'bg-slate-300 dark:bg-slate-700 group-hover:bg-slate-400'"></span>
                                    <span>{{ item.label }}</span>
                                </div>
                            </button>

                            <!-- Inertia Link if on another page -->
                            <Link
                                v-else-if="item.type === 'tab'"
                                :href="route(item.route, { tab: item.id })"
                                :class="[
                                    'group flex items-center justify-between w-full px-3 py-2 rounded-xl text-xs font-bold transition-all duration-150 relative text-left',
                                    isItemActive(item)
                                        ? 'bg-indigo-50/90 dark:bg-indigo-950/40 text-indigo-600 dark:text-[#06b6d4] shadow-xs'
                                        : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-800/50'
                                ]"
                            >
                                <span v-if="isItemActive(item)" class="absolute left-0 top-1/2 -translate-y-1/2 w-1 h-4 bg-indigo-600 dark:bg-[#06b6d4] rounded-r-full"></span>
                                <div class="flex items-center gap-2.5">
                                    <span class="w-1.5 h-1.5 rounded-full transition-colors"
                                          :class="isItemActive(item) ? 'bg-indigo-600 dark:bg-[#06b6d4]' : 'bg-slate-300 dark:bg-slate-700 group-hover:bg-slate-400'"></span>
                                    <span>{{ item.label }}</span>
                                </div>
                            </Link>

                            <!-- Route Link for Dedicated Pages (e.g. Menu Management) -->
                            <Link
                                v-else
                                :href="route(item.route)"
                                :class="[
                                    'group flex items-center justify-between w-full px-3 py-2 rounded-xl text-xs font-bold transition-all duration-150 relative text-left border-t border-slate-100 dark:border-slate-800/80 mt-1 pt-2',
                                    isItemActive(item)
                                        ? 'bg-indigo-50/90 dark:bg-indigo-950/40 text-indigo-600 dark:text-[#06b6d4] shadow-xs'
                                        : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-800/50'
                                ]"
                            >
                                <span v-if="isItemActive(item)" class="absolute left-0 top-1/2 -translate-y-1/2 w-1 h-4 bg-indigo-600 dark:bg-[#06b6d4] rounded-r-full"></span>
                                <div class="flex items-center gap-2.5">
                                    <svg class="w-3.5 h-3.5 transition-colors"
                                         :class="isItemActive(item) ? 'text-indigo-600 dark:text-[#06b6d4]' : 'text-slate-400 dark:text-slate-500 group-hover:text-slate-600 dark:group-hover:text-slate-300'"
                                         xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                                    </svg>
                                    <span>{{ item.label }}</span>
                                </div>
                            </Link>
                        </template>
                    </div>
                </div>

                <!-- 2. Manage Ingredients -->
                <div class="rounded-xl border border-transparent transition-colors">
                    <button 
                        @click="toggleCategory('ingredients')" 
                        type="button" 
                        class="w-full group flex justify-between items-center px-2.5 py-2 rounded-xl text-xs font-bold uppercase tracking-wider transition-all duration-200 focus:outline-none"
                        :class="[
                            activeCategory === 'ingredients'
                                ? 'text-indigo-600 dark:text-[#06b6d4] bg-indigo-50/50 dark:bg-indigo-950/20'
                                : 'text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800/40'
                        ]"
                    >
                        <div class="flex items-center gap-2">
                            <div class="p-1 rounded-md transition-colors"
                                 :class="activeCategory === 'ingredients' ? 'bg-indigo-100/70 dark:bg-cyan-900/40 text-indigo-600 dark:text-[#06b6d4]' : 'bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400 group-hover:text-indigo-600 dark:group-hover:text-[#06b6d4]'">
                                <svg class="w-3.5 h-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z" />
                                </svg>
                            </div>
                            <span class="text-[11px] font-black">Ingredients</span>
                        </div>

                        <div class="flex items-center gap-1.5">
                            <span v-if="activeCategory === 'ingredients'" class="w-1.5 h-1.5 rounded-full bg-indigo-500 dark:bg-cyan-400"></span>
                            <span class="text-[10px] font-semibold text-slate-400 dark:text-slate-500">{{ ingredientItems.length }}</span>
                            <svg 
                                :class="{'rotate-180': expandedCategories.ingredients}" 
                                class="h-3.5 w-3.5 text-slate-400 dark:text-slate-500 transition-transform duration-200" 
                                viewBox="0 0 20 20" 
                                fill="currentColor"
                            >
                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                            </svg>
                        </div>
                    </button>

                    <div v-show="expandedCategories.ingredients" class="space-y-1 pt-1.5 pb-1">
                        <template v-for="item in ingredientItems" :key="item.id">
                            <!-- In-page Tab Buttons for ingredients.index -->
                            <button
                                v-if="item.type === 'tab' && route().current('ingredients.index')"
                                type="button"
                                @click="selectTab(item)"
                                :class="[
                                    'group flex items-center justify-between w-full px-3 py-2 rounded-xl text-xs font-bold transition-all duration-150 relative text-left',
                                    isItemActive(item)
                                        ? 'bg-indigo-50/90 dark:bg-indigo-950/40 text-indigo-600 dark:text-[#06b6d4] shadow-xs'
                                        : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-800/50'
                                ]"
                            >
                                <span v-if="isItemActive(item)" class="absolute left-0 top-1/2 -translate-y-1/2 w-1 h-4 bg-indigo-600 dark:bg-[#06b6d4] rounded-r-full"></span>
                                <div class="flex items-center gap-2.5">
                                    <span class="w-1.5 h-1.5 rounded-full transition-colors"
                                          :class="isItemActive(item) ? 'bg-indigo-600 dark:bg-[#06b6d4]' : 'bg-slate-300 dark:bg-slate-700 group-hover:bg-slate-400'"></span>
                                    <span>{{ item.label }}</span>
                                </div>
                            </button>

                            <!-- Inertia Link if on another page -->
                            <Link
                                v-else-if="item.type === 'tab'"
                                :href="route(item.route, { tab: item.id })"
                                :class="[
                                    'group flex items-center justify-between w-full px-3 py-2 rounded-xl text-xs font-bold transition-all duration-150 relative text-left',
                                    isItemActive(item)
                                        ? 'bg-indigo-50/90 dark:bg-indigo-950/40 text-indigo-600 dark:text-[#06b6d4] shadow-xs'
                                        : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-800/50'
                                ]"
                            >
                                <span v-if="isItemActive(item)" class="absolute left-0 top-1/2 -translate-y-1/2 w-1 h-4 bg-indigo-600 dark:bg-[#06b6d4] rounded-r-full"></span>
                                <div class="flex items-center gap-2.5">
                                    <span class="w-1.5 h-1.5 rounded-full transition-colors"
                                          :class="isItemActive(item) ? 'bg-indigo-600 dark:bg-[#06b6d4]' : 'bg-slate-300 dark:bg-slate-700 group-hover:bg-slate-400'"></span>
                                    <span>{{ item.label }}</span>
                                </div>
                            </Link>

                            <!-- Dedicated Routes (Recipes, Production) -->
                            <Link
                                v-else
                                :href="route(item.route)"
                                :class="[
                                    'group flex items-center justify-between w-full px-3 py-2 rounded-xl text-xs font-bold transition-all duration-150 relative text-left',
                                    isItemActive(item)
                                        ? 'bg-indigo-50/90 dark:bg-indigo-950/40 text-indigo-600 dark:text-[#06b6d4] shadow-xs'
                                        : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-800/50'
                                ]"
                            >
                                <span v-if="isItemActive(item)" class="absolute left-0 top-1/2 -translate-y-1/2 w-1 h-4 bg-indigo-600 dark:bg-[#06b6d4] rounded-r-full"></span>
                                <div class="flex items-center gap-2.5">
                                    <svg v-if="item.id === 'recipes'" class="w-3.5 h-3.5 transition-colors"
                                         :class="isItemActive(item) ? 'text-indigo-600 dark:text-[#06b6d4]' : 'text-slate-400 dark:text-slate-500 group-hover:text-slate-600 dark:group-hover:text-slate-300'"
                                         xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                                    </svg>
                                    <svg v-else class="w-3.5 h-3.5 transition-colors"
                                         :class="isItemActive(item) ? 'text-indigo-600 dark:text-[#06b6d4]' : 'text-slate-400 dark:text-slate-500 group-hover:text-slate-600 dark:group-hover:text-slate-300'"
                                         xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                    </svg>
                                    <span>{{ item.label }}</span>
                                </div>
                            </Link>
                        </template>
                    </div>
                </div>

                <!-- 3. Manage Purchase (Trade & Procurement) -->
                <div class="rounded-xl border border-transparent transition-colors">
                    <button 
                        @click="toggleCategory('purchase')" 
                        type="button" 
                        class="w-full group flex justify-between items-center px-2.5 py-2 rounded-xl text-xs font-bold uppercase tracking-wider transition-all duration-200 focus:outline-none"
                        :class="[
                            activeCategory === 'purchase'
                                ? 'text-indigo-600 dark:text-[#06b6d4] bg-indigo-50/50 dark:bg-indigo-950/20'
                                : 'text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800/40'
                        ]"
                    >
                        <div class="flex items-center gap-2">
                            <div class="p-1 rounded-md transition-colors"
                                 :class="activeCategory === 'purchase' ? 'bg-indigo-100/70 dark:bg-cyan-900/40 text-indigo-600 dark:text-[#06b6d4]' : 'bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400 group-hover:text-indigo-600 dark:group-hover:text-[#06b6d4]'">
                                <svg class="w-3.5 h-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                                </svg>
                            </div>
                            <span class="text-[11px] font-black">Trade</span>
                        </div>

                        <div class="flex items-center gap-1.5">
                            <span v-if="activeCategory === 'purchase'" class="w-1.5 h-1.5 rounded-full bg-indigo-500 dark:bg-cyan-400"></span>
                            <span class="text-[10px] font-semibold text-slate-400 dark:text-slate-500">{{ tradeItems.length }}</span>
                            <svg 
                                :class="{'rotate-180': expandedCategories.purchase}" 
                                class="h-3.5 w-3.5 text-slate-400 dark:text-slate-500 transition-transform duration-200" 
                                viewBox="0 0 20 20" 
                                fill="currentColor"
                            >
                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                            </svg>
                        </div>
                    </button>

                    <div v-show="expandedCategories.purchase" class="space-y-1 pt-1.5 pb-1">
                        <Link
                            v-for="item in tradeItems"
                            :key="item.id"
                            :href="route(item.route)"
                            :class="[
                                'group flex items-center justify-between w-full px-3 py-2 rounded-xl text-xs font-bold transition-all duration-150 relative text-left',
                                isItemActive(item)
                                    ? 'bg-indigo-50/90 dark:bg-indigo-950/40 text-indigo-600 dark:text-[#06b6d4] shadow-xs'
                                    : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-800/50'
                            ]"
                        >
                            <span v-if="isItemActive(item)" class="absolute left-0 top-1/2 -translate-y-1/2 w-1 h-4 bg-indigo-600 dark:bg-[#06b6d4] rounded-r-full"></span>
                            <div class="flex items-center gap-2.5">
                                <span class="w-1.5 h-1.5 rounded-full transition-colors"
                                      :class="isItemActive(item) ? 'bg-indigo-600 dark:bg-[#06b6d4]' : 'bg-slate-300 dark:bg-slate-700 group-hover:bg-slate-400'"></span>
                                <span>{{ item.label }}</span>
                            </div>
                        </Link>
                    </div>
                </div>

            </div>

        </div>
    </aside>
</template>
