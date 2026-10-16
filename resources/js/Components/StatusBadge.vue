<script setup>
import { computed } from 'vue';

const props = defineProps({
    value: {
        type: [Boolean, String, Number],
        required: true
    },
    label: {
        type: String,
        default: null
    }
});

const isBooleanType = computed(() => {
    return typeof props.value === 'boolean' || props.value === 1 || props.value === 0 || props.value === '1' || props.value === '0';
});

const isActive = computed(() => {
    return props.value === true || props.value === 1 || props.value === '1';
});

const statusString = computed(() => {
    return String(props.value || '').toLowerCase();
});

const badgeClasses = computed(() => {
    if (isBooleanType.value) {
        return isActive.value
            ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-400'
            : 'bg-slate-100 text-slate-800 dark:bg-slate-700/50 dark:text-slate-400';
    }

    switch (statusString.value) {
        case 'completed':
        case 'paid':
        case 'active':
            return 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400';
        case 'issued':
        case 'approved':
            return 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400';
        case 'draft':
            return 'bg-slate-100 text-slate-600 dark:bg-slate-700/60 dark:text-slate-300';
        case 'cancelled':
        case 'rejected':
            return 'bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-400';
        default:
            return 'bg-indigo-50 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-400';
    }
});

const displayLabel = computed(() => {
    if (props.label) return props.label;
    if (isBooleanType.value) {
        return isActive.value ? 'Active' : 'Inactive';
    }
    return String(props.value || '').replace(/_/g, ' ').toUpperCase();
});
</script>

<template>
    <span :class="['px-2.5 py-0.5 rounded-full text-xs font-bold inline-flex items-center tracking-wide', badgeClasses]">
        {{ displayLabel }}
    </span>
</template>
