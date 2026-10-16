<script setup>
import { ref, computed } from 'vue';

const props = defineProps({
    activeTab: {
        type: String,
        default: 'users'
    }
});

const emit = defineEmits(['update:activeTab']);

const expanded = ref(true);

const toggleCategory = () => {
    expanded.value = !expanded.value;
};

// Search filter state for Users Sidebar
const searchQuery = ref('');
const isSearching = computed(() => searchQuery.value.trim().length > 0);

const matchesUsers = computed(() => {
    if (!isSearching.value) return true;
    const q = searchQuery.value.toLowerCase().trim();
    return 'users'.includes(q) || 'staff & security'.includes(q);
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
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-xs font-black tracking-wide text-slate-800 dark:text-slate-100 uppercase">User Hub</h2>
                        <p class="text-[10px] text-slate-400 dark:text-slate-500 font-medium">Access & Roles</p>
                    </div>
                </div>
                <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-cyan-400 border border-indigo-100 dark:border-indigo-900/40">
                    Staff
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
                    placeholder="Filter user tabs..."
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

            <!-- Staff & Security Section -->
            <div v-show="matchesUsers" class="rounded-xl border border-transparent transition-colors">
                <button 
                    @click="toggleCategory" 
                    type="button" 
                    class="w-full group flex justify-between items-center px-2.5 py-2 rounded-xl text-xs font-bold uppercase tracking-wider transition-all duration-200 focus:outline-none text-indigo-600 dark:text-[#06b6d4] bg-indigo-50/50 dark:bg-indigo-950/20"
                >
                    <div class="flex items-center gap-2">
                        <div class="p-1 rounded-md transition-colors bg-indigo-100/70 dark:bg-cyan-900/40 text-indigo-600 dark:text-[#06b6d4]">
                            <svg class="w-3.5 h-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                            </svg>
                        </div>
                        <span class="text-[11px] font-black">Staff & Security</span>
                    </div>

                    <div class="flex items-center gap-1.5">
                        <span class="w-1.5 h-1.5 rounded-full bg-indigo-500 dark:bg-cyan-400"></span>
                        <span class="text-[10px] font-semibold text-slate-400 dark:text-slate-500">1</span>
                        <svg 
                            :class="{'rotate-180': isSearching || expanded}" 
                            class="h-3.5 w-3.5 text-slate-400 dark:text-slate-500 transition-transform duration-200" 
                            viewBox="0 0 20 20" 
                            fill="currentColor"
                        >
                            <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                        </svg>
                    </div>
                </button>

                <div v-show="isSearching || expanded" class="space-y-1 pt-1.5 pb-1">
                    <button
                        type="button"
                        @click="emit('update:activeTab', 'users')"
                        :class="[
                            'group flex items-center justify-between w-full px-3 py-2 rounded-xl text-xs font-bold transition-all duration-150 relative text-left',
                            props.activeTab === 'users'
                                ? 'bg-indigo-50/90 dark:bg-indigo-950/40 text-indigo-600 dark:text-[#06b6d4] shadow-xs'
                                : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-800/50'
                        ]"
                    >
                        <span v-if="props.activeTab === 'users'" class="absolute left-0 top-1/2 -translate-y-1/2 w-1 h-4 bg-indigo-600 dark:bg-[#06b6d4] rounded-r-full"></span>
                        <div class="flex items-center gap-2.5">
                            <span class="w-1.5 h-1.5 rounded-full transition-colors"
                                  :class="props.activeTab === 'users' ? 'bg-indigo-600 dark:bg-[#06b6d4]' : 'bg-slate-300 dark:bg-slate-700 group-hover:bg-slate-400'"></span>
                            <span>Users</span>
                        </div>
                    </button>
                </div>
            </div>

            <!-- Empty State -->
            <div v-if="isSearching && !matchesUsers" class="px-3 py-6 text-center rounded-xl bg-slate-50/70 dark:bg-slate-800/30 border border-dashed border-slate-200 dark:border-slate-800">
                <svg class="w-6 h-6 mx-auto text-slate-300 dark:text-slate-600 mb-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
                <p class="text-xs font-bold text-slate-600 dark:text-slate-300">No sections found</p>
                <p class="text-[10px] text-slate-400 dark:text-slate-500 mt-0.5">No match for "{{ searchQuery }}"</p>
                <button @click="searchQuery = ''" type="button" class="mt-2.5 px-2.5 py-1 text-[11px] font-bold text-indigo-600 dark:text-[#06b6d4] bg-indigo-50 dark:bg-indigo-950/40 rounded-lg hover:underline transition-all">
                    Clear filter
                </button>
            </div>

        </div>
    </aside>
</template>
