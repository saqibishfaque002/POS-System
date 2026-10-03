<?php

namespace App\Http\Controllers;

use App\Models\Production;
use App\Models\ProductionItem;
use App\Models\Recipe;
use App\Models\Branch;
use App\Models\IngredientItem;
use App\Models\InventoryLedger;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class ProductionController extends Controller
{
    public function index()
    {
        $productions = Production::with(['branch', 'recipe.recipeable', 'items.ingredientItem', 'items.unit', 'creator', 'completer'])->latest()->get();
        // Load recipes that have recipeable (like Sub-Recipe or FoodItem) and ingredients
        $recipes = Recipe::with(['recipeable', 'ingredients.ingredientItem.consumptionUnit', 'ingredients.unit'])->where('status', 'active')->get();
        $branches = Branch::where('is_active', true)->get();

        return Inertia::render('Production/Index', [
            'productions' => $productions,
            'recipes' => $recipes,
            'branches' => $branches,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'branch_id' => 'required|exists:branches,id',
            'production_type' => 'required|string|in:raw_material,food_item',
            'recipe_id' => 'required|exists:recipes,id',
            'target_yield_qty' => 'required|numeric|min:0.01',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.ingredient_item_id' => 'required|exists:ingredient_items,id',
            'items.*.expected_qty' => 'required|numeric|min:0',
            'items.*.actual_qty' => 'nullable|numeric|min:0',
            'items.*.unit_id' => 'required|exists:ingredient_units,id',
            'items.*.unit_cost' => 'required|numeric|min:0',
            'items.*.total_cost' => 'required|numeric|min:0',
        ]);

        DB::transaction(function () use ($validated, $request) {
            $latest = Production::latest('id')->first();
            $nextId = $latest ? $latest->id + 1 : 1;
            
            $totalCost = array_sum(array_column($validated['items'], 'total_cost'));

            $production = Production::create([
                'document_number' => 'PROD-' . str_pad($nextId, 4, '0', STR_PAD_LEFT),
                'branch_id' => $validated['branch_id'],
                'recipe_id' => $validated['recipe_id'],
                'production_type' => $validated['production_type'],
                'target_yield_qty' => $validated['target_yield_qty'],
                'total_cost' => $totalCost,
                'status' => 'draft',
                'notes' => $validated['notes'] ?? null,
                'created_by' => Auth::id(),
            ]);

            foreach ($validated['items'] as $item) {
                ProductionItem::create([
                    'production_id' => $production->id,
                    'ingredient_item_id' => $item['ingredient_item_id'],
                    'expected_qty' => $item['expected_qty'],
                    'actual_qty' => $item['actual_qty'] ?? $item['expected_qty'],
                    'unit_id' => $item['unit_id'],
                    'unit_cost' => $item['unit_cost'],
                    'total_cost' => $item['total_cost'],
                ]);
            }
        });

        return back()->with('success', 'Production record created successfully.');
    }

    public function update(Request $request, Production $production)
    {
        if ($production->status === 'completed' || $production->status === 'cancelled') {
            return back()->with('error', 'Cannot edit a completed or cancelled production.');
        }

        $validated = $request->validate([
            'branch_id' => 'required|exists:branches,id',
            'production_type' => 'required|string|in:raw_material,food_item',
            'recipe_id' => 'required|exists:recipes,id',
            'target_yield_qty' => 'required|numeric|min:0.01',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.id' => 'nullable|exists:production_items,id',
            'items.*.ingredient_item_id' => 'required|exists:ingredient_items,id',
            'items.*.expected_qty' => 'required|numeric|min:0',
            'items.*.actual_qty' => 'nullable|numeric|min:0',
            'items.*.unit_id' => 'required|exists:ingredient_units,id',
            'items.*.unit_cost' => 'required|numeric|min:0',
            'items.*.total_cost' => 'required|numeric|min:0',
        ]);

        DB::transaction(function () use ($validated, $production) {
            $totalCost = array_sum(array_column($validated['items'], 'total_cost'));

            $production->update([
                'branch_id' => $validated['branch_id'],
                'recipe_id' => $validated['recipe_id'],
                'production_type' => $validated['production_type'],
                'target_yield_qty' => $validated['target_yield_qty'],
                'total_cost' => $totalCost,
                'notes' => $validated['notes'] ?? null,
            ]);

            // Sync items
            $existingItemIds = [];
            foreach ($validated['items'] as $item) {
                if (isset($item['id']) && $item['id']) {
                    $prodItem = ProductionItem::find($item['id']);
                    if ($prodItem) {
                        $prodItem->update([
                            'ingredient_item_id' => $item['ingredient_item_id'],
                            'expected_qty' => $item['expected_qty'],
                            'actual_qty' => $item['actual_qty'] ?? $item['expected_qty'],
                            'unit_id' => $item['unit_id'],
                            'unit_cost' => $item['unit_cost'],
                            'total_cost' => $item['total_cost'],
                        ]);
                        $existingItemIds[] = $prodItem->id;
                    }
                } else {
                    $newProdItem = ProductionItem::create([
                        'production_id' => $production->id,
                        'ingredient_item_id' => $item['ingredient_item_id'],
                        'expected_qty' => $item['expected_qty'],
                        'actual_qty' => $item['actual_qty'] ?? $item['expected_qty'],
                        'unit_id' => $item['unit_id'],
                        'unit_cost' => $item['unit_cost'],
                        'total_cost' => $item['total_cost'],
                    ]);
                    $existingItemIds[] = $newProdItem->id;
                }
            }

            // Delete removed items
            ProductionItem::where('production_id', $production->id)
                ->whereNotIn('id', $existingItemIds)
                ->delete();
        });

        return back()->with('success', 'Production record updated successfully.');
    }

    public function updateStatus(Request $request, Production $production)
    {
        $validated = $request->validate([
            'status' => 'required|in:draft,in_progress,completed,cancelled',
            'actual_yield_qty' => 'required_if:status,completed|nullable|numeric|min:0',
            'wastage_qty' => 'nullable|numeric|min:0',
        ]);

        if ($production->status === 'completed' || $production->status === 'cancelled') {
            return back()->with('error', 'Document is already ' . $production->status);
        }

        DB::transaction(function () use ($validated, $production) {
            $production->update([
                'status' => $validated['status'],
                'actual_yield_qty' => $validated['actual_yield_qty'] ?? null,
                'wastage_qty' => $validated['wastage_qty'] ?? 0,
                'completed_by' => $validated['status'] === 'completed' ? Auth::id() : null,
                'completed_at' => $validated['status'] === 'completed' ? now() : null,
            ]);

            if ($validated['status'] === 'completed') {
                $production->load(['items', 'recipe']);
                
                // 1. Deduct Raw Materials (Credit)
                foreach ($production->items as $item) {
                    $qtyToDeduct = $item->actual_qty ?? $item->expected_qty;
                    InventoryLedger::create([
                        'branch_id' => $production->branch_id,
                        'document_type' => 'App\Models\Production',
                        'document_id' => $production->id,
                        'item_id' => $item->ingredient_item_id,
                        'quantity' => -$qtyToDeduct,
                        'unit_price' => $item->unit_cost,
                        'document_date' => now()->toDateString(),
                        'notes' => 'Consumed in production: ' . $production->document_number,
                        'created_by' => Auth::id(),
                    ]);
                }

                // 2. Add Finished/Prepared Item (Debit)
                // The recipeable could be an IngredientItem (e.g. sub-recipe) or FoodItem
                // InventoryLedger only supports IngredientItem foreign key 'item_id'.
                if ($production->recipe && $production->recipe->recipeable_type === 'App\Models\IngredientItem') {
                    $producedQty = $production->actual_yield_qty ?? $production->target_yield_qty;
                    // Calculate unit cost based on total material cost divided by actual yield
                    $unitCost = $producedQty > 0 ? ($production->total_cost / $producedQty) : 0;
                    
                    InventoryLedger::create([
                        'branch_id' => $production->branch_id,
                        'document_type' => 'App\Models\Production',
                        'document_id' => $production->id,
                        'item_id' => $production->recipe->recipeable_id,
                        'quantity' => $producedQty,
                        'unit_price' => $unitCost,
                        'document_date' => now()->toDateString(),
                        'notes' => 'Produced from recipe: ' . $production->document_number,
                        'created_by' => Auth::id(),
                    ]);
                }
            }
        });

        return back()->with('success', 'Production status updated to ' . $validated['status']);
    }

    public function destroy(Production $production)
    {
        if ($production->status === 'completed') {
            return back()->with('error', 'Cannot delete a completed production record.');
        }

        $production->delete();
        return back()->with('success', 'Production record deleted successfully.');
    }
}
