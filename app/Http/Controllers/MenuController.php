<?php

namespace App\Http\Controllers;

use App\Models\Menu;
use App\Models\Branch;
use App\Models\FoodItem;
use Illuminate\Http\Request;
use Inertia\Inertia;

class MenuController extends Controller
{
    public function index()
    {
        $menus = Menu::with(['branches', 'items'])->latest()->get();
        $branches = Branch::where('is_active', true)->select('id', 'name')->get();
        $foodItems = FoodItem::where('is_active', true)->select('id', 'name', 'base_price', 'food_group_id')->with('foodGroup:id,name')->get();

        return Inertia::render('Menus/Index', [
            'menus' => $menus,
            'branches' => $branches,
            'foodItems' => $foodItems,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'tax_percentage' => 'numeric|min:0|max:100',
            'discount_percentage' => 'numeric|min:0|max:100',
            'branch_ids' => 'array',
            'branch_ids.*' => 'exists:branches,id',
            'item_ids' => 'array',
            'item_ids.*' => 'exists:food_items,id',
        ]);

        $menu = Menu::create([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'is_active' => $validated['is_active'] ?? true,
            'tax_percentage' => $validated['tax_percentage'] ?? 0,
            'discount_percentage' => $validated['discount_percentage'] ?? 0,
        ]);

        if (isset($validated['branch_ids'])) {
            $menu->branches()->sync($validated['branch_ids']);
        }
        if (isset($validated['item_ids'])) {
            $menu->items()->sync($validated['item_ids']);
        }

        return redirect()->back()->with('success', 'Menu created successfully.');
    }

    public function update(Request $request, Menu $menu)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'tax_percentage' => 'numeric|min:0|max:100',
            'discount_percentage' => 'numeric|min:0|max:100',
            'branch_ids' => 'array',
            'branch_ids.*' => 'exists:branches,id',
            'item_ids' => 'array',
            'item_ids.*' => 'exists:food_items,id',
        ]);

        $menu->update([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'is_active' => $validated['is_active'] ?? true,
            'tax_percentage' => $validated['tax_percentage'] ?? 0,
            'discount_percentage' => $validated['discount_percentage'] ?? 0,
        ]);

        if (isset($validated['branch_ids'])) {
            $menu->branches()->sync($validated['branch_ids']);
        } else {
            $menu->branches()->detach();
        }

        if (isset($validated['item_ids'])) {
            $menu->items()->sync($validated['item_ids']);
        } else {
            $menu->items()->detach();
        }

        return redirect()->back()->with('success', 'Menu updated successfully.');
    }

    public function destroy(Menu $menu)
    {
        $menu->delete();
        return redirect()->back()->with('success', 'Menu deleted successfully.');
    }
}
