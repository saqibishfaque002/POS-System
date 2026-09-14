<script setup>
import { ref, computed, watch, onMounted } from 'vue';
import { useForm, router, usePage } from '@inertiajs/vue3';
import { useNetworkStatus } from '@/Composables/useNetworkStatus';
import localforage from 'localforage';
import axios from 'axios';

const page = usePage();
const user = computed(() => page.props.auth.user);

const logout = () => {
    router.post(route('logout'));
};

const props = defineProps({
    products: Array,
});

const { isOnline } = useNetworkStatus();

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

// Cart State
const cart = ref([]);

const addToCart = (product) => {
    const existing = cart.value.find(item => item.product_id === product.id);
    if (existing) {
        existing.quantity++;
    } else {
        cart.value.push({
            product_id: product.id,
            name: product.name,
            price: product.price,
            quantity: 1
        });
    }
};

const removeFromCart = (index) => {
    cart.value.splice(index, 1);
};

const cartTotal = computed(() => {
    return cart.value.reduce((sum, item) => sum + (item.price * item.quantity), 0);
});

// Checkout logic
const isProcessing = ref(false);

const checkout = async () => {
    if (cart.value.length === 0) return;
    isProcessing.value = true;

    // We must generate UUID manually if crypto.randomUUID() is not available on some environments (e.g. non-HTTPS local dev), 
    // but modern browsers support crypto.randomUUID() well enough.
    const offlineUuid = crypto.randomUUID ? crypto.randomUUID() : (Math.random().toString(36).substring(2, 15) + Math.random().toString(36).substring(2, 15));

    const payload = {
        items: cart.value.map(item => ({
            product_id: item.product_id,
            quantity: item.quantity
        })),
        total: cartTotal.value,
        offline_uuid: offlineUuid
    };

    if (isOnline.value) {
        // Online: submit to backend via Inertia
        router.post(route('orders.store'), payload, {
            preserveScroll: true,
            onSuccess: () => {
                cart.value = [];
                isProcessing.value = false;
                alert('Order processed successfully!');
            },
            onError: () => {
                isProcessing.value = false;
                alert('Error processing order.');
            }
        });
    } else {
        // Offline: save to localforage
        const pendingOrders = await localforage.getItem('pending_orders') || [];
        pendingOrders.push(payload);
        await localforage.setItem('pending_orders', pendingOrders);
        
        cart.value = [];
        isProcessing.value = false;
        alert('You are offline. Order saved locally and will sync when back online.');
    }
};

// Background Sync
const syncPendingOrders = async () => {
    if (!isOnline.value) return;

    const pendingOrders = await localforage.getItem('pending_orders') || [];
    if (pendingOrders.length === 0) return;

    console.log(`Syncing ${pendingOrders.length} pending orders...`);
    
    // We create a fresh array to hold the ones that failed
    const failedOrders = [];
    
    // Attempt to sync each order one by one
    for (let i = 0; i < pendingOrders.length; i++) {
        const order = pendingOrders[i];
        try {
            await axios.post(route('orders.store'), order);
            console.log('Successfully synced order:', order.offline_uuid);
        } catch (error) {
            console.error('Failed to sync order', order, error);
            failedOrders.push(order);
        }
    }
    
    await localforage.setItem('pending_orders', failedOrders);
    
    // If all synced, we might want to refresh the products list to get updated stock
    if (failedOrders.length < pendingOrders.length) {
        router.reload({ only: ['products'] });
    }
};

// Watch for network status changes to trigger sync
watch(isOnline, (newStatus) => {
    if (newStatus) {
        syncPendingOrders();
    }
});

// Run initial sync check
onMounted(() => {
    if (isOnline.value) {
        syncPendingOrders();
    }
});
</script>

<template>
    <div class="min-h-screen flex flex-col transition-colors duration-300">
        <!-- Header -->
        <header class="pos-card m-4 p-4 flex justify-between items-center rounded-2xl">
            <div class="flex items-center gap-4">
                <h1 class="text-2xl font-bold bg-clip-text text-transparent bg-gradient-to-r from-[#4f46e5] to-[#9333ea] dark:from-[#06b6d4] dark:to-blue-500">POS System</h1>
                
                <!-- Status Indicator -->
                <div class="flex items-center gap-2 px-3 py-1 rounded-full text-sm font-medium transition-colors"
                     :class="isOnline ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400' : 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400'">
                    <span class="w-2 h-2 rounded-full" :class="isOnline ? 'bg-green-500' : 'bg-red-500'"></span>
                    {{ isOnline ? 'Online' : 'Offline Mode' }}
                </div>

                <div v-if="user" class="hidden md:flex items-center gap-3 ml-4 border-l pl-4 border-slate-200 dark:border-slate-700">
                    <div class="text-sm">
                        <div class="font-bold text-slate-800 dark:text-slate-200">{{ user.name }}</div>
                        <div class="text-xs text-slate-500 dark:text-slate-400 capitalize">{{ user.role }}</div>
                    </div>
                    <button @click="logout" class="text-sm font-semibold text-red-500 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300 transition-colors">
                        Logout
                    </button>
                    <a v-if="user.role === 'admin'" :href="route('inventory.index')" class="ml-2 px-3 py-1.5 bg-indigo-100 text-indigo-700 dark:bg-indigo-900/50 dark:text-indigo-400 rounded-lg text-xs font-bold hover:bg-indigo-200 transition-colors">
                        Admin Panel
                    </a>
                </div>
            </div>

            <button @click="toggleTheme" class="p-2 rounded-full hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">
                <svg v-if="!isDark" xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-slate-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                </svg>
                <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-[#06b6d4]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
                </svg>
            </button>
        </header>

        <div class="flex-1 flex gap-6 p-4 pt-0 overflow-hidden h-[calc(100vh-6rem)]">
            
            <!-- Left Panel: Products -->
            <div class="flex-1 pos-card p-6 overflow-y-auto flex flex-col h-full relative">
                <div class="mb-6 flex justify-between items-center sticky top-0 bg-white/90 dark:bg-[#1e293b]/90 backdrop-blur-md z-10 pb-4">
                    <h2 class="text-xl font-bold">Products</h2>
                    <input type="text" placeholder="Search..." class="pos-input max-w-xs" />
                </div>
                
                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                    <div v-for="product in products" :key="product.id" 
                         @click="addToCart(product)"
                         class="border border-[#f4f4f5] dark:border-slate-700/50 rounded-xl p-4 cursor-pointer hover:shadow-lg dark:hover:bg-slate-700/30 transition-all active:scale-95 group">
                        <div class="aspect-square bg-slate-50 dark:bg-slate-800/50 rounded-lg mb-3 flex items-center justify-center text-slate-300 dark:text-slate-600 group-hover:bg-indigo-50 dark:group-hover:bg-[#06b6d4]/10 transition-colors">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-indigo-400 dark:text-[#06b6d4]/50 group-hover:text-indigo-600 dark:group-hover:text-[#06b6d4]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                            </svg>
                        </div>
                        <h3 class="font-semibold text-sm truncate" :title="product.name">{{ product.name }}</h3>
                        <div class="flex justify-between items-center mt-2">
                            <span class="text-indigo-600 dark:text-[#06b6d4] font-bold">${{ product.price }}</span>
                            <span class="text-xs pos-muted">Stock: {{ product.stock }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Panel: Cart -->
            <div class="w-96 pos-card flex flex-col p-6 h-full">
                <h2 class="text-xl font-bold mb-6">Current Order</h2>
                
                <div class="flex-1 overflow-y-auto pr-2 space-y-4">
                    <div v-if="cart.length === 0" class="text-center mt-10 pos-muted flex flex-col items-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 mb-2 opacity-50" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                        Cart is empty
                    </div>

                    <div v-for="(item, index) in cart" :key="index" class="flex items-center justify-between border-b border-[#f4f4f5] dark:border-slate-700/50 pb-4">
                        <div class="flex-1">
                            <h4 class="font-semibold text-sm">{{ item.name }}</h4>
                            <div class="text-indigo-600 dark:text-[#06b6d4] font-medium">${{ item.price }}</div>
                        </div>
                        <div class="flex items-center gap-3">
                            <button @click="item.quantity > 1 ? item.quantity-- : removeFromCart(index)" class="w-8 h-8 rounded-full bg-slate-100 dark:bg-slate-800 flex items-center justify-center hover:bg-slate-200 dark:hover:bg-slate-700 transition-colors">
                                <span class="font-bold text-slate-600 dark:text-slate-300">-</span>
                            </button>
                            <span class="w-6 text-center font-medium">{{ item.quantity }}</span>
                            <button @click="item.quantity++" class="w-8 h-8 rounded-full bg-slate-100 dark:bg-slate-800 flex items-center justify-center hover:bg-slate-200 dark:hover:bg-slate-700 transition-colors">
                                <span class="font-bold text-slate-600 dark:text-slate-300">+</span>
                            </button>
                        </div>
                    </div>
                </div>

                <div class="mt-6 pt-6 border-t border-[#f4f4f5] dark:border-slate-700/50">
                    <div class="flex justify-between items-center mb-6">
                        <span class="text-lg font-medium pos-muted">Total</span>
                        <span class="text-3xl font-bold text-indigo-600 dark:text-[#06b6d4]">${{ cartTotal.toFixed(2) }}</span>
                    </div>
                    <button 
                        @click="checkout" 
                        :disabled="cart.length === 0 || isProcessing"
                        class="w-full pos-btn-primary flex justify-center items-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed text-lg">
                        <svg v-if="!isProcessing" xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                        <svg v-else class="animate-spin h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        {{ isProcessing ? 'Processing...' : 'Checkout' }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
