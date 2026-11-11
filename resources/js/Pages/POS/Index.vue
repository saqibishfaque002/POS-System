<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue';
import { Head } from '@inertiajs/vue3';
import axios from 'axios';

import PosHeader from './Components/PosHeader.vue';
import MenuCatalog from './Components/MenuCatalog.vue';
import CartPanel from './Components/CartPanel.vue';
import ItemCustomizerModal from './Components/ItemCustomizerModal.vue';
import TableSelectModal from './Components/TableSelectModal.vue';
import CustomerModal from './Components/CustomerModal.vue';
import PaymentModal from './Components/PaymentModal.vue';
import HeldOrdersModal from './Components/HeldOrdersModal.vue';
import SubmittedOrdersModal from './Components/SubmittedOrdersModal.vue';
import SettledOrdersModal from './Components/SettledOrdersModal.vue';
import ThermalReceipt from './Components/ThermalReceipt.vue';
import DiscountVoucherModal from './Components/DiscountVoucherModal.vue';
import FocModal from './Components/FocModal.vue';
import OpenBusinessDayModal from './Components/OpenBusinessDayModal.vue';
import CloseBusinessDayModal from './Components/CloseBusinessDayModal.vue';
import StartShiftModal from './Components/StartShiftModal.vue';
import CloseShiftModal from './Components/CloseShiftModal.vue';
import ShiftSummaryModal from './Components/ShiftSummaryModal.vue';

const props = defineProps({
    initialBranchId: [Number, String],
    user: Object,
});

// Master state
const loading = ref(true);
const catalogLoading = ref(false);
const submitting = ref(false);

const branches = ref([]);
const currentBranch = ref(null);
const currentBranchId = ref(props.initialBranchId || null);

const menus = ref([]);
const currentMenu = ref(null);
const currentMenuId = ref(null);

const foodGroups = ref([]);
const foodItems = ref([]);
const propertyGroups = ref([]);
const orderTypes = ref([]);
const currentOrderTypeId = ref(null);
const tableOrderTypeIds = ref([]);
const parties = ref([]);
const paymentTypes = ref([]);
const floors = ref([]);
const activeOrders = ref([]);
const submittedOrdersCount = ref(0);
const customers = ref([]);
const businessDate = ref('');
const businessDay = ref('');
const businessDateFormatted = ref('');

// Active transaction state
const cart = ref([]);
const selectedTable = ref(null);
const selectedCustomer = ref(null);
const selectedParty = ref(null);
const deliveryCharges = ref(0);
const riderName = ref('');
const deliveryAddress = ref('');
const editingOrderId = ref(null);
const editingOrderToken = ref(null);
const appliedDiscount = ref(null);
const activeCardBin = ref('');

// Modals
const isCustomizerOpen = ref(false);
const customizerItem = ref(null);
const isTableModalOpen = ref(false);
const isCustomerModalOpen = ref(false);
const isPaymentModalOpen = ref(false);
const isHeldOrdersModalOpen = ref(false);
const isSubmittedOrdersModalOpen = ref(false);
const isSettledOrdersModalOpen = ref(false);
const isReceiptModalOpen = ref(false);
const isDiscountModalOpen = ref(false);
const isFocModalOpen = ref(false);
const currentReceiptOrder = ref(null);
const currentReceiptType = ref('invoice'); // 'invoice' | 'kot'

// Business Day & Cashier Shift State
const activeBusinessDay = ref(null);
const activeShift = ref(null);
const isOpenBusinessDayModalOpen = ref(false);
const isCloseBusinessDayModalOpen = ref(false);
const isStartShiftModalOpen = ref(false);
const isCloseShiftModalOpen = ref(false);
const isShiftSummaryModalOpen = ref(false);
const activeXReportSummary = ref(null);

// Bootstrap Terminal
const fetchBootstrapData = async () => {
    loading.value = true;
    try {
        const response = await axios.get(route('pos.bootstrap'), {
            params: {
                branch_id: currentBranchId.value,
                menu_id: currentMenuId.value,
            }
        });
        const data = response.data;
        branches.value = data.branches || [];
        currentBranch.value = data.current_branch || null;
        currentBranchId.value = data.current_branch?.id || currentBranchId.value;

        menus.value = data.menus || [];
        currentMenu.value = data.current_menu || null;
        currentMenuId.value = data.current_menu?.id || (data.menus?.[0]?.id ?? null);

        foodGroups.value = data.food_groups || [];
        foodItems.value = data.food_items || [];
        propertyGroups.value = data.property_groups || [];
        orderTypes.value = data.order_types || [];
        tableOrderTypeIds.value = (data.table_order_type_ids || []).map(Number);
        currentOrderTypeId.value = data.order_types?.[0]?.id || null;

        // Auto-select initial party if available for default order type
        if (data.order_types?.[0]?.parties?.length > 0 && !selectedParty.value) {
            selectedParty.value = data.order_types[0].parties[0];
        }

        parties.value = data.parties || [];
        paymentTypes.value = data.payment_types || [];
        floors.value = data.floors || [];
        activeOrders.value = data.active_orders || [];
        submittedOrdersCount.value = data.submitted_orders_count || 0;
        customers.value = data.customers || [];
        businessDate.value = data.business_date || new Date().toLocaleDateString('en-CA');
        businessDay.value = data.business_day || new Date().toLocaleDateString('en-US', { weekday: 'long' });
        businessDateFormatted.value = data.business_date_formatted || new Date().toLocaleDateString('en-US', { day: '2-digit', month: 'short', year: 'numeric' });

        activeBusinessDay.value = data.active_business_day || null;
        activeShift.value = data.active_shift || null;

        // Auto-prompt cashier if business day or shift is not open
        if (!activeBusinessDay.value) {
            isOpenBusinessDayModalOpen.value = true;
        } else if (!activeShift.value) {
            isStartShiftModalOpen.value = true;
        }
    } catch (e) {
        console.error('Failed to bootstrap POS data:', e);
    } finally {
        loading.value = false;
    }
};

const isTerminalLocked = computed(() => !loading.value && (!activeBusinessDay.value || !activeShift.value));

// Reload Catalog when Menu or Branch changes
const reloadItems = async () => {
    if (!currentBranchId.value || !currentMenuId.value) return;
    catalogLoading.value = true;
    try {
        const response = await axios.get(route('pos.items'), {
            params: {
                branch_id: currentBranchId.value,
                menu_id: currentMenuId.value,
            }
        });
        foodItems.value = response.data.items || [];
    } catch (e) {
        console.error('Failed to load items:', e);
    } finally {
        catalogLoading.value = false;
    }
};

watch(currentBranchId, (newBranchId, oldBranchId) => {
    if (oldBranchId && newBranchId !== oldBranchId) {
        fetchBootstrapData();
    }
});

watch(currentMenuId, (newMenuId, oldMenuId) => {
    if (oldMenuId && newMenuId !== oldMenuId) {
        reloadItems();
    }
});

// Current Order Type
const currentOrderType = computed(() => {
    return orderTypes.value.find(ot => ot.id == currentOrderTypeId.value);
});

// Available parties strictly linked to current Order Type
const availableParties = computed(() => {
    return currentOrderType.value?.parties || [];
});

const selectedPartyId = computed({
    get: () => selectedParty.value?.id || null,
    set: (newId) => {
        if (!newId) {
            selectedParty.value = null;
        } else {
            selectedParty.value = availableParties.value.find(p => p.id == newId)
                || parties.value.find(p => p.id == newId)
                || null;
        }
    }
});

// Table & Delivery detection
const isTableRequired = computed(() => {
    if (!currentOrderTypeId.value) return false;
    return tableOrderTypeIds.value.map(Number).includes(Number(currentOrderTypeId.value));
});

const isDineIn = computed(() => {
    return isTableRequired.value || currentOrderType.value?.code === 'dine_in' || currentOrderType.value?.name?.toLowerCase().includes('dine');
});

const isDelivery = computed(() => {
    const code = currentOrderType.value?.code?.toLowerCase() || '';
    const name = currentOrderType.value?.name?.toLowerCase() || '';
    return code === 'delivery' || name.includes('delivery');
});

// Explicit order type selection handler from cart segmented tabs
const handleSelectOrderType = (orderType) => {
    const otId = typeof orderType === 'object' ? orderType.id : orderType;
    currentOrderTypeId.value = otId;
    if (tableOrderTypeIds.value.map(Number).includes(Number(otId))) {
        isTableModalOpen.value = true;
    } else {
        selectedTable.value = null;
        isTableModalOpen.value = false;
    }
};

// Auto-sync selectedParty, deliveryCharges, and table when currentOrderTypeId changes
watch(currentOrderTypeId, (newTypeId) => {
    const ot = orderTypes.value.find(o => o.id == newTypeId);
    const validParties = ot?.parties || [];
    if (validParties.length > 0) {
        const isCurrentValid = selectedParty.value && validParties.some(p => p.id == selectedParty.value.id);
        if (!isCurrentValid) {
            selectedParty.value = validParties[0];
        }
    } else {
        selectedParty.value = null;
    }

    // Automatic Delivery Charges from Branch configuration
    const isDeliv = ot?.code === 'delivery' || ot?.name?.toLowerCase().includes('delivery');
    if (isDeliv) {
        deliveryCharges.value = Number(currentBranch.value?.delivery_charges || 0);
    } else {
        deliveryCharges.value = 0;
    }

    // Clear table if order type does not use tables
    const requiresTable = tableOrderTypeIds.value.map(Number).includes(Number(newTypeId));
    if (!requiresTable) {
        selectedTable.value = null;
        isTableModalOpen.value = false;
    }
});

// Auto-update delivery charges when branch changes
watch(currentBranch, (newBranch) => {
    if (isDelivery.value && newBranch) {
        deliveryCharges.value = Number(newBranch.delivery_charges || 0);
    }
});

// Tax percentage for current branch & order
const currentTaxPercentage = computed(() => {
    // Default to branch payment tax or menu tax
    if (currentMenu.value && currentMenu.value.tax_percentage > 0) {
        return Number(currentMenu.value.tax_percentage);
    }
    const defaultPt = paymentTypes.value.find(pt => pt.name.toLowerCase().includes('cash')) || paymentTypes.value[0];
    return Number(defaultPt?.tax_percentage || 0);
});

// Calculations
const subtotal = computed(() => {
    return cart.value.reduce((sum, item) => sum + Number(item.total_price || 0), 0);
});

const discountAmount = computed(() => {
    if (!appliedDiscount.value || subtotal.value <= 0) return 0;
    const disc = appliedDiscount.value;

    let eligibleSubtotal = subtotal.value;
    if (disc.applicable_scope === 'food_groups' && disc.food_group_ids?.length) {
        eligibleSubtotal = cart.value
            .filter(i => disc.food_group_ids.includes(i.food_group_id))
            .reduce((s, i) => s + i.total_price, 0);
    } else if (disc.applicable_scope === 'food_items' && disc.food_item_ids?.length) {
        eligibleSubtotal = cart.value
            .filter(i => disc.food_item_ids.includes(i.food_item_id))
            .reduce((s, i) => s + i.total_price, 0);
    }

    let calculated = 0;
    if (disc.type === 'percentage') {
        calculated = (eligibleSubtotal * Number(disc.value)) / 100;
        if (disc.max_discount_cap && Number(disc.max_discount_cap) > 0) {
            calculated = Math.min(calculated, Number(disc.max_discount_cap));
        }
    } else {
        calculated = Number(disc.value);
    }

    return Math.min(calculated, eligibleSubtotal);
});

// Service Charges: Branch configuration takes precedence for Dine-in orders
const serviceChargeLabel = computed(() => {
    if (!isDineIn.value) return '';
    const branchVal = Number(currentBranch.value?.service_charge_value || 0);
    const branchType = currentBranch.value?.service_charge_type || 'percentage';
    if (branchVal > 0) {
        return branchType === 'percentage' ? `${branchVal}%` : `Fixed Rs. ${branchVal}`;
    }
    const fallbackPercent = Number(currentOrderType.value?.service_charge_percent || 0);
    return fallbackPercent > 0 ? `${fallbackPercent}%` : '';
});

const serviceChargeAmount = computed(() => {
    if (!isDineIn.value) return 0;
    const branchVal = Number(currentBranch.value?.service_charge_value || 0);
    const branchType = currentBranch.value?.service_charge_type || 'percentage';
    const base = Math.max(0, subtotal.value - discountAmount.value);

    if (branchVal > 0) {
        if (branchType === 'percentage') {
            return (base * branchVal) / 100;
        } else {
            return branchVal;
        }
    }
    const fallbackPercent = Number(currentOrderType.value?.service_charge_percent || 0);
    if (fallbackPercent > 0) {
        return (base * fallbackPercent) / 100;
    }
    return 0;
});

const taxAmount = computed(() => {
    if (currentTaxPercentage.value <= 0) return 0;
    const taxableBase = Math.max(0, subtotal.value - discountAmount.value + serviceChargeAmount.value);
    return (taxableBase * currentTaxPercentage.value) / 100;
});

const grandTotal = computed(() => {
    const total = subtotal.value - discountAmount.value + serviceChargeAmount.value + taxAmount.value + Number(deliveryCharges.value || 0);
    return Math.max(0, Math.round(total * 100) / 100);
});

// Automatic check for available discounts from discount engine
const checkAvailableDiscounts = async () => {
    if (subtotal.value <= 0) {
        appliedDiscount.value = null;
        return;
    }

    // Preserve manually entered vouchers
    if (appliedDiscount.value?.code) {
        return;
    }

    try {
        const response = await axios.post(route('restaurant.discounts.available'), {
            branch_id: currentBranchId.value,
            order_type_id: currentOrderTypeId.value,
            subtotal: subtotal.value,
            cart_subtotal: subtotal.value,
            card_number: activeCardBin.value || null,
            card_bin: activeCardBin.value || null,
        });

        const list = response.data.available_discounts || response.data.discounts || [];
        if (list.length > 0) {
            // Apply the best discount (highest value)
            appliedDiscount.value = list[0];
        } else {
            if (!activeCardBin.value && !appliedDiscount.value?.code) {
                appliedDiscount.value = null;
            }
        }
    } catch (e) {
        console.warn('Discount check info:', e.response?.data?.message || e.message);
    }
};

watch(subtotal, () => {
    checkAvailableDiscounts();
});

// Item Selection from Catalog
const handleSelectItem = (item) => {
    if (!ensureShiftActive()) return;

    // If item has variations or property groups exist, open customizer modal
    if ((item.variations && item.variations.length > 0) || (propertyGroups.value && propertyGroups.value.length > 0)) {
        customizerItem.value = item;
        isCustomizerOpen.value = true;
    } else {
        // Ultra-fast direct add
        const unitPrice = Number(item.effective_price || item.base_price || 0);
        handleAddToCart({
            food_item_id: item.id,
            name: item.name,
            food_group_id: item.food_group_id,
            variation_id: null,
            variation_name: null,
            unit_price: unitPrice,
            quantity: 1,
            total_price: unitPrice,
            notes: null,
            properties: [],
        });
    }
};

// Add to Cart
const handleAddToCart = (newItem) => {
    // Check if duplicate item (same item, variation, notes and properties) exists
    const existingIndex = cart.value.findIndex(i => 
        i.food_item_id === newItem.food_item_id &&
        i.variation_id === newItem.variation_id &&
        (i.notes || '') === (newItem.notes || '') &&
        JSON.stringify(i.properties || []) === JSON.stringify(newItem.properties || [])
    );

    if (existingIndex > -1) {
        cart.value[existingIndex].quantity += newItem.quantity;
        cart.value[existingIndex].total_price = cart.value[existingIndex].quantity * cart.value[existingIndex].unit_price;
    } else {
        cart.value.push(newItem);
    }
};

// Cart Item Quantity
const handleUpdateQuantity = ({ index, delta }) => {
    const item = cart.value[index];
    if (!item) return;
    const newQty = item.quantity + delta;
    if (newQty <= 0) {
        cart.value.splice(index, 1);
    } else {
        item.quantity = newQty;
        item.total_price = item.quantity * item.unit_price;
    }
};

// Remove Cart Item
const handleRemoveItem = (index) => {
    cart.value.splice(index, 1);
};

// Clear Cart
const handleClearCart = () => {
    cart.value = [];
    editingOrderId.value = null;
    editingOrderToken.value = null;
    selectedTable.value = null;
    selectedCustomer.value = null;
    appliedDiscount.value = null;
    activeCardBin.value = '';
    deliveryCharges.value = 0;
    riderName.value = '';
    deliveryAddress.value = '';
    if (availableParties.value.length > 0) {
        selectedParty.value = availableParties.value[0];
    } else {
        selectedParty.value = null;
    }
    mobileActiveView.value = 'catalog';
};

// Shift & Business Day Verification Guard
const ensureShiftActive = () => {
    if (!activeBusinessDay.value) {
        isOpenBusinessDayModalOpen.value = true;
        return false;
    }
    if (!activeShift.value) {
        isStartShiftModalOpen.value = true;
        return false;
    }
    return true;
};

const handleOpenCurrentShiftSummary = async () => {
    if (!activeShift.value) {
        isStartShiftModalOpen.value = true;
        return;
    }
    try {
        const res = await axios.get(route('pos.shifts.summary'), {
            params: { shift_id: activeShift.value.id }
        });
        if (res.data.success) {
            activeXReportSummary.value = res.data.summary;
            isShiftSummaryModalOpen.value = true;
        }
    } catch (e) {
        console.error('Error fetching shift summary', e);
    }
};

const handleDayOpened = (day) => {
    activeBusinessDay.value = day;
    businessDate.value = day.business_date;
    businessDateFormatted.value = day.business_date_formatted;
    businessDay.value = day.business_day;
    if (!activeShift.value) {
        isStartShiftModalOpen.value = true;
    }
};

const handleDayClosed = (data) => {
    activeBusinessDay.value = null;
    activeShift.value = null;
    fetchBootstrapData();
};

const handleShiftStarted = (shift) => {
    activeShift.value = shift;
    fetchBootstrapData();
};

const handleShiftClosed = (data) => {
    activeShift.value = null;
    fetchBootstrapData();
};

// Send KOT (Kitchen Order Ticket) / Submit Order
const handleSendKot = async () => {
    if (cart.value.length === 0 || submitting.value) return;
    if (!ensureShiftActive()) return;
    submitting.value = true;
    try {
        const payload = {
            order_id: editingOrderId.value,
            branch_id: currentBranchId.value,
            order_type_id: currentOrderTypeId.value,
            table_id: selectedTable.value?.id || null,
            party_id: selectedParty.value?.id || null,
            customer_id: selectedCustomer.value?.id || null,
            rider_name: riderName.value || null,
            delivery_address: deliveryAddress.value || null,
            items: cart.value,
            subtotal: subtotal.value,
            discount_id: appliedDiscount.value?.id || null,
            discount_type: appliedDiscount.value?.type || null,
            discount_value: discountAmount.value,
            tax_amount: taxAmount.value,
            service_charges: serviceChargeAmount.value,
            delivery_charges: deliveryCharges.value,
            grand_total: grandTotal.value,
            status: 'submitted',
            is_submitted: true,
            is_kot: true,
            is_paid: false,
        };

        const response = await axios.post(route('pos.orders.store'), payload);
        if (response.data.success && response.data.order) {
            currentReceiptOrder.value = response.data.order;
            currentReceiptType.value = 'kot';
            isReceiptModalOpen.value = true;

            // Clear the cart so cashier can start new order immediately
            handleClearCart();

            // Refresh active orders list & submitted count
            fetchBootstrapData();
        }
    } catch (e) {
        alert(e.response?.data?.message || 'Error submitting order.');
    } finally {
        submitting.value = false;
    }
};

// Hold / Park Order
const handleHoldOrder = async () => {
    if (cart.value.length === 0) return;
    if (!ensureShiftActive()) return;
    submitting.value = true;
    try {
        const payload = {
            order_id: editingOrderId.value,
            branch_id: currentBranchId.value,
            order_type_id: currentOrderTypeId.value,
            table_id: selectedTable.value?.id || null,
            party_id: selectedParty.value?.id || null,
            customer_id: selectedCustomer.value?.id || null,
            rider_name: riderName.value || null,
            delivery_address: deliveryAddress.value || null,
            items: cart.value,
            subtotal: subtotal.value,
            discount_id: appliedDiscount.value?.id || null,
            discount_type: appliedDiscount.value?.type || null,
            discount_value: discountAmount.value,
            tax_amount: taxAmount.value,
            service_charges: serviceChargeAmount.value,
            delivery_charges: deliveryCharges.value,
            grand_total: grandTotal.value,
            notes: 'Held / Parked Order',
            is_kot: false,
            is_paid: false,
        };

        const response = await axios.post(route('pos.orders.store'), payload);
        if (response.data.success) {
            handleClearCart();
            fetchBootstrapData();
        }
    } catch (e) {
        alert(e.response?.data?.message || 'Error parking order.');
    } finally {
        submitting.value = false;
    }
};

// Settle / Complete Payment
const handleConfirmPayment = async (paymentData) => {
    if (!ensureShiftActive()) return;
    submitting.value = true;
    try {
        let response = null;

        if (editingOrderId.value) {
            // Settle existing running order
            response = await axios.post(route('pos.orders.settle', editingOrderId.value), {
                payments: paymentData.payments,
                card_bin: paymentData.card_bin || activeCardBin.value,
                party_id: selectedParty.value?.id || null,
                rider_name: riderName.value || null,
                delivery_address: deliveryAddress.value || null,
                delivery_charges: deliveryCharges.value || 0,
            });
        } else {
            // Create & Pay in single step
            const payload = {
                branch_id: currentBranchId.value,
                order_type_id: currentOrderTypeId.value,
                table_id: selectedTable.value?.id || null,
                party_id: selectedParty.value?.id || null,
                customer_id: selectedCustomer.value?.id || null,
                rider_name: riderName.value || null,
                delivery_address: deliveryAddress.value || null,
                items: cart.value,
                subtotal: subtotal.value,
                discount_id: appliedDiscount.value?.id || null,
                discount_type: appliedDiscount.value?.type || null,
                discount_value: discountAmount.value,
                tax_amount: taxAmount.value,
                service_charges: serviceChargeAmount.value,
                delivery_charges: deliveryCharges.value,
                grand_total: grandTotal.value,
                is_kot: false,
                is_paid: true,
                payments: paymentData.payments,
                card_bin: paymentData.card_bin || activeCardBin.value,
            };
            response = await axios.post(route('pos.orders.store'), payload);
        }

        if (response.data.success && response.data.order) {
            isPaymentModalOpen.value = false;
            currentReceiptOrder.value = response.data.order;
            currentReceiptType.value = 'invoice';
            isReceiptModalOpen.value = true;

            handleClearCart();
            fetchBootstrapData();
        }
    } catch (e) {
        alert(e.response?.data?.message || 'Error processing payment.');
    } finally {
        submitting.value = false;
    }
};

// Process Free of Charge (FOC) Complimentary Order
const handleProcessFoc = async (reason) => {
    if (cart.value.length === 0) return;
    if (!ensureShiftActive()) return;
    submitting.value = true;
    try {
        let response = null;
        if (editingOrderId.value) {
            response = await axios.post(route('pos.orders.foc', editingOrderId.value), {
                foc_reason: reason,
            });
        } else {
            const payload = {
                branch_id: currentBranchId.value,
                order_type_id: currentOrderTypeId.value,
                table_id: selectedTable.value?.id || null,
                party_id: selectedParty.value?.id || null,
                customer_id: selectedCustomer.value?.id || null,
                rider_name: riderName.value || null,
                delivery_address: deliveryAddress.value || null,
                items: cart.value,
                subtotal: subtotal.value,
                discount_id: null,
                discount_type: 'percentage',
                discount_value: subtotal.value,
                tax_amount: 0,
                service_charges: 0,
                delivery_charges: 0,
                grand_total: 0,
                is_kot: false,
                is_paid: true,
                is_foc: true,
                foc_reason: reason,
            };
            response = await axios.post(route('pos.orders.store'), payload);
        }

        if (response.data.success && response.data.order) {
            isFocModalOpen.value = false;
            currentReceiptOrder.value = response.data.order;
            currentReceiptType.value = 'invoice';
            isReceiptModalOpen.value = true;

            handleClearCart();
            fetchBootstrapData();
        }
    } catch (e) {
        alert(e.response?.data?.message || 'Error processing FOC order.');
    } finally {
        submitting.value = false;
    }
};

// Card BIN check from payment modal
const handleCardBinEntered = (bin) => {
    activeCardBin.value = bin;
    checkAvailableDiscounts();
};

// Helper to populate order into cart
const loadOrderIntoCart = (order) => {
    editingOrderId.value = order.id;
    editingOrderToken.value = order.token_number;
    currentOrderTypeId.value = order.order_type_id;
    selectedTable.value = order.table || null;
    selectedCustomer.value = order.customer || null;
    selectedParty.value = order.party || null;
    deliveryCharges.value = Number(order.delivery_charges || 0);
    riderName.value = order.rider_name || '';
    deliveryAddress.value = order.delivery_address || '';
    appliedDiscount.value = order.discount || null;

    cart.value = (order.items || []).map(item => ({
        food_item_id: item.food_item_id,
        name: item.food_item?.name || 'Item',
        food_group_id: item.food_item?.food_group_id,
        variation_id: item.variation_id,
        variation_name: item.variation?.name,
        unit_price: Number(item.unit_price),
        quantity: item.quantity,
        total_price: Number(item.total_price),
        notes: item.notes,
        properties: (item.properties || []).map(p => ({
            property_group_id: p.property_item?.property_group_id,
            property_item_id: p.property_item_id,
            property_name: p.property_name,
            extra_price: Number(p.extra_price),
            quantity: p.quantity,
        })),
    }));
    mobileActiveView.value = 'cart';
};

// Resume Held Order
const handleResumeOrder = (order) => {
    loadOrderIntoCart(order);
    isHeldOrdersModalOpen.value = false;
    isTableModalOpen.value = false;
};

// Edit Submitted Order (Loads order into active cart for item additions/updates)
const handleEditSubmittedOrder = (order) => {
    loadOrderIntoCart(order);
    isSubmittedOrdersModalOpen.value = false;
};

// Settle Submitted Order (Loads order and opens payment modal)
const handleSettleSubmittedOrder = (order) => {
    loadOrderIntoCart(order);
    isSubmittedOrdersModalOpen.value = false;
    isPaymentModalOpen.value = true;
};

// Reprint KOT from Submitted Orders
const handlePrintKotFromSubmitted = (order) => {
    currentReceiptOrder.value = order;
    currentReceiptType.value = 'kot';
    isReceiptModalOpen.value = true;
};

// Reprint Bill from Settled Orders
const handleReprintFromSettled = (order) => {
    currentReceiptOrder.value = order;
    currentReceiptType.value = 'invoice';
    isReceiptModalOpen.value = true;
};

// Void Order
const handleVoidOrder = async ({ orderId, reason }) => {
    try {
        const response = await axios.post(route('pos.orders.void', orderId), { reason });
        if (response.data.success) {
            if (editingOrderId.value === orderId) {
                handleClearCart();
            }
            fetchBootstrapData();
        }
    } catch (e) {
        alert(e.response?.data?.message || 'Error voiding order.');
    }
};

// Global Keyboard Shortcuts
const handleKeydown = (e) => {
    // If typing inside an input or textarea, don't trigger hotkeys unless Esc
    const isInput = ['INPUT', 'TEXTAREA', 'SELECT'].includes(e.target.tagName);

    if (e.key === 'Escape') {
        isCustomizerOpen.value = false;
        isTableModalOpen.value = false;
        isCustomerModalOpen.value = false;
        isPaymentModalOpen.value = false;
        isHeldOrdersModalOpen.value = false;
        isSubmittedOrdersModalOpen.value = false;
        isSettledOrdersModalOpen.value = false;
        isReceiptModalOpen.value = false;
        isDiscountModalOpen.value = false;
        isFocModalOpen.value = false;
        isOpenBusinessDayModalOpen.value = false;
        isCloseBusinessDayModalOpen.value = false;
        isStartShiftModalOpen.value = false;
        isCloseShiftModalOpen.value = false;
        isShiftSummaryModalOpen.value = false;
        return;
    }

    if (isInput) return;

    // If terminal is locked because Day or Shift is not open, block ordering shortcuts
    if (isTerminalLocked.value) return;

    if (e.key === 'F1' || (e.altKey && e.key.toLowerCase() === 'h')) {
        e.preventDefault();
        handleHoldOrder();
    } else if (e.key === 'F2' || (e.altKey && e.key.toLowerCase() === 'p')) {
        e.preventDefault();
        if (cart.value.length > 0) {
            isPaymentModalOpen.value = true;
        }
    } else if (e.key === 'F3' || (e.altKey && e.key.toLowerCase() === 'k')) {
        e.preventDefault();
        handleSendKot();
    } else if (e.key === 'F4' || (e.altKey && e.key.toLowerCase() === 's')) {
        e.preventDefault();
        isSubmittedOrdersModalOpen.value = true;
    } else if (e.key === 'F9' || (e.altKey && e.key.toLowerCase() === 't')) {
        e.preventDefault();
        isTableModalOpen.value = true;
    }
};

// Responsive Mobile View State ('catalog' | 'cart')
const mobileActiveView = ref('catalog');
const cartItemsCount = computed(() => {
    return cart.value.reduce((total, item) => total + (Number(item.quantity) || 1), 0);
});
const formatPrice = (val) => {
    return Number(val || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
};

onMounted(() => {
    fetchBootstrapData();
    window.addEventListener('keydown', handleKeydown);
});

onUnmounted(() => {
    window.removeEventListener('keydown', handleKeydown);
});
</script>

<template>
    <Head title="POS Terminal" />

    <div class="h-screen w-screen flex flex-col bg-slate-100 dark:bg-slate-950 overflow-hidden font-sans select-none antialiased">
        <!-- Top App Header -->
        <PosHeader
            :branches="branches"
            v-model:currentBranchId="currentBranchId"
            :menus="menus"
            v-model:currentMenuId="currentMenuId"
            :submittedOrdersCount="submittedOrdersCount"
            :heldOrdersCount="activeOrders.length"
            :user="user"
            :businessDate="businessDate"
            :businessDay="businessDay"
            :businessDateFormatted="businessDateFormatted"
            :activeBusinessDay="activeBusinessDay"
            :activeShift="activeShift"
            :mobileActiveView="mobileActiveView"
            :cartItemsCount="cartItemsCount"
            @update:mobileActiveView="mobileActiveView = $event"
            @openHeldOrdersModal="isHeldOrdersModalOpen = true"
            @openSubmittedOrdersModal="isSubmittedOrdersModalOpen = true"
            @openSettledOrdersModal="isSettledOrdersModalOpen = true"
            @openStartShiftModal="isStartShiftModalOpen = true"
            @openCloseShiftModal="isCloseShiftModalOpen = true"
            @openShiftSummaryModal="handleOpenCurrentShiftSummary"
            @openOpenBusinessDayModal="isOpenBusinessDayModalOpen = true"
            @openCloseBusinessDayModal="isCloseBusinessDayModalOpen = true"
        />

        <!-- Main Body: Catalog (Left) + Cart (Right) -->
        <div class="flex-1 flex overflow-hidden relative">
            <!-- Left: Catalog & Categories -->
            <div
                class="flex-1 flex flex-col h-full overflow-hidden"
                :class="[
                    mobileActiveView === 'catalog' ? 'flex w-full' : 'hidden lg:flex'
                ]"
            >
                <MenuCatalog
                    :foodGroups="foodGroups"
                    :foodItems="foodItems"
                    :propertyGroups="propertyGroups"
                    :loading="catalogLoading"
                    @selectItem="handleSelectItem"
                />

                <!-- Mobile Floating Cart Action Bar (Visible only on mobile in catalog view when cart has items) -->
                <div
                    v-if="cart.length > 0"
                    class="lg:hidden p-3 bg-white/95 dark:bg-slate-900/95 backdrop-blur-md border-t border-slate-200 dark:border-slate-800 shadow-xl shrink-0 flex items-center justify-between gap-3"
                >
                    <div class="flex items-center space-x-2.5">
                        <div class="w-10 h-10 rounded-xl bg-indigo-600 dark:bg-cyan-600 text-white flex items-center justify-center font-bold relative shadow-xs">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                            <span class="absolute -top-1.5 -right-1.5 bg-rose-500 text-white text-[10px] font-black w-5 h-5 rounded-full flex items-center justify-center border-2 border-white dark:border-slate-900">
                                {{ cartItemsCount }}
                            </span>
                        </div>
                        <div class="flex flex-col text-left">
                            <span class="text-xs font-black text-slate-800 dark:text-white">
                                {{ cartItemsCount }} {{ cartItemsCount === 1 ? 'Item' : 'Items' }}
                            </span>
                            <span class="text-xs font-black text-indigo-600 dark:text-cyan-400">
                                Rs. {{ formatPrice(grandTotal) }}
                            </span>
                        </div>
                    </div>

                    <button
                        type="button"
                        @click="mobileActiveView = 'cart'"
                        class="pos-btn-primary !py-2.5 !px-5 text-xs font-black flex items-center space-x-1.5 shadow-md active:scale-95 cursor-pointer"
                    >
                        <span>View Cart</span>
                        <span>→</span>
                    </button>
                </div>
            </div>

            <!-- Right: Cart & Settle Panel -->
            <div
                class="h-full"
                :class="[
                    mobileActiveView === 'cart' ? 'flex flex-1 w-full' : 'hidden lg:flex shrink-0'
                ]"
            >
                <CartPanel
                    :isMobileView="mobileActiveView === 'cart'"
                    @backToCatalog="mobileActiveView = 'catalog'"
                    :cart="cart"
                    :orderType="currentOrderType"
                    :orderTypes="orderTypes"
                    :tableOrderTypeIds="tableOrderTypeIds"
                    v-model:currentOrderTypeId="currentOrderTypeId"
                    @selectOrderType="handleSelectOrderType"
                    :availableParties="availableParties"
                    v-model:selectedPartyId="selectedPartyId"
                    :selectedTable="selectedTable"
                    :selectedCustomer="selectedCustomer"
                    :selectedParty="selectedParty"
                    :selectedDiscount="appliedDiscount"
                    :tokenNumber="editingOrderToken"
                    :taxPercentage="currentTaxPercentage"
                    :deliveryCharges="deliveryCharges"
                    @update:deliveryCharges="deliveryCharges = $event"
                    :riderName="riderName"
                    @update:riderName="riderName = $event"
                    :deliveryAddress="deliveryAddress"
                    @update:deliveryAddress="deliveryAddress = $event"
                    :subtotal="subtotal"
                    :discountAmount="discountAmount"
                    :serviceChargeAmount="serviceChargeAmount"
                    :serviceChargeLabel="serviceChargeLabel"
                    :taxAmount="taxAmount"
                    :grandTotal="grandTotal"
                    :editingOrderId="editingOrderId"
                    :submitting="submitting"
                    :businessDate="businessDate"
                    :businessDay="businessDay"
                    :businessDateFormatted="businessDateFormatted"
                    @updateQuantity="handleUpdateQuantity"
                    @removeItem="handleRemoveItem"
                    @clearCart="handleClearCart"
                    @openPaymentModal="isPaymentModalOpen = true"
                    @sendKot="handleSendKot"
                    @holdOrder="handleHoldOrder"
                    @openFocModal="isFocModalOpen = true"
                    @openDiscountModal="isDiscountModalOpen = true"
                    @removeDiscount="appliedDiscount = null"
                    @openTableModal="isTableModalOpen = true"
                    @openCustomerModal="isCustomerModalOpen = true"
                />
            </div>

            <!-- Terminal Workspace Gatekeeper (Blocks all catalog & cart activity until Day and Shift are open) -->
            <div
                v-if="isTerminalLocked"
                class="absolute inset-0 z-30 bg-slate-900/40 backdrop-blur-xs flex items-center justify-center p-4 animate-fade-in"
            >
                <div class="bg-white dark:bg-slate-900 rounded-3xl p-8 max-w-md w-full shadow-2xl border border-slate-200 dark:border-slate-800 text-center flex flex-col items-center space-y-4">
                    <!-- Icon badge -->
                    <div
                        class="w-16 h-16 rounded-2xl flex items-center justify-center text-3xl shadow-lg"
                        :class="!activeBusinessDay ? 'bg-rose-100 dark:bg-rose-950/80 text-rose-600 border border-rose-200 dark:border-rose-800' : 'bg-amber-100 dark:bg-amber-950/80 text-amber-600 border border-amber-200 dark:border-amber-800'"
                    >
                        <span v-if="!activeBusinessDay">📅</span>
                        <span v-else>🟡</span>
                    </div>

                    <!-- Status pill -->
                    <div
                        class="px-3 py-1 rounded-full text-xs font-black uppercase tracking-wider flex items-center space-x-1.5"
                        :class="!activeBusinessDay ? 'bg-rose-100 dark:bg-rose-950/60 text-rose-700 dark:text-rose-400 border border-rose-200 dark:border-rose-800' : 'bg-amber-100 dark:bg-amber-950/60 text-amber-700 dark:text-amber-400 border border-amber-200 dark:border-amber-800'"
                    >
                        <span class="w-2 h-2 rounded-full animate-ping" :class="!activeBusinessDay ? 'bg-rose-500' : 'bg-amber-500'"></span>
                        <span>{{ !activeBusinessDay ? 'Business Day Closed' : 'No Active Shift' }}</span>
                    </div>

                    <!-- Title & Description -->
                    <div>
                        <h2 class="text-lg font-black text-slate-800 dark:text-slate-100">
                            {{ !activeBusinessDay ? 'Branch Business Day Required' : 'Cashier Shift Required' }}
                        </h2>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-2 leading-relaxed">
                            <template v-if="!activeBusinessDay">
                                The accounting business day for <strong class="text-slate-700 dark:text-slate-300">{{ currentBranch?.name || 'this branch' }}</strong> has not been opened yet. You must open the business day before starting cashier shifts and taking orders.
                            </template>
                            <template v-else>
                                You do not have an active shift for today (<strong class="text-slate-700 dark:text-slate-300">{{ businessDateFormatted }}</strong>). Please start your shift and record your opening cash float to begin taking orders.
                            </template>
                        </p>
                    </div>

                    <!-- Primary Action Button -->
                    <button
                        type="button"
                        @click="!activeBusinessDay ? (isOpenBusinessDayModalOpen = true) : (isStartShiftModalOpen = true)"
                        class="w-full pos-btn-primary !py-3 !px-6 text-sm font-black flex items-center justify-center space-x-2 shadow-lg cursor-pointer hover:scale-[1.02] transition-transform"
                    >
                        <span v-if="!activeBusinessDay">📅 Open Business Day</span>
                        <span v-else>🟡 Start Shift & Enter Float</span>
                    </button>

                    <p class="text-[11px] text-slate-400 dark:text-slate-500">
                        All ordering and punching activities are locked until the required day and shift are open.
                    </p>
                </div>
            </div>
        </div>

        <!-- ================= MODALS ================= -->
        <!-- Item Customizer Modal (Variations + Addons) -->
        <ItemCustomizerModal
            :isOpen="isCustomizerOpen"
            :item="customizerItem"
            :propertyGroups="propertyGroups"
            @close="isCustomizerOpen = false"
            @addToCart="handleAddToCart"
        />

        <!-- Table Selection Modal -->
        <TableSelectModal
            :isOpen="isTableModalOpen"
            :floors="floors"
            :selectedTableId="selectedTable?.id"
            @close="isTableModalOpen = false"
            @selectTable="(t) => { selectedTable = t; isTableModalOpen = false; }"
            @resumeTableOrder="(orderId) => { const o = activeOrders.find(ord => ord.id === orderId); if (o) handleResumeOrder(o); }"
        />

        <!-- Customer Directory Modal -->
        <CustomerModal
            :isOpen="isCustomerModalOpen"
            :customers="customers"
            :selectedCustomer="selectedCustomer"
            @close="isCustomerModalOpen = false"
            @selectCustomer="(c) => { selectedCustomer = c; if (c?.address && !deliveryAddress) { deliveryAddress = c.address; } }"
            @customerCreated="(c) => { customers.unshift(c); selectedCustomer = c; if (c?.address && !deliveryAddress) { deliveryAddress = c.address; } }"
        />

        <!-- Payment Settlement Modal -->
        <PaymentModal
            :isOpen="isPaymentModalOpen"
            :grandTotal="grandTotal"
            :paymentTypes="paymentTypes"
            :submitting="submitting"
            @close="isPaymentModalOpen = false"
            @confirmPayment="handleConfirmPayment"
            @cardBinEntered="handleCardBinEntered"
        />

        <!-- Held & Parked Orders Drawer/Modal -->
        <HeldOrdersModal
            :isOpen="isHeldOrdersModalOpen"
            :orders="activeOrders"
            @close="isHeldOrdersModalOpen = false"
            @resumeOrder="handleResumeOrder"
            @voidOrder="handleVoidOrder"
        />

        <!-- Submitted Orders Modal (Active Unsettled Orders) -->
        <SubmittedOrdersModal
            :isOpen="isSubmittedOrdersModalOpen"
            :branchId="currentBranchId"
            :orderTypes="orderTypes"
            :businessDate="businessDate"
            @close="isSubmittedOrdersModalOpen = false"
            @editOrder="handleEditSubmittedOrder"
            @settleOrder="handleSettleSubmittedOrder"
            @printKot="handlePrintKotFromSubmitted"
            @voidOrder="handleVoidOrder"
        />

        <!-- Settled Orders Modal (Completed Paid Archive) -->
        <SettledOrdersModal
            :isOpen="isSettledOrdersModalOpen"
            :branchId="currentBranchId"
            :paymentTypes="paymentTypes"
            :orderTypes="orderTypes"
            :businessDate="businessDate"
            @close="isSettledOrdersModalOpen = false"
            @reprintBill="handleReprintFromSettled"
        />

        <!-- Thermal Receipt Print Modal (Customer Invoice & KOT) -->
        <ThermalReceipt
            :isOpen="isReceiptModalOpen"
            :order="currentReceiptOrder"
            :type="currentReceiptType"
            @close="isReceiptModalOpen = false"
        />

        <!-- Discount & Voucher Modal -->
        <DiscountVoucherModal
            :isOpen="isDiscountModalOpen"
            :branchId="currentBranchId"
            :orderTypeId="currentOrderTypeId"
            :cart="cart"
            :subtotal="subtotal"
            :appliedDiscount="appliedDiscount"
            @close="isDiscountModalOpen = false"
            @applyDiscount="(d) => { appliedDiscount = d; }"
            @removeDiscount="appliedDiscount = null"
        />

        <!-- Free of Charge (FOC) Modal -->
        <FocModal
            :isOpen="isFocModalOpen"
            :subtotal="subtotal"
            :submitting="submitting"
            @close="isFocModalOpen = false"
            @confirmFoc="handleProcessFoc"
        />

        <!-- Business Day Open Modal -->
        <OpenBusinessDayModal
            :isOpen="isOpenBusinessDayModalOpen"
            :branchId="currentBranchId"
            :branchName="currentBranch?.name"
            @close="isOpenBusinessDayModalOpen = false"
            @dayOpened="handleDayOpened"
        />

        <!-- Business Day Close Modal (Day End & Z-Report) -->
        <CloseBusinessDayModal
            :isOpen="isCloseBusinessDayModalOpen"
            :branchId="currentBranchId"
            :branchName="currentBranch?.name"
            :activeBusinessDay="activeBusinessDay"
            @close="isCloseBusinessDayModalOpen = false"
            @dayClosed="handleDayClosed"
        />

        <!-- Start Shift Modal -->
        <StartShiftModal
            :isOpen="isStartShiftModalOpen"
            :branchId="currentBranchId"
            :branchName="currentBranch?.name"
            :activeBusinessDay="activeBusinessDay"
            :user="user"
            @close="isStartShiftModalOpen = false"
            @shiftStarted="handleShiftStarted"
            @openBusinessDayPrompt="isStartShiftModalOpen = false; isOpenBusinessDayModalOpen = true;"
        />

        <!-- Close Shift Modal (Drawer Reconciliation & X-Report) -->
        <CloseShiftModal
            :isOpen="isCloseShiftModalOpen"
            :shift="activeShift"
            :branchId="currentBranchId"
            :branchName="currentBranch?.name"
            @close="isCloseShiftModalOpen = false"
            @shiftClosed="handleShiftClosed"
            @openXReport="(r) => { activeXReportSummary = r; isShiftSummaryModalOpen = true; }"
        />

        <!-- Shift Summary / Thermal Print Modal (X-Report) -->
        <ShiftSummaryModal
            :isOpen="isShiftSummaryModalOpen"
            :summary="activeXReportSummary"
            @close="isShiftSummaryModalOpen = false"
        />
    </div>
</template>
