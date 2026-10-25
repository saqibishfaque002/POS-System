<script setup>
import { ref, computed, watch, onMounted } from 'vue';

const props = defineProps({
    activeTab: {
        type: String,
        default: 'storeProfile'
    }
});

const emit = defineEmits(['update:activeTab']);

const expandedCategories = ref({
    general: false,
    geographic: false,
    restaurant: false,
    financial: false,
});

const categories = [
    {
        id: 'general',
        label: 'General Setting',
        shortLabel: 'General',
        icon: 'cog',
        items: [
            { id: 'storeProfile', label: 'Store Profile', icon: 'store' },
            { id: 'posSettings', label: 'POS Settings', icon: 'desktop' },
        ]
    },
    {
        id: 'geographic',
        label: 'Geographic Locations',
        shortLabel: 'Locations',
        icon: 'globe',
        items: [
            { id: 'countries', label: 'Countries', icon: 'flag' },
            { id: 'provinces', label: 'Provinces', icon: 'map' },
            { id: 'cities', label: 'Cities', icon: 'building' },
        ]
    },
    {
        id: 'restaurant',
        label: 'Manage Restaurant',
        shortLabel: 'Restaurant',
        icon: 'building-office',
        items: [
            { id: 'companies', label: 'Companies', icon: 'briefcase' },
            { id: 'branches', label: 'Branches', icon: 'storefront' },
            { id: 'floors', label: 'Floors', icon: 'layers' },
            { id: 'tables', label: 'Tables', icon: 'table' },
            { id: 'orderTypes', label: 'Order Types', icon: 'tag' },
            { id: 'parties', label: 'Parties (POS)', icon: 'users' },
        ]
    },
    {
        id: 'financial',
        label: 'Financial Settings',
        shortLabel: 'Financial',
        icon: 'credit-card',
        items: [
            { id: 'paymentTypes', label: 'Payment Types', icon: 'credit-card' },
            { id: 'discounts', label: 'Discounts & Deals', icon: 'tag' },
            { id: 'bankDiscounts', label: 'Bank Card Discounts', icon: 'credit-card' },
        ]
    }
];

const activeCategory = computed(() => {
    for (const cat of categories) {
        if (cat.items.some(item => item.id === props.activeTab)) {
            return cat.id;
        }
    }
    return 'general';
});

const activeCategoryLabel = computed(() => {
    const found = categories.find(c => c.id === activeCategory.value);
    return found ? found.shortLabel : 'Settings';
});

const syncExpandedState = () => {
    const current = activeCategory.value;
    if (current) {
        expandedCategories.value[current] = true;
    }
};

watch(() => props.activeTab, () => {
    syncExpandedState();
}, { immediate: true });

onMounted(() => {
    syncExpandedState();
});

const toggleCategory = (catId) => {
    expandedCategories.value[catId] = !expandedCategories.value[catId];
};

const selectTab = (tabId) => {
    emit('update:activeTab', tabId);
};

// Search filter state for Settings Sidebar
const searchQuery = ref('');
const isSearching = computed(() => searchQuery.value.trim().length > 0);

const filteredCategories = computed(() => {
    if (!isSearching.value) return categories;
    const q = searchQuery.value.toLowerCase().trim();
    return categories
        .map(cat => ({
            ...cat,
            items: cat.items.filter(item => item.label.toLowerCase().includes(q))
        }))
        .filter(cat => cat.items.length > 0);
});

const totalMatches = computed(() => {
    return filteredCategories.value.reduce((acc, cat) => acc + cat.items.length, 0);
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
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-xs font-black tracking-wide text-slate-800 dark:text-slate-100 uppercase">Settings Hub</h2>
                        <p class="text-[10px] text-slate-400 dark:text-slate-500 font-medium">System & Locations</p>
                    </div>
                </div>
                <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-cyan-400 border border-indigo-100 dark:border-indigo-900/40">
                    {{ activeCategoryLabel }}
                </span>
            </div>

            <!-- Modern Search Filter -->
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-2.5 flex items-center pointer-events-none text-slate-400 dark:text-slate-500">
                    <svg class="w-3.5 h-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>
                <input
                    type="text"
                    v-model="searchQuery"
                    placeholder="Filter settings tabs..."
                    class="w-full pl-8 pr-7 py-1.5 text-xs rounded-xl bg-slate-50/90 dark:bg-slate-800/60 border border-slate-200/90 dark:border-slate-700/80 text-slate-700 dark:text-slate-200 placeholder-slate-400 dark:placeholder-slate-500 focus:outline-none focus:border-indigo-500 dark:focus:border-[#06b6d4] focus:ring-1 focus:ring-indigo-500/20 dark:focus:ring-cyan-500/20 transition-all"
                />
                <button
                    v-if="searchQuery"
                    @click="searchQuery = ''"
                    type="button"
                    class="absolute inset-y-0 right-0 pr-2.5 flex items-center text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition-colors"
                    title="Clear filter"
                >
                    <svg class="w-3.5 h-3.5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                    </svg>
                </button>
            </div>

            <!-- Category Accordions -->
            <div class="space-y-2">
                <div v-for="cat in filteredCategories" :key="cat.id" class="rounded-xl border border-transparent transition-colors">
                    <button 
                        @click="toggleCategory(cat.id)" 
                        type="button" 
                        class="w-full group flex justify-between items-center px-2.5 py-2 rounded-xl text-xs font-bold uppercase tracking-wider transition-all duration-200 focus:outline-none"
                        :class="[
                            activeCategory === cat.id
                                ? 'text-indigo-600 dark:text-[#06b6d4] bg-indigo-50/50 dark:bg-indigo-950/20'
                                : 'text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800/40'
                        ]"
                    >
                        <div class="flex items-center gap-2">
                            <div class="p-1 rounded-md transition-colors"
                                 :class="activeCategory === cat.id ? 'bg-indigo-100/70 dark:bg-cyan-900/40 text-indigo-600 dark:text-[#06b6d4]' : 'bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400 group-hover:text-indigo-600 dark:group-hover:text-[#06b6d4]'">
                                
                                <svg v-if="cat.id === 'general'" class="w-3.5 h-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4" />
                                </svg>
                                
                                <svg v-else-if="cat.id === 'geographic'" class="w-3.5 h-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>

                                <svg v-else-if="cat.id === 'restaurant'" class="w-3.5 h-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                </svg>

                                <svg v-else class="w-3.5 h-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                                </svg>
                            </div>
                            <span class="text-[11px] font-black">{{ cat.label }}</span>
                        </div>

                        <div class="flex items-center gap-1.5">
                            <span v-if="activeCategory === cat.id" class="w-1.5 h-1.5 rounded-full bg-indigo-500 dark:bg-cyan-400"></span>
                            <span class="text-[10px] font-semibold text-slate-400 dark:text-slate-500">{{ cat.items.length }}</span>
                            <svg 
                                :class="{'rotate-180': isSearching || expandedCategories[cat.id]}" 
                                class="h-3.5 w-3.5 text-slate-400 dark:text-slate-500 transition-transform duration-200" 
                                viewBox="0 0 20 20" 
                                fill="currentColor"
                            >
                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                            </svg>
                        </div>
                    </button>

                    <div v-show="isSearching || expandedCategories[cat.id]" class="space-y-1 pt-1.5 pb-1">
                        <button
                            v-for="item in cat.items"
                            :key="item.id"
                            type="button"
                            @click="selectTab(item.id)"
                            :class="[
                                'group flex items-center justify-between w-full px-3 py-2 rounded-xl text-xs font-bold transition-all duration-150 relative text-left',
                                props.activeTab === item.id
                                    ? 'bg-indigo-50/90 dark:bg-indigo-950/40 text-indigo-600 dark:text-[#06b6d4] shadow-xs'
                                    : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-800/50'
                            ]"
                        >
                            <span v-if="props.activeTab === item.id" class="absolute left-0 top-1/2 -translate-y-1/2 w-1 h-4 bg-indigo-600 dark:bg-[#06b6d4] rounded-r-full"></span>
                            <div class="flex items-center gap-2.5">
                                <span class="w-1.5 h-1.5 rounded-full transition-colors"
                                      :class="props.activeTab === item.id ? 'bg-indigo-600 dark:bg-[#06b6d4]' : 'bg-slate-300 dark:bg-slate-700 group-hover:bg-slate-400'"></span>
                                <span>{{ item.label }}</span>
                            </div>
                        </button>
                    </div>
                </div>

                <!-- Empty State -->
                <div v-if="isSearching && totalMatches === 0" class="px-3 py-6 text-center rounded-xl bg-slate-50/70 dark:bg-slate-800/30 border border-dashed border-slate-200 dark:border-slate-800">
                    <svg class="w-6 h-6 mx-auto text-slate-300 dark:text-slate-600 mb-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    <p class="text-xs font-bold text-slate-600 dark:text-slate-300">No settings found</p>
                    <p class="text-[10px] text-slate-400 dark:text-slate-500 mt-0.5">No match for "{{ searchQuery }}"</p>
                    <button @click="searchQuery = ''" type="button" class="mt-2.5 px-2.5 py-1 text-[11px] font-bold text-indigo-600 dark:text-[#06b6d4] bg-indigo-50 dark:bg-indigo-950/40 rounded-lg hover:underline transition-all">
                        Clear filter
                    </button>
                </div>
            </div>

        </div>
    </aside>
</template>
