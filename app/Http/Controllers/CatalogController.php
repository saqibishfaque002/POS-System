<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\FoodGroup;
use App\Models\FoodItem;
use App\Models\PropertyGroup;
use App\Models\PropertyItem;
use App\Models\Variation;

class CatalogController extends Controller
{
    public function index()
    {
        return Inertia::render('Catalog/Index', [
            'foodGroups' => FoodGroup::all(),
            'foodItems' => FoodItem::with('foodGroup')->get(),
            'propertyGroups' => PropertyGroup::all(),
            'propertyItems' => PropertyItem::with('propertyGroup')->get(),
            'variations' => Variation::with('foodItem')->get(),
        ]);
    }

    // Food Group
    public function storeFoodGroup(Request $request) {
        $validated = $request->validate(['name' => 'required|string', 'description' => 'nullable|string', 'is_active' => 'boolean']);
        if (!isset($validated['is_active'])) $validated['is_active'] = false;
        FoodGroup::create($validated);
        return back();
    }
    public function updateFoodGroup(Request $request, FoodGroup $foodGroup) {
        $validated = $request->validate(['name' => 'required|string', 'description' => 'nullable|string', 'is_active' => 'boolean']);
        if (!isset($validated['is_active'])) $validated['is_active'] = false;
        $foodGroup->update($validated);
        return back();
    }
    public function destroyFoodGroup(FoodGroup $foodGroup) {
        $foodGroup->delete();
        return back();
    }

    // Food Item
    public function storeFoodItem(Request $request) {
        $validated = $request->validate(['food_group_id' => 'required|exists:food_groups,id', 'name' => 'required|string', 'base_price' => 'required|numeric', 'description' => 'nullable|string', 'is_active' => 'boolean']);
        if (!isset($validated['is_active'])) $validated['is_active'] = false;
        FoodItem::create($validated);
        return back();
    }
    public function updateFoodItem(Request $request, FoodItem $foodItem) {
        $validated = $request->validate(['food_group_id' => 'required|exists:food_groups,id', 'name' => 'required|string', 'base_price' => 'required|numeric', 'description' => 'nullable|string', 'is_active' => 'boolean']);
        if (!isset($validated['is_active'])) $validated['is_active'] = false;
        $foodItem->update($validated);
        return back();
    }
    public function destroyFoodItem(FoodItem $foodItem) {
        $foodItem->delete();
        return back();
    }

    // Property Group
    public function storePropertyGroup(Request $request) {
        $validated = $request->validate(['name' => 'required|string', 'is_multiple' => 'boolean', 'is_active' => 'boolean']);
        if (!isset($validated['is_multiple'])) $validated['is_multiple'] = false;
        if (!isset($validated['is_active'])) $validated['is_active'] = false;
        PropertyGroup::create($validated);
        return back();
    }
    public function updatePropertyGroup(Request $request, PropertyGroup $propertyGroup) {
        $validated = $request->validate(['name' => 'required|string', 'is_multiple' => 'boolean', 'is_active' => 'boolean']);
        if (!isset($validated['is_multiple'])) $validated['is_multiple'] = false;
        if (!isset($validated['is_active'])) $validated['is_active'] = false;
        $propertyGroup->update($validated);
        return back();
    }
    public function destroyPropertyGroup(PropertyGroup $propertyGroup) {
        $propertyGroup->delete();
        return back();
    }

    // Property Item
    public function storePropertyItem(Request $request) {
        $validated = $request->validate(['property_group_id' => 'required|exists:property_groups,id', 'name' => 'required|string', 'extra_price' => 'required|numeric', 'is_active' => 'boolean']);
        if (!isset($validated['is_active'])) $validated['is_active'] = false;
        PropertyItem::create($validated);
        return back();
    }
    public function updatePropertyItem(Request $request, PropertyItem $propertyItem) {
        $validated = $request->validate(['property_group_id' => 'required|exists:property_groups,id', 'name' => 'required|string', 'extra_price' => 'required|numeric', 'is_active' => 'boolean']);
        if (!isset($validated['is_active'])) $validated['is_active'] = false;
        $propertyItem->update($validated);
        return back();
    }
    public function destroyPropertyItem(PropertyItem $propertyItem) {
        $propertyItem->delete();
        return back();
    }

    // Variation
    public function storeVariation(Request $request) {
        $validated = $request->validate(['food_item_id' => 'required|exists:food_items,id', 'name' => 'required|string', 'sku' => 'nullable|string', 'price' => 'required|numeric', 'is_active' => 'boolean']);
        if (!isset($validated['is_active'])) $validated['is_active'] = false;
        Variation::create($validated);
        return back();
    }
    public function updateVariation(Request $request, Variation $variation) {
        $validated = $request->validate(['food_item_id' => 'required|exists:food_items,id', 'name' => 'required|string', 'sku' => 'nullable|string', 'price' => 'required|numeric', 'is_active' => 'boolean']);
        if (!isset($validated['is_active'])) $validated['is_active'] = false;
        $variation->update($validated);
        return back();
    }
    public function destroyVariation(Variation $variation) {
        $variation->delete();
        return back();
    }
}
