<script setup>
const props = defineProps({
    isOpen: Boolean,
    order: Object,
    type: {
        type: String,
        default: 'invoice' // 'invoice' or 'kot'
    },
});

const emit = defineEmits(['close']);

const printReceipt = () => {
    window.print();
};

const formatPrice = (val) => {
    return Number(val || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
};

const formatDate = (dateString) => {
    if (!dateString) return new Date().toLocaleString();
    return new Date(dateString).toLocaleString();
};
</script>

<template>
    <div v-if="isOpen && order" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs select-none">
        <div class="bg-white dark:bg-slate-900 w-full max-w-md rounded-2xl shadow-2xl border border-slate-200 dark:border-slate-800 overflow-hidden flex flex-col max-h-[90vh] animate-in fade-in zoom-in-95 duration-150">
            <!-- Modal Header (Hidden on Print) -->
            <div class="px-5 py-3 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between bg-slate-50 dark:bg-slate-800/40 print:hidden">
                <span class="text-xs font-black uppercase tracking-wider text-slate-700 dark:text-slate-300">
                    {{ type === 'kot' ? 'Kitchen Order Ticket (KOT)' : 'Customer Bill Receipt' }}
                </span>
                <div class="flex items-center space-x-2">
                    <button
                        type="button"
                        @click="printReceipt"
                        class="py-1 px-3 bg-indigo-600 text-white rounded-lg text-xs font-bold shadow-xs hover:bg-indigo-700 transition-colors flex items-center space-x-1"
                    >
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                        </svg>
                        <span>Print</span>
                    </button>
                    <button
                        type="button"
                        @click="emit('close')"
                        class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 p-1 text-xs"
                    >
                        ✕
                    </button>
                </div>
            </div>

            <!-- Receipt Body (Target for 80mm thermal printing) -->
            <div class="p-6 overflow-y-auto flex-1 bg-white text-black font-mono text-xs printable-receipt">
                <!-- ================= KOT FORMAT ================= -->
                <div v-if="type === 'kot'" class="space-y-3 text-center">
                    <div class="border-b-2 border-black pb-2">
                        <h2 class="text-xl font-black uppercase tracking-wider">KITCHEN TICKET</h2>
                        <p class="text-sm font-bold mt-1">{{ order.kot_number || 'KOT' }}</p>
                    </div>

                    <div class="text-left text-xs font-bold border-b border-dashed border-black pb-2 space-y-1">
                        <div class="flex justify-between">
                            <span>Token:</span>
                            <span class="text-base font-black">{{ order.token_number || order.order_number }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span>Type:</span>
                            <span>{{ order.order_type?.name }}</span>
                        </div>
                        <div v-if="order.party" class="flex justify-between">
                            <span>Party / Platform:</span>
                            <span class="font-black">{{ order.party.name }}</span>
                        </div>
                        <div v-if="order.table" class="flex justify-between">
                            <span>Table:</span>
                            <span class="text-sm font-black">{{ order.table.name }}</span>
                        </div>
                        <div v-if="order.customer" class="flex justify-between">
                            <span>Customer:</span>
                            <span>{{ order.customer.name }}</span>
                        </div>
                        <div v-if="order.rider_name" class="flex justify-between">
                            <span>Rider:</span>
                            <span>{{ order.rider_name }}</span>
                        </div>
                        <div class="flex justify-between text-[11px] text-gray-700">
                            <span>Date/Time:</span>
                            <span>{{ formatDate(order.kot_printed_at || order.created_at) }}</span>
                        </div>
                    </div>

                    <!-- Items list -->
                    <table class="w-full text-left border-b-2 border-black pb-2">
                        <thead>
                            <tr class="border-b border-black">
                                <th class="py-1 w-10">Qty</th>
                                <th class="py-1">Item Description</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="item in order.items" :key="item.id" class="border-b border-gray-300">
                                <td class="py-1.5 align-top font-black text-sm">{{ item.quantity }}</td>
                                <td class="py-1.5 align-top">
                                    <div class="font-bold text-xs">{{ item.food_item?.name }}</div>
                                    <div v-if="item.variation" class="text-[11px] font-semibold text-gray-700">
                                        [{{ item.variation.name }}]
                                    </div>
                                    <div v-if="item.properties && item.properties.length > 0" class="text-[10px] text-gray-600">
                                        <span v-for="p in item.properties" :key="p.id" class="mr-1">
                                            + {{ p.property_name }}
                                        </span>
                                    </div>
                                    <div v-if="item.notes" class="text-[10px] font-bold italic text-black mt-0.5">
                                        NOTE: {{ item.notes }}
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <div v-if="order.notes" class="text-left text-xs font-bold border border-black p-2">
                        ORDER NOTE: {{ order.notes }}
                    </div>
                </div>

                <!-- ================= CUSTOMER INVOICE FORMAT ================= -->
                <div v-else class="space-y-3 text-center">
                    <!-- Restaurant Header -->
                    <div class="border-b-2 border-black pb-2 space-y-0.5">
                        <h2 class="text-lg font-black uppercase">POINT OF SALE</h2>
                        <p class="text-xs font-bold">{{ order.branch?.name || 'Main Branch' }}</p>
                        <p v-if="order.branch?.address" class="text-[10px] text-gray-700">{{ order.branch.address }}</p>
                        <p v-if="order.branch?.phone" class="text-[10px] text-gray-700">Tel: {{ order.branch.phone }}</p>
                    </div>

                    <!-- Order Meta Info -->
                    <div class="text-left text-[11px] border-b border-dashed border-black pb-2 space-y-0.5">
                        <div class="flex justify-between font-bold">
                            <span>Receipt #:</span>
                            <span>{{ order.order_number }}</span>
                        </div>
                        <div v-if="order.token_number" class="flex justify-between font-black text-sm">
                            <span>Token #:</span>
                            <span>{{ order.token_number }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span>Date:</span>
                            <span>{{ formatDate(order.created_at) }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span>Type:</span>
                            <span>{{ order.order_type?.name }}</span>
                        </div>
                        <div v-if="order.party" class="flex justify-between font-bold">
                            <span>Party / Platform:</span>
                            <span>{{ order.party.name }}</span>
                        </div>
                        <div v-if="order.table" class="flex justify-between font-bold">
                            <span>Table:</span>
                            <span>{{ order.table.name }}</span>
                        </div>
                        <div v-if="order.customer" class="flex justify-between">
                            <span>Customer:</span>
                            <span>{{ order.customer.name }} ({{ order.customer.phone }})</span>
                        </div>
                        <div v-if="order.rider_name" class="flex justify-between">
                            <span>Rider / Courier:</span>
                            <span>{{ order.rider_name }}</span>
                        </div>
                        <div v-if="order.delivery_address" class="flex justify-between text-[11px]">
                            <span>Delivery Address:</span>
                            <span class="text-right max-w-[200px]">{{ order.delivery_address }}</span>
                        </div>
                    </div>

                    <!-- Items Table -->
                    <table class="w-full text-left text-xs border-b border-black pb-2">
                        <thead>
                            <tr class="border-b border-black font-bold">
                                <th class="py-1">Item</th>
                                <th class="py-1 text-center w-8">Qty</th>
                                <th class="py-1 text-right w-14">Price</th>
                                <th class="py-1 text-right w-16">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="item in order.items" :key="item.id" class="border-b border-gray-200">
                                <td class="py-1">
                                    <div class="font-bold">{{ item.food_item?.name }}</div>
                                    <div v-if="item.variation" class="text-[10px] text-gray-700">
                                        [{{ item.variation.name }}]
                                    </div>
                                    <div v-if="item.properties && item.properties.length > 0" class="text-[9px] text-gray-600">
                                        <span v-for="p in item.properties" :key="p.id">
                                            + {{ p.property_name }}
                                        </span>
                                    </div>
                                </td>
                                <td class="py-1 text-center font-bold">{{ item.quantity }}</td>
                                <td class="py-1 text-right">{{ formatPrice(item.unit_price) }}</td>
                                <td class="py-1 text-right font-bold">{{ formatPrice(item.total_price) }}</td>
                            </tr>
                        </tbody>
                    </table>

                    <!-- Financial Summary -->
                    <div class="text-right text-xs space-y-1 border-b-2 border-black pb-2 font-mono">
                        <div class="flex justify-between">
                            <span>Subtotal:</span>
                            <span>Rs. {{ formatPrice(order.subtotal) }}</span>
                        </div>
                        <div v-if="order.discount_value > 0" class="flex justify-between font-bold">
                            <span>Discount:</span>
                            <span>- Rs. {{ formatPrice(order.discount_value) }}</span>
                        </div>
                        <div v-if="order.service_charges > 0" class="flex justify-between">
                            <span>Service Charges:</span>
                            <span>+ Rs. {{ formatPrice(order.service_charges) }}</span>
                        </div>
                        <div v-if="order.tax_amount > 0" class="flex justify-between">
                            <span>Sales Tax:</span>
                            <span>+ Rs. {{ formatPrice(order.tax_amount) }}</span>
                        </div>
                        <div v-if="order.delivery_charges > 0" class="flex justify-between">
                            <span>Delivery Charges:</span>
                            <span>+ Rs. {{ formatPrice(order.delivery_charges) }}</span>
                        </div>
                        <div class="flex justify-between font-black text-sm pt-1 border-t border-dashed border-black">
                            <span>NET PAYABLE:</span>
                            <span>Rs. {{ formatPrice(order.grand_total) }}</span>
                        </div>
                    </div>

                    <!-- Payment Information -->
                    <div v-if="order.payments && order.payments.length > 0" class="text-left text-[11px] border-b border-black pb-2 space-y-0.5">
                        <div v-for="pay in order.payments" :key="pay.id" class="flex justify-between">
                            <span>Paid via {{ pay.payment_type?.name }}:</span>
                            <span class="font-bold">Rs. {{ formatPrice(pay.amount) }}</span>
                        </div>
                        <div v-if="order.tendered_amount > 0" class="flex justify-between text-gray-700">
                            <span>Tendered:</span>
                            <span>Rs. {{ formatPrice(order.tendered_amount) }}</span>
                        </div>
                        <div v-if="order.change_amount > 0" class="flex justify-between font-bold text-black">
                            <span>Change Return:</span>
                            <span>Rs. {{ formatPrice(order.change_amount) }}</span>
                        </div>
                    </div>

                    <!-- Footer message -->
                    <div class="pt-2 text-center text-[10px] space-y-0.5">
                        <p class="font-bold">Thank You For Your Business!</p>
                        <p class="text-gray-600">Software by PointOfSale</p>
                    </div>
                </div>
            </div>

            <!-- Modal Footer (Hidden on print) -->
            <div class="px-5 py-3 border-t border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 flex items-center justify-between print:hidden">
                <button
                    type="button"
                    @click="emit('close')"
                    class="px-4 py-1.5 text-xs font-bold text-slate-600 dark:text-slate-400 hover:text-slate-800"
                >
                    Close
                </button>
                <button
                    type="button"
                    @click="printReceipt"
                    class="px-5 py-1.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-black text-xs shadow-md transition-all flex items-center space-x-1.5"
                >
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                    </svg>
                    <span>Print Receipt</span>
                </button>
            </div>
        </div>
    </div>
</template>

<style scoped>
@media print {
    body * {
        visibility: hidden;
    }
    .printable-receipt, .printable-receipt * {
        visibility: visible;
    }
    .printable-receipt {
        position: absolute;
        left: 0;
        top: 0;
        width: 80mm;
        margin: 0;
        padding: 5mm;
    }
}
</style>
