<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\IngredientUnit;
use App\Models\IngredientGroup;
use App\Models\IngredientItem;

class IngredientManagementTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_can_view_ingredients_index()
    {
        $response = $this->actingAs($this->user)->get(route('ingredients.index'));
        $response->assertStatus(200);
    }

    public function test_can_create_ingredient_unit()
    {
        $response = $this->actingAs($this->user)->post(route('ingredients.ingredient-units.store'), [
            'name' => 'Kilogram',
            'short_code' => 'KG',
            'is_active' => true,
        ]);
        
        $response->assertRedirect();
        $this->assertDatabaseHas('ingredient_units', ['name' => 'Kilogram', 'short_code' => 'KG']);
    }

    public function test_can_update_ingredient_unit()
    {
        $unit = IngredientUnit::create(['name' => 'Liter', 'short_code' => 'L', 'is_active' => true]);
        
        $response = $this->actingAs($this->user)->put(route('ingredients.ingredient-units.update', $unit), [
            'name' => 'Milliliter',
            'short_code' => 'ML',
            'is_active' => false,
        ]);
        
        $response->assertRedirect();
        $this->assertDatabaseHas('ingredient_units', ['name' => 'Milliliter', 'short_code' => 'ML', 'is_active' => false]);
    }

    public function test_can_delete_ingredient_unit()
    {
        $unit = IngredientUnit::create(['name' => 'Gram', 'short_code' => 'G', 'is_active' => true]);
        
        $response = $this->actingAs($this->user)->delete(route('ingredients.ingredient-units.destroy', $unit));
        
        $response->assertRedirect();
        $this->assertDatabaseMissing('ingredient_units', ['id' => $unit->id]);
    }

    public function test_can_create_ingredient_group()
    {
        $response = $this->actingAs($this->user)->post(route('ingredients.ingredient-groups.store'), [
            'name' => 'Vegetables',
            'description' => 'Fresh veggies',
            'is_active' => true,
        ]);
        
        $response->assertRedirect();
        $this->assertDatabaseHas('ingredient_groups', ['name' => 'Vegetables']);
    }

    public function test_can_update_ingredient_group()
    {
        $group = IngredientGroup::create(['name' => 'Fruits', 'is_active' => true]);
        
        $response = $this->actingAs($this->user)->put(route('ingredients.ingredient-groups.update', $group), [
            'name' => 'Fresh Fruits',
            'description' => 'All fruits',
            'is_active' => false,
        ]);
        
        $response->assertRedirect();
        $this->assertDatabaseHas('ingredient_groups', ['name' => 'Fresh Fruits', 'is_active' => false]);
    }

    public function test_can_delete_ingredient_group()
    {
        $group = IngredientGroup::create(['name' => 'Meat', 'is_active' => true]);
        
        $response = $this->actingAs($this->user)->delete(route('ingredients.ingredient-groups.destroy', $group));
        
        $response->assertRedirect();
        $this->assertDatabaseMissing('ingredient_groups', ['id' => $group->id]);
    }

    public function test_can_create_ingredient_item()
    {
        $group = IngredientGroup::create(['name' => 'Spices', 'is_active' => true]);
        $unit = IngredientUnit::create(['name' => 'Kilogram', 'short_code' => 'KG', 'is_active' => true]);
        
        $response = $this->actingAs($this->user)->post(route('ingredients.ingredient-items.store'), [
            'ingredient_group_id' => $group->id,
            'name' => 'Salt',
            'base_price' => 10.50,
            'description' => 'Sea salt',
            'purchase_unit_id' => $unit->id,
            'conversion_qty' => 1,
            'consumption_unit_id' => $unit->id,
            'consumption_qty' => 1000,
            'reorder_level' => 500,
            'is_active' => true,
        ]);
        
        $response->assertRedirect();
        $this->assertDatabaseHas('ingredient_items', [
            'name' => 'Salt',
            'ingredient_group_id' => $group->id,
            'purchase_unit_id' => $unit->id,
            'conversion_qty' => 1,
            'consumption_unit_id' => $unit->id,
            'consumption_qty' => 1000,
            'reorder_level' => 500,
            'base_price' => 10.50,
        ]);
    }

    public function test_can_update_ingredient_item()
    {
        $group = IngredientGroup::create(['name' => 'Dairy', 'is_active' => true]);
        $unit = IngredientUnit::create(['name' => 'Liter', 'short_code' => 'L', 'is_active' => true]);
        
        $item = IngredientItem::create([
            'ingredient_group_id' => $group->id,
            'name' => 'Milk',
            'base_price' => 5,
            'purchase_unit_id' => $unit->id,
            'conversion_qty' => 1,
            'consumption_unit_id' => $unit->id,
            'consumption_qty' => 1000,
            'reorder_level' => 100,
            'is_active' => true,
        ]);
        
        $response = $this->actingAs($this->user)->put(route('ingredients.ingredient-items.update', $item), [
            'ingredient_group_id' => $group->id,
            'name' => 'Fresh Milk',
            'base_price' => 6.50,
            'description' => 'Cow milk',
            'purchase_unit_id' => $unit->id,
            'conversion_qty' => 1,
            'consumption_unit_id' => $unit->id,
            'consumption_qty' => 1000,
            'reorder_level' => 200,
            'is_active' => true,
        ]);
        
        $response->assertRedirect();
        $this->assertDatabaseHas('ingredient_items', [
            'id' => $item->id,
            'name' => 'Fresh Milk',
            'base_price' => 6.50,
            'reorder_level' => 200,
        ]);
    }

    public function test_can_delete_ingredient_item()
    {
        $group = IngredientGroup::create(['name' => 'Bakery', 'is_active' => true]);
        
        $item = IngredientItem::create([
            'ingredient_group_id' => $group->id,
            'name' => 'Flour',
            'base_price' => 2,
            'is_active' => true,
        ]);
        
        $response = $this->actingAs($this->user)->delete(route('ingredients.ingredient-items.destroy', $item));
        
        $response->assertRedirect();
        $this->assertDatabaseMissing('ingredient_items', ['id' => $item->id]);
    }
}
