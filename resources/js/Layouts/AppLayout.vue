<script setup>
import { Head } from '@inertiajs/vue3';
import AppHeader from '@/Components/AppHeader.vue';
import InventorySidebar from '@/Components/InventorySidebar.vue';

const props = defineProps({
    title: {
        type: String,
        required: true,
    },
    headerColorClass: {
        type: String,
        default: 'border-t-indigo-500',
    },
    showBack: {
        type: Boolean,
        default: true,
    },
});
</script>

<template>
    <Head :title="title" />

    <div class="min-h-screen flex flex-col transition-colors duration-300 bg-slate-50 dark:bg-slate-900 pb-16">
        <div class="w-full mx-auto px-4 sm:px-6 lg:px-8 mt-6">
            <!-- Modern Standardized Header -->
            <AppHeader :title="title" :show-back="showBack">
                <template #actions>
                    <slot name="header-actions" />
                </template>
            </AppHeader>

            <div class="flex flex-col md:flex-row gap-6">
                <!-- Sidebar Tabs (Shared) -->
                <slot name="sidebar">
                    <InventorySidebar />
                </slot>

                <!-- Content Slot -->
                <main class="flex-1 min-w-0">
                    <slot />
                </main>
            </div>
            
            <!-- Modal Slots (if any) -->
            <slot name="modals" />
        </div>
    </div>
</template>
