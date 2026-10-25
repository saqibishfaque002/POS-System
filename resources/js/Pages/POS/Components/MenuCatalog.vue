<script setup>
import { ref, computed } from 'vue';

const props = defineProps({
    foodGroups: {
        type: Array,
        default: () => []
    },
    foodItems: {
        type: Array,
        default: () => []
    },
    propertyGroups: {
        type: Array,
        default: () => []
    },
    loading: Boolean,
});

const emit = defineEmits(['selectItem']);

const selectedGroupId = ref('all');
const searchQuery = ref('');

const filteredItems = computed(() => {
    let items = props.foodItems;

    // Filter by food group
    if (selectedGroupId.value !== 'all') {
        items = items.filter(item => item.food_group_id == selectedGroupId.value);
    }

    // Filter by search query
    if (searchQuery.value.trim()) {
        const query = searchQuery.value.toLowerCase().trim();
        items = items.filter(item => 
            item.name.toLowerCase().includes(query) ||
            (item.description && item.description.toLowerCase().includes(query)) ||
            (item.variations && item.variations.some(v => v.sku && v.sku.toLowerCase().includes(query)))
        );
    }

    return items;
});

const handleItemClick = (item) => {
    emit('selectItem', item);
};

const formatPrice = (price) => {
    return Number(price || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
};
</script>

<template>
    <div class="flex-1 flex flex-col h-full bg-slate-50 dark:bg-slate-950 overflow-hidden">
        <!-- Top Toolbar: Search + Category Pills -->
        <div class="p-3 bg-white dark:bg-slate-900 border-b border-slate-200 dark:border-slate-800 space-y-2.5 shrink-0 shadow-xs">
            <!-- Search Input -->
            <div class="relative">
                <svg class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
                <input
                    type="text"
                    v-model="searchQuery"
                    placeholder="Search food item by name or SKU... (Press / to focus)"
                    class="w-full pl-9 pr-8 py-2 text-xs bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-slate-900 dark:text-slate-100 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500"
                />
                <button
                    v-if="searchQuery"
                    type="button"
                    @click="searchQuery = ''"
                    class="absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 text-xs"
                >
                    ✕
                </button>
            </div>

            <!-- Horizontal Scrollable Food Groups -->
            <div class="flex items-center space-x-1.5 overflow-x-auto pb-1 no-scrollbar text-xs">
                <button
                    type="button"
                    @click="selectedGroupId = 'all'"
                    :class="[
                        'px-3.5 py-1.5 rounded-lg font-bold shrink-0 transition-all text-xs',
                        selectedGroupId === 'all'
                            ? 'bg-indigo-600 dark:bg-cyan-600 text-white shadow-xs'
                            : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 hover:bg-slate-200 dark:hover:bg-slate-700'
                    ]"
                >
                    All Items ({{ foodItems.length }})
                </button>
                <button
                    v-for="group in foodGroups"
                    :key="group.id"
                    type="button"
                    @click="selectedGroupId = group.id"
                    :class="[
                        'px-3.5 py-1.5 rounded-lg font-bold shrink-0 transition-all text-xs flex items-center space-x-1',
                        selectedGroupId === group.id
                            ? 'bg-indigo-600 dark:bg-cyan-600 text-white shadow-xs'
                            : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 hover:bg-slate-200 dark:hover:bg-slate-700'
                    ]"
                >
                    <span>{{ group.name }}</span>
                </button>
            </div>
        </div>

        <!-- Items Grid Area -->
        <div class="flex-1 overflow-y-auto p-3.5">
            <!-- Loading State -->
            <div v-if="loading" class="h-64 flex flex-col items-center justify-center text-slate-400">
                <svg class="w-8 h-8 animate-spin text-indigo-500 mb-2" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                </svg>
                <p class="text-xs font-semibold">Loading Catalog Items...</p>
            </div>

            <!-- Empty State -->
            <div v-else-if="filteredItems.length === 0" class="h-64 flex flex-col items-center justify-center text-slate-400">
                <svg class="w-12 h-12 text-slate-300 dark:text-slate-700 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                </svg>
                <p class="text-xs font-bold text-slate-600 dark:text-slate-400">No food items found</p>
                <p class="text-[11px] text-slate-400 mt-0.5">Try selecting a different menu or food category</p>
            </div>

            <!-- Grid -->
            <div v-else class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-4 xl:grid-cols-5 2xl:grid-cols-6 gap-3">
                <button
                    v-for="item in filteredItems"
                    :key="item.id"
                    type="button"
                    @click="handleItemClick(item)"
                    :disabled="!item.is_active"
                    class="group relative text-left bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 hover:border-indigo-400 dark:hover:border-cyan-500 rounded-2xl p-3 flex flex-col justify-between transition-all duration-150 hover:shadow-md active:scale-98 cursor-pointer select-none disabled:opacity-50 disabled:cursor-not-allowed"
                >
                    <!-- Category & Status Badge -->
                    <div>
                        <div class="flex items-center justify-between mb-1.5">
                            <span class="text-[10px] font-bold uppercase tracking-wider text-indigo-600 dark:text-cyan-400 bg-indigo-50 dark:bg-cyan-950/60 px-1.5 py-0.5 rounded">
                                {{ item.food_group_name || 'Item' }}
                            </span>
                            <span
                                v-if="item.variations && item.variations.length > 0"
                                class="text-[10px] font-bold text-amber-600 dark:text-amber-400 bg-amber-50 dark:bg-amber-950/40 px-1.5 py-0.5 rounded"
                            >
                                {{ item.variations.length }} sizes
                            </span>
                        </div>

                        <!-- Item Name -->
                        <h3 class="text-xs font-black text-slate-800 dark:text-slate-100 group-hover:text-indigo-600 dark:group-hover:text-cyan-400 transition-colors line-clamp-2">
                            {{ item.name }}
                        </h3>

                        <!-- Description preview -->
                        <p v-if="item.description" class="text-[10px] text-slate-400 line-clamp-1 mt-0.5">
                            {{ item.description }}
                        </p>
                    </div>

                    <!-- Price Footer -->
                    <div class="mt-3 pt-2 border-t border-slate-100 dark:border-slate-800/80 flex items-center justify-between">
                        <div>
                            <span class="text-[9px] uppercase font-bold text-slate-400">Price</span>
                            <p class="text-xs font-black text-slate-900 dark:text-white">
                                <span class="text-[10px] font-normal text-slate-500 mr-0.5">Rs.</span>
                                <span>{{ formatPrice(item.effective_price || (item.variations?.[0]?.price ?? item.base_price)) }}</span>
                            </p>
                        </div>
                        <div class="w-7 h-7 rounded-lg bg-indigo-50 dark:bg-slate-800 text-indigo-600 dark:text-cyan-400 flex items-center justify-center font-bold text-sm group-hover:bg-indigo-600 group-hover:text-white dark:group-hover:bg-cyan-600 dark:group-hover:text-white transition-all shadow-xs">
                            +
                        </div>
                    </div>
                </button>
            </div>
        </div>
    </div>
</template>

<style scoped>
.no-scrollbar::-webkit-scrollbar {
    display: none;
}
.no-scrollbar {
    -ms-overflow-style: none;
    scrollbar-width: none;
}
</style>
