<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\FoodGroup;
use App\Models\FoodItem;
use App\Models\PropertyGroup;
use App\Models\PropertyItem;
use App\Models\Variation;
use App\Http\Requests\Catalog\FoodGroupRequest;
use App\Http\Requests\Catalog\FoodItemRequest;
use App\Http\Requests\Catalog\PropertyGroupRequest;
use App\Http\Requests\Catalog\PropertyItemRequest;
use App\Http\Requests\Catalog\VariationRequest;

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
    public function storeFoodGroup(FoodGroupRequest $request) {
        FoodGroup::create($request->validated());
        return back();
    }
    public function updateFoodGroup(FoodGroupRequest $request, FoodGroup $foodGroup) {
        $foodGroup->update($request->validated());
        return back();
    }
    public function destroyFoodGroup(FoodGroup $foodGroup) {
        $foodGroup->delete();
        return back();
    }

    // Food Item
    public function storeFoodItem(FoodItemRequest $request) {
        FoodItem::create($request->validated());
        return back();
    }
    public function updateFoodItem(FoodItemRequest $request, FoodItem $foodItem) {
        $foodItem->update($request->validated());
        return back();
    }
    public function destroyFoodItem(FoodItem $foodItem) {
        $foodItem->delete();
        return back();
    }

    // Property Group
    public function storePropertyGroup(PropertyGroupRequest $request) {
        PropertyGroup::create($request->validated());
        return back();
    }
    public function updatePropertyGroup(PropertyGroupRequest $request, PropertyGroup $propertyGroup) {
        $propertyGroup->update($request->validated());
        return back();
    }
    public function destroyPropertyGroup(PropertyGroup $propertyGroup) {
        $propertyGroup->delete();
        return back();
    }

    // Property Item
    public function storePropertyItem(PropertyItemRequest $request) {
        PropertyItem::create($request->validated());
        return back();
    }
    public function updatePropertyItem(PropertyItemRequest $request, PropertyItem $propertyItem) {
        $propertyItem->update($request->validated());
        return back();
    }
    public function destroyPropertyItem(PropertyItem $propertyItem) {
        $propertyItem->delete();
        return back();
    }

    // Variation
    public function storeVariation(VariationRequest $request) {
        Variation::create($request->validated());
        return back();
    }
    public function updateVariation(VariationRequest $request, Variation $variation) {
        $variation->update($request->validated());
        return back();
    }
    public function destroyVariation(Variation $variation) {
        $variation->delete();
        return back();
    }
}
