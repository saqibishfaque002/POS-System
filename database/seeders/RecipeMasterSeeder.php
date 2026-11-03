<?php

namespace Database\Seeders;

use App\Models\FoodItem;
use App\Models\IngredientItem;
use App\Models\IngredientUnit;
use App\Models\Recipe;
use App\Models\RecipeIngredient;
use Illuminate\Database\Seeder;

class RecipeMasterSeeder extends Seeder
{
    /**
     * Seed master recipes (Bill of Materials) linking Food Items to Ingredient Items idempotently.
     */
    public function run(): void
    {
        $unitG = IngredientUnit::where('short_code', 'G')->first() ?? IngredientUnit::first();
        $unitML = IngredientUnit::where('short_code', 'ML')->first() ?? IngredientUnit::first();
        $unitPCS = IngredientUnit::where('short_code', 'PCS')->first() ?? IngredientUnit::first();

        $recipes = [
            'Classic Smash Beef Burger' => [
                'instructions' => 'Grill 150g beef patty on flat top, toast brioche bun, assemble with cheddar, lettuce, and tomato.',
                'yield_quantity' => 1.00,
                'total_cost' => 240.00,
                'ingredients' => [
                    ['name' => 'Prime Beef Mince (80/20)', 'quantity' => 150.00, 'unit_id' => $unitG->id],
                    ['name' => 'Brioche Sesame Burger Buns', 'quantity' => 1.00, 'unit_id' => $unitPCS->id],
                    ['name' => 'Cheddar Cheese Slices', 'quantity' => 1.00, 'unit_id' => $unitPCS->id],
                    ['name' => 'Iceberg Lettuce', 'quantity' => 20.00, 'unit_id' => $unitG->id],
                    ['name' => 'Roma Tomatoes', 'quantity' => 20.00, 'unit_id' => $unitG->id],
                    ['name' => 'Canola Cooking Oil', 'quantity' => 10.00, 'unit_id' => $unitML->id],
                ]
            ],
            'Crispy Zinger Chicken Burger' => [
                'instructions' => 'Dredge 150g chicken breast in seasoned flour, deep fry in canola oil until crispy, toast bun and dress with lettuce.',
                'yield_quantity' => 1.00,
                'total_cost' => 180.00,
                'ingredients' => [
                    ['name' => 'Boneless Chicken Breast Fillet', 'quantity' => 150.00, 'unit_id' => $unitG->id],
                    ['name' => 'Brioche Sesame Burger Buns', 'quantity' => 1.00, 'unit_id' => $unitPCS->id],
                    ['name' => 'Iceberg Lettuce', 'quantity' => 25.00, 'unit_id' => $unitG->id],
                    ['name' => 'Canola Cooking Oil', 'quantity' => 30.00, 'unit_id' => $unitML->id],
                ]
            ],
            'Margherita Basil Pizza' => [
                'instructions' => 'Stretch 250g dough ball to 10 inches, spread tomato sauce, top with 150g mozzarella and bake at 350C.',
                'yield_quantity' => 1.00,
                'total_cost' => 320.00,
                'ingredients' => [
                    ['name' => 'Artisan Pizza Dough Ball (250g)', 'quantity' => 1.00, 'unit_id' => $unitPCS->id],
                    ['name' => 'Grated Mozzarella Cheese', 'quantity' => 150.00, 'unit_id' => $unitG->id],
                    ['name' => 'Canola Cooking Oil', 'quantity' => 15.00, 'unit_id' => $unitML->id],
                ]
            ],
            'Loaded Cheesy French Fries' => [
                'instructions' => 'Deep fry 250g frozen cut fries, drain, top with melted cheddar cheese slice sauce.',
                'yield_quantity' => 1.00,
                'total_cost' => 160.00,
                'ingredients' => [
                    ['name' => 'Frozen French Fries (9mm Cut)', 'quantity' => 250.00, 'unit_id' => $unitG->id],
                    ['name' => 'Canola Cooking Oil', 'quantity' => 40.00, 'unit_id' => $unitML->id],
                    ['name' => 'Cheddar Cheese Slices', 'quantity' => 2.00, 'unit_id' => $unitPCS->id],
                ]
            ],
            'Fresh Mint Lemonade (500ml)' => [
                'instructions' => 'Squeeze 2 lemons, blend with 15g fresh mint leaves, 30g sugar, and 400ml chilled water and ice.',
                'yield_quantity' => 1.00,
                'total_cost' => 45.00,
                'ingredients' => [
                    ['name' => 'Fresh Lemons', 'quantity' => 2.00, 'unit_id' => $unitPCS->id],
                    ['name' => 'Fresh Mint Leaves', 'quantity' => 15.00, 'unit_id' => $unitG->id],
                    ['name' => 'Refined White Sugar', 'quantity' => 30.00, 'unit_id' => $unitG->id],
                ]
            ],
        ];

        foreach ($recipes as $foodItemName => $rData) {
            $foodItem = FoodItem::where('name', $foodItemName)->first();
            if (!$foodItem) continue;

            $recipe = Recipe::updateOrCreate(
                [
                    'recipeable_id' => $foodItem->id,
                    'recipeable_type' => FoodItem::class,
                ],
                [
                    'instructions' => $rData['instructions'],
                    'yield_quantity' => $rData['yield_quantity'],
                    'total_cost' => $rData['total_cost'],
                    'status' => 'active',
                ]
            );

            // Re-sync recipe ingredients
            RecipeIngredient::where('recipe_id', $recipe->id)->delete();

            foreach ($rData['ingredients'] as $ingData) {
                $ingItem = IngredientItem::where('name', $ingData['name'])->first();
                if (!$ingItem) continue;

                RecipeIngredient::create([
                    'recipe_id' => $recipe->id,
                    'ingredient_item_id' => $ingItem->id,
                    'quantity' => $ingData['quantity'],
                    'unit_id' => $ingData['unit_id'],
                ]);
            }
        }
    }
}
