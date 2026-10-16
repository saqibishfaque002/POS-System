<script setup>
import InventorySidebar from '@/Components/InventorySidebar.vue';

defineProps({
    activeTab: {
        type: String,
        default: ''
    }
});

const emit = defineEmits(['update:activeTab']);
</script>

<template>
    <InventorySidebar :active-tab="activeTab" @update:active-tab="emit('update:activeTab', $event)" />
</template>
