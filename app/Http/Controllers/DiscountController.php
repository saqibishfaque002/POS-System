<?php

namespace App\Http\Controllers;

use App\Models\Discount;
use App\Http\Requests\DiscountRequest;
use Illuminate\Http\Request;
use Carbon\Carbon;

class DiscountController extends Controller
{
    public function store(DiscountRequest $request)
    {
        $validated = $request->validated();

        $branchIds = $validated['branch_ids'] ?? [];
        $orderTypeIds = $validated['order_type_ids'] ?? [];
        $foodGroupIds = $validated['food_group_ids'] ?? [];
        $foodItemIds = $validated['food_item_ids'] ?? [];

        unset(
            $validated['branch_ids'],
            $validated['order_type_ids'],
            $validated['food_group_ids'],
            $validated['food_item_ids']
        );

        $discount = Discount::create($validated);

        if ($request->has('branch_ids')) {
            $discount->branches()->sync($branchIds);
        }
        if ($request->has('order_type_ids')) {
            $discount->orderTypes()->sync($orderTypeIds);
        }
        if ($request->has('food_group_ids')) {
            $discount->foodGroups()->sync($foodGroupIds);
        }
        if ($request->has('food_item_ids')) {
            $discount->foodItems()->sync($foodItemIds);
        }

        return back()->with('success', 'Discount created successfully.');
    }

    public function update(DiscountRequest $request, Discount $discount)
    {
        $validated = $request->validated();

        $branchIds = $validated['branch_ids'] ?? [];
        $orderTypeIds = $validated['order_type_ids'] ?? [];
        $foodGroupIds = $validated['food_group_ids'] ?? [];
        $foodItemIds = $validated['food_item_ids'] ?? [];

        unset(
            $validated['branch_ids'],
            $validated['order_type_ids'],
            $validated['food_group_ids'],
            $validated['food_item_ids']
        );

        $discount->update($validated);

        if ($request->has('branch_ids')) {
            $discount->branches()->sync($branchIds);
        } else {
            $discount->branches()->detach();
        }

        if ($request->has('order_type_ids')) {
            $discount->orderTypes()->sync($orderTypeIds);
        } else {
            $discount->orderTypes()->detach();
        }

        if ($request->has('food_group_ids')) {
            $discount->foodGroups()->sync($foodGroupIds);
        } else {
            $discount->foodGroups()->detach();
        }

        if ($request->has('food_item_ids')) {
            $discount->foodItems()->sync($foodItemIds);
        } else {
            $discount->foodItems()->detach();
        }

        return back()->with('success', 'Discount updated successfully.');
    }

    public function destroy(Discount $discount)
    {
        $discount->branches()->detach();
        $discount->orderTypes()->detach();
        $discount->foodGroups()->detach();
        $discount->foodItems()->detach();
        $discount->delete();

        return back()->with('success', 'Discount deleted successfully.');
    }

    /**
     * API for POS terminal to fetch all eligible discounts for current cart context
     */
    public function getAvailableDiscounts(Request $request)
    {
        $validated = $request->validate([
            'branch_id' => 'nullable|exists:branches,id',
            'order_type_id' => 'nullable|exists:order_types,id',
            'subtotal' => 'nullable|numeric|min:0',
            'cart_subtotal' => 'nullable|numeric|min:0',
            'addon_amount' => 'nullable|numeric|min:0',
            'card_number' => 'nullable|string',
            'card_bin' => 'nullable|string',
            'items' => 'nullable|array',
        ]);

        $branchId = isset($validated['branch_id']) ? (int)$validated['branch_id'] : null;
        $orderTypeId = isset($validated['order_type_id']) ? (int)$validated['order_type_id'] : null;
        $subtotal = isset($validated['subtotal']) ? (float)$validated['subtotal'] : (float)($validated['cart_subtotal'] ?? 0);
        $addonAmount = isset($validated['addon_amount']) ? (float)$validated['addon_amount'] : 0.00;
        $cardInput = $validated['card_bin'] ?? $validated['card_number'] ?? null;
        $cardNumber = $cardInput ? substr(preg_replace('/\D/', '', $cardInput), 0, 6) : null;
        $items = $validated['items'] ?? [];

        $allActiveDiscounts = Discount::active()
            ->with(['branches:id,name', 'orderTypes:id,name', 'foodGroups:id,name', 'foodItems:id,name', 'party:id,name'])
            ->get();

        $now = Carbon::now();
        $eligible = [];

        foreach ($allActiveDiscounts as $discount) {
            if ($discount->isApplicableTo($branchId, $orderTypeId, $subtotal, $now, $cardNumber)) {
                $discountAmount = $discount->calculateDiscountAmount($subtotal, $items, $addonAmount);
                if ($discountAmount > 0) {
                    $eligible[] = [
                        'id' => $discount->id,
                        'name' => $discount->name,
                        'code' => $discount->code,
                        'description' => $discount->description,
                        'type' => $discount->type,
                        'value' => (float)$discount->value,
                        'scope' => $discount->scope,
                        'discount_amount' => $discountAmount,
                        'apply_tax_timing' => $discount->apply_tax_timing,
                        'apply_addon_timing' => $discount->apply_addon_timing,
                        'merchant_share_percentage' => (float)$discount->merchant_share_percentage,
                        'partner_share_percentage' => (float)$discount->partner_share_percentage,
                        'is_bank_discount' => (bool)$discount->is_bank_discount,
                        'bank_name' => $discount->bank_name,
                        'party' => $discount->party ? ['id' => $discount->party->id, 'name' => $discount->party->name] : null,
                        'requires_manager_approval' => (bool)$discount->requires_manager_approval,
                    ];
                }
            }
        }

        return response()->json([
            'success' => true,
            'subtotal' => $subtotal,
            'count' => count($eligible),
            'discounts' => $eligible,
            'available_discounts' => $eligible,
        ]);
    }

    /**
     * Validate and apply a specific discount or voucher code for POS Terminal
     */
    public function validateVoucher(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string',
            'branch_id' => 'nullable|exists:branches,id',
            'order_type_id' => 'nullable|exists:order_types,id',
            'subtotal' => 'nullable|numeric|min:0',
            'cart_subtotal' => 'nullable|numeric|min:0',
            'addon_amount' => 'nullable|numeric|min:0',
            'card_number' => 'nullable|string',
            'card_bin' => 'nullable|string',
            'items' => 'nullable|array',
        ]);

        $code = trim($validated['code']);
        $branchId = isset($validated['branch_id']) ? (int)$validated['branch_id'] : null;
        $orderTypeId = isset($validated['order_type_id']) ? (int)$validated['order_type_id'] : null;
        $subtotal = isset($validated['subtotal']) ? (float)$validated['subtotal'] : (float)($validated['cart_subtotal'] ?? 0);
        $addonAmount = isset($validated['addon_amount']) ? (float)$validated['addon_amount'] : 0.00;
        $cardInput = $validated['card_bin'] ?? $validated['card_number'] ?? null;
        $cardNumber = $cardInput ? substr(preg_replace('/\D/', '', $cardInput), 0, 6) : null;
        $items = $validated['items'] ?? [];

        $discount = Discount::where('code', $code)->first();

        if (!$discount) {
            return response()->json([
                'success' => false,
                'message' => "Invalid voucher code '{$code}'. Please check and try again.",
            ], 422);
        }

        if (!$discount->is_active) {
            return response()->json([
                'success' => false,
                'message' => "Voucher code '{$code}' is currently inactive or disabled.",
            ], 422);
        }

        $now = Carbon::now();

        // DateTime range checks
        if (!empty($discount->valid_from) && $now->lt($discount->valid_from)) {
            return response()->json([
                'success' => false,
                'message' => "Voucher '{$code}' is not active yet. Valid from " . $discount->valid_from->format('d M Y, h:i A') . ".",
            ], 422);
        }

        if (!empty($discount->valid_to) && $now->gt($discount->valid_to)) {
            return response()->json([
                'success' => false,
                'message' => "Voucher '{$code}' expired on " . $discount->valid_to->format('d M Y, h:i A') . ".",
            ], 422);
        }

        // Legacy date range check
        $todayDate = $now->format('Y-m-d');
        if (!empty($discount->start_date) && $todayDate < $discount->start_date->format('Y-m-d')) {
            return response()->json([
                'success' => false,
                'message' => "Voucher '{$code}' is only valid starting from " . $discount->start_date->format('d M Y') . ".",
            ], 422);
        }
        if (!empty($discount->end_date) && $todayDate > $discount->end_date->format('Y-m-d')) {
            return response()->json([
                'success' => false,
                'message' => "Voucher '{$code}' expired on " . $discount->end_date->format('d M Y') . ".",
            ], 422);
        }

        // Time window check
        $currentTime = $now->format('H:i:s');
        if (!empty($discount->start_time) && $currentTime < $discount->start_time) {
            return response()->json([
                'success' => false,
                'message' => "Voucher '{$code}' is only valid between " . substr($discount->start_time, 0, 5) . " and " . substr($discount->end_time, 0, 5) . ".",
            ], 422);
        }
        if (!empty($discount->end_time) && $currentTime > $discount->end_time) {
            return response()->json([
                'success' => false,
                'message' => "Voucher '{$code}' is only valid until " . substr($discount->end_time, 0, 5) . ".",
            ], 422);
        }

        // Days of week check
        if (!empty($discount->days_of_week) && is_array($discount->days_of_week) && count($discount->days_of_week) > 0) {
            $currentDay = strtolower($now->format('l'));
            if (!in_array($currentDay, array_map('strtolower', $discount->days_of_week))) {
                return response()->json([
                    'success' => false,
                    'message' => "Voucher '{$code}' is not valid on {$now->format('l')}s.",
                ], 422);
            }
        }

        // Branch restriction check
        if ($branchId && $discount->branches()->exists()) {
            if (!$discount->branches()->where('branches.id', $branchId)->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => "Voucher '{$code}' is not applicable at the selected branch.",
                ], 422);
            }
        }

        // Order type restriction check
        if ($orderTypeId && $discount->orderTypes()->exists()) {
            if (!$discount->orderTypes()->where('order_types.id', $orderTypeId)->exists()) {
                $otName = \App\Models\OrderType::find($orderTypeId)?->name ?? 'this order type';
                return response()->json([
                    'success' => false,
                    'message' => "Voucher '{$code}' is not valid for {$otName} orders.",
                ], 422);
            }
        }

        // Minimum order amount
        if (!is_null($discount->min_order_amount) && $subtotal < (float)$discount->min_order_amount) {
            return response()->json([
                'success' => false,
                'message' => "Minimum order subtotal of Rs. " . number_format($discount->min_order_amount, 2) . " required for voucher '{$code}'.",
            ], 422);
        }

        // Card BIN check if bank discount
        if (!empty($discount->card_numbers)) {
            if (empty($cardNumber)) {
                return response()->json([
                    'success' => false,
                    'message' => "Voucher '{$code}' requires an eligible bank payment card.",
                ], 422);
            }
            $cleanCard = preg_replace('/\D/', '', $cardNumber);
            $rawNumbers = preg_split('/[\r\n,]+/', $discount->card_numbers, -1, PREG_SPLIT_NO_EMPTY);
            $matched = false;
            foreach ($rawNumbers as $num) {
                $cleanNum = preg_replace('/\D/', '', trim($num));
                if (!empty($cleanNum) && str_starts_with($cleanCard, $cleanNum)) {
                    $matched = true;
                    break;
                }
            }
            if (!$matched) {
                return response()->json([
                    'success' => false,
                    'message' => "The entered card number does not match eligible cards for voucher '{$code}'.",
                ], 422);
            }
        }

        $discountAmount = $discount->calculateDiscountAmount($subtotal, $items, $addonAmount);
        if ($discountAmount <= 0) {
            return response()->json([
                'success' => false,
                'message' => "None of the items in your cart qualify for voucher '{$code}'.",
            ], 422);
        }

        return response()->json([
            'success' => true,
            'valid' => true,
            'message' => "Voucher '{$discount->name}' applied successfully!",
            'discount' => [
                'id' => $discount->id,
                'name' => $discount->name,
                'code' => $discount->code,
                'description' => $discount->description,
                'type' => $discount->type,
                'value' => (float)$discount->value,
                'scope' => $discount->scope,
                'discount_amount' => $discountAmount,
                'apply_tax_timing' => $discount->apply_tax_timing,
                'apply_addon_timing' => $discount->apply_addon_timing,
                'merchant_share_percentage' => (float)$discount->merchant_share_percentage,
                'partner_share_percentage' => (float)$discount->partner_share_percentage,
                'is_bank_discount' => (bool)$discount->is_bank_discount,
                'bank_name' => $discount->bank_name,
                'party' => $discount->party ? ['id' => $discount->party->id, 'name' => $discount->party->name] : null,
                'requires_manager_approval' => (bool)$discount->requires_manager_approval,
            ]
        ]);
    }
}
