<script setup>
import AppLayout from './AppLayout.vue';

defineProps({
    title: {
        type: String,
        required: true,
    },
    headerColorClass: {
        type: String,
        default: '',
    },
});
</script>

<template>
    <AppLayout :title="title">
        <template #sidebar>
            <slot name="sidebar" />
        </template>
        
        <slot />

        <template #modals>
            <slot name="modals" />
        </template>
    </AppLayout>
</template>
