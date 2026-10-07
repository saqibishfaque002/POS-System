<script setup>
import { ref, computed } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import CartItem from './Components/CartItem.vue';
import PaymentModal from './Components/PaymentModal.vue';
import SelectInput from '@/Components/SelectInput.vue';

const props = defineProps({
    foodGroups: Array,
    foodItems: Array,
    orderTypes: Array,
    parties: Array,
    tables: Array,
    taxPercent: Number,
    branchId: Number,
    flash: Object
});

const activeCategory = ref('all');
const cart = ref([]);
const orderTypeId = ref(props.orderTypes.length > 0 ? props.orderTypes[0].id : null);
const partyId = ref(null);
const tableId = ref(null);
const discountType = ref('fixed');
const discountValue = ref(0);
const showPaymentModal = ref(false);

const searchQuery = ref('');

// Computed filtered items
const filteredItems = computed(() => {
    return props.foodItems.filter(item => {
        const matchesCategory = activeCategory.value === 'all' || item.food_group_id === activeCategory.value;
        const matchesSearch = item.name.toLowerCase().includes(searchQuery.value.toLowerCase());
        return matchesCategory && matchesSearch;
    });
});

// Cart Math
const subtotal = computed(() => {
    return cart.value.reduce((total, item) => total + (item.quantity * item.unit_price), 0);
});

const discountAmount = computed(() => {
    const val = parseFloat(discountValue.value) || 0;
    if (discountType.value === 'percentage') {
        return subtotal.value * (val / 100);
    }
    return val;
});

const taxAmount = computed(() => {
    const afterDiscount = Math.max(0, subtotal.value - discountAmount.value);
    return afterDiscount * (props.taxPercent / 100);
});

const grandTotal = computed(() => {
    return Math.max(0, subtotal.value - discountAmount.value + taxAmount.value);
});

// Cart Actions
const addItemToCart = (item) => {
    // Simple variation handling: if it has variations, ideally show a modal.
    // For this initial version, if it has variations, we just pick the first one.
    // A robust version would open a variation selector modal.
    
    let variation = null;
    let price = item.price || 0;
    
    if (item.variations && item.variations.length > 0) {
        variation = item.variations[0];
        price = variation.price;
    }

    const existingIndex = cart.value.findIndex(c => c.food_item_id === item.id && c.variation_id === (variation ? variation.id : null));
    
    if (existingIndex > -1) {
        cart.value[existingIndex].quantity += 1;
    } else {
        cart.value.push({
            food_item_id: item.id,
            variation_id: variation ? variation.id : null,
            name: item.name,
            variation_name: variation ? variation.name : null,
            unit_price: price,
            quantity: 1,
            properties: [] // Add-ons can go here
        });
    }
};

const updateQty = (index, delta) => {
    if (cart.value[index]) {
        cart.value[index].quantity += delta;
        if (cart.value[index].quantity <= 0) {
            cart.value.splice(index, 1);
        }
    }
};

const removeFromCart = (index) => {
    cart.value.splice(index, 1);
};

// Checkout
const processing = ref(false);

const handleCheckout = (paymentDetails) => {
    processing.value = true;
    showPaymentModal.value = false;
    
    const payload = {
        order_type_id: orderTypeId.value,
        party_id: partyId.value,
        table_id: tableId.value,
        subtotal: subtotal.value,
        discount_type: discountType.value,
        discount_value: parseFloat(discountValue.value) || 0,
        tax_amount: taxAmount.value,
        grand_total: grandTotal.value,
        payment_status: 'paid',
        status: 'completed',
        payment_method: paymentDetails.method,
        items: cart.value.map(c => ({
            food_item_id: c.food_item_id,
            variation_id: c.variation_id,
            quantity: c.quantity,
            unit_price: c.unit_price,
            total_price: c.quantity * c.unit_price
        }))
    };
    
    router.post(route('pos.orders.store'), payload, {
        onSuccess: () => {
            cart.value = [];
            discountValue.value = 0;
            processing.value = false;
        },
        onError: () => {
            processing.value = false;
        }
    });
};

const isDineIn = computed(() => {
    const ot = props.orderTypes.find(t => t.id === orderTypeId.value);
    return ot && ot.name.toLowerCase().includes('dine');
});
</script>

<template>
    <Head title="POS Terminal" />
    
    <!-- Animated Background -->
    <div class="h-screen flex overflow-hidden bg-slate-50 dark:bg-slate-950 font-sans relative selection:bg-indigo-500/30">
        
        <!-- Decorative Background Gradients (Glassmorphism) -->
        <div class="absolute top-0 left-0 w-full h-full overflow-hidden pointer-events-none z-0">
            <div class="absolute -top-[20%] -left-[10%] w-[50%] h-[50%] rounded-full bg-indigo-500/10 blur-[120px]"></div>
            <div class="absolute top-[60%] -right-[10%] w-[40%] h-[60%] rounded-full bg-cyan-500/10 blur-[100px]"></div>
        </div>

        <!-- Flash Messages -->
        <transition enter-active-class="transition ease-out duration-300 transform" enter-from-class="-translate-y-10 opacity-0" enter-to-class="translate-y-0 opacity-100" leave-active-class="transition ease-in duration-200 transform" leave-from-class="translate-y-0 opacity-100" leave-to-class="-translate-y-10 opacity-0">
            <div v-if="flash && flash.success" class="fixed top-6 left-1/2 -translate-x-1/2 z-50 bg-slate-900/90 backdrop-blur-md text-green-400 border border-green-500/30 px-6 py-3 rounded-full shadow-2xl shadow-green-500/20 font-medium flex items-center gap-3">
                <div class="bg-green-500/20 p-1 rounded-full">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-green-400" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" /></svg>
                </div>
                {{ flash.success }}
            </div>
        </transition>
        
        <!-- LEFT PANEL: Catalog -->
        <div class="flex-1 flex flex-col min-w-0 z-10">
            <!-- Header Bar -->
            <div class="h-20 px-8 flex items-center justify-between shrink-0 bg-white/40 dark:bg-slate-900/40 backdrop-blur-xl border-b border-white/50 dark:border-slate-800/50 shadow-sm relative z-20">
                <div class="flex items-center gap-4">
                    <div class="bg-gradient-to-br from-indigo-500 to-cyan-500 text-white p-2.5 rounded-xl shadow-lg shadow-indigo-500/30 transform hover:scale-105 transition-transform duration-300">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" /></svg>
                    </div>
                    <div>
                        <h1 class="text-2xl font-black bg-clip-text text-transparent bg-gradient-to-r from-slate-800 to-slate-500 dark:from-white dark:to-slate-300 tracking-tight">Point of Sale</h1>
                        <p class="text-xs text-slate-500 dark:text-slate-400 font-medium">Next-gen checkout</p>
                    </div>
                </div>
                <div class="flex items-center gap-5">
                    <div class="relative w-80 group">
                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none transition-transform group-focus-within:scale-110">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                        </div>
                        <input type="text" v-model="searchQuery" placeholder="Search delicious food..." class="w-full pl-12 pr-4 py-3 bg-white/60 dark:bg-slate-800/60 backdrop-blur-md border border-white/80 dark:border-slate-700/80 rounded-2xl text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:text-white shadow-inner transition-all duration-300 hover:bg-white dark:hover:bg-slate-800 outline-none">
                    </div>
                    <a :href="route('dashboard')" class="p-3 bg-white/60 dark:bg-slate-800/60 backdrop-blur-md border border-white/80 dark:border-slate-700/80 text-slate-500 hover:text-indigo-600 dark:hover:text-indigo-400 rounded-2xl shadow-sm hover:shadow-md transition-all duration-300 hover:-translate-y-0.5 active:translate-y-0">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M10 19l-7-7m0 0l7-7m-7 7h18" /></svg>
                    </a>
                </div>
            </div>

            <!-- Categories (Pills) -->
            <div class="px-8 py-5 overflow-x-auto shrink-0 no-scrollbar flex gap-3 relative z-10">
                <button 
                    @click="activeCategory = 'all'" 
                    class="px-6 py-2.5 rounded-xl font-bold text-sm transition-all duration-300 whitespace-nowrap border"
                    :class="activeCategory === 'all' ? 'bg-indigo-600 text-white border-indigo-500 shadow-lg shadow-indigo-500/40 scale-105' : 'bg-white/70 dark:bg-slate-800/70 backdrop-blur-md text-slate-600 dark:text-slate-300 border-white/50 dark:border-slate-700/50 hover:bg-white dark:hover:bg-slate-800 hover:shadow-md hover:-translate-y-0.5'"
                >
                    All Items
                </button>
                <button 
                    v-for="group in foodGroups" 
                    :key="group.id"
                    @click="activeCategory = group.id" 
                    class="px-6 py-2.5 rounded-xl font-bold text-sm transition-all duration-300 whitespace-nowrap border"
                    :class="activeCategory === group.id ? 'bg-indigo-600 text-white border-indigo-500 shadow-lg shadow-indigo-500/40 scale-105' : 'bg-white/70 dark:bg-slate-800/70 backdrop-blur-md text-slate-600 dark:text-slate-300 border-white/50 dark:border-slate-700/50 hover:bg-white dark:hover:bg-slate-800 hover:shadow-md hover:-translate-y-0.5'"
                >
                    {{ group.name }}
                </button>
            </div>

            <!-- Items Grid -->
            <div class="flex-1 px-8 pb-8 overflow-y-auto custom-scrollbar relative z-10">
                
                <transition-group name="grid-anim" tag="div" class="grid grid-cols-3 xl:grid-cols-4 2xl:grid-cols-5 gap-6">
                    <button 
                        v-for="item in filteredItems" 
                        :key="item.id"
                        @click="addItemToCart(item)"
                        class="group bg-white/80 dark:bg-slate-800/80 backdrop-blur-xl border border-white/50 dark:border-slate-700/50 rounded-2xl p-4 flex flex-col text-left transition-all duration-300 hover:shadow-2xl hover:shadow-indigo-500/20 hover:-translate-y-2 hover:border-indigo-400/50 dark:hover:border-indigo-500/50 relative overflow-hidden focus:outline-none"
                    >
                        <!-- Sparkle hover effect -->
                        <div class="absolute inset-0 bg-gradient-to-tr from-transparent via-white/40 dark:via-white/5 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-700 pointer-events-none transform -translate-x-full group-hover:translate-x-full"></div>
                        
                        <!-- Item Image Placeholder -->
                        <div class="w-full aspect-square bg-slate-100/50 dark:bg-slate-700/50 rounded-xl mb-4 flex items-center justify-center overflow-hidden transition-transform duration-500 group-hover:scale-105 shadow-inner">
                            <svg v-if="!item.image" xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-slate-300 dark:text-slate-500 transition-transform duration-500 group-hover:rotate-6 group-hover:scale-110" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                        </div>
                        
                        <h3 class="font-black text-slate-800 dark:text-white line-clamp-2 leading-snug flex-1 group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition-colors">{{ item.name }}</h3>
                        
                        <div class="mt-3 flex items-center justify-between w-full">
                            <div class="text-indigo-600 dark:text-cyan-400 font-black text-xl tracking-tight">
                                ${{ parseFloat(item.price || (item.variations?.length ? item.variations[0].price : 0)).toFixed(2) }}
                            </div>
                            <div class="bg-indigo-100 dark:bg-indigo-500/20 text-indigo-600 dark:text-indigo-400 p-1.5 rounded-lg opacity-0 group-hover:opacity-100 transition-all duration-300 transform translate-y-2 group-hover:translate-y-0">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd" /></svg>
                            </div>
                        </div>

                        <div v-if="item.variations?.length > 0" class="absolute top-3 right-3 bg-white/90 dark:bg-slate-800/90 backdrop-blur-md shadow-sm border border-slate-200 dark:border-slate-700 text-indigo-600 dark:text-cyan-400 text-[10px] uppercase tracking-wider font-black px-2 py-1 rounded-md">
                            Opts
                        </div>
                    </button>
                </transition-group>

                <!-- Empty State -->
                <div v-if="filteredItems.length === 0" class="flex flex-col items-center justify-center h-full opacity-60">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-24 w-24 text-slate-300 dark:text-slate-600 mb-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z" /></svg>
                    <p class="text-xl font-bold text-slate-500 dark:text-slate-400">No items found</p>
                </div>
            </div>
        </div>
        
        <!-- RIGHT PANEL: Cart & Checkout -->
        <div class="w-96 lg:w-[420px] flex flex-col bg-white/80 dark:bg-slate-900/80 backdrop-blur-2xl border-l border-white/50 dark:border-slate-700/50 shrink-0 shadow-2xl relative z-20">
            <!-- Order Meta Data -->
            <div class="p-6 border-b border-slate-200/50 dark:border-slate-800/50 space-y-4 shrink-0 bg-slate-50/50 dark:bg-slate-900/50">
                <SelectInput 
                    v-model="orderTypeId" 
                    :options="orderTypes" 
                    placeholder="Order Type" 
                    valueKey="id" 
                    labelKey="name"
                />
                
                <SelectInput 
                    v-model="partyId" 
                    :options="parties" 
                    placeholder="Select Customer (Optional)" 
                    valueKey="id" 
                    labelKey="name"
                    searchPlaceholder="Search customers..."
                />
                
                <transition enter-active-class="transition duration-300 ease-out" enter-from-class="opacity-0 -translate-y-2" enter-to-class="opacity-100 translate-y-0" leave-active-class="transition duration-200 ease-in" leave-from-class="opacity-100 translate-y-0" leave-to-class="opacity-0 -translate-y-2">
                    <SelectInput 
                        v-if="isDineIn"
                        v-model="tableId" 
                        :options="tables" 
                        placeholder="Select Table" 
                        valueKey="id" 
                        labelKey="name"
                    />
                </transition>
            </div>
            
            <!-- Cart Items List -->
            <div class="flex-1 overflow-y-auto custom-scrollbar relative px-2">
                <transition enter-active-class="transition duration-500 ease-out" enter-from-class="opacity-0 scale-95" enter-to-class="opacity-100 scale-100" leave-active-class="transition duration-300 ease-in" leave-from-class="opacity-100 scale-100" leave-to-class="opacity-0 scale-95">
                    <div v-if="cart.length === 0" class="absolute inset-0 flex flex-col items-center justify-center text-slate-400 p-8 text-center">
                        <div class="bg-indigo-50 dark:bg-slate-800 p-6 rounded-full mb-6 shadow-inner">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 text-indigo-200 dark:text-indigo-900" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" /></svg>
                        </div>
                        <p class="text-xl font-bold text-slate-600 dark:text-slate-300">Your cart is hungry</p>
                        <p class="text-sm mt-2 text-slate-400">Select delicious items from the menu to build the order.</p>
                    </div>
                </transition>

                <div class="pt-4 pb-4 h-full">
                    <transition-group name="cart-list" tag="div" class="space-y-2 px-4">
                        <CartItem 
                            v-for="(item, index) in cart" 
                            :key="index"
                            :item="item"
                            :index="index"
                            @update-qty="updateQty"
                            @remove="removeFromCart"
                            class="bg-white dark:bg-slate-800 border border-slate-100 dark:border-slate-700 shadow-sm rounded-xl"
                        />
                    </transition-group>
                </div>
            </div>
            
            <!-- Financials Summary -->
            <div class="p-6 border-t border-slate-200/50 dark:border-slate-700/50 bg-white dark:bg-slate-900 shrink-0 shadow-[0_-10px_40px_-15px_rgba(0,0,0,0.1)] z-10 relative">
                <div class="space-y-3 mb-6 text-sm">
                    <div class="flex justify-between items-center text-slate-500 dark:text-slate-400 font-medium">
                        <span>Subtotal</span>
                        <span class="text-slate-800 dark:text-slate-200">${{ subtotal.toFixed(2) }}</span>
                    </div>
                    <div class="flex justify-between items-center text-slate-500 dark:text-slate-400 font-medium">
                        <span>Discount</span>
                        <div class="flex items-center gap-2 bg-slate-50 dark:bg-slate-800 p-1 rounded-lg border border-slate-200 dark:border-slate-700">
                            <select v-model="discountType" class="text-xs font-bold bg-transparent border-none focus:ring-0 text-slate-600 dark:text-slate-300 py-1 pl-2 pr-6 cursor-pointer">
                                <option value="fixed">$</option>
                                <option value="percentage">%</option>
                            </select>
                            <input type="number" v-model="discountValue" class="w-16 text-right text-xs font-bold bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-md py-1 px-2 focus:ring-2 focus:ring-indigo-500 text-slate-800 dark:text-white" min="0">
                        </div>
                    </div>
                    <div class="flex justify-between items-center text-slate-500 dark:text-slate-400 font-medium">
                        <span>Tax ({{ taxPercent }}%)</span>
                        <span class="text-slate-800 dark:text-slate-200">${{ taxAmount.toFixed(2) }}</span>
                    </div>
                </div>
                
                <div class="flex justify-between items-end border-t border-slate-100 dark:border-slate-800 pt-5 mb-6">
                    <div>
                        <span class="block text-sm text-slate-500 dark:text-slate-400 font-medium mb-1">Total to Pay</span>
                        <span class="text-4xl font-black bg-clip-text text-transparent bg-gradient-to-r from-indigo-600 to-cyan-500 dark:from-indigo-400 dark:to-cyan-400 tabular-nums tracking-tight">${{ grandTotal.toFixed(2) }}</span>
                    </div>
                </div>
                
                <div class="grid grid-cols-2 gap-4">
                    <button class="py-4 rounded-xl font-bold text-slate-600 dark:text-slate-300 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 transition-all duration-300 active:scale-95 shadow-inner">
                        Hold Order
                    </button>
                    <button 
                        @click="showPaymentModal = true"
                        :disabled="cart.length === 0 || processing"
                        class="relative overflow-hidden py-4 rounded-xl font-black text-white transition-all duration-300 shadow-xl group disabled:opacity-50 disabled:cursor-not-allowed disabled:transform-none disabled:shadow-none active:scale-95"
                        :class="cart.length === 0 ? 'bg-slate-400' : 'bg-gradient-to-r from-indigo-600 to-indigo-500 hover:shadow-indigo-500/40 hover:-translate-y-1'"
                    >
                        <span class="relative z-10 flex items-center justify-center gap-2">
                            PAY NOW
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 transform group-hover:translate-x-1 transition-transform" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M12.293 5.293a1 1 0 011.414 0l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-2.293-2.293a1 1 0 010-1.414z" clip-rule="evenodd" /></svg>
                        </span>
                        <!-- Shimmer effect -->
                        <div v-if="cart.length > 0" class="absolute inset-0 -translate-x-full bg-gradient-to-r from-transparent via-white/20 to-transparent group-hover:animate-shimmer"></div>
                    </button>
                </div>
            </div>
        </div>
        
        <PaymentModal 
            :show="showPaymentModal"
            :totalAmount="grandTotal"
            @close="showPaymentModal = false"
            @confirm="handleCheckout"
        />
        
    </div>
</template>

<style>
/* CSS Animations */
.grid-anim-enter-active,
.grid-anim-leave-active {
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
}
.grid-anim-enter-from,
.grid-anim-leave-to {
    opacity: 0;
    transform: translateY(20px) scale(0.95);
}

.cart-list-enter-active,
.cart-list-leave-active {
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}
.cart-list-enter-from {
    opacity: 0;
    transform: translateX(30px);
}
.cart-list-leave-to {
    opacity: 0;
    transform: translateX(30px);
}
.cart-list-leave-active {
    position: absolute;
}

@keyframes shimmer {
    100% {
        transform: translateX(100%);
    }
}
.animate-shimmer {
    animation: shimmer 1.5s infinite;
}
</style>
