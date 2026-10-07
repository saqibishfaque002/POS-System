<?php

namespace App\Http\Controllers\POS;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\FoodGroup;
use App\Models\FoodItem;
use App\Models\OrderType;
use App\Models\Party;
use App\Models\Table;
use App\Models\Setting;
use App\Models\PosOrder;
use App\Models\PosOrderItem;
use App\Models\PosOrderItemProperty;
use App\Models\Recipe;
use App\Models\InventoryLedger;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TerminalController extends Controller
{
    public function index()
    {
        $branchId = session('branch_id');

        $foodGroups = FoodGroup::where('is_active', true)->get();
        
        // Lazy load Food Items and their variations
        $foodItems = FoodItem::with(['variations' => function ($query) {
            $query->where('is_active', true);
        }, 'foodGroup'])->where('is_active', true)->get();
        
        $orderTypes = OrderType::where('is_active', true)->get();
        $parties = Party::where('is_active', true)->get();
        $tables = Table::whereHas('floor', function($q) use ($branchId) {
            $q->where('branch_id', $branchId);
        })->where('is_active', true)->get();
        
        // Fetch Tax settings
        $posSettings = Setting::where('group', 'pos')->get()->pluck('value', 'key');
        $taxPercent = isset($posSettings['tax_percentage']) ? (float)$posSettings['tax_percentage'] : 0;

        return Inertia::render('POS/Terminal', [
            'foodGroups' => $foodGroups,
            'foodItems' => $foodItems,
            'orderTypes' => $orderTypes,
            'parties' => $parties,
            'tables' => $tables,
            'taxPercent' => $taxPercent,
            'branchId' => $branchId
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'order_type_id' => 'required|exists:order_types,id',
            'party_id' => 'nullable|exists:parties,id',
            'table_id' => 'nullable|exists:tables,id',
            'items' => 'required|array|min:1',
            'items.*.food_item_id' => 'required|exists:food_items,id',
            'items.*.variation_id' => 'nullable|exists:variations,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_price' => 'required|numeric',
            'items.*.total_price' => 'required|numeric',
            'subtotal' => 'required|numeric',
            'discount_type' => 'nullable|in:fixed,percentage',
            'discount_value' => 'required|numeric',
            'tax_amount' => 'required|numeric',
            'grand_total' => 'required|numeric',
            'payment_status' => 'required|in:unpaid,paid',
            'status' => 'required|in:pending,completed',
            'payment_method' => 'required_if:payment_status,paid'
        ]);

        try {
            DB::beginTransaction();

            $branchId = session('branch_id');
            $orderNumber = 'POS-' . strtoupper(Str::random(8));

            $order = PosOrder::create([
                'order_number' => $orderNumber,
                'branch_id' => $branchId,
                'user_id' => auth()->id(),
                'party_id' => $request->party_id,
                'order_type_id' => $request->order_type_id,
                'table_id' => $request->table_id,
                'subtotal' => $request->subtotal,
                'discount_type' => $request->discount_type,
                'discount_value' => $request->discount_value,
                'tax_amount' => $request->tax_amount,
                'grand_total' => $request->grand_total,
                'status' => $request->status,
                'payment_status' => $request->payment_status,
            ]);

            foreach ($request->items as $item) {
                $posItem = PosOrderItem::create([
                    'pos_order_id' => $order->id,
                    'food_item_id' => $item['food_item_id'],
                    'variation_id' => $item['variation_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'total_price' => $item['total_price'],
                    'notes' => $item['notes'] ?? null,
                ]);

                // Inventory Deduction Logic if Order is Completed
                if ($order->status === 'completed') {
                    $this->deductInventoryForOrderItem($posItem, $branchId, $order);
                }
            }

            DB::commit();
            return redirect()->back()->with('success', 'Order ' . $orderNumber . ' processed successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Failed to process order: ' . $e->getMessage());
        }
    }

    private function deductInventoryForOrderItem(PosOrderItem $item, $branchId, PosOrder $order)
    {
        $recipeableType = $item->variation_id ? 'App\Models\Variation' : 'App\Models\FoodItem';
        $recipeableId = $item->variation_id ?: $item->food_item_id;

        $recipe = Recipe::where('recipeable_type', $recipeableType)
                        ->where('recipeable_id', $recipeableId)
                        ->with('ingredients')
                        ->first();

        if ($recipe) {
            $multiplier = $item->quantity;

            foreach ($recipe->ingredients as $recipeIngredient) {
                $deductQty = $recipeIngredient->quantity * $multiplier;
                
                InventoryLedger::create([
                    'item_id' => $recipeIngredient->ingredient_item_id,
                    'branch_id' => $branchId,
                    'document_type' => 'PosOrder',
                    'document_id' => $order->id,
                    'document_date' => now()->toDateString(),
                    'quantity' => -$deductQty, // Deducting
                    'unit_price' => 0, 
                    'notes' => 'POS Sale Deduction'
                ]);
            }
        }
    }
}
