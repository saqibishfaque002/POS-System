<script setup>
import { onMounted, ref } from 'vue';
import { Head } from '@inertiajs/vue3';
import html2pdf from 'html2pdf.js';

const props = defineProps({
    document: Object,
    type: String,
    settings: Object,
    company: Object,
});

const isProcessing = ref(true);

const formatDate = (dateString) => {
    if (!dateString) return '';
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
};

onMounted(() => {
    const printMode = props.settings?.print_mode || 'browser';
    
    // Give it a tiny bit of time to render the DOM completely before print/pdf
    setTimeout(() => {
        isProcessing.value = false;
        
        if (printMode === 'pdf') {
            const element = document.getElementById('printable-document');
            const filename = `${props.type}_${props.document.document_number}.pdf`;
            
            html2pdf().set({
                margin: 10,
                filename: filename,
                image: { type: 'jpeg', quality: 0.98 },
                html2canvas: { scale: 2, useCORS: true },
                jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait' }
            }).from(element).save().then(() => {
                // Close tab after download
                setTimeout(() => window.close(), 1000);
            });
        } else {
            // Default: Browser Print
            window.print();
        }
    }, 500);
});
</script>

<template>
    <Head :title="`${type} - ${document.document_number}`" />
    
    <!-- Screen Loading State (Hidden on print) -->
    <div v-if="isProcessing" class="fixed inset-0 flex items-center justify-center bg-white z-50 print:hidden">
        <p class="text-slate-500 font-medium">Preparing document...</p>
    </div>

    <!-- Main Printable Container -->
    <div class="min-h-screen bg-slate-100 print:bg-white flex justify-center py-8 print:py-0">
        <div id="printable-document" class="bg-white w-[210mm] min-h-[297mm] p-8 md:p-12 shadow-lg print:shadow-none print:w-full print:h-auto print:p-0">
            
            <!-- Ultra Modern Header Section -->
            <div class="flex justify-between items-start mb-8 border-b-2 border-slate-800 pb-6">
                <!-- Company Logo & Details -->
                <div class="flex items-center gap-6">
                    <img v-if="settings?.logo" :src="'/storage/' + settings.logo" class="h-24 w-24 object-contain" alt="Logo">
                    <div>
                        <h1 class="text-4xl font-black text-slate-900 tracking-tight">{{ company?.name || 'Company Name' }}</h1>
                        <div class="mt-2 text-sm text-slate-600 font-medium">
                            <p class="text-slate-800 font-bold uppercase tracking-wider mb-1">{{ document.branch?.name }} Branch</p>
                            <p v-if="document.branch?.address">{{ document.branch?.address }}</p>
                            <p v-if="document.branch?.phone">{{ document.branch?.phone }}</p>
                        </div>
                    </div>
                </div>

                <!-- Document Title -->
                <div class="text-right flex flex-col justify-end h-full mt-4">
                    <h2 class="text-4xl font-black text-slate-200 uppercase tracking-widest">
                        {{ type === 'PR' ? 'Purchase Request' : type === 'PO' ? 'Purchase Order' : type === 'GRN' ? 'Goods Received Note' : type === 'PI' ? 'Purchase Invoice' : type === 'PRTN' ? 'Purchase Return' : type === 'SI' ? 'Sale Invoice' : type === 'SRTN' ? 'Sale Return' : 'Document' }}
                    </h2>
                </div>
            </div>

            <!-- Metadata & Vendor Grid -->
            <div class="grid grid-cols-2 gap-12 mb-12">
                <!-- Left Column: Parties -->
                <div class="space-y-8">
                    <!-- PO/GRN/PI Specific: Vendor Info -->
                    <div v-if="['PO', 'GRN', 'PI', 'PRTN'].includes(type)">
                        <h3 class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-3 border-b border-slate-100 pb-2">Vendor Details</h3>
                        <p class="text-xl font-bold text-slate-800">{{ document.supplier?.name || 'N/A' }}</p>
                        <div class="mt-2 text-sm text-slate-500 leading-relaxed">
                            <p v-if="document.supplier?.address">{{ document.supplier?.address }}</p>
                            <p v-if="document.supplier?.phone">Phone: <span class="text-slate-700">{{ document.supplier?.phone }}</span></p>
                            <p v-if="document.supplier?.email">Email: <span class="text-slate-700">{{ document.supplier?.email }}</span></p>
                        </div>
                    </div>

                    <!-- SI / SRTN Specific: Customer Info -->
                    <div v-if="['SI', 'SRTN'].includes(type)">
                        <h3 class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-3 border-b border-slate-100 pb-2">Customer Details</h3>
                        <p class="text-xl font-bold text-slate-800">{{ document.customer_name || 'Walk-in Customer' }}</p>
                        <div class="mt-2 text-sm text-slate-500 leading-relaxed">
                            <p v-if="document.customer_phone">Phone: <span class="text-slate-700">{{ document.customer_phone }}</span></p>
                        </div>
                    </div>

                    <!-- Ship To Info -->
                    <div v-if="['PO', 'GRN', 'PI', 'PRTN', 'SI', 'SRTN'].includes(type)">
                        <h3 class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-3 border-b border-slate-100 pb-2">Ship To / Branch</h3>
                        <p class="text-lg font-bold text-slate-800">{{ document.branch?.name }}</p>
                        <div class="mt-2 text-sm text-slate-500 leading-relaxed">
                            <p v-if="document.branch?.address">{{ document.branch?.address }}</p>
                        </div>
                    </div>
                </div>

                <!-- Right Column: Document Details Card -->
                <div>
                    <div class="bg-slate-800 rounded-xl p-6 text-white shadow-md">
                        <div class="space-y-4">
                            <div class="flex justify-between items-center border-b border-slate-600/50 pb-3">
                                <span class="text-slate-400 font-semibold uppercase tracking-wider text-xs">Document No</span>
                                <span class="font-bold text-lg tracking-wide">{{ document.document_number }}</span>
                            </div>
                            <div v-if="type === 'PI' && document.reference_id" class="flex justify-between items-center border-b border-slate-600/50 pb-3">
                                <span class="text-slate-400 font-semibold uppercase tracking-wider text-xs">GRN Ref</span>
                                <span class="font-medium">{{ document.good_received_note?.document_number || 'N/A' }}</span>
                            </div>
                            <div v-if="type === 'PRTN' && document.reference_id" class="flex justify-between items-center border-b border-slate-600/50 pb-3">
                                <span class="text-slate-400 font-semibold uppercase tracking-wider text-xs">PI Ref</span>
                                <span class="font-medium">{{ document.purchase_invoice?.document_number || 'N/A' }}</span>
                            </div>
                            <div v-if="type === 'SRTN' && document.reference_id" class="flex justify-between items-center border-b border-slate-600/50 pb-3">
                                <span class="text-slate-400 font-semibold uppercase tracking-wider text-xs">SI Ref</span>
                                <span class="font-medium">{{ document.sale_invoice?.document_number || 'N/A' }}</span>
                            </div>
                            <div class="flex justify-between items-center border-b border-slate-600/50 pb-3">
                                <span class="text-slate-400 font-semibold uppercase tracking-wider text-xs">Date</span>
                                <span class="font-medium">{{ formatDate(document.date || document.created_at) }}</span>
                            </div>
                            <div v-if="['PO', 'GRN'].includes(type) && document.delivery_date" class="flex justify-between items-center border-b border-slate-600/50 pb-3">
                                <span class="text-slate-400 font-semibold uppercase tracking-wider text-xs">Delivery Date</span>
                                <span class="font-medium text-emerald-400">{{ formatDate(document.delivery_date) }}</span>
                            </div>
                            <div v-if="type === 'PI' && document.due_date" class="flex justify-between items-center border-b border-slate-600/50 pb-3">
                                <span class="text-slate-400 font-semibold uppercase tracking-wider text-xs">Due Date</span>
                                <span class="font-medium text-rose-400">{{ formatDate(document.due_date) }}</span>
                            </div>
                            <div class="flex justify-between items-center pt-1">
                                <span class="text-slate-400 font-semibold uppercase tracking-wider text-xs">Status</span>
                                <span class="px-3 py-1 rounded bg-slate-700 text-white font-bold uppercase tracking-wider text-xs">
                                    {{ document.status.replace('_', ' ') }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Items Table -->
            <div class="mb-8">
                <table class="w-full text-left border-collapse border border-slate-200">
                    <thead>
                        <tr class="bg-slate-50">
                            <th class="py-3 px-4 text-xs font-bold text-slate-600 uppercase tracking-wider border border-slate-200 w-12">#</th>
                            <th class="py-3 px-4 text-xs font-bold text-slate-600 uppercase tracking-wider border border-slate-200">Item Description</th>
                            <th class="py-3 px-4 text-xs font-bold text-slate-600 uppercase tracking-wider border border-slate-200 text-right w-24">Qty</th>
                            <th class="py-3 px-4 text-xs font-bold text-slate-600 uppercase tracking-wider border border-slate-200 w-32">Unit</th>
                            
                            <template v-if="['PO', 'GRN', 'PI', 'PRTN', 'SI', 'SRTN'].includes(type)">
                                <th class="py-3 px-4 text-xs font-bold text-slate-600 uppercase tracking-wider border border-slate-200 text-right w-32">Rate</th>
                                <th v-if="type === 'PO'" class="py-3 px-4 text-xs font-bold text-slate-600 uppercase tracking-wider border border-slate-200 text-right w-24">Tax %</th>
                                <th v-if="['PI', 'PRTN', 'SI', 'SRTN'].includes(type)" class="py-3 px-4 text-xs font-bold text-slate-600 uppercase tracking-wider border border-slate-200 text-right w-24">Disc</th>
                                <th v-if="['PI', 'PRTN', 'SI', 'SRTN'].includes(type)" class="py-3 px-4 text-xs font-bold text-slate-600 uppercase tracking-wider border border-slate-200 text-right w-24">Tax</th>
                                <th class="py-3 px-4 text-xs font-bold text-slate-600 uppercase tracking-wider border border-slate-200 text-right w-32">Amount</th>
                            </template>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(item, index) in document.items" :key="item.id">
                            <td class="py-3 px-4 text-sm text-slate-600 border border-slate-200">{{ index + 1 }}</td>
                            <td class="py-3 px-4 text-sm font-medium text-slate-800 border border-slate-200">{{ item.item?.name }}</td>
                            <td class="py-3 px-4 text-sm text-slate-800 text-right border border-slate-200">{{ item.quantity }}</td>
                            <td class="py-3 px-4 text-sm text-slate-600 border border-slate-200">{{ item.unit?.name }}</td>
                            
                            <template v-if="['PO', 'GRN', 'PI', 'PRTN', 'SI', 'SRTN'].includes(type)">
                                <td class="py-3 px-4 text-sm text-slate-600 text-right border border-slate-200">{{ parseFloat(item.unit_price).toFixed(2) }}</td>
                                <td v-if="type === 'PO'" class="py-3 px-4 text-sm text-slate-600 text-right border border-slate-200">{{ parseFloat(item.tax_percent || 0).toFixed(2) }}%</td>
                                <td v-if="['PI', 'PRTN', 'SI', 'SRTN'].includes(type)" class="py-3 px-4 text-sm text-slate-600 text-right border border-slate-200">{{ parseFloat(item.discount_amount || 0).toFixed(2) }}</td>
                                <td v-if="['PI', 'PRTN', 'SI', 'SRTN'].includes(type)" class="py-3 px-4 text-sm text-slate-600 text-right border border-slate-200">{{ parseFloat(item.tax_amount || 0).toFixed(2) }}</td>
                                <td class="py-3 px-4 text-sm font-medium text-slate-800 text-right border border-slate-200">{{ parseFloat(item.subtotal || item.net_amount).toFixed(2) }}</td>
                            </template>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Document Totals -->
            <div v-if="['PO', 'GRN', 'PI', 'PRTN', 'SI', 'SRTN'].includes(type)" class="flex justify-end mb-12">
                <div class="w-1/2 md:w-1/3">
                    <template v-if="['PI', 'PRTN', 'SI', 'SRTN'].includes(type)">
                        <div class="flex justify-between py-2 border-b border-slate-100">
                            <span class="text-sm text-slate-600">Subtotal:</span>
                            <span class="text-sm text-slate-800">{{ (parseFloat(document.total_amount) + parseFloat(document.discount_amount || 0) - parseFloat(document.tax_amount || 0) - parseFloat(document.shipping_amount || 0)).toFixed(2) }}</span>
                        </div>
                        <div v-if="parseFloat(document.discount_amount) > 0" class="flex justify-between py-2 border-b border-slate-100">
                            <span class="text-sm text-slate-600">Discount:</span>
                            <span class="text-sm text-rose-500">-{{ parseFloat(document.discount_amount).toFixed(2) }}</span>
                        </div>
                        <div v-if="parseFloat(document.tax_amount) > 0" class="flex justify-between py-2 border-b border-slate-100">
                            <span class="text-sm text-slate-600">Tax:</span>
                            <span class="text-sm text-indigo-500">+{{ parseFloat(document.tax_amount).toFixed(2) }}</span>
                        </div>
                        <div v-if="parseFloat(document.shipping_amount) > 0" class="flex justify-between py-2 border-b border-slate-100">
                            <span class="text-sm text-slate-600">Shipping:</span>
                            <span class="text-sm text-indigo-500">+{{ parseFloat(document.shipping_amount).toFixed(2) }}</span>
                        </div>
                    </template>
                    <template v-else>
                        <div class="flex justify-between py-2 border-b border-slate-100">
                            <span class="text-sm font-medium text-slate-600">Subtotal:</span>
                            <span class="text-sm font-medium text-slate-800">{{ parseFloat(document.total_amount).toFixed(2) }}</span>
                        </div>
                    </template>
                    
                    <div class="flex justify-between py-3 border-b-2 border-slate-800">
                        <span class="text-base font-bold text-slate-800">Grand Total:</span>
                        <span class="text-base font-bold text-slate-800">{{ parseFloat(document.total_amount).toFixed(2) }}</span>
                    </div>
                </div>
            </div>

            <!-- Signatures Section -->
            <div class="grid grid-cols-2 gap-16 mt-24">
                <div class="text-center">
                    <div class="border-b border-slate-400 mb-2"></div>
                    <p class="text-xs font-bold text-slate-600 uppercase tracking-wider">Requested By</p>
                </div>
                <div class="text-center">
                    <div class="border-b border-slate-400 mb-2"></div>
                    <p class="text-xs font-bold text-slate-600 uppercase tracking-wider">Authorized Signature</p>
                </div>
            </div>
            
            <div class="mt-12 text-center text-xs text-slate-400 print:text-slate-500">
                <p>This is a computer generated document and does not require a physical signature if printed from authorized account.</p>
            </div>
        </div>
    </div>
</template>

<style>
@media print {
    @page {
        margin: 0;
    }
    body {
        margin: 1cm;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }
}
</style>
