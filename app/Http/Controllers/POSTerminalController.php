<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use App\Models\PosOrder;
use App\Models\PosOrderItem;
use App\Models\PosOrderItemProperty;
use App\Models\PosOrderPayment;
use App\Models\Branch;
use App\Models\Menu;
use App\Models\FoodGroup;
use App\Models\FoodItem;
use App\Models\PropertyGroup;
use App\Models\PropertyItem;
use App\Models\OrderType;
use App\Models\Party;
use App\Models\PaymentType;
use App\Models\Floor;
use App\Models\Table;
use App\Models\Customer;
use App\Models\Discount;
use App\Models\InventoryLedger;
use App\Models\PosBusinessDay;
use App\Models\PosShift;
use App\Models\User;
use App\Models\Setting;

class POSTerminalController extends Controller
{
    /**
     * Main entry point for POS Terminal
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        
        // Find user branch or fallback to first active branch
        $userBranchId = $user->branch_id ?? null;
        if (!$userBranchId) {
            $firstBranch = Branch::where('is_active', true)->first();
            $userBranchId = $firstBranch?->id;
        }

        return Inertia::render('POS/Index', [
            'initialBranchId' => $userBranchId,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
        ]);
    }

    /**
     * Bootstrap all terminal data in a single optimized payload
     */
    public function bootstrap(Request $request)
    {
        $user = Auth::user();
        $branchId = (int)$request->get('branch_id');
        if (!$branchId) {
            $branchId = $user?->branch_id ?? Branch::where('is_active', true)->value('id');
        }

        // Branch authorization check (VULN-08)
        if ($user && !in_array($user->role, ['admin', 'manager', 'multi branch manager'])) {
            if ($user->branches()->exists() && !$user->branches()->where('branches.id', $branchId)->exists()) {
                abort(403, 'Unauthorized access to branch data.');
            }
        }

        $branches = Branch::where('is_active', true)->get([
            'id', 'name', 'phone', 'address', 
            'delivery_charges', 'service_charge_type', 'service_charge_value', 'table_order_types'
        ]);
        $currentBranch = Branch::with(['paymentTypes' => function ($q) {
            $q->where('payment_types.is_active', true)
              ->withPivot('tax_percentage');
        }])->find($branchId);

        // Branch menus
        $menus = Menu::where('is_active', true)
            ->whereHas('branches', function ($q) use ($branchId) {
                $q->where('branches.id', $branchId);
            })
            ->get(['id', 'name', 'tax_percentage', 'discount_percentage']);

        // Default menu
        $menuId = (int)$request->get('menu_id') ?: ($menus->first()?->id ?? null);
        $currentMenu = $menus->firstWhere('id', $menuId);

        // Food Categories & Items
        $foodGroups = FoodGroup::where('is_active', true)->get(['id', 'name']);
        
        $foodItems = [];
        if ($branchId && $menuId) {
            $items = FoodItem::forBranchAndMenu($branchId, $menuId)->get();
            $foodItems = $items->map(function ($item) use ($branchId, $menuId) {
                return [
                    'id' => $item->id,
                    'name' => $item->name,
                    'description' => $item->description,
                    'food_group_id' => $item->food_group_id,
                    'food_group_name' => $item->foodGroup?->name,
                    'base_price' => (float)$item->base_price,
                    'effective_price' => $item->getEffectivePriceFor($branchId, $menuId),
                    'variations' => $item->variations->map(fn($v) => [
                        'id' => $v->id,
                        'variation_id' => $v->variation_id,
                        'name' => $v->variation?->name,
                        'price' => (float)$v->price,
                        'sku' => $v->sku,
                    ]),
                    'is_active' => (bool)$item->is_active,
                ];
            });
        }

        // Addon Property Groups
        $propertyGroups = PropertyGroup::where('is_active', true)
            ->with(['propertyItems' => fn($q) => $q->where('is_active', true)])
            ->get();

        // Order Types & linked parties
        $orderTypes = OrderType::where('is_active', true)
            ->with(['parties' => fn($q) => $q->where('parties.is_active', true)])
            ->get();

        // Delivery Parties
        $parties = Party::where('is_active', true)->get();

        // Payment Types with branch tax rates
        $paymentTypes = PaymentType::where('is_active', true)->get()->map(function ($pt) use ($currentBranch) {
            $branchPt = $currentBranch?->paymentTypes->firstWhere('id', $pt->id);
            return [
                'id' => $pt->id,
                'name' => $pt->name,
                'tax_percentage' => $branchPt && $branchPt->pivot ? (float)$branchPt->pivot->tax_percentage : 0,
            ];
        });

        // Floors & Tables with dynamic occupancy status
        $floors = Floor::where('branch_id', $branchId)
            ->where('is_active', true)
            ->with(['tables' => fn($q) => $q->where('is_active', true)])
            ->get();

        // Active orders on tables to mark them as occupied
        $activeTableOrders = PosOrder::where('branch_id', $branchId)
            ->whereNotNull('table_id')
            ->whereNotIn('status', ['completed', 'cancelled'])
            ->get(['id', 'order_number', 'table_id', 'grand_total', 'status', 'created_at']);

        $occupiedTableIds = $activeTableOrders->pluck('table_id')->unique()->toArray();

        $floors = $floors->map(function ($floor) use ($occupiedTableIds, $activeTableOrders) {
            $floor->tables = $floor->tables->map(function ($table) use ($occupiedTableIds, $activeTableOrders) {
                $isOccupied = in_array($table->id, $occupiedTableIds);
                $activeOrder = $isOccupied ? $activeTableOrders->firstWhere('table_id', $table->id) : null;
                return [
                    'id' => $table->id,
                    'floor_id' => $table->floor_id,
                    'name' => $table->name,
                    'capacity' => $table->capacity,
                    'is_occupied' => $isOccupied,
                    'active_order' => $activeOrder,
                ];
            });
            return $floor;
        });

        // Active/Held orders for this branch
        $activeOrders = PosOrder::where('branch_id', $branchId)
            ->whereNotIn('status', ['completed', 'cancelled'])
            ->with(['items.foodItem', 'items.variation', 'items.properties', 'table', 'customer', 'orderType', 'party'])
            ->latest()
            ->take(20)
            ->get();

        // Customers list
        $customers = Customer::where('is_active', true)
            ->latest()
            ->take(50)
            ->get(['id', 'name', 'phone', 'address', 'credit_limit']);

        // Count unsettled submitted orders
        $submittedOrdersCount = PosOrder::where('branch_id', $branchId)
            ->whereIn('status', ['submitted', 'preparing', 'ready', 'delivered'])
            ->where('payment_status', 'unpaid')
            ->count();

        // Active Business Day for Branch
        $activeBusinessDay = PosBusinessDay::where('branch_id', $branchId)
            ->where('status', 'open')
            ->with('openedBy')
            ->latest('id')
            ->first();

        // Active Shift for logged-in user at this branch
        $activeShift = $user ? PosShift::where('branch_id', $branchId)
            ->where('user_id', $user->id)
            ->where('status', 'open')
            ->with('user')
            ->latest('id')
            ->first() : null;

        $businessDate = $activeBusinessDay ? $activeBusinessDay->business_date->toDateString() : now()->toDateString();
        $businessDay = $activeBusinessDay ? $activeBusinessDay->business_date->format('l') : now()->format('l');
        $businessDateFormatted = $activeBusinessDay ? $activeBusinessDay->business_date->format('d M Y') : now()->format('d M Y');

        // Resolve order types that require dining table selection (Branch Override -> Global POS Setting -> Dine-in default)
        $branchTableOrderTypes = $currentBranch?->table_order_types;
        if ($branchTableOrderTypes !== null && is_array($branchTableOrderTypes)) {
            $tableOrderTypeIds = array_values(array_map('intval', $branchTableOrderTypes));
        } else {
            $globalSetting = Setting::where('key', 'table_required_order_types')->value('value');
            if ($globalSetting !== null && $globalSetting !== '') {
                $decoded = is_string($globalSetting) ? json_decode($globalSetting, true) : $globalSetting;
                $tableOrderTypeIds = is_array($decoded) ? array_values(array_map('intval', $decoded)) : [];
            } else {
                $tableOrderTypeIds = OrderType::where('is_active', true)
                    ->where(fn($q) => $q->where('code', 'dine_in')->orWhere('name', 'like', '%dine%'))
                    ->pluck('id')
                    ->map(fn($id) => (int)$id)
                    ->values()
                    ->toArray();
            }
        }

        return response()->json([
            'branches' => $branches,
            'current_branch' => $currentBranch,
            'menus' => $menus,
            'current_menu' => $currentMenu,
            'food_groups' => $foodGroups,
            'food_items' => $foodItems,
            'property_groups' => $propertyGroups,
            'order_types' => $orderTypes,
            'table_order_type_ids' => $tableOrderTypeIds,
            'parties' => $parties,
            'payment_types' => $paymentTypes,
            'floors' => $floors,
            'active_orders' => $activeOrders,
            'submitted_orders_count' => $submittedOrdersCount,
            'customers' => $customers,
            'business_date' => $businessDate,
            'business_day' => $businessDay,
            'business_date_formatted' => $businessDateFormatted,
            'active_business_day' => $activeBusinessDay ? [
                'id' => $activeBusinessDay->id,
                'business_date' => $activeBusinessDay->business_date->toDateString(),
                'business_date_formatted' => $activeBusinessDay->business_date->format('d M Y'),
                'business_day' => $activeBusinessDay->business_date->format('l'),
                'opened_at' => $activeBusinessDay->opened_at->toDateTimeString(),
                'opened_by' => $activeBusinessDay->openedBy?->name,
                'status' => $activeBusinessDay->status,
                'notes' => $activeBusinessDay->notes,
            ] : null,
            'active_shift' => $activeShift ? [
                'id' => $activeShift->id,
                'shift_number' => $activeShift->shift_number,
                'opening_float' => (float)$activeShift->opening_float,
                'opened_at' => $activeShift->opened_at->toDateTimeString(),
                'opened_at_time' => $activeShift->opened_at->format('h:i A'),
                'status' => $activeShift->status,
                'user_id' => $activeShift->user_id,
                'user_name' => $activeShift->user?->name,
                'notes' => $activeShift->notes,
            ] : null,
            'can_manage_business_day' => $user->role === 'admin',
        ]);
    }

    /**
     * Get items dynamically when Menu or Branch is changed
     */
    public function getItems(Request $request)
    {
        $validated = $request->validate([
            'branch_id' => 'required|exists:branches,id',
            'menu_id' => 'required|exists:menus,id',
        ]);

        $branchId = (int)$validated['branch_id'];
        $menuId = (int)$validated['menu_id'];

        $items = FoodItem::forBranchAndMenu($branchId, $menuId)->get();

        $formatted = $items->map(function ($item) use ($branchId, $menuId) {
            return [
                'id' => $item->id,
                'name' => $item->name,
                'description' => $item->description,
                'food_group_id' => $item->food_group_id,
                'food_group_name' => $item->foodGroup?->name,
                'base_price' => (float)$item->base_price,
                'effective_price' => $item->getEffectivePriceFor($branchId, $menuId),
                'variations' => $item->variations->map(fn($v) => [
                    'id' => $v->id,
                    'variation_id' => $v->variation_id,
                    'name' => $v->variation?->name,
                    'price' => (float)$v->price,
                    'sku' => $v->sku,
                ]),
                'is_active' => (bool)$item->is_active,
            ];
        });

        return response()->json([
            'items' => $formatted,
            'count' => $formatted->count(),
        ]);
    }

    /**
     * Create or update a POS Order
     */
    public function storeOrder(Request $request)
    {
        $validated = $request->validate([
            'order_id' => 'nullable|exists:pos_orders,id',
            'branch_id' => 'required|exists:branches,id',
            'order_type_id' => 'required|exists:order_types,id',
            'table_id' => 'nullable|exists:tables,id',
            'party_id' => 'nullable|exists:parties,id',
            'customer_id' => 'nullable|exists:customers,id',
            'items' => 'required|array|min:1',
            'items.*.food_item_id' => 'required|exists:food_items,id',
            'items.*.variation_id' => 'nullable|exists:variations,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.total_price' => 'required|numeric|min:0',
            'items.*.notes' => 'nullable|string',
            'items.*.properties' => 'nullable|array',
            'items.*.properties.*.property_item_id' => 'nullable|exists:property_items,id',
            'items.*.properties.*.property_name' => 'required|string',
            'items.*.properties.*.extra_price' => 'required|numeric|min:0',
            'items.*.properties.*.quantity' => 'nullable|integer|min:1',
            
            'subtotal' => 'required|numeric|min:0',
            'discount_id' => 'nullable|exists:discounts,id',
            'discount_type' => 'nullable|in:fixed,percentage',
            'discount_value' => 'nullable|numeric|min:0',
            'tax_amount' => 'required|numeric|min:0',
            'service_charges' => 'nullable|numeric|min:0',
            'delivery_charges' => 'nullable|numeric|min:0',
            'grand_total' => 'required|numeric|min:0',
            'notes' => 'nullable|string',
            'delivery_address' => 'nullable|string',
            'rider_name' => 'nullable|string',
            'card_bin' => 'nullable|string|max:10',
            'status' => 'nullable|in:pending,submitted,preparing,ready,delivered,completed,cancelled',
            'is_submitted' => 'nullable|boolean',
            'is_held' => 'nullable|boolean',
            
            'is_kot' => 'nullable|boolean',
            'is_paid' => 'nullable|boolean',
            'is_foc' => 'nullable|boolean',
            'foc_reason' => 'nullable|string|max:255',
            'payments' => 'nullable|array',
            'payments.*.payment_type_id' => 'required_with:payments|exists:payment_types,id',
            'payments.*.amount' => 'required_with:payments|numeric|min:0',
            'payments.*.tendered' => 'nullable|numeric|min:0',
            'payments.*.change_returned' => 'nullable|numeric|min:0',
            'payments.*.transaction_reference' => 'nullable|string',
            'payments.*.card_last_four' => 'nullable|string|max:4',
            'payments.*.card_bank' => 'nullable|string',
        ]);

        return DB::transaction(function () use ($validated, $request) {
            $user = Auth::user();

            // Branch authorization check (VULN-08)
            if ($user && !in_array($user->role, ['admin', 'manager', 'multi branch manager'])) {
                if ($user->branches()->exists() && !$user->branches()->where('branches.id', $validated['branch_id'])->exists()) {
                    abort(403, 'Unauthorized access to branch data.');
                }
            }

            $orderId = $validated['order_id'] ?? null;
            $isPaid = (bool)($validated['is_paid'] ?? false);
            $isKot = (bool)($validated['is_kot'] ?? false);
            $isFoc = (bool)($validated['is_foc'] ?? false);
            $focReason = $validated['foc_reason'] ?? 'Complimentary VIP';

            // FOC Authorization Verification (VULN-04)
            if ($isFoc && !in_array($user->role, ['admin', 'manager'])) {
                $managerPin = $request->input('manager_pin') ?? $request->input('manager_password');
                $managerEmail = $request->input('manager_email');
                $manager = null;
                if ($managerEmail) {
                    $manager = User::where('email', $managerEmail)->whereIn('role', ['admin', 'manager'])->where('is_active', true)->first();
                }
                if (!$manager || !Hash::check($managerPin, $manager->password)) {
                    abort(403, 'Manager authorization required for Free of Charge (FOC) orders.');
                }
            }

            // Server-Side Item Price & Total Tampering Prevention (VULN-02)
            $computedSubtotal = 0;
            $verifiedItems = [];

            foreach ($validated['items'] as $itemData) {
                $foodItem = FoodItem::with(['branches', 'menus', 'variations'])->find($itemData['food_item_id']);
                if (!$foodItem) {
                    abort(422, "Food item #{$itemData['food_item_id']} not found.");
                }

                $catalogUnitPrice = null;
                if (!empty($itemData['variation_id'])) {
                    $itemVariation = FoodItemVariation::where('food_item_id', $foodItem->id)
                        ->where('variation_id', $itemData['variation_id'])
                        ->first();
                    if ($itemVariation && (float)$itemVariation->price > 0) {
                        $catalogUnitPrice = (float)$itemVariation->price;
                    }
                }

                if ($catalogUnitPrice === null) {
                    $catalogUnitPrice = $foodItem->getEffectivePriceFor($validated['branch_id'], $request->get('menu_id'));
                    if ($catalogUnitPrice <= 0 && (float)$foodItem->base_price > 0) {
                        $catalogUnitPrice = (float)$foodItem->base_price;
                    }
                }

                $unitPrice = (float)$itemData['unit_price'];
                if ($catalogUnitPrice > 0 && !in_array($user->role, ['admin', 'manager'])) {
                    if ($unitPrice < round($catalogUnitPrice - 0.05, 2)) {
                        abort(422, "Price tampering detected for item '{$foodItem->name}'. Catalog price: Rs. " . number_format($catalogUnitPrice, 2));
                    }
                }

                $extraPropsPrice = 0;
                $verifiedProps = [];
                if (!empty($itemData['properties'])) {
                    foreach ($itemData['properties'] as $prop) {
                        $propExtra = (float)($prop['extra_price'] ?? 0);
                        if (!empty($prop['property_item_id'])) {
                            $propItem = PropertyItem::find($prop['property_item_id']);
                            if ($propItem && (float)$propItem->extra_price > 0) {
                                $propExtra = (float)$propItem->extra_price;
                            }
                        }
                        $propQty = (int)($prop['quantity'] ?? 1);
                        $extraPropsPrice += ($propExtra * $propQty);
                        $verifiedProps[] = array_merge($prop, ['extra_price' => $propExtra]);
                    }
                }

                $itemQty = (int)$itemData['quantity'];
                $itemTotal = ($unitPrice + $extraPropsPrice) * $itemQty;
                $computedSubtotal += $itemTotal;

                $verifiedItems[] = array_merge($itemData, [
                    'unit_price' => $unitPrice,
                    'total_price' => $itemTotal,
                    'properties' => $verifiedProps,
                ]);
            }

            $discountVal = (float)($validated['discount_value'] ?? 0);
            $taxAmt = (float)($validated['tax_amount'] ?? 0);
            $servCharges = (float)($validated['service_charges'] ?? 0);
            $delCharges = (float)($validated['delivery_charges'] ?? 0);
            $expectedGrandTotal = max(0, $computedSubtotal - $discountVal + $taxAmt + $servCharges + $delCharges);

            if (!in_array($user->role, ['admin', 'manager']) && round((float)$validated['subtotal'], 2) < round($computedSubtotal - 0.05, 2)) {
                abort(422, 'Order subtotal does not match item totals.');
            }

            if (!$isFoc && !in_array($user->role, ['admin', 'manager'])) {
                if (round((float)$validated['grand_total'], 2) < round($expectedGrandTotal - 0.05, 2)) {
                    abort(422, 'Order grand total does not match calculated totals.');
                }
            }

            $activeBusinessDay = PosBusinessDay::where('branch_id', $validated['branch_id'])
                ->where('status', 'open')
                ->latest('id')
                ->first();

            $activeShift = PosShift::where('branch_id', $validated['branch_id'])
                ->where('user_id', $user->id)
                ->where('status', 'open')
                ->latest('id')
                ->first();

            if ($orderId) {
                $order = PosOrder::findOrFail($orderId);
                if ($order->status === 'completed' || $order->payment_status === 'paid') {
                    abort(422, 'Settled orders cannot be modified.');
                }
                // Clean existing items if updating active order
                $order->items()->delete();
                if (!$order->pos_business_day_id && $activeBusinessDay) {
                    $order->pos_business_day_id = $activeBusinessDay->id;
                }
                if (!$order->pos_shift_id && $activeShift) {
                    $order->pos_shift_id = $activeShift->id;
                }
            } else {
                // Generate Daily Rolling Token Number (e.g., T-001) for this branch
                $todayOrdersCount = PosOrder::where('branch_id', $validated['branch_id'])
                    ->whereDate('created_at', now()->toDateString())
                    ->count();
                $tokenNumber = 'T-' . str_pad($todayOrdersCount + 1, 3, '0', STR_PAD_LEFT);

                // Generate Unique System-wide Order Number (e.g., ORD-20261024-B1-0001)
                $branchPrefix = 'B' . $validated['branch_id'];
                $orderSeq = PosOrder::whereDate('created_at', now()->toDateString())->count() + 1;
                $orderNumber = 'ORD-' . date('Ymd') . '-' . $branchPrefix . '-' . str_pad($orderSeq, 4, '0', STR_PAD_LEFT);
                while (PosOrder::where('order_number', $orderNumber)->exists()) {
                    $orderSeq++;
                    $orderNumber = 'ORD-' . date('Ymd') . '-' . $branchPrefix . '-' . str_pad($orderSeq, 4, '0', STR_PAD_LEFT);
                }

                $order = new PosOrder();
                $order->order_number = $orderNumber;
                $order->token_number = $tokenNumber;
                $order->branch_id = $validated['branch_id'];
                $order->user_id = $user->id;
                $order->pos_business_day_id = $activeBusinessDay?->id;
                $order->pos_shift_id = $activeShift?->id;
            }

            $order->order_type_id = $validated['order_type_id'];
            $order->table_id = $validated['table_id'] ?? null;
            $order->party_id = $validated['party_id'] ?? null;
            $order->customer_id = $validated['customer_id'] ?? null;
            
            $order->subtotal = !in_array($user->role, ['admin', 'manager']) ? $computedSubtotal : $validated['subtotal'];
            $order->discount_id = $validated['discount_id'] ?? null;
            $order->discount_type = $validated['discount_type'] ?? null;
            $order->discount_value = $validated['discount_value'] ?? 0;
            $order->tax_amount = $validated['tax_amount'];
            $order->service_charges = $validated['service_charges'] ?? 0;
            $order->delivery_charges = $validated['delivery_charges'] ?? 0;
            $order->grand_total = (!in_array($user->role, ['admin', 'manager']) && !$isFoc) ? $expectedGrandTotal : $validated['grand_total'];
            $order->notes = $validated['notes'] ?? null;
            $order->delivery_address = $validated['delivery_address'] ?? null;
            $order->rider_name = $validated['rider_name'] ?? null;
            $order->card_bin = $validated['card_bin'] ?? null;

            if ($isKot) {
                if (!$order->kot_number) {
                    $todayKotCount = PosOrder::where('branch_id', $validated['branch_id'])
                        ->whereNotNull('kot_number')
                        ->whereDate('created_at', now()->toDateString())
                        ->count();
                    $order->kot_number = 'KOT-' . str_pad($todayKotCount + 1, 3, '0', STR_PAD_LEFT);
                }
                $order->kot_printed_at = now();
            }

            if ($isFoc) {
                $order->discount_type = 'percentage';
                $order->discount_value = $order->subtotal;
                $order->grand_total = 0;
                $order->payment_status = 'paid';
                $order->status = 'completed';
                $order->notes = ($order->notes ? $order->notes . ' | ' : '') . 'FOC: ' . $focReason;
            } elseif ($isPaid) {
                $order->payment_status = 'paid';
                $order->status = 'completed';
            } else {
                $isHeld = (bool)($request->input('is_held', false) || ($validated['notes'] ?? '') === 'Held / Parked Order');
                if ($request->filled('status')) {
                    $order->status = $request->input('status');
                } elseif ($isHeld) {
                    $order->status = 'pending';
                } elseif ($request->input('is_submitted', false)) {
                    $order->status = 'submitted';
                } elseif ($isKot) {
                    $order->status = 'preparing';
                } else {
                    $order->status = 'pending';
                }
                $order->payment_status = 'unpaid';
            }

            $order->save();

            // Save order items & properties
            foreach ($verifiedItems as $itemData) {
                $orderItem = $order->items()->create([
                    'food_item_id' => $itemData['food_item_id'],
                    'variation_id' => $itemData['variation_id'] ?? null,
                    'quantity' => $itemData['quantity'],
                    'unit_price' => $itemData['unit_price'],
                    'total_price' => $itemData['total_price'],
                    'notes' => $itemData['notes'] ?? null,
                ]);

                if (!empty($itemData['properties'])) {
                    foreach ($itemData['properties'] as $prop) {
                        $orderItem->properties()->create([
                            'property_item_id' => $prop['property_item_id'] ?? null,
                            'property_name' => $prop['property_name'],
                            'extra_price' => $prop['extra_price'] ?? 0,
                            'quantity' => $prop['quantity'] ?? 1,
                        ]);
                    }
                }
            }

            // Save payments if FOC or paid
            if ($isFoc) {
                $order->payments()->delete();
                $focType = PaymentType::where('name', 'like', '%foc%')
                    ->orWhere('name', 'like', '%complimentary%')
                    ->first() ?? PaymentType::first();

                if ($focType) {
                    $order->payments()->create([
                        'payment_type_id' => $focType->id,
                        'amount' => 0,
                        'tendered' => 0,
                        'change_returned' => 0,
                        'transaction_reference' => 'FOC: ' . $focReason,
                        'pos_shift_id' => $activeShift?->id ?? $order->pos_shift_id,
                        'user_id' => $user->id,
                    ]);
                }

                $order->tendered_amount = 0;
                $order->change_amount = 0;
                $order->save();

                // Deduct inventory ledgers upon completion (Rule 7)
                $this->deductInventoryLedger($order);
            } elseif ($isPaid && !empty($validated['payments'])) {
                $order->payments()->delete();
                $totalTendered = 0;
                $totalChange = 0;

                foreach ($validated['payments'] as $pay) {
                    $order->payments()->create([
                        'payment_type_id' => $pay['payment_type_id'],
                        'amount' => $pay['amount'],
                        'tendered' => $pay['tendered'] ?? null,
                        'change_returned' => $pay['change_returned'] ?? 0,
                        'transaction_reference' => $pay['transaction_reference'] ?? null,
                        'card_last_four' => $pay['card_last_four'] ?? null,
                        'card_bank' => $pay['card_bank'] ?? null,
                        'pos_shift_id' => $activeShift?->id ?? $order->pos_shift_id,
                        'user_id' => $user->id,
                    ]);
                    $totalTendered += ($pay['tendered'] ?? $pay['amount']);
                    $totalChange += ($pay['change_returned'] ?? 0);
                }

                $order->tendered_amount = $totalTendered;
                $order->change_amount = $totalChange;
                $order->save();

                // Deduct inventory ledgers upon completion (Rule 7)
                $this->deductInventoryLedger($order);
            }

            $order->load(['items.foodItem', 'items.variation', 'items.properties', 'table', 'customer', 'orderType', 'party', 'payments.paymentType']);

            return response()->json([
                'success' => true,
                'message' => $isFoc ? 'FOC order completed successfully' : ($isPaid ? 'Order completed and paid successfully' : ($orderId ? 'Order updated and resubmitted successfully' : 'Order submitted successfully')),
                'order' => $order,
            ]);
        });
    }

    /**
     * Settle payment for an existing open/held/dine-in order
     */
    public function settlePayment(Request $request, PosOrder $order)
    {
        $user = Auth::user();
        if ($user && !in_array($user->role, ['admin', 'manager', 'multi branch manager'])) {
            if ($user->branches()->exists() && !$user->branches()->where('branches.id', $order->branch_id)->exists()) {
                abort(403, 'Unauthorized access to orders in this branch.');
            }
        }

        if ($order->status === 'completed' && $order->payment_status === 'paid') {
            return response()->json([
                'success' => false,
                'message' => 'Order is already settled and paid.',
                'order' => $order,
            ], 422);
        }

        $validated = $request->validate([
            'payments' => 'required|array|min:1',
            'payments.*.payment_type_id' => 'required|exists:payment_types,id',
            'payments.*.amount' => 'required|numeric|min:0',
            'payments.*.tendered' => 'nullable|numeric|min:0',
            'payments.*.change_returned' => 'nullable|numeric|min:0',
            'payments.*.transaction_reference' => 'nullable|string',
            'payments.*.card_last_four' => 'nullable|string|max:4',
            'payments.*.card_bank' => 'nullable|string',
            'card_bin' => 'nullable|string|max:10',
            'party_id' => 'nullable|exists:parties,id',
            'rider_name' => 'nullable|string',
            'delivery_address' => 'nullable|string',
            'delivery_charges' => 'nullable|numeric|min:0',
        ]);

        return DB::transaction(function () use ($validated, $order, $request, $user) {
            $activeShift = PosShift::where('branch_id', $order->branch_id)
                ->where('user_id', $user->id)
                ->where('status', 'open')
                ->latest('id')
                ->first();

            $totalPaid = 0;
            $totalTendered = 0;
            $totalChange = 0;

            $order->payments()->delete();

            foreach ($validated['payments'] as $pay) {
                $order->payments()->create([
                    'payment_type_id' => $pay['payment_type_id'],
                    'amount' => $pay['amount'],
                    'tendered' => $pay['tendered'] ?? null,
                    'change_returned' => $pay['change_returned'] ?? 0,
                    'transaction_reference' => $pay['transaction_reference'] ?? null,
                    'card_last_four' => $pay['card_last_four'] ?? null,
                    'card_bank' => $pay['card_bank'] ?? null,
                    'pos_shift_id' => $activeShift?->id ?? $order->pos_shift_id,
                    'user_id' => $user->id,
                ]);
                $totalPaid += (float)$pay['amount'];
                $totalTendered += (float)($pay['tendered'] ?? $pay['amount']);
                $totalChange += (float)($pay['change_returned'] ?? 0);
            }

            if (!empty($validated['card_bin'])) {
                $order->card_bin = $validated['card_bin'];
            }

            if ($request->filled('party_id')) {
                $order->party_id = $validated['party_id'];
            }
            if ($request->filled('rider_name')) {
                $order->rider_name = $validated['rider_name'];
            }
            if ($request->filled('delivery_address')) {
                $order->delivery_address = $validated['delivery_address'];
            }
            if ($request->filled('delivery_charges')) {
                $oldDel = (float)$order->delivery_charges;
                $newDel = (float)$validated['delivery_charges'];
                $order->delivery_charges = $newDel;
                $order->grand_total = max(0, (float)$order->grand_total - $oldDel + $newDel);
            }

            if (round($totalPaid, 2) < round((float)$order->grand_total - 0.05, 2)) {
                abort(422, 'Insufficient payment amount tendered to settle order.');
            }

            $order->tendered_amount = $totalTendered;
            $order->change_amount = $totalChange;
            $order->payment_status = 'paid';
            $order->status = 'completed';
            $order->save();

            // Deduct recipe ingredients via central inventory_ledgers (Rule 7)
            $this->deductInventoryLedger($order);

            $order->load(['items.foodItem', 'items.variation', 'items.properties', 'table', 'customer', 'orderType', 'party', 'payments.paymentType', 'branch']);

            return response()->json([
                'success' => true,
                'message' => 'Payment settled successfully.',
                'order' => $order,
            ]);
        });
    }

    public function focOrder(Request $request, PosOrder $order)
    {
        $user = Auth::user();
        if ($user && !in_array($user->role, ['admin', 'manager', 'multi branch manager'])) {
            if ($user->branches()->exists() && !$user->branches()->where('branches.id', $order->branch_id)->exists()) {
                abort(403, 'Unauthorized access to orders in this branch.');
            }
        }

        $validated = $request->validate([
            'foc_reason' => 'required|string|max:255',
            'manager_email' => 'nullable|string|email',
            'manager_pin' => 'nullable|string',
            'manager_password' => 'nullable|string',
        ]);

        if (!in_array($user->role, ['admin', 'manager'])) {
            $managerPin = $request->input('manager_pin') ?? $request->input('manager_password');
            $managerEmail = $request->input('manager_email');
            $manager = null;
            if ($managerEmail) {
                $manager = User::where('email', $managerEmail)->whereIn('role', ['admin', 'manager'])->where('is_active', true)->first();
            }
            if (!$manager || !Hash::check($managerPin, $manager->password)) {
                abort(403, 'Manager authorization required for Free of Charge (FOC) orders.');
            }
        }

        return DB::transaction(function () use ($validated, $order, $user) {
            $focReason = $validated['foc_reason'];

            $order->discount_type = 'percentage';
            $order->discount_value = $order->subtotal;
            $order->grand_total = 0;
            $order->payment_status = 'paid';
            $order->status = 'completed';
            $order->notes = ($order->notes ? $order->notes . ' | ' : '') . 'FOC: ' . $focReason;
            $order->tendered_amount = 0;
            $order->change_amount = 0;
            $order->save();

            $order->payments()->delete();
            $focType = PaymentType::where('name', 'like', '%foc%')
                ->orWhere('name', 'like', '%complimentary%')
                ->first() ?? PaymentType::first();

            $user = Auth::user();
            $activeShift = PosShift::where('branch_id', $order->branch_id)
                ->where('user_id', $user->id)
                ->where('status', 'open')
                ->latest('id')
                ->first();

            if ($focType) {
                $order->payments()->create([
                    'payment_type_id' => $focType->id,
                    'amount' => 0,
                    'tendered' => 0,
                    'change_returned' => 0,
                    'transaction_reference' => 'FOC: ' . $focReason,
                    'pos_shift_id' => $activeShift?->id ?? $order->pos_shift_id,
                    'user_id' => $user->id,
                ]);
            }

            // Deduct recipe ingredients via central inventory_ledgers (Rule 7)
            $this->deductInventoryLedger($order);

            $order->load(['items.foodItem', 'items.variation', 'items.properties', 'table', 'customer', 'orderType', 'party', 'payments.paymentType', 'branch']);

            return response()->json([
                'success' => true,
                'message' => 'Order processed as Free of Charge (FOC) successfully.',
                'order' => $order,
            ]);
        });
    }

    /**
     * Send or reprint Kitchen Order Ticket (KOT)
     */
    public function printKot(PosOrder $order)
    {
        $user = Auth::user();
        if ($user && !in_array($user->role, ['admin', 'manager', 'multi branch manager'])) {
            if ($user->branches()->exists() && !$user->branches()->where('branches.id', $order->branch_id)->exists()) {
                abort(403, 'Unauthorized access to orders in this branch.');
            }
        }

        if (!$order->kot_number) {
            $todayKotCount = PosOrder::where('branch_id', $order->branch_id)
                ->whereNotNull('kot_number')
                ->whereDate('created_at', now()->toDateString())
                ->count();
            $order->kot_number = 'KOT-' . str_pad($todayKotCount + 1, 3, '0', STR_PAD_LEFT);
        }
        $order->kot_printed_at = now();
        $order->status = 'preparing';
        $order->save();

        $order->load(['items.foodItem', 'items.variation', 'items.properties', 'table', 'orderType']);

        return response()->json([
            'success' => true,
            'message' => 'KOT sent to kitchen.',
            'order' => $order,
        ]);
    }

    /**
     * Cancel / Void an order with reason
     */
    public function voidOrder(Request $request, PosOrder $order)
    {
        $user = Auth::user();
        if ($user && !in_array($user->role, ['admin', 'manager', 'multi branch manager'])) {
            if ($user->branches()->exists() && !$user->branches()->where('branches.id', $order->branch_id)->exists()) {
                abort(403, 'Unauthorized access to orders in this branch.');
            }
        }

        $validated = $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        return DB::transaction(function () use ($validated, $order) {
            $previousStatus = $order->status;
            $order->status = 'cancelled';
            $order->notes = ($order->notes ? $order->notes . " | " : "") . "Voided: " . $validated['reason'];
            $order->save();

            // If it was completed earlier, reverse inventory ledger
            if ($previousStatus === 'completed') {
                $alreadyReversed = InventoryLedger::where('document_type', PosOrder::class)
                    ->where('document_id', $order->id)
                    ->where('quantity', '>', 0)
                    ->exists();

                if (!$alreadyReversed) {
                    $ledgers = InventoryLedger::where('document_type', PosOrder::class)
                        ->where('document_id', $order->id)
                        ->where('quantity', '<', 0)
                        ->get();

                    foreach ($ledgers as $ledger) {
                        InventoryLedger::create([
                            'branch_id' => $ledger->branch_id,
                            'document_type' => PosOrder::class,
                            'document_id' => $order->id,
                            'item_id' => $ledger->item_id,
                            'quantity' => abs($ledger->quantity), // Positive to restore
                            'unit_price' => $ledger->unit_price,
                            'document_date' => now()->toDateString(),
                            'notes' => "Reversal for Voided POS Order #{$order->order_number}",
                            'created_by' => Auth::id() ?? $order->user_id,
                        ]);
                    }
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Order voided successfully.',
            ]);
        });
    }

    /**
     * Get active/unsettled submitted orders for cashier and waiters
     */
    public function getSubmittedOrders(Request $request)
    {
        $user = Auth::user();
        $branchId = (int)$request->get('branch_id');
        if (!$branchId) {
            $branchId = $user->branch_id ?? Branch::where('is_active', true)->value('id');
        }

        if ($user && !in_array($user->role, ['admin', 'manager', 'multi branch manager'])) {
            if ($user->branches()->exists() && !$user->branches()->where('branches.id', $branchId)->exists()) {
                abort(403, 'Unauthorized access to branch orders.');
            }
        }

        $query = PosOrder::where('branch_id', $branchId)
            ->whereIn('status', ['submitted', 'preparing', 'ready', 'delivered'])
            ->where('payment_status', 'unpaid');

        // Search filter: order number, token, kot, customer, table
        if ($search = trim($request->get('search', ''))) {
            $query->where(function ($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                  ->orWhere('token_number', 'like', "%{$search}%")
                  ->orWhere('kot_number', 'like', "%{$search}%")
                  ->orWhereHas('customer', function ($cq) use ($search) {
                      $cq->where('name', 'like', "%{$search}%")
                         ->orWhere('phone', 'like', "%{$search}%");
                  })
                  ->orWhereHas('table', function ($tq) use ($search) {
                      $tq->where('name', 'like', "%{$search}%");
                  });
            });
        }

        // Order type filter
        if ($request->filled('order_type_id')) {
            $query->where('order_type_id', $request->get('order_type_id'));
        }

        // Specific status filter
        if ($request->filled('status')) {
            $query->where('status', $request->get('status'));
        }

        // Specific date filter
        if ($request->has('date') && $request->filled('date') && $request->get('date') !== 'all') {
            $query->whereDate('created_at', $request->get('date'));
        }

        $orders = $query->with([
            'items.foodItem',
            'items.variation',
            'items.properties.propertyItem',
            'table',
            'customer',
            'orderType',
            'party',
            'user',
            'discount'
        ])
        ->latest('updated_at')
        ->get();

        return response()->json([
            'success' => true,
            'orders' => $orders,
            'total' => $orders->count(),
        ]);
    }

    /**
     * Get settled/completed paid orders for review and reprint
     */
    public function getSettledOrders(Request $request)
    {
        $user = Auth::user();
        $branchId = (int)$request->get('branch_id');
        if (!$branchId) {
            $branchId = $user->branch_id ?? Branch::where('is_active', true)->value('id');
        }

        if ($user && !in_array($user->role, ['admin', 'manager', 'multi branch manager'])) {
            if ($user->branches()->exists() && !$user->branches()->where('branches.id', $branchId)->exists()) {
                abort(403, 'Unauthorized access to branch orders.');
            }
        }

        $query = PosOrder::where('branch_id', $branchId)
            ->where('status', 'completed')
            ->where('payment_status', 'paid');

        // Search filter: order number, token, kot, customer, table
        if ($search = trim($request->get('search', ''))) {
            $query->where(function ($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                  ->orWhere('token_number', 'like', "%{$search}%")
                  ->orWhere('kot_number', 'like', "%{$search}%")
                  ->orWhereHas('customer', function ($cq) use ($search) {
                      $cq->where('name', 'like', "%{$search}%")
                         ->orWhere('phone', 'like', "%{$search}%");
                  })
                  ->orWhereHas('table', function ($tq) use ($search) {
                      $tq->where('name', 'like', "%{$search}%");
                  });
            });
        }

        // Order type filter
        if ($request->filled('order_type_id')) {
            $query->where('order_type_id', $request->get('order_type_id'));
        }

        // Payment type filter
        if ($request->filled('payment_type_id')) {
            $paymentTypeId = $request->get('payment_type_id');
            $query->whereHas('payments', function ($pq) use ($paymentTypeId) {
                $pq->where('payment_type_id', $paymentTypeId);
            });
        }

        // Date filter
        if ($request->has('date')) {
            if ($request->filled('date') && $request->get('date') !== 'all') {
                $query->whereDate('created_at', $request->get('date'));
            }
        } elseif ($request->filled('date_from') && $request->filled('date_to')) {
            $query->whereBetween('created_at', [$request->get('date_from') . ' 00:00:00', $request->get('date_to') . ' 23:59:59']);
        } else {
            $query->whereDate('created_at', now()->toDateString());
        }

        $orders = $query->with([
            'items.foodItem',
            'items.variation',
            'items.properties.propertyItem',
            'table',
            'customer',
            'orderType',
            'party',
            'user',
            'payments.paymentType',
            'discount',
            'branch'
        ])
        ->latest('updated_at')
        ->get();

        $totalRevenue = $orders->sum('grand_total');

        return response()->json([
            'success' => true,
            'orders' => $orders,
            'total' => $orders->count(),
            'total_revenue' => (float)$totalRevenue,
        ]);
    }

    /**
     * Quick Customer Creation from Terminal
     */
    public function quickStoreCustomer(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:50',
            'address' => 'nullable|string|max:500',
        ]);

        $customer = Customer::updateOrCreate(
            ['phone' => $validated['phone']],
            [
                'name' => $validated['name'],
                'address' => $validated['address'] ?? null,
                'customer_type' => 'regular',
                'is_active' => true,
            ]
        );

        return response()->json([
            'success' => true,
            'customer' => $customer,
        ]);
    }

    /**
     * Deduct Raw Materials / Ingredients from central inventory_ledgers (Rule 7)
     */
    protected function deductInventoryLedger(PosOrder $order): void
    {
        // Prevent duplicate deductions (Idempotency)
        $alreadyDeducted = InventoryLedger::where('document_type', PosOrder::class)
            ->where('document_id', $order->id)
            ->where('quantity', '<', 0)
            ->exists();

        if ($alreadyDeducted) {
            return;
        }

        $order->load([
            'items.foodItem.recipe.ingredients.orderTypes',
            'items.properties.propertyItem.recipe.ingredients.orderTypes',
            'orderType'
        ]);

        foreach ($order->items as $orderItem) {
            $orderedQty = $orderItem->quantity;

            // BUG-POS-01 fix: Check FoodItemVariation recipe first, fallback to foodItem recipe
            $foodItemVariation = null;
            if ($orderItem->variation_id) {
                $foodItemVariation = \App\Models\FoodItemVariation::where('food_item_id', $orderItem->food_item_id)
                    ->where('variation_id', $orderItem->variation_id)
                    ->with('recipe.ingredients.orderTypes')
                    ->first();
            }

            $recipe = $foodItemVariation?->recipe ?? $orderItem->foodItem?->recipe;

            if ($recipe && $recipe->ingredients) {
                // BUG-POS-03 fix: Respect recipe yield_quantity
                $yieldQty = (float)($recipe->yield_quantity ?? 1) ?: 1.0;

                foreach ($recipe->ingredients as $recipeIngredient) {
                    // Check order-type packaging restrictions if present
                    if ($recipeIngredient->orderTypes && $recipeIngredient->orderTypes->isNotEmpty()) {
                        $matchesOrderType = $recipeIngredient->orderTypes
                            ->pluck('id')
                            ->contains($order->order_type_id);
                        if (!$matchesOrderType) {
                            continue;
                        }
                    }

                    $qtyToDeduct = ((float)$recipeIngredient->quantity / $yieldQty) * $orderedQty;
                    if ($qtyToDeduct > 0) {
                        InventoryLedger::create([
                            'branch_id' => $order->branch_id,
                            'document_type' => PosOrder::class,
                            'document_id' => $order->id,
                            'item_id' => $recipeIngredient->ingredient_item_id,
                            'quantity' => -$qtyToDeduct, // Outgoing stock is strictly negative (Rule 7)
                            'unit_price' => 0,
                            'document_date' => now()->toDateString(),
                            'notes' => "Deduction for POS Order {$order->order_number} ({$orderItem->foodItem?->name})",
                            'created_by' => $order->user_id,
                        ]);
                    }
                }
            }

            // BUG-POS-04 fix: Deduct recipes for Addons / Property Items
            if ($orderItem->properties && $orderItem->properties->isNotEmpty()) {
                foreach ($orderItem->properties as $prop) {
                    $propRecipe = $prop->propertyItem?->recipe;
                    if ($propRecipe && $propRecipe->ingredients) {
                        $propYield = (float)($propRecipe->yield_quantity ?? 1) ?: 1.0;
                        $propQty = $orderedQty * ($prop->quantity ?? 1);

                        foreach ($propRecipe->ingredients as $propIngredient) {
                            if ($propIngredient->orderTypes && $propIngredient->orderTypes->isNotEmpty()) {
                                $matches = $propIngredient->orderTypes->pluck('id')->contains($order->order_type_id);
                                if (!$matches) {
                                    continue;
                                }
                            }

                            $propDeduct = ((float)$propIngredient->quantity / $propYield) * $propQty;
                            if ($propDeduct > 0) {
                                InventoryLedger::create([
                                    'branch_id' => $order->branch_id,
                                    'document_type' => PosOrder::class,
                                    'document_id' => $order->id,
                                    'item_id' => $propIngredient->ingredient_item_id,
                                    'quantity' => -$propDeduct,
                                    'unit_price' => 0,
                                    'document_date' => now()->toDateString(),
                                    'notes' => "Deduction for POS Addon {$prop->property_name} in {$order->order_number}",
                                    'created_by' => $order->user_id,
                                ]);
                            }
                        }
                    }
                }
            }
        }
    }

    /**
     * Get active Business Day and Shift status for current terminal session
     */
    public function getShiftStatus(Request $request)
    {
        $user = Auth::user();
        $branchId = (int)$request->get('branch_id');
        if (!$branchId) {
            $branchId = $user?->branch_id ?? Branch::where('is_active', true)->value('id');
        }

        $activeBusinessDay = PosBusinessDay::where('branch_id', $branchId)
            ->where('status', 'open')
            ->with('openedBy')
            ->latest('id')
            ->first();

        $activeShift = $user ? PosShift::where('branch_id', $branchId)
            ->where('user_id', $user->id)
            ->where('status', 'open')
            ->with(['user', 'businessDay', 'branch'])
            ->latest('id')
            ->first() : null;

        $shiftSummary = null;
        if ($activeShift) {
            $shiftSummary = $this->calculateShiftMetrics($activeShift);
        }

        return response()->json([
            'success' => true,
            'business_day' => $activeBusinessDay ? [
                'id' => $activeBusinessDay->id,
                'business_date' => $activeBusinessDay->business_date->toDateString(),
                'business_date_formatted' => $activeBusinessDay->business_date->format('d M Y'),
                'business_day' => $activeBusinessDay->business_date->format('l'),
                'opened_at' => $activeBusinessDay->opened_at->toDateTimeString(),
                'opened_by' => $activeBusinessDay->openedBy?->name,
                'status' => $activeBusinessDay->status,
                'notes' => $activeBusinessDay->notes,
            ] : null,
            'active_shift' => $activeShift ? [
                'id' => $activeShift->id,
                'shift_number' => $activeShift->shift_number,
                'opening_float' => (float)$activeShift->opening_float,
                'opened_at' => $activeShift->opened_at->toDateTimeString(),
                'opened_at_time' => $activeShift->opened_at->format('h:i A'),
                'status' => $activeShift->status,
                'user' => [
                    'id' => $activeShift->user?->id,
                    'name' => $activeShift->user?->name,
                ],
            ] : null,
            'shift_summary' => $shiftSummary,
        ]);
    }

    /**
     * Open a new business day for a branch
     */
    public function openBusinessDay(Request $request)
    {
        $validated = $request->validate([
            'branch_id' => 'required|exists:branches,id',
            'business_date' => 'nullable|date',
            'notes' => 'nullable|string|max:500',
        ]);

        $user = Auth::user();
        if ($user && !in_array($user->role, ['admin', 'manager', 'multi branch manager'])) {
            if ($user->branches()->exists() && !$user->branches()->where('branches.id', $validated['branch_id'])->exists()) {
                abort(403, 'Unauthorized access to branch.');
            }
        }

        $todayDate = now()->toDateString();

        $existing = PosBusinessDay::where('branch_id', $validated['branch_id'])
            ->where('status', 'open')
            ->first();

        if ($existing) {
            return response()->json([
                'success' => false,
                'message' => 'A business day is already open for this branch (' . $existing->business_date->format('d M Y') . '). Close it before opening a new one.',
                'business_day' => $existing,
            ], 422);
        }

        $businessDay = PosBusinessDay::create([
            'branch_id' => $validated['branch_id'],
            'business_date' => $todayDate, // Strictly locked to current date to prevent backdating/forward dating
            'status' => 'open',
            'opened_by' => Auth::id(),
            'opened_at' => now(),
            'notes' => $validated['notes'] ?? null,
        ]);

        $businessDay->load('openedBy');

        return response()->json([
            'success' => true,
            'message' => 'Business day opened successfully for ' . $businessDay->business_date->format('d M Y'),
            'business_day' => [
                'id' => $businessDay->id,
                'business_date' => $businessDay->business_date->toDateString(),
                'business_date_formatted' => $businessDay->business_date->format('d M Y'),
                'business_day' => $businessDay->business_date->format('l'),
                'opened_at' => $businessDay->opened_at->toDateTimeString(),
                'opened_by' => $businessDay->openedBy?->name,
                'status' => $businessDay->status,
                'notes' => $businessDay->notes,
            ],
        ]);
    }

    /**
     * Close the active business day for a branch (Z-Report generation)
     */
    public function closeBusinessDay(Request $request)
    {
        $validated = $request->validate([
            'branch_id' => 'required|exists:branches,id',
            'notes' => 'nullable|string|max:500',
            'force' => 'nullable|boolean',
        ]);

        $user = Auth::user();
        if ($user && !in_array($user->role, ['admin', 'manager', 'multi branch manager'])) {
            if ($user->branches()->exists() && !$user->branches()->where('branches.id', $validated['branch_id'])->exists()) {
                abort(403, 'Unauthorized access to branch.');
            }
        }

        if (!empty($validated['force']) && !in_array($user->role, ['admin', 'manager'])) {
            abort(403, 'Only managers and administrators are authorized to force-close active shifts.');
        }

        $activeDay = PosBusinessDay::where('branch_id', $validated['branch_id'])
            ->where('status', 'open')
            ->latest('id')
            ->first();

        if (!$activeDay) {
            return response()->json([
                'success' => false,
                'message' => 'No active business day found for this branch to close.',
            ], 422);
        }

        $openShifts = PosShift::where('pos_business_day_id', $activeDay->id)
            ->where('status', 'open')
            ->with('user')
            ->get();

        if ($openShifts->isNotEmpty() && empty($validated['force'])) {
            $shiftUsers = $openShifts->map(fn($s) => $s->user?->name ?? "Shift #{$s->shift_number}")->implode(', ');
            return response()->json([
                'success' => false,
                'message' => "Cannot close business day: Active shift(s) still open ({$shiftUsers}). Please close all cashier shifts first, or select force close.",
                'open_shifts_count' => $openShifts->count(),
            ], 422);
        }

        // Force close open shifts if requested
        if ($openShifts->isNotEmpty() && !empty($validated['force'])) {
            foreach ($openShifts as $openShift) {
                $metrics = $this->calculateShiftMetrics($openShift);
                $expected = $metrics['expected_cash'];
                $openShift->update([
                    'closing_cash_expected' => $expected,
                    'closing_cash_actual' => null,
                    'cash_variance' => null,
                    'status' => 'closed',
                    'closed_at' => now(),
                    'closed_by' => Auth::id(),
                    'notes' => ($openShift->notes ? $openShift->notes . ' | ' : '') . 'Auto-closed on Day End (Unreconciled)',
                ]);
            }
        }

        // Check for unsettled orders
        $unsettledCount = PosOrder::where('pos_business_day_id', $activeDay->id)
            ->whereIn('status', ['submitted', 'preparing', 'ready', 'delivered'])
            ->where('payment_status', 'unpaid')
            ->count();

        $activeDay->update([
            'status' => 'closed',
            'closed_by' => Auth::id(),
            'closed_at' => now(),
            'notes' => ($activeDay->notes ? $activeDay->notes . ' | ' : '') . ($validated['notes'] ?? ''),
        ]);

        $dayMetrics = $this->calculateBusinessDayMetrics($activeDay);

        return response()->json([
            'success' => true,
            'message' => 'Business day closed successfully.',
            'business_day' => $activeDay,
            'unsettled_orders_warning' => $unsettledCount > 0 ? "Notice: {$unsettledCount} submitted order(s) remain unpaid from this day." : null,
            'z_report' => $dayMetrics,
        ]);
    }

    /**
     * Start a new user shift / work period
     */
    public function startShift(Request $request)
    {
        $validated = $request->validate([
            'branch_id' => 'required|exists:branches,id',
            'opening_float' => 'required|numeric|min:0',
            'notes' => 'nullable|string|max:500',
        ]);

        $user = Auth::user();
        if ($user && !in_array($user->role, ['admin', 'manager', 'multi branch manager'])) {
            if ($user->branches()->exists() && !$user->branches()->where('branches.id', $validated['branch_id'])->exists()) {
                abort(403, 'Unauthorized access to branch.');
            }
        }

        // Must have an active open business day for this branch
        $activeDay = PosBusinessDay::where('branch_id', $validated['branch_id'])
            ->where('status', 'open')
            ->latest('id')
            ->first();

        if (!$activeDay) {
            return response()->json([
                'success' => false,
                'message' => 'No active business day is currently open for this branch. A business day must be opened before starting a cashier shift.',
            ], 422);
        }

        // Cashier cannot have two open shifts simultaneously
        $existingShift = PosShift::where('branch_id', $validated['branch_id'])
            ->where('user_id', $user->id)
            ->where('status', 'open')
            ->first();

        if ($existingShift) {
            return response()->json([
                'success' => false,
                'message' => 'You already have an active shift open (' . $existingShift->shift_number . '). Please end your current shift before starting a new one.',
                'shift' => $existingShift,
            ], 422);
        }

        $shift = PosShift::create([
            'pos_business_day_id' => $activeDay->id,
            'branch_id' => $validated['branch_id'],
            'user_id' => $user->id,
            'shift_number' => PosShift::generateShiftNumber($validated['branch_id']),
            'opening_float' => $validated['opening_float'],
            'status' => 'open',
            'opened_at' => now(),
            'notes' => $validated['notes'] ?? null,
        ]);

        $shift->load(['user', 'businessDay', 'branch']);

        return response()->json([
            'success' => true,
            'message' => 'Shift started successfully with float of ' . number_format($shift->opening_float, 2),
            'shift' => [
                'id' => $shift->id,
                'shift_number' => $shift->shift_number,
                'opening_float' => (float)$shift->opening_float,
                'opened_at' => $shift->opened_at->toDateTimeString(),
                'opened_at_time' => $shift->opened_at->format('h:i A'),
                'status' => $shift->status,
                'user' => [
                    'id' => $shift->user?->id,
                    'name' => $shift->user?->name,
                ],
            ],
        ]);
    }

    /**
     * Close user shift / work period and reconcile drawer (X-Report)
     */
    public function closeShift(Request $request, ?PosShift $shift = null)
    {
        $user = Auth::user();
        if (!$shift || !$shift->id) {
            $shiftId = (int)$request->get('shift_id');
            if ($shiftId) {
                $shift = PosShift::find($shiftId);
            } else {
                $branchId = (int)$request->get('branch_id') ?: ($user->branch_id ?? Branch::where('is_active', true)->value('id'));
                $shift = PosShift::where('branch_id', $branchId)
                    ->where('user_id', $user->id)
                    ->where('status', 'open')
                    ->latest('id')
                    ->first();
            }
        }

        if (!$shift) {
            return response()->json([
                'success' => false,
                'message' => 'No active shift found to close.',
            ], 422);
        }

        if ($shift->user_id !== $user->id && !in_array($user->role, ['admin', 'manager'])) {
            abort(403, 'Unauthorized to close another user\'s shift.');
        }

        if (!in_array($user->role, ['admin', 'manager', 'multi branch manager'])) {
            if ($user->branches()->exists() && !$user->branches()->where('branches.id', $shift->branch_id)->exists()) {
                abort(403, 'Unauthorized access to shifts in this branch.');
            }
        }

        if ($shift->status === 'closed') {
            return response()->json([
                'success' => false,
                'message' => 'This shift is already closed.',
            ], 422);
        }

        $validated = $request->validate([
            'closing_cash_actual' => 'required|numeric|min:0',
            'notes' => 'nullable|string|max:500',
        ]);

        $metrics = $this->calculateShiftMetrics($shift);
        $expectedCash = $metrics['expected_cash'];
        $actualCash = (float)$validated['closing_cash_actual'];
        $variance = round($actualCash - $expectedCash, 2);

        $shift->update([
            'closing_cash_actual' => $actualCash,
            'closing_cash_expected' => $expectedCash,
            'cash_variance' => $variance,
            'status' => 'closed',
            'closed_at' => now(),
            'closed_by' => $user->id,
            'notes' => ($shift->notes ? $shift->notes . ' | ' : '') . ($validated['notes'] ?? ''),
        ]);

        $xReport = $this->calculateShiftMetrics($shift->fresh());

        return response()->json([
            'success' => true,
            'message' => 'Shift closed successfully. ' . ($variance == 0 ? 'Drawer balanced perfectly!' : ($variance > 0 ? "Cash overage: +" . number_format($variance, 2) : "Cash shortage: " . number_format($variance, 2))),
            'shift' => $shift->fresh()->load('user'),
            'x_report' => $xReport,
        ]);
    }

    /**
     * Get live or historical Shift Summary / X-Report
     */
    public function getShiftSummary(Request $request, ?PosShift $shift = null)
    {
        $user = Auth::user();
        if (!$shift || !$shift->id) {
            $shiftId = (int)$request->get('shift_id');
            if ($shiftId) {
                $shift = PosShift::with(['user', 'businessDay', 'branch'])->find($shiftId);
            } else {
                $branchId = (int)$request->get('branch_id') ?: ($user->branch_id ?? Branch::where('is_active', true)->value('id'));
                $shift = PosShift::where('branch_id', $branchId)
                    ->where('user_id', $user->id)
                    ->where('status', 'open')
                    ->latest('id')
                    ->first();
            }
        }

        if (!$shift) {
            return response()->json([
                'success' => false,
                'message' => 'No shift found to generate summary.',
            ], 404);
        }

        if ($user && !in_array($user->role, ['admin', 'manager', 'multi branch manager'])) {
            if ($user->branches()->exists() && !$user->branches()->where('branches.id', $shift->branch_id)->exists()) {
                abort(403, 'Unauthorized access to shift summary.');
            }
        }

        $summary = $this->calculateShiftMetrics($shift);

        return response()->json([
            'success' => true,
            'summary' => $summary,
        ]);
    }

    /**
     * Admin Shift Monitor API query with date range, closed_by, duration, and KPI totals
     */
    public function getShiftMonitor(Request $request)
    {
        $user = Auth::user();
        $branchId = $request->get('branch_id');
        $date = $request->get('date');
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');
        $status = $request->get('status');
        $userId = $request->get('user_id');
        $search = $request->get('search');

        $query = PosShift::with(['user', 'closedBy', 'branch', 'businessDay']);

        if ($user && !in_array($user->role, ['admin', 'manager', 'multi branch manager'])) {
            if ($branchId && $user->branches()->exists() && !$user->branches()->where('branches.id', $branchId)->exists()) {
                abort(403, 'Unauthorized access to branch data.');
            }
            if (!$branchId && $user->branches()->exists()) {
                $query->whereIn('branch_id', $user->branches->pluck('id'));
            }
        }

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        if ($startDate && $endDate) {
            $query->where(function ($q) use ($startDate, $endDate) {
                $q->whereBetween(DB::raw('DATE(opened_at)'), [$startDate, $endDate])
                  ->orWhereHas('businessDay', function ($bq) use ($startDate, $endDate) {
                      $bq->whereBetween('business_date', [$startDate, $endDate]);
                  });
            });
        } elseif ($date) {
            $query->where(function ($q) use ($date) {
                $q->whereDate('opened_at', $date)
                  ->orWhereHas('businessDay', function ($bq) use ($date) {
                      $bq->whereDate('business_date', $date);
                  });
            });
        }

        if ($status && $status !== 'all') {
            $query->where('status', $status);
        }

        if ($userId) {
            $query->where(function ($q) use ($userId) {
                $q->where('user_id', $userId)
                  ->orWhere('closed_by', $userId);
            });
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('shift_number', 'like', "%{$search}%")
                  ->orWhere('notes', 'like', "%{$search}%")
                  ->orWhereHas('user', fn($uq) => $uq->where('name', 'like', "%{$search}%"))
                  ->orWhereHas('closedBy', fn($cq) => $cq->where('name', 'like', "%{$search}%"));
            });
        }

        $shifts = $query->latest('id')->get();

        $shiftsData = $shifts->map(function ($s) {
            $metrics = $this->calculateShiftMetrics($s);
            $closedByName = $s->closedBy?->name ?? ($s->status === 'closed' ? ($s->user?->name ?? 'System') : '-');

            return [
                'id' => $s->id,
                'shift_number' => $s->shift_number,
                'branch_id' => $s->branch_id,
                'branch_name' => $s->branch?->name,
                'user_id' => $s->user_id,
                'cashier_name' => $s->user?->name ?? 'Unknown',
                'closed_by_id' => $s->closed_by,
                'closed_by_name' => $closedByName,
                'business_date' => $s->businessDay?->business_date?->format('d M Y') ?? $s->opened_at->format('d M Y'),
                'opened_at' => $s->opened_at->toDateTimeString(),
                'opened_at_formatted' => $s->opened_at->format('d M Y, h:i A'),
                'opened_at_time' => $s->opened_at->format('h:i A'),
                'closed_at' => $s->closed_at?->toDateTimeString(),
                'closed_at_formatted' => $s->closed_at ? $s->closed_at->format('d M Y, h:i A') : 'Still Open (Active)',
                'closed_at_time' => $s->closed_at?->format('h:i A'),
                'duration' => $s->duration,
                'status' => $s->status,
                'opening_float' => (float)$s->opening_float,
                'closing_cash_actual' => $s->closing_cash_actual !== null ? (float)$s->closing_cash_actual : null,
                'closing_cash_expected' => $s->closing_cash_expected !== null ? (float)$s->closing_cash_expected : $metrics['expected_cash'],
                'cash_variance' => $s->cash_variance !== null ? (float)$s->cash_variance : round(($metrics['actual_cash'] ?: 0) - $metrics['expected_cash'], 2),
                'orders_count' => $metrics['orders_count'],
                'completed_orders_count' => $metrics['completed_orders_count'],
                'gross_sales' => $metrics['gross_sales'],
                'cash_collected' => $metrics['cash_collected'],
                'notes' => $s->notes,
            ];
        });

        $kpis = [
            'total_shifts' => $shiftsData->count(),
            'open_shifts' => $shiftsData->where('status', 'open')->count(),
            'closed_shifts' => $shiftsData->where('status', 'closed')->count(),
            'total_orders' => $shiftsData->sum('orders_count'),
            'total_sales' => round($shiftsData->sum('gross_sales'), 2),
            'total_variance' => round($shiftsData->where('status', 'closed')->sum('cash_variance'), 2),
        ];

        return response()->json([
            'success' => true,
            'shifts' => $shiftsData,
            'kpis' => $kpis,
        ]);
    }

    /**
     * Day Management API query with date range, opened_by, closed_by, duration, and KPI totals
     */
    public function getBusinessDaysData(Request $request)
    {
        $user = Auth::user();
        $branchId = $request->get('branch_id');
        $date = $request->get('date');
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');
        $status = $request->get('status');
        $userId = $request->get('user_id');
        $search = $request->get('search');

        $query = PosBusinessDay::with(['branch', 'openedBy', 'closedBy', 'shifts.user']);

        if ($user && !in_array($user->role, ['admin', 'manager', 'multi branch manager'])) {
            if ($branchId && $user->branches()->exists() && !$user->branches()->where('branches.id', $branchId)->exists()) {
                abort(403, 'Unauthorized access to branch data.');
            }
            if (!$branchId && $user->branches()->exists()) {
                $query->whereIn('branch_id', $user->branches->pluck('id'));
            }
        }

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        if ($startDate && $endDate) {
            $query->whereBetween('business_date', [$startDate, $endDate]);
        } elseif ($date) {
            $query->whereDate('business_date', $date);
        }

        if ($status && $status !== 'all') {
            $query->where('status', $status);
        }

        if ($userId) {
            $query->where(function ($q) use ($userId) {
                $q->where('opened_by', $userId)
                  ->orWhere('closed_by', $userId)
                  ->orWhereHas('shifts', function ($sq) use ($userId) {
                      $sq->where('user_id', $userId)->orWhere('closed_by', $userId);
                  });
            });
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('notes', 'like', "%{$search}%")
                  ->orWhereHas('openedBy', fn($uq) => $uq->where('name', 'like', "%{$search}%"))
                  ->orWhereHas('closedBy', fn($cq) => $cq->where('name', 'like', "%{$search}%"))
                  ->orWhereHas('branch', fn($bq) => $bq->where('name', 'like', "%{$search}%"));
            });
        }

        $days = $query->latest('business_date')->latest('id')->get();

        $daysData = $days->map(function ($day) {
            $metrics = $this->calculateBusinessDayMetrics($day);

            return [
                'id' => $day->id,
                'branch_id' => $day->branch_id,
                'branch_name' => $day->branch?->name,
                'business_date' => $day->business_date->toDateString(),
                'business_date_formatted' => $day->business_date->format('d M Y'),
                'business_day' => $day->business_date->format('l'),
                'status' => $day->status,
                'opened_at' => $day->opened_at->toDateTimeString(),
                'opened_at_formatted' => $day->opened_at->format('d M Y, h:i A'),
                'opened_at_time' => $day->opened_at->format('h:i A'),
                'opened_by_id' => $day->opened_by,
                'opened_by_name' => $day->openedBy?->name ?? 'System',
                'closed_at' => $day->closed_at?->toDateTimeString(),
                'closed_at_formatted' => $day->closed_at ? $day->closed_at->format('d M Y, h:i A') : 'Still Open (Active)',
                'closed_at_time' => $day->closed_at?->format('h:i A'),
                'closed_by_id' => $day->closed_by,
                'closed_by_name' => $day->closedBy?->name ?? ($day->status === 'closed' ? 'System' : '-'),
                'duration' => $day->duration,
                'shifts_count' => $day->shifts->count(),
                'open_shifts_count' => $day->shifts->where('status', 'open')->count(),
                'closed_shifts_count' => $day->shifts->where('status', 'closed')->count(),
                'orders_count' => $metrics['orders_count'],
                'completed_orders_count' => $metrics['completed_orders_count'],
                'gross_sales' => $metrics['gross_sales'],
                'cash_collected' => $metrics['cash_collected'],
                'total_variance' => $metrics['total_variance'],
                'notes' => $day->notes,
            ];
        });

        $kpis = [
            'total_days' => $daysData->count(),
            'open_days' => $daysData->where('status', 'open')->count(),
            'closed_days' => $daysData->where('status', 'closed')->count(),
            'total_shifts' => $daysData->sum('shifts_count'),
            'total_sales' => round($daysData->sum('gross_sales'), 2),
            'total_variance' => round($daysData->sum('total_variance'), 2),
        ];

        return response()->json([
            'success' => true,
            'days' => $daysData,
            'kpis' => $kpis,
        ]);
    }

    /**
     * Get complete Z-Report summary for a business day
     */
    public function getBusinessDaySummary(Request $request)
    {
        $dayId = $request->get('day_id');
        $day = PosBusinessDay::with(['branch', 'openedBy', 'closedBy'])->find($dayId);

        if (!$day) {
            return response()->json([
                'success' => false,
                'message' => 'Business day not found.',
            ], 404);
        }

        $user = Auth::user();
        if ($user && !in_array($user->role, ['admin', 'manager', 'multi branch manager'])) {
            if ($user->branches()->exists() && !$user->branches()->where('branches.id', $day->branch_id)->exists()) {
                abort(403, 'Unauthorized access to business day summary.');
            }
        }

        $zReport = $this->calculateBusinessDayMetrics($day);

        return response()->json([
            'success' => true,
            'business_day' => [
                'id' => $day->id,
                'branch_id' => $day->branch_id,
                'branch_name' => $day->branch?->name,
                'business_date' => $day->business_date->format('d M Y'),
                'business_day' => $day->business_date->format('l'),
                'status' => $day->status,
                'opened_at' => $day->opened_at->format('d M Y, h:i A'),
                'opened_by' => $day->openedBy?->name ?? 'System',
                'closed_at' => $day->closed_at ? $day->closed_at->format('d M Y, h:i A') : 'Still Open (Active)',
                'closed_by' => $day->closedBy?->name ?? ($day->status === 'closed' ? 'System' : '-'),
                'duration' => $day->duration,
                'notes' => $day->notes,
            ],
            'z_report' => $zReport,
        ]);
    }

    /**
     * Render the Day & Shift Management Inertia page
     */
    public function renderWorkPeriods(Request $request)
    {
        $branches = Branch::where('is_active', true)->get(['id', 'name']);
        $users = User::where('is_active', true)->get(['id', 'name']);

        return Inertia::render('POS/ShiftMonitor', [
            'branches' => $branches,
            'users' => $users,
        ]);
    }

    /**
     * Render the Admin Shift Monitor Inertia page (alias for renderWorkPeriods)
     */
    public function renderShiftMonitor(Request $request)
    {
        return $this->renderWorkPeriods($request);
    }

    /**
     * Helper: Calculate complete X-Report metrics for a shift
     */
    public function calculateShiftMetrics(PosShift $shift): array
    {
        $orders = PosOrder::where('pos_shift_id', $shift->id)->get();
        $completedOrders = $orders->where('status', 'completed');

        $payments = PosOrderPayment::where('pos_shift_id', $shift->id)
            ->with('paymentType')
            ->get();

        $paymentsByType = [];
        $totalCollected = 0;
        $cashCollected = 0;

        foreach ($payments as $payment) {
            $typeName = $payment->paymentType?->name ?? 'Unknown';
            $isCash = stripos($typeName, 'cash') !== false;

            if (!isset($paymentsByType[$typeName])) {
                $paymentsByType[$typeName] = [
                    'name' => $typeName,
                    'is_cash' => $isCash,
                    'amount' => 0,
                    'count' => 0,
                ];
            }
            $amount = (float)$payment->amount;
            $paymentsByType[$typeName]['amount'] += $amount;
            $paymentsByType[$typeName]['count'] += 1;
            $totalCollected += $amount;

            if ($isCash) {
                $cashCollected += $amount;
            }
        }

        $openingFloat = (float)$shift->opening_float;
        $expectedCash = round($openingFloat + $cashCollected, 2);
        $actualCash = $shift->closing_cash_actual !== null ? (float)$shift->closing_cash_actual : null;
        $variance = $shift->cash_variance !== null ? (float)$shift->cash_variance : ($actualCash !== null ? round($actualCash - $expectedCash, 2) : 0);

        return [
            'shift_id' => $shift->id,
            'shift_number' => $shift->shift_number,
            'status' => $shift->status,
            'cashier_name' => $shift->user?->name,
            'branch_name' => $shift->branch?->name,
            'business_date' => $shift->businessDay?->business_date?->format('d M Y') ?? $shift->opened_at->format('d M Y'),
            'opened_at' => $shift->opened_at->format('d M Y h:i A'),
            'closed_at' => $shift->closed_at?->format('d M Y h:i A'),
            'opening_float' => $openingFloat,
            'orders_count' => $orders->count(),
            'completed_orders_count' => $completedOrders->count(),
            'submitted_orders_count' => $orders->whereIn('status', ['submitted', 'preparing', 'ready', 'delivered'])->count(),
            'voided_orders_count' => $orders->where('status', 'cancelled')->count(),
            'subtotal' => round($completedOrders->sum('subtotal'), 2),
            'tax_amount' => round($completedOrders->sum('tax_amount'), 2),
            'discount_amount' => round($completedOrders->sum('discount_value'), 2),
            'delivery_charges' => round($completedOrders->sum('delivery_charges'), 2),
            'service_charges' => round($completedOrders->sum('service_charges'), 2),
            'gross_sales' => round($completedOrders->sum('grand_total'), 2),
            'payments_by_type' => array_values($paymentsByType),
            'total_payments_collected' => round($totalCollected, 2),
            'cash_collected' => round($cashCollected, 2),
            'expected_cash' => $expectedCash,
            'actual_cash' => $actualCash,
            'cash_variance' => $variance,
        ];
    }

    /**
     * Helper: Calculate complete Z-Report metrics for a business day
     */
    public function calculateBusinessDayMetrics(PosBusinessDay $day): array
    {
        $shifts = PosShift::where('pos_business_day_id', $day->id)->with('user')->get();
        $orders = PosOrder::where('pos_business_day_id', $day->id)->get();
        $completedOrders = $orders->where('status', 'completed');

        $shiftIds = $shifts->pluck('id')->toArray();
        $payments = PosOrderPayment::whereIn('pos_shift_id', $shiftIds)
            ->with('paymentType')
            ->get();

        $paymentsByType = [];
        $totalCollected = 0;
        $cashCollected = 0;

        foreach ($payments as $payment) {
            $typeName = $payment->paymentType?->name ?? 'Unknown';
            $isCash = stripos($typeName, 'cash') !== false;

            if (!isset($paymentsByType[$typeName])) {
                $paymentsByType[$typeName] = [
                    'name' => $typeName,
                    'is_cash' => $isCash,
                    'amount' => 0,
                    'count' => 0,
                ];
            }
            $amount = (float)$payment->amount;
            $paymentsByType[$typeName]['amount'] += $amount;
            $paymentsByType[$typeName]['count'] += 1;
            $totalCollected += $amount;

            if ($isCash) {
                $cashCollected += $amount;
            }
        }

        $totalOpeningFloats = (float)$shifts->sum('opening_float');
        $totalActualCash = (float)$shifts->sum('closing_cash_actual');
        $totalExpectedCash = round($totalOpeningFloats + $cashCollected, 2);
        $totalVariance = round($shifts->sum('cash_variance'), 2);

        return [
            'business_day_id' => $day->id,
            'business_date' => $day->business_date->format('d M Y'),
            'opened_at' => $day->opened_at->format('d M Y h:i A'),
            'closed_at' => $day->closed_at?->format('d M Y h:i A'),
            'shifts_count' => $shifts->count(),
            'orders_count' => $orders->count(),
            'completed_orders_count' => $completedOrders->count(),
            'gross_sales' => round($completedOrders->sum('grand_total'), 2),
            'payments_by_type' => array_values($paymentsByType),
            'total_payments_collected' => round($totalCollected, 2),
            'cash_collected' => round($cashCollected, 2),
            'total_opening_floats' => $totalOpeningFloats,
            'total_actual_cash' => $totalActualCash,
            'total_expected_cash' => $totalExpectedCash,
            'total_variance' => $totalVariance,
        ];
    }
}
