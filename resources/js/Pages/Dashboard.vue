<script setup>
import { ref, onMounted } from 'vue';
import { Head, Link } from '@inertiajs/vue3';

// Theme management
const isDark = ref(false);
const toggleTheme = () => {
    isDark.value = !isDark.value;
    if (isDark.value) {
        document.documentElement.classList.add('dark');
        localStorage.setItem('theme', 'dark');
    } else {
        document.documentElement.classList.remove('dark');
        localStorage.setItem('theme', 'light');
    }
};

onMounted(() => {
    if (localStorage.theme === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
        isDark.value = true;
        document.documentElement.classList.add('dark');
    } else {
        isDark.value = false;
        document.documentElement.classList.remove('dark');
    }
});

const stats = [
    { name: 'Total Revenue', value: '$24,500', trend: '+12%', isPositive: true },
    { name: 'Orders Today', value: '142', trend: '+5%', isPositive: true },
    { name: 'Low Stock Items', value: '8', trend: '-2', isPositive: false },
    { name: 'Active Users', value: '12', trend: '+2', isPositive: true },
];

const widgets = [
    { title: 'Point of Sale', desc: 'Open the main cashier terminal.', icon: 'M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z', color: 'text-emerald-500', route: route('pos.index') },
    { title: 'Inventory Management', desc: 'Add, update, and track product stock.', icon: 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4', color: 'text-indigo-500', route: route('catalog.index') },
    { title: 'Sales Reports', desc: 'View detailed analytics and export data.', icon: 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z', color: 'text-purple-500', route: '#' },
    { title: 'User Management', desc: 'Manage roles, cashiers, and admin access.', icon: 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z', color: 'text-blue-500', route: '#' },
    { title: 'System Settings', desc: 'Configure store details, tax rates, and manage restaurant locations.', icon: 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z M15 12a3 3 0 11-6 0 3 3 0 016 0z', color: 'text-teal-500', route: route('settings.index') },
];
</script>

<template>
    <Head title="Admin Dashboard" />

    <div class="min-h-screen flex flex-col transition-colors duration-300">
        
        <div class="w-full mx-auto px-4 sm:px-6 lg:px-8 mt-6">
            <!-- Header -->
            <header class="dashboard-card px-6 py-4 flex justify-between items-center">
                <h1 class="text-xl font-semibold text-indigo-500 dark:text-[#06b6d4]">ERP Dashboard</h1>
                
                <button @click="toggleTheme" class="p-2 rounded-full hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors">
                    <svg v-if="!isDark" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-slate-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                    </svg>
                    <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-[#06b6d4]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
                    </svg>
                </button>
            </header>

            <main class="mt-10 space-y-10">
                
                <!-- Quick Stats -->
                <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-6">
                    <div v-for="stat in stats" :key="stat.name" class="dashboard-card p-6 flex flex-col justify-between group hover:-translate-y-1 hover:shadow-lg transition-all cursor-default">
                        <div class="flex justify-between items-start">
                            <span class="text-xs font-medium text-slate-500 dark:text-slate-400">{{ stat.name }}</span>
                            <span :class="stat.isPositive ? 'badge-positive' : 'badge-negative'">
                                {{ stat.trend }}
                            </span>
                        </div>
                        <div class="mt-4 text-2xl font-bold text-slate-800 dark:text-white">
                            {{ stat.value }}
                        </div>
                    </div>
                </div>

                <!-- ERP Widgets -->
                <div>
                    <h2 class="text-sm font-bold text-slate-800 dark:text-white mb-4">Management Modules</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <Link v-for="widget in widgets" :key="widget.title" :href="widget.route" class="dashboard-card p-6 flex gap-5 items-center hover:-translate-y-1 hover:shadow-lg transition-all cursor-pointer group">
                            <div class="icon-wrapper bg-slate-50 dark:bg-[#1a202c] shrink-0 border border-slate-100 dark:border-slate-700/50">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" :class="widget.color" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" :d="widget.icon" />
                                </svg>
                            </div>
                            <div class="flex-1">
                                <h3 class="text-sm font-bold text-slate-800 dark:text-white group-hover:text-indigo-500 dark:group-hover:text-[#06b6d4] transition-colors">{{ widget.title }}</h3>
                                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1 pr-4 leading-relaxed">{{ widget.desc }}</p>
                            </div>
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0 text-slate-300 dark:text-slate-600 transition-transform group-hover:translate-x-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                            </svg>
                        </Link>
                    </div>
                </div>
                
            </main>
        </div>
    </div>
</template>
