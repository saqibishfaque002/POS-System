<script setup>
const props = defineProps({
    item: Object,
    index: Number
});
const emit = defineEmits(['update-qty', 'remove']);
</script>
<template>
    <div class="flex items-center justify-between p-4 group">
        <div class="flex-1 pr-4">
            <h4 class="font-bold text-slate-800 dark:text-white leading-tight truncate transition-colors group-hover:text-indigo-600 dark:group-hover:text-indigo-400">{{ item.name }}</h4>
            <div v-if="item.variation_name" class="text-xs font-semibold text-slate-500 dark:text-slate-400 mt-1 uppercase tracking-wide">
                {{ item.variation_name }}
            </div>
            <div v-if="item.properties && item.properties.length > 0" class="text-xs text-indigo-500/80 dark:text-indigo-400/80 mt-0.5">
                + {{ item.properties.map(p => p.name).join(', ') }}
            </div>
            <div class="text-sm font-black text-indigo-600 dark:text-cyan-400 mt-2">
                ${{ parseFloat(item.unit_price).toFixed(2) }}
            </div>
        </div>
        
        <div class="flex flex-col items-end gap-3 shrink-0">
            <div class="flex items-center justify-between w-full">
                <div class="font-black text-lg text-slate-800 dark:text-white tabular-nums">
                    ${{ (item.quantity * item.unit_price).toFixed(2) }}
                </div>
                <button @click="emit('remove', index)" class="text-slate-400 hover:text-red-500 hover:bg-red-50 dark:hover:bg-red-500/20 p-2 rounded-full transition-all duration-300 focus:outline-none hover:scale-110 active:scale-95 ml-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                </button>
            </div>
            
            <div class="flex items-center bg-slate-100 dark:bg-slate-700/50 rounded-lg p-1 shadow-inner">
                <button @click="emit('update-qty', index, -1)" class="w-8 h-8 flex items-center justify-center rounded-md bg-white dark:bg-slate-600 text-slate-600 dark:text-slate-300 shadow-sm hover:text-indigo-600 dark:hover:text-cyan-400 hover:shadow transition-all duration-200 active:scale-95 font-medium">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4" /></svg>
                </button>
                <span class="px-3 text-sm font-black w-10 text-center text-slate-800 dark:text-white select-none tabular-nums">
                    {{ item.quantity }}
                </span>
                <button @click="emit('update-qty', index, 1)" class="w-8 h-8 flex items-center justify-center rounded-md bg-white dark:bg-slate-600 text-slate-600 dark:text-slate-300 shadow-sm hover:text-indigo-600 dark:hover:text-cyan-400 hover:shadow transition-all duration-200 active:scale-95 font-medium">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                </button>
            </div>
        </div>
    </div>
</template>
