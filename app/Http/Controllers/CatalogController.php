<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\FoodGroup;
use App\Models\FoodItem;
use App\Models\PropertyGroup;
use App\Models\PropertyItem;
use App\Models\FoodItemVariation;
use App\Models\Variation;
use App\Models\Branch;
use App\Models\Menu;
use App\Http\Requests\Catalog\FoodGroupRequest;
use App\Http\Requests\Catalog\FoodItemRequest;
use App\Http\Requests\Catalog\PropertyGroupRequest;
use App\Http\Requests\Catalog\PropertyItemRequest;

class CatalogController extends Controller
{
    public function index()
    {
        return Inertia::render('Catalog/Index', [
            'foodGroups' => FoodGroup::all(),
            'foodItems' => FoodItem::with(['foodGroup', 'variations.variation', 'branches', 'menus'])->get(),
            'propertyGroups' => PropertyGroup::all(),
            'propertyItems' => PropertyItem::with('propertyGroup')->get(),
            'globalVariations' => Variation::all(),
            'branches' => Branch::where('is_active', true)->select('id', 'name')->get(),
            'menus' => Menu::where('is_active', true)->select('id', 'name')->get(),
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
        $validated = $request->validated();
        $foodItem = FoodItem::create([
            'food_group_id' => $validated['food_group_id'],
            'name' => $validated['name'],
            'base_price' => $validated['has_variations'] ? 0 : $validated['base_price'],
            'description' => $validated['description'] ?? null,
            'is_active' => $validated['is_active'] ?? true,
        ]);

        if ($validated['has_variations'] && !empty($validated['variations'])) {
            foreach ($validated['variations'] as $v) {
                $foodItem->variations()->create([
                    'variation_id' => $v['variation_id'],
                    'price' => $v['price'],
                    'sku' => $v['sku'] ?? null,
                    'is_active' => true,
                ]);
            }
        }

        if (isset($validated['branch_ids'])) {
            $foodItem->branches()->sync($validated['branch_ids']);
        }
        if (isset($validated['menu_ids'])) {
            $foodItem->menus()->sync($validated['menu_ids']);
        }

        return back()->with('success', 'Food item created successfully.');
    }

    public function updateFoodItem(FoodItemRequest $request, FoodItem $foodItem) {
        $validated = $request->validated();
        $foodItem->update([
            'food_group_id' => $validated['food_group_id'],
            'name' => $validated['name'],
            'base_price' => $validated['has_variations'] ? 0 : $validated['base_price'],
            'description' => $validated['description'] ?? null,
            'is_active' => $validated['is_active'] ?? true,
        ]);

        $foodItem->variations()->delete();
        if ($validated['has_variations'] && !empty($validated['variations'])) {
            foreach ($validated['variations'] as $v) {
                $foodItem->variations()->create([
                    'variation_id' => $v['variation_id'],
                    'price' => $v['price'],
                    'sku' => $v['sku'] ?? null,
                    'is_active' => true,
                ]);
            }
        }

        if (isset($validated['branch_ids'])) {
            $foodItem->branches()->sync($validated['branch_ids']);
        } else {
            $foodItem->branches()->detach();
        }

        if (isset($validated['menu_ids'])) {
            $foodItem->menus()->sync($validated['menu_ids']);
        } else {
            $foodItem->menus()->detach();
        }

        return back()->with('success', 'Food item updated successfully.');
    }

    public function destroyFoodItem(FoodItem $foodItem) {
        $foodItem->branches()->detach();
        $foodItem->menus()->detach();
        $foodItem->variations()->delete();
        $foodItem->delete();
        return back()->with('success', 'Food item deleted successfully.');
    }

    /**
     * Get food items matching selected branch and menu for POS Terminal
     */
    public function getItemsForBranchAndMenu(Request $request)
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
            'branch_id' => $branchId,
            'menu_id' => $menuId,
            'count' => $formatted->count(),
            'items' => $formatted,
        ]);
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

    // Global Variation
    public function storeVariation(Request $request) {
        $data = $request->validate(['name' => 'required|string|unique:variations,name', 'is_active' => 'boolean']);
        Variation::create($data);
        return back();
    }
    public function updateVariation(Request $request, Variation $variation) {
        $data = $request->validate(['name' => 'required|string|unique:variations,name,'.$variation->id, 'is_active' => 'boolean']);
        $variation->update($data);
        return back();
    }
    public function destroyVariation(Variation $variation) {
        $variation->delete();
        return back();
    }
}
