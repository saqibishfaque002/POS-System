<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { Link, usePage, router } from '@inertiajs/vue3';
import { useTheme } from '@/Composables/useTheme';

const props = defineProps({
    title: {
        type: String,
        required: true,
    },
    showBack: {
        type: Boolean,
        default: true,
    },
});

const page = usePage();
const authUser = computed(() => page.props.auth?.user || null);

// User Initials
const userInitials = computed(() => {
    if (!authUser.value?.name) return 'U';
    const parts = authUser.value.name.trim().split(' ');
    if (parts.length >= 2) {
        return (parts[0][0] + parts[1][0]).toUpperCase();
    }
    return authUser.value.name.substring(0, 2).toUpperCase();
});

// Theme Management via Shared Composable
const { isDark, toggleTheme } = useTheme();

// Profile Dropdown state
const isProfileDropdownOpen = ref(false);
const dropdownRef = ref(null);

const toggleProfileDropdown = () => {
    isProfileDropdownOpen.value = !isProfileDropdownOpen.value;
};

const closeDropdownOnOutsideClick = (e) => {
    if (dropdownRef.value && !dropdownRef.value.contains(e.target)) {
        isProfileDropdownOpen.value = false;
    }
};

onMounted(() => {
    window.addEventListener('click', closeDropdownOnOutsideClick);
});

onUnmounted(() => {
    window.removeEventListener('click', closeDropdownOnOutsideClick);
});

// Logout handler
const logout = () => {
    router.post(route('logout'));
};

// Navigation Modules
const navModules = [
    {
        name: 'Dashboard',
        route: 'dashboard',
        pattern: 'dashboard',
        icon: 'dashboard'
    },
    {
        name: 'Inventory',
        route: 'catalog.index',
        pattern: ['catalog.*', 'ingredients.*', 'recipes.*', 'production.*', 'trade.*'],
        icon: 'inventory'
    },
    {
        name: 'Users',
        route: 'users.index',
        pattern: 'users.*',
        icon: 'users'
    },
    {
        name: 'Settings',
        route: 'settings.index',
        pattern: 'settings.*',
        icon: 'settings'
    },
];

// Check active module
const isModuleActive = (mod) => {
    try {
        if (Array.isArray(mod.pattern)) {
            return mod.pattern.some(p => route().current(p));
        }
        return route().current(mod.pattern);
    } catch (e) {
        return false;
    }
};

const isDashboard = computed(() => {
    try {
        return route().current('dashboard');
    } catch (e) {
        return false;
    }
});

// Current module icon type
const currentModuleType = computed(() => {
    try {
        if (route().current('dashboard')) return 'dashboard';
        if (route().current('users.*')) return 'users';
        if (route().current('settings.*')) return 'settings';
        return 'inventory';
    } catch (e) {
        return 'inventory';
    }
});
</script>

<template>
    <header class="dashboard-card !p-3 sm:!px-6 sm:!py-3.5 mb-6 border border-slate-200/90 dark:border-slate-800/90 rounded-2xl bg-white/95 dark:bg-slate-900/95 shadow-sm transition-all duration-300">
        <div class="flex items-center justify-between gap-3 flex-wrap sm:flex-nowrap">
            
            <!-- Left: Module Branding & Breadcrumbs -->
            <div class="flex items-center gap-3 sm:gap-4 min-w-0">
                <!-- Dynamic Module Icon Badge -->
                <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-indigo-500/10 via-indigo-500/15 to-purple-500/10 dark:from-cyan-500/10 dark:via-cyan-500/20 dark:to-blue-500/10 text-indigo-600 dark:text-[#06b6d4] border border-indigo-100/80 dark:border-cyan-900/40 flex items-center justify-center shrink-0 shadow-xs">
                    <svg v-if="currentModuleType === 'dashboard'" class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                    </svg>
                    <svg v-else-if="currentModuleType === 'settings'" class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    <svg v-else-if="currentModuleType === 'users'" class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                    </svg>
                    <svg v-else class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                    </svg>
                </div>

                <!-- Title & Dynamic Breadcrumb -->
                <div class="truncate">
                    <div class="flex items-center gap-2">
                        <h1 class="text-base sm:text-lg font-black tracking-tight text-slate-800 dark:text-white truncate">
                            {{ title }}
                        </h1>
                        <span class="hidden sm:inline-flex text-[10px] font-bold px-2 py-0.5 rounded-md bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-[#06b6d4] border border-indigo-100 dark:border-indigo-900/40 uppercase tracking-wider">
                            ERP
                        </span>
                    </div>

                    <!-- Breadcrumbs / Status -->
                    <div class="flex items-center gap-1.5 text-xs text-slate-400 dark:text-slate-500 font-medium mt-0.5">
                        <template v-if="!isDashboard && showBack">
                            <Link :href="route('dashboard')" class="hover:text-indigo-600 dark:hover:text-[#06b6d4] transition-colors flex items-center gap-1 font-semibold text-slate-500 dark:text-slate-400">
                                <span>Dashboard</span>
                            </Link>
                            <span>/</span>
                            <span class="text-slate-700 dark:text-slate-300 font-bold truncate">{{ title }}</span>
                        </template>
                        <template v-else>
                            <span class="flex items-center gap-1.5">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                <span>Point of Sale System · Live</span>
                            </span>
                        </template>
                    </div>
                </div>
            </div>

            <!-- Center: Modern Navigation Module Switcher Pills -->
            <nav class="hidden lg:flex items-center p-1 bg-slate-100/80 dark:bg-slate-800/60 rounded-xl border border-slate-200/60 dark:border-slate-700/50 shadow-inner">
                <Link
                    v-for="mod in navModules"
                    :key="mod.name"
                    :href="route(mod.route)"
                    class="px-3.5 py-1.5 text-xs font-bold rounded-lg transition-all duration-200 flex items-center gap-1.5 select-none"
                    :class="[
                        isModuleActive(mod)
                            ? 'bg-white dark:bg-slate-900 text-indigo-600 dark:text-[#06b6d4] shadow-xs'
                            : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-white/50 dark:hover:bg-slate-800/40'
                    ]"
                >
                    <span v-if="isModuleActive(mod)" class="w-1.5 h-1.5 rounded-full bg-indigo-600 dark:bg-[#06b6d4]"></span>
                    <span>{{ mod.name }}</span>
                </Link>
            </nav>

            <!-- Right: Actions, Theme Switcher & Profile Dropdown -->
            <div class="flex items-center gap-2 sm:gap-3 shrink-0">
                
                <!-- Custom page action slot (if any) -->
                <slot name="actions" />

                <!-- Back button on mobile if not dashboard -->
                <Link
                    v-if="!isDashboard && showBack"
                    :href="route('dashboard')"
                    class="lg:hidden p-2 rounded-xl text-slate-500 hover:text-indigo-600 dark:text-slate-400 dark:hover:text-[#06b6d4] hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors"
                    title="Back to Dashboard"
                >
                    <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                </Link>

                <!-- Modern Theme Switcher -->
                <button
                    @click="toggleTheme"
                    type="button"
                    class="p-2 sm:p-2.5 rounded-xl border border-slate-200/80 dark:border-slate-800 bg-slate-50/80 dark:bg-slate-800/50 hover:bg-slate-100 dark:hover:bg-slate-700/60 text-slate-600 dark:text-slate-300 transition-all duration-200 focus:outline-none"
                    :title="isDark ? 'Switch to Light Mode' : 'Switch to Dark Mode'"
                >
                    <!-- Sun Icon (for dark mode) -->
                    <svg v-if="isDark" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 sm:h-4.5 sm:w-4.5 text-[#06b6d4] transition-transform duration-300 hover:rotate-45" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
                    </svg>
                    <!-- Moon Icon (for light mode) -->
                    <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 sm:h-4.5 sm:w-4.5 text-slate-600 transition-transform duration-300 hover:-rotate-12" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                    </svg>
                </button>

                <!-- User Profile Dropdown Container -->
                <div class="relative" ref="dropdownRef">
                    <button
                        @click.stop="toggleProfileDropdown"
                        type="button"
                        class="group flex items-center gap-2 p-1 sm:pr-2.5 sm:pl-1 rounded-xl border border-slate-200/80 dark:border-slate-800 bg-slate-50/80 dark:bg-slate-800/50 hover:bg-slate-100 dark:hover:bg-slate-700/60 transition-all duration-200 focus:outline-none"
                    >
                        <!-- Avatar with Online Dot -->
                        <div class="relative">
                            <div class="w-8 h-8 rounded-lg bg-gradient-to-tr from-indigo-600 to-indigo-500 dark:from-cyan-600 dark:to-cyan-500 text-white font-black text-xs flex items-center justify-center shadow-xs select-none">
                                {{ userInitials }}
                            </div>
                            <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 ring-2 ring-white dark:ring-slate-900 absolute -bottom-0.5 -right-0.5"></span>
                        </div>

                        <!-- User Info on desktop -->
                        <div class="hidden sm:flex flex-col text-left leading-tight pr-1">
                            <span class="text-xs font-bold text-slate-800 dark:text-slate-100 truncate max-w-[110px]">
                                {{ authUser?.name || 'User' }}
                            </span>
                            <span class="text-[10px] font-semibold text-slate-400 capitalize">
                                {{ authUser?.role || 'Staff' }}
                            </span>
                        </div>

                        <!-- Chevron -->
                        <svg
                            :class="{'rotate-180': isProfileDropdownOpen}"
                            class="w-3.5 h-3.5 text-slate-400 dark:text-slate-500 transition-transform duration-200"
                            xmlns="http://www.w3.org/2000/svg"
                            viewBox="0 0 20 20"
                            fill="currentColor"
                        >
                            <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                        </svg>
                    </button>

                    <!-- Profile Dropdown Menu -->
                    <transition
                        enter-active-class="transition duration-150 ease-out"
                        enter-from-class="transform scale-95 opacity-0"
                        enter-to-class="transform scale-100 opacity-100"
                        leave-active-class="transition duration-100 ease-in"
                        leave-from-class="transform scale-100 opacity-100"
                        leave-to-class="transform scale-95 opacity-0"
                    >
                        <div
                            v-if="isProfileDropdownOpen"
                            class="absolute right-0 mt-2 w-56 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 shadow-xl py-2 z-50 overflow-hidden"
                        >
                            <!-- User Header Card -->
                            <div class="px-4 py-3 bg-slate-50/80 dark:bg-slate-800/40 border-b border-slate-100 dark:border-slate-800/80 flex items-center gap-3">
                                <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-indigo-600 to-indigo-500 dark:from-cyan-600 dark:to-cyan-500 text-white font-black text-sm flex items-center justify-center shrink-0">
                                    {{ userInitials }}
                                </div>
                                <div class="truncate">
                                    <p class="text-xs font-bold text-slate-800 dark:text-white truncate">
                                        {{ authUser?.name || 'User' }}
                                    </p>
                                    <p class="text-[10px] text-slate-400 truncate">
                                        {{ authUser?.email || '' }}
                                    </p>
                                    <span class="inline-block mt-0.5 text-[9px] font-bold uppercase tracking-wider px-1.5 py-0.2 rounded bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-[#06b6d4]">
                                        {{ authUser?.role || 'Staff' }}
                                    </span>
                                </div>
                            </div>

                            <!-- Menu Links -->
                            <div class="py-1">
                                <Link
                                    :href="route('profile.edit')"
                                    class="flex items-center gap-2.5 px-4 py-2 text-xs font-semibold text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors"
                                    @click="isProfileDropdownOpen = false"
                                >
                                    <svg class="w-4 h-4 text-slate-400 dark:text-slate-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                    </svg>
                                    <span>Account Profile</span>
                                </Link>

                                <Link
                                    :href="route('settings.index')"
                                    class="flex items-center gap-2.5 px-4 py-2 text-xs font-semibold text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors"
                                    @click="isProfileDropdownOpen = false"
                                >
                                    <svg class="w-4 h-4 text-slate-400 dark:text-slate-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    </svg>
                                    <span>System Settings</span>
                                </Link>

                                <Link
                                    :href="route('users.index')"
                                    class="flex items-center gap-2.5 px-4 py-2 text-xs font-semibold text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors"
                                    @click="isProfileDropdownOpen = false"
                                >
                                    <svg class="w-4 h-4 text-slate-400 dark:text-slate-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                    </svg>
                                    <span>User Management</span>
                                </Link>
                            </div>

                            <!-- Logout Divider & Button -->
                            <div class="border-t border-slate-100 dark:border-slate-800/80 pt-1">
                                <button
                                    type="button"
                                    @click="logout"
                                    class="w-full flex items-center gap-2.5 px-4 py-2 text-xs font-bold text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-950/30 transition-colors text-left"
                                >
                                    <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                                    </svg>
                                    <span>Log Out</span>
                                </button>
                            </div>
                        </div>
                    </transition>
                </div>

            </div>

        </div>
    </header>
</template>
