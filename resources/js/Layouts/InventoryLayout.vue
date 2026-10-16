<script setup>
import AppLayout from './AppLayout.vue';
import InventorySidebar from '@/Components/InventorySidebar.vue';

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
            <slot name="sidebar">
                <InventorySidebar />
            </slot>
        </template>
        
        <slot />

        <template #modals v-if="$slots.modals">
            <slot name="modals" />
        </template>
    </AppLayout>
</template>
