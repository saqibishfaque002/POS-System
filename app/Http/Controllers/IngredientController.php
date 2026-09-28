<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\IngredientGroup;
use App\Models\IngredientItem;
use App\Models\IngredientUnit;
use App\Http\Requests\Ingredients\IngredientUnitRequest;
use App\Http\Requests\Ingredients\IngredientGroupRequest;
use App\Http\Requests\Ingredients\IngredientItemRequest;

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
    public function storeIngredientUnit(IngredientUnitRequest $request) {
        IngredientUnit::create($request->validated());
        return back();
    }
    public function updateIngredientUnit(IngredientUnitRequest $request, IngredientUnit $ingredientUnit) {
        $ingredientUnit->update($request->validated());
        return back();
    }
    public function destroyIngredientUnit(IngredientUnit $ingredientUnit) {
        $ingredientUnit->delete();
        return back();
    }

    // Ingredient Group
    public function storeIngredientGroup(IngredientGroupRequest $request) {
        IngredientGroup::create($request->validated());
        return back();
    }
    public function updateIngredientGroup(IngredientGroupRequest $request, IngredientGroup $ingredientGroup) {
        $ingredientGroup->update($request->validated());
        return back();
    }
    public function destroyIngredientGroup(IngredientGroup $ingredientGroup) {
        $ingredientGroup->delete();
        return back();
    }

    // Ingredient Item
    public function storeIngredientItem(IngredientItemRequest $request) {
        IngredientItem::create($request->validated());
        return back();
    }
    public function updateIngredientItem(IngredientItemRequest $request, IngredientItem $ingredientItem) {
        $ingredientItem->update($request->validated());
        return back();
    }
    public function destroyIngredientItem(IngredientItem $ingredientItem) {
        $ingredientItem->delete();
        return back();
    }
}
