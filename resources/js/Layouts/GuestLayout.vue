<script setup>
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0">
        <div>
            <Link href="/">
                <h1 class="text-4xl font-bold bg-clip-text text-transparent bg-gradient-to-r from-[#4f46e5] to-[#9333ea] dark:from-[#06b6d4] dark:to-blue-500 mb-6">POS System</h1>
            </Link>
        </div>

        <div
            class="w-full sm:max-w-md px-6 py-8 pos-card"
        >
            <slot />
        </div>
    </div>
</template>
