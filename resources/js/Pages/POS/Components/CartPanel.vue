<script setup>
import { computed } from 'vue';

const props = defineProps({
    cart: {
        type: Array,
        default: () => []
    },
    orderType: Object,
    selectedTable: Object,
    selectedCustomer: Object,
    selectedParty: Object,
    selectedDiscount: Object,
    tokenNumber: String,
    taxPercentage: {
        type: Number,
        default: 0
    },
    deliveryCharges: {
        type: Number,
        default: 0
    },
    riderName: {
        type: String,
        default: ''
    },
    deliveryAddress: {
        type: String,
        default: ''
    },
    subtotal: {
        type: Number,
        default: 0
    },
    discountAmount: {
        type: Number,
        default: 0
    },
    serviceChargeAmount: {
        type: Number,
        default: 0
    },
    serviceChargeLabel: {
        type: String,
        default: ''
    },
    taxAmount: {
        type: Number,
        default: 0
    },
    grandTotal: {
        type: Number,
        default: 0
    },
    editingOrderId: [Number, String],
    submitting: {
        type: Boolean,
        default: false
    },
    businessDate: String,
    businessDay: String,
    businessDateFormatted: String,
});

const emit = defineEmits([
    'updateQuantity',
    'removeItem',
    'clearCart',
    'openPaymentModal',
    'sendKot',
    'holdOrder',
    'openFocModal',
    'openDiscountModal',
    'removeDiscount',
    'update:deliveryCharges',
    'update:riderName',
    'update:deliveryAddress',
]);

const formatPrice = (val) => {
    return Number(val || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
};

const isDineIn = computed(() => {
    return props.orderType?.name?.toLowerCase().includes('dine') || props.orderType?.code === 'dine_in';
});

const isDelivery = computed(() => {
    return props.orderType?.name?.toLowerCase().includes('delivery') || props.orderType?.code === 'delivery';
});
</script>

<template>
    <div class="w-80 md:w-96 lg:w-[420px] bg-white dark:bg-slate-900 border-l border-slate-200 dark:border-slate-800 flex flex-col h-full shrink-0 select-none shadow-lg z-10">
        <!-- Cart Header: Order Info & Business Date -->
        <div class="p-3.5 border-b border-slate-200 dark:border-slate-800 bg-slate-50/70 dark:bg-slate-800/50 flex flex-col gap-2 shrink-0">
            <div class="flex items-center justify-between">
                <div>
                    <div class="flex items-center space-x-2">
                        <span class="text-xs font-black text-slate-900 dark:text-white uppercase tracking-wider">
                            {{ editingOrderId ? `Order #${editingOrderId}` : 'Current Order' }}
                        </span>
                        <span v-if="tokenNumber" class="text-[10px] font-black px-2 py-0.5 rounded-full bg-indigo-100 dark:bg-cyan-950 text-indigo-700 dark:text-cyan-400 border border-indigo-200 dark:border-cyan-800">
                            {{ tokenNumber }}
                        </span>
                    </div>
                    <div class="flex flex-wrap items-center gap-1.5 mt-1 text-[11px] text-slate-500 dark:text-slate-400">
                        <span class="font-bold text-slate-700 dark:text-slate-300">
                            {{ orderType?.name || 'Order' }}
                        </span>
                        <span v-if="selectedParty" class="font-bold text-indigo-600 dark:text-cyan-400 bg-indigo-50 dark:bg-cyan-950/60 px-1.5 py-0.2 rounded border border-indigo-200 dark:border-cyan-800">
                            • {{ selectedParty.name }}
                        </span>
                        <span v-if="isDineIn && selectedTable" class="text-amber-600 dark:text-amber-400 font-bold">
                            • Table: {{ selectedTable.name }}
                        </span>
                        <span v-if="selectedCustomer" class="text-emerald-600 dark:text-emerald-400 font-bold truncate max-w-[120px]">
                            • {{ selectedCustomer.name }}
                        </span>
                    </div>
                </div>

                <!-- Clear Cart Button -->
                <button
                    v-if="cart.length > 0"
                    type="button"
                    @click="emit('clearCart')"
                    class="text-xs text-rose-500 hover:text-rose-700 dark:hover:text-rose-400 font-semibold flex items-center space-x-1 p-1 hover:bg-rose-50 dark:hover:bg-rose-950/40 rounded-lg transition-colors"
                    title="Clear Cart"
                >
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                    <span>Clear</span>
                </button>
            </div>

            <!-- Business Date Indicator in Cart Header -->
            <div v-if="businessDate" class="flex items-center justify-between px-2.5 py-1.5 rounded-lg bg-indigo-50/70 dark:bg-cyan-950/40 border border-indigo-200/70 dark:border-cyan-800/60 text-slate-700 dark:text-slate-300">
                <div class="flex items-center space-x-1.5">
                    <svg class="w-3.5 h-3.5 text-indigo-600 dark:text-cyan-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    <span class="text-[10px] font-bold uppercase tracking-wider text-indigo-900/80 dark:text-cyan-300/80">Business Date:</span>
                </div>
                <span class="text-[11px] font-black text-indigo-950 dark:text-cyan-200">
                    {{ businessDay }}, {{ businessDateFormatted || businessDate }}
                </span>
            </div>
        </div>

        <!-- Editing Order Active Banner -->
        <div v-if="editingOrderId" class="px-3.5 py-2 bg-amber-50 dark:bg-amber-950/60 border-b border-amber-200 dark:border-amber-800 flex items-center justify-between text-xs text-amber-800 dark:text-amber-300 shrink-0">
            <span class="font-bold flex items-center space-x-1.5">
                <svg class="w-4 h-4 text-amber-600 dark:text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                </svg>
                <span>Editing Order #{{ editingOrderId }}</span>
            </span>
            <button
                type="button"
                @click="emit('clearCart')"
                class="font-black text-rose-600 dark:text-rose-400 hover:underline text-[11px] px-2 py-0.5 rounded hover:bg-rose-100/50"
            >
                Cancel Edit ✕
            </button>
        </div>

        <!-- Cart Items List (Scrollable) -->
        <div class="flex-1 overflow-y-auto p-3 space-y-2">
            <!-- Empty Cart -->
            <div v-if="cart.length === 0" class="h-full flex flex-col items-center justify-center text-slate-400 text-center px-4">
                <div class="w-14 h-14 rounded-2xl bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-slate-300 dark:text-slate-600 mb-3">
                    <svg class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                    </svg>
                </div>
                <p class="text-xs font-bold text-slate-600 dark:text-slate-300">Your cart is empty</p>
                <p class="text-[11px] text-slate-400 mt-0.5">Click on menu items to begin adding items</p>
            </div>

            <!-- Item Rows -->
            <div
                v-for="(item, index) in cart"
                :key="index"
                class="bg-slate-50 dark:bg-slate-800/60 border border-slate-200/80 dark:border-slate-800 rounded-xl p-2.5 transition-all"
            >
                <!-- Item Title & Remove Button -->
                <div class="flex items-start justify-between">
                    <div>
                        <h4 class="text-xs font-black text-slate-800 dark:text-slate-100 leading-snug">
                            {{ item.name }}
                        </h4>
                        <!-- Variation Pill -->
                        <span v-if="item.variation_name" class="inline-block mt-0.5 text-[10px] font-bold text-indigo-600 dark:text-cyan-400 bg-indigo-50 dark:bg-cyan-950/60 px-1.5 py-0.2 rounded">
                            {{ item.variation_name }}
                        </span>
                    </div>

                    <!-- Remove -->
                    <button
                        type="button"
                        @click="emit('removeItem', index)"
                        class="text-slate-400 hover:text-rose-500 dark:hover:text-rose-400 p-0.5 text-xs transition-colors"
                        title="Remove Item"
                    >
                        ✕
                    </button>
                </div>

                <!-- Addons / Properties Pills -->
                <div v-if="item.properties && item.properties.length > 0" class="mt-1 flex flex-wrap gap-1">
                    <span
                        v-for="(prop, pIndex) in item.properties"
                        :key="pIndex"
                        class="text-[9px] font-semibold text-slate-600 dark:text-slate-300 bg-white dark:bg-slate-700/80 px-1.5 py-0.5 rounded border border-slate-200 dark:border-slate-600"
                    >
                        + {{ prop.property_name }} <span v-if="prop.extra_price > 0">(Rs. {{ formatPrice(prop.extra_price) }})</span>
                    </span>
                </div>

                <!-- Item Note -->
                <p v-if="item.notes" class="mt-1 text-[10px] italic text-amber-600 dark:text-amber-400">
                    Note: {{ item.notes }}
                </p>

                <!-- Quantity & Price Footer -->
                <div class="mt-2.5 flex items-center justify-between pt-1 border-t border-slate-200/50 dark:border-slate-700/50">
                    <!-- Quantity Stepper -->
                    <div class="flex items-center space-x-1.5 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-lg p-0.5">
                        <button
                            type="button"
                            @click="emit('updateQuantity', { index, delta: -1 })"
                            class="w-5 h-5 rounded flex items-center justify-center text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 text-xs font-black transition-colors"
                        >
                            -
                        </button>
                        <span class="w-6 text-center text-xs font-black text-slate-800 dark:text-slate-100">
                            {{ item.quantity }}
                        </span>
                        <button
                            type="button"
                            @click="emit('updateQuantity', { index, delta: 1 })"
                            class="w-5 h-5 rounded flex items-center justify-center text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 text-xs font-black transition-colors"
                        >
                            +
                        </button>
                    </div>

                    <!-- Line Total -->
                    <div class="text-right">
                        <span class="text-[10px] text-slate-400 mr-1">@ Rs. {{ formatPrice(item.unit_price) }}</span>
                        <span class="text-xs font-black text-slate-900 dark:text-white">
                            Rs. {{ formatPrice(item.total_price) }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Financial Summary Breakdown -->
        <div class="p-3.5 bg-slate-50 dark:bg-slate-900 border-t border-slate-200 dark:border-slate-800 space-y-1.5 shrink-0 text-xs">
            <!-- Subtotal -->
            <div class="flex items-center justify-between text-slate-600 dark:text-slate-400">
                <span>Subtotal</span>
                <span class="font-bold text-slate-800 dark:text-slate-200">Rs. {{ formatPrice(subtotal) }}</span>
            </div>

            <!-- Discount & Voucher Row -->
            <div class="flex items-center justify-between text-xs py-0.5">
                <div class="flex items-center space-x-1.5 flex-wrap">
                    <span class="text-slate-600 dark:text-slate-400">Discount</span>
                    <span
                        v-if="selectedDiscount"
                        class="text-[10px] font-bold px-1.5 py-0.5 rounded bg-emerald-100 dark:bg-emerald-950/70 text-emerald-800 dark:text-emerald-300 flex items-center space-x-1"
                    >
                        <span>{{ selectedDiscount.name }}</span>
                        <span v-if="selectedDiscount.code" class="font-mono opacity-80">({{ selectedDiscount.code }})</span>
                        <button
                            type="button"
                            @click="emit('removeDiscount')"
                            class="text-rose-500 hover:text-rose-700 ml-1 font-bold text-xs"
                            title="Remove Discount"
                        >
                            ✕
                        </button>
                    </span>
                </div>
                <div class="flex items-center space-x-2">
                    <span v-if="discountAmount > 0" class="text-emerald-600 dark:text-emerald-400 font-bold">
                        - Rs. {{ formatPrice(discountAmount) }}
                    </span>
                    <button
                        type="button"
                        @click="emit('openDiscountModal')"
                        class="text-[11px] font-bold text-indigo-600 dark:text-cyan-400 hover:underline px-1 py-0.5"
                    >
                        {{ selectedDiscount ? 'Change' : '+ Apply Deal / Voucher' }}
                    </button>
                </div>
            </div>

            <!-- Service Charges (if applicable) -->
            <div v-if="serviceChargeAmount > 0" class="flex items-center justify-between text-slate-600 dark:text-slate-400">
                <span>Service Charges <span v-if="serviceChargeLabel">({{ serviceChargeLabel }})</span></span>
                <span class="font-bold text-slate-800 dark:text-slate-200">+ Rs. {{ formatPrice(serviceChargeAmount) }}</span>
            </div>

            <!-- Tax -->
            <div v-if="taxAmount > 0 || taxPercentage > 0" class="flex items-center justify-between text-slate-600 dark:text-slate-400">
                <span>Sales Tax ({{ taxPercentage }}%)</span>
                <span class="font-bold text-slate-800 dark:text-slate-200">+ Rs. {{ formatPrice(taxAmount) }}</span>
            </div>

            <!-- Delivery Details Box (if delivery) -->
            <div v-if="isDelivery" class="p-2.5 bg-slate-100/80 dark:bg-slate-800/80 rounded-xl border border-slate-200 dark:border-slate-700/80 space-y-2">
                <div class="flex items-center justify-between">
                    <span class="text-[11px] font-black text-slate-700 dark:text-slate-300 uppercase tracking-wider flex items-center gap-1">
                        <svg class="w-3.5 h-3.5 text-indigo-600 dark:text-cyan-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg>
                        Delivery Info
                    </span>
                    <div class="flex items-center space-x-1">
                        <span class="text-[10px] text-slate-500 font-bold">Fee (Rs.):</span>
                        <input
                            type="number"
                            min="0"
                            step="10"
                            :value="deliveryCharges"
                            @input="emit('update:deliveryCharges', Number($event.target.value))"
                            class="w-20 text-right text-xs font-bold bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-lg px-2 py-1 focus:ring-1 focus:ring-indigo-500 text-slate-800 dark:text-slate-100"
                            placeholder="0"
                        />
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-1.5">
                    <input
                        type="text"
                        :value="riderName"
                        @input="emit('update:riderName', $event.target.value)"
                        placeholder="Rider / Courier..."
                        class="text-xs bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-lg px-2 py-1 text-slate-800 dark:text-slate-100 focus:ring-1 focus:ring-indigo-500"
                    />
                    <input
                        type="text"
                        :value="deliveryAddress"
                        @input="emit('update:deliveryAddress', $event.target.value)"
                        placeholder="Delivery address..."
                        class="text-xs bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-lg px-2 py-1 text-slate-800 dark:text-slate-100 focus:ring-1 focus:ring-indigo-500"
                    />
                </div>
            </div>

            <!-- Delivery Charges line in summary -->
            <div v-if="isDelivery && deliveryCharges > 0" class="flex items-center justify-between text-slate-600 dark:text-slate-400">
                <span>Delivery Charges</span>
                <span class="font-bold text-slate-800 dark:text-slate-200">+ Rs. {{ formatPrice(deliveryCharges) }}</span>
            </div>

            <!-- Grand Total -->
            <div class="pt-2 border-t border-slate-200 dark:border-slate-800 flex items-center justify-between">
                <div>
                    <span class="text-xs font-black uppercase text-slate-800 dark:text-slate-200">Payable Total</span>
                    <p class="text-[10px] text-slate-400">Inclusive of taxes & charges</p>
                </div>
                <div class="text-right">
                    <span class="text-lg font-black text-indigo-600 dark:text-cyan-400">
                        <span class="text-xs font-bold mr-0.5">Rs.</span>
                        <span>{{ formatPrice(grandTotal) }}</span>
                    </span>
                </div>
            </div>
        </div>

        <!-- Action Buttons Footer (4 Primary Order Actions) -->
        <div class="p-2.5 bg-white dark:bg-slate-900 border-t border-slate-200 dark:border-slate-800 grid grid-cols-4 gap-1.5 shrink-0">
            <!-- 1. Hold / Park -->
            <button
                type="button"
                @click="emit('holdOrder')"
                :disabled="cart.length === 0 || submitting"
                class="py-2.5 px-1 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 font-bold rounded-xl text-xs flex flex-col items-center justify-center transition-all disabled:opacity-40 disabled:cursor-not-allowed"
                title="Hold / Park Order (Alt+H / F1)"
            >
                <svg class="w-4 h-4 mb-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span>Hold</span>
            </button>

            <!-- 2. KOT / Submit (Kitchen Order Ticket) -->
            <button
                type="button"
                @click="emit('sendKot')"
                :disabled="cart.length === 0 || submitting"
                class="py-2.5 px-1 bg-amber-500 hover:bg-amber-600 text-white font-bold rounded-xl text-xs flex flex-col items-center justify-center transition-all shadow-sm active:scale-95 disabled:opacity-40 disabled:cursor-not-allowed"
                :title="editingOrderId ? 'Update Order & Resubmit (Alt+K / F3)' : 'Submit Order & Print KOT (Alt+K / F3)'"
            >
                <svg v-if="!submitting" class="w-4 h-4 mb-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                </svg>
                <svg v-else class="w-4 h-4 mb-0.5 animate-spin" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                </svg>
                <span>{{ editingOrderId ? 'Update' : 'Submit' }}</span>
            </button>

            <!-- 3. FOC (Free of Charge / Complimentary) -->
            <button
                type="button"
                @click="emit('openFocModal')"
                :disabled="cart.length === 0 || submitting"
                class="py-2.5 px-1 bg-purple-600 hover:bg-purple-700 text-white font-bold rounded-xl text-xs flex flex-col items-center justify-center transition-all shadow-sm active:scale-95 disabled:opacity-40 disabled:cursor-not-allowed"
                title="Process Free of Charge (Complimentary) Order"
            >
                <svg class="w-4 h-4 mb-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v13m0-13V4a2 2 0 114 0v4H8V4a2 2 0 114 0v4zm0 0H4a2 2 0 00-2 2v3a2 2 0 002 2h16a2 2 0 002-2v-3a2 2 0 00-2-2h-8z" />
                </svg>
                <span>FOC</span>
            </button>

            <!-- 4. Pay / Settle -->
            <button
                type="button"
                @click="emit('openPaymentModal')"
                :disabled="cart.length === 0 || submitting"
                class="py-2.5 px-1 bg-emerald-600 hover:bg-emerald-700 text-white font-black rounded-xl text-xs flex flex-col items-center justify-center transition-all shadow-md active:scale-95 disabled:opacity-40 disabled:cursor-not-allowed"
                title="Settle Payment (Alt+P / F2)"
            >
                <svg class="w-4 h-4 mb-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                </svg>
                <span>Settle</span>
            </button>
        </div>
    </div>
</template>
