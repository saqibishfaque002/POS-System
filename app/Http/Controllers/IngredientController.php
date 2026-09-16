<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\IngredientGroup;
use App\Models\IngredientItem;
use App\Models\IngredientUnit;

class IngredientController extends Controller
{
    public function index()
    {
        return Inertia::render('Ingredients/Index', [
            'ingredientGroups' => IngredientGroup::all(),
            'ingredientItems' => IngredientItem::with(['group', 'purchaseUnit', 'consumptionUnit'])->get(),
            'ingredientUnits' => IngredientUnit::all(),
        ]);
    }

    // Ingredient Unit
    public function storeIngredientUnit(Request $request) {
        $validated = $request->validate(['name' => 'required|string', 'short_code' => 'nullable|string', 'is_active' => 'boolean']);
        if (!isset($validated['is_active'])) $validated['is_active'] = false;
        IngredientUnit::create($validated);
        return back();
    }
    public function updateIngredientUnit(Request $request, IngredientUnit $ingredientUnit) {
        $validated = $request->validate(['name' => 'required|string', 'short_code' => 'nullable|string', 'is_active' => 'boolean']);
        if (!isset($validated['is_active'])) $validated['is_active'] = false;
        $ingredientUnit->update($validated);
        return back();
    }
    public function destroyIngredientUnit(IngredientUnit $ingredientUnit) {
        $ingredientUnit->delete();
        return back();
    }

    // Ingredient Group
    public function storeIngredientGroup(Request $request) {
        $validated = $request->validate(['name' => 'required|string', 'description' => 'nullable|string', 'is_active' => 'boolean']);
        if (!isset($validated['is_active'])) $validated['is_active'] = false;
        IngredientGroup::create($validated);
        return back();
    }
    public function updateIngredientGroup(Request $request, IngredientGroup $ingredientGroup) {
        $validated = $request->validate(['name' => 'required|string', 'description' => 'nullable|string', 'is_active' => 'boolean']);
        if (!isset($validated['is_active'])) $validated['is_active'] = false;
        $ingredientGroup->update($validated);
        return back();
    }
    public function destroyIngredientGroup(IngredientGroup $ingredientGroup) {
        $ingredientGroup->delete();
        return back();
    }

    // Ingredient Item
    public function storeIngredientItem(Request $request) {
        $validated = $request->validate([
            'ingredient_group_id' => 'required|exists:ingredient_groups,id',
            'name' => 'required|string',
            'base_price' => 'required|numeric',
            'description' => 'nullable|string',
            'purchase_unit_id' => 'required|exists:ingredient_units,id',
            'conversion_qty' => 'required|numeric',
            'consumption_unit_id' => 'required|exists:ingredient_units,id',
            'consumption_qty' => 'required|numeric',
            'is_active' => 'boolean'
        ]);
        if (!isset($validated['is_active'])) $validated['is_active'] = false;
        IngredientItem::create($validated);
        return back();
    }
    public function updateIngredientItem(Request $request, IngredientItem $ingredientItem) {
        $validated = $request->validate([
            'ingredient_group_id' => 'required|exists:ingredient_groups,id',
            'name' => 'required|string',
            'base_price' => 'required|numeric',
            'description' => 'nullable|string',
            'purchase_unit_id' => 'required|exists:ingredient_units,id',
            'conversion_qty' => 'required|numeric',
            'consumption_unit_id' => 'required|exists:ingredient_units,id',
            'consumption_qty' => 'required|numeric',
            'is_active' => 'boolean'
        ]);
        if (!isset($validated['is_active'])) $validated['is_active'] = false;
        $ingredientItem->update($validated);
        return back();
    }
    public function destroyIngredientItem(IngredientItem $ingredientItem) {
        $ingredientItem->delete();
        return back();
    }
}
