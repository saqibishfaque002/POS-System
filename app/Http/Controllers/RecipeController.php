<?php

namespace App\Http\Controllers;

use App\Models\Recipe;
use App\Models\FoodItem;
use App\Models\IngredientItem;
use App\Models\PropertyItem;
use App\Models\IngredientUnit;
use App\Models\OrderType;
use Illuminate\Http\Request;
use Inertia\Inertia;

class RecipeController extends Controller
{
    public function index()
    {
        $recipes = Recipe::with(['recipeable', 'ingredients.ingredientItem.consumptionUnit', 'ingredients.unit', 'ingredients.orderTypes'])->latest()->get();
        
        $foodItems = FoodItem::with('variations.variation')->where('is_active', true)->get();
        $propertyItems = PropertyItem::where('is_active', true)->get();
        $ingredientItems = IngredientItem::with(['purchaseUnit', 'consumptionUnit'])->where('is_active', true)->get();
        $units = IngredientUnit::all();
        $orderTypes = OrderType::where('is_active', true)->get();

        return Inertia::render('Catalog/Recipes/Index', [
            'recipes' => $recipes,
            'foodItems' => $foodItems,
            'propertyItems' => $propertyItems,
            'ingredientItems' => $ingredientItems,
            'units' => $units,
            'orderTypes' => $orderTypes,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'recipeable_id' => 'required|integer',
            'recipeable_type' => 'required|string|in:App\Models\FoodItem,App\Models\IngredientItem,App\Models\PropertyItem,App\Models\FoodItemVariation',
            'total_cost' => 'required|numeric|min:0',
            'yield_quantity' => 'required|numeric|min:0.001',
            'instructions' => 'nullable|string',
            'status' => 'required|in:active,inactive',
            'items' => 'required|array|min:1',
            'items.*.ingredient_item_id' => 'required|exists:ingredient_items,id',
            'items.*.quantity' => 'required|numeric|min:0.001',
            'items.*.unit_id' => 'required|exists:ingredient_units,id',
            'items.*.order_type_ids' => 'nullable|array',
            'items.*.order_type_ids.*' => 'exists:order_types,id',
        ]);

        // Check if recipe already exists for this item
        if (Recipe::where('recipeable_id', $validated['recipeable_id'])
            ->where('recipeable_type', $validated['recipeable_type'])
            ->exists()) {
            return redirect()->back()->withErrors(['recipeable_id' => 'A recipe already exists for this item.']);
        }

        $recipe = Recipe::create([
            'recipeable_id' => $validated['recipeable_id'],
            'recipeable_type' => $validated['recipeable_type'],
            'total_cost' => $validated['total_cost'],
            'yield_quantity' => $validated['yield_quantity'],
            'instructions' => $validated['instructions'],
            'status' => $validated['status'],
        ]);

        foreach ($validated['items'] as $item) {
            $ingredient = $recipe->ingredients()->create([
                'ingredient_item_id' => $item['ingredient_item_id'],
                'quantity' => $item['quantity'],
                'unit_id' => $item['unit_id'],
            ]);

            if (!empty($item['order_type_ids'])) {
                $ingredient->orderTypes()->sync($item['order_type_ids']);
            }
        }

        return redirect()->back()->with('success', 'Recipe created successfully.');
    }

    public function update(Request $request, Recipe $recipe)
    {
        $validated = $request->validate([
            'recipeable_type' => 'nullable|string|in:App\Models\FoodItem,App\Models\IngredientItem,App\Models\PropertyItem,App\Models\FoodItemVariation',
            'total_cost' => 'required|numeric|min:0',
            'yield_quantity' => 'required|numeric|min:0.001',
            'instructions' => 'nullable|string',
            'status' => 'required|in:active,inactive',
            'items' => 'required|array|min:1',
            'items.*.ingredient_item_id' => 'required|exists:ingredient_items,id',
            'items.*.quantity' => 'required|numeric|min:0.001',
            'items.*.unit_id' => 'required|exists:ingredient_units,id',
            'items.*.order_type_ids' => 'nullable|array',
            'items.*.order_type_ids.*' => 'exists:order_types,id',
        ]);

        $recipe->update([
            'total_cost' => $validated['total_cost'],
            'yield_quantity' => $validated['yield_quantity'],
            'instructions' => $validated['instructions'],
            'status' => $validated['status'],
        ]);

        $recipe->ingredients()->delete();

        foreach ($validated['items'] as $item) {
            $ingredient = $recipe->ingredients()->create([
                'ingredient_item_id' => $item['ingredient_item_id'],
                'quantity' => $item['quantity'],
                'unit_id' => $item['unit_id'],
            ]);

            if (!empty($item['order_type_ids'])) {
                $ingredient->orderTypes()->sync($item['order_type_ids']);
            }
        }

        return redirect()->back()->with('success', 'Recipe updated successfully.');
    }

    public function destroy(Recipe $recipe)
    {
        $recipe->delete();
        return redirect()->back()->with('success', 'Recipe deleted successfully.');
    }
}
