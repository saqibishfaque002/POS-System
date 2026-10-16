<script setup>
import { defineProps, defineEmits, ref, computed } from 'vue';
import StatusBadge from '@/Components/StatusBadge.vue';

const props = defineProps({
    columns: Array,
    data: Array,
    title: String,
});

const emit = defineEmits(['edit', 'delete']);

const searchQuery = ref('');

const searchObject = (obj, query) => {
    for (const key in obj) {
        const val = obj[key];
        if (val === null || val === undefined) continue;
        if (typeof val === 'object') {
            if (searchObject(val, query)) return true;
        } else {
            let strVal = String(val).toLowerCase();
            if (key === 'is_active') strVal = val ? 'active' : 'inactive';
            if (key === 'is_multiple') strVal = val ? 'yes' : 'no';
            if (strVal.includes(query)) return true;
        }
    }
    return false;
};

const filteredData = computed(() => {
    if (!searchQuery.value || !props.data) return props.data || [];
    const query = searchQuery.value.toLowerCase();
    return props.data.filter(row => searchObject(row, query));
});
</script>

<template>
    <div class="dashboard-card overflow-hidden">
        <div class="px-6 py-4 border-b border-erp-border-light dark:border-erp-border-dark flex flex-col sm:flex-row justify-between items-center gap-4 bg-slate-50 dark:bg-slate-800/50">
            <h3 class="font-bold text-slate-800 dark:text-white">{{ title }}</h3>
            <div class="flex items-center gap-3 w-full sm:w-auto">
                <input type="text" v-model="searchQuery" placeholder="Search..." class="pos-input !py-1.5 text-sm w-full sm:w-64" />
                <slot name="actions"></slot>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr>
                        <th v-for="col in columns" :key="col.key" class="px-6 py-3 border-b border-erp-border-light dark:border-erp-border-dark bg-slate-50 dark:bg-slate-800/20 text-xs font-bold text-slate-500 uppercase tracking-wider">
                            {{ col.label }}
                        </th>
                        <th class="px-6 py-3 border-b border-erp-border-light dark:border-erp-border-dark bg-slate-50 dark:bg-slate-800/20 text-xs font-bold text-slate-500 uppercase tracking-wider text-right w-32">
                            Actions
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-erp-border-light dark:divide-erp-border-dark">
                    <tr v-for="row in filteredData" :key="row.id" class="hover:bg-slate-50 dark:hover:bg-slate-800/30 transition-colors">
                        <td v-for="col in columns" :key="col.key" class="px-6 py-4 whitespace-nowrap text-sm text-slate-700 dark:text-slate-300">
                            <slot :name="'cell-'+col.key" :item="row">
                                <StatusBadge v-if="col.key === 'is_active'" :value="row[col.key]" />
                                <StatusBadge v-else-if="col.key === 'status'" :value="row[col.key]" />
                                <span v-else-if="col.key === 'is_multiple'" :class="row.is_multiple ? 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400 px-2.5 py-0.5 rounded-full text-xs font-bold' : 'bg-slate-100 text-slate-800 dark:bg-slate-700/50 dark:text-slate-400 px-2.5 py-0.5 rounded-full text-xs font-bold'">
                                    {{ row.is_multiple ? 'Yes' : 'No' }}
                                </span>
                                <template v-else>
                                    {{ row[col.key] }}
                                </template>
                            </slot>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-right font-medium">
                            <slot name="row-actions" :item="row">
                                <button @click="emit('edit', row)" class="text-indigo-600 hover:text-indigo-900 dark:text-[#06b6d4] dark:hover:text-cyan-300 mr-4 transition-colors">Edit</button>
                                <button @click="emit('delete', row)" class="text-red-500 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300 transition-colors">Delete</button>
                            </slot>
                        </td>
                    </tr>
                    <tr v-if="!filteredData || !filteredData.length">
                        <td :colspan="columns.length + 1" class="px-6 py-8 text-center text-slate-500">No {{ title.toLowerCase() }} found.</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
