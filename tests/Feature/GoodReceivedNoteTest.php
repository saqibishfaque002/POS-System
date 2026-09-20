<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Company;
use App\Models\Branch;
use App\Models\Supplier;
use App\Models\IngredientGroup;
use App\Models\IngredientUnit;
use App\Models\IngredientItem;
use App\Models\GoodReceivedNote;

use App\Models\Country;
use App\Models\Province;
use App\Models\City;

class GoodReceivedNoteTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $branch;
    protected $supplier;
    protected $item;
    protected $unit;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();

        $country = Country::create(['name' => 'Test Country', 'is_active' => true]);
        $province = Province::create(['country_id' => $country->id, 'name' => 'Test Province', 'is_active' => true]);
        $city = City::create(['province_id' => $province->id, 'name' => 'Test City', 'is_active' => true]);

        $company = Company::create([
            'name' => 'Test Company',
            'is_active' => true
        ]);

        $this->branch = Branch::create([
            'company_id' => $company->id,
            'city_id' => $city->id,
            'name' => 'Test Branch',
            'is_active' => true
        ]);

        $this->supplier = Supplier::create([
            'name' => 'Test Supplier',
            'email' => 'supplier@test.com',
            'phone' => '123456789',
            'address' => '123 Test St',
            'is_active' => true
        ]);

        $group = IngredientGroup::create([
            'name' => 'Test Group',
            'is_active' => true
        ]);

        $this->unit = IngredientUnit::create([
            'name' => 'Kilogram',
            'short_code' => 'KG',
            'is_active' => true
        ]);

        $this->item = IngredientItem::create([
            'ingredient_group_id' => $group->id,
            'purchase_unit_id' => $this->unit->id,
            'consumption_unit_id' => $this->unit->id,
            'name' => 'Test Ingredient',
            'base_price' => 10,
            'conversion_qty' => 1,
            'is_active' => true
        ]);
    }

    public function test_grn_can_be_created()
    {
        $payload = [
            'branch_id' => $this->branch->id,
            'supplier_id' => $this->supplier->id,
            'reference_id' => null,
            'date' => '2026-09-20',
            'notes' => 'Test note',
            'items' => [
                [
                    'item_id' => $this->item->id,
                    'unit_id' => $this->unit->id,
                    'quantity' => 10,
                    'unit_price' => 15,
                ]
            ]
        ];

        $response = $this->actingAs($this->user)->post(route('trade.good-received-notes.store'), $payload);

        $response->assertSessionHas('success');
        
        $this->assertDatabaseHas('good_received_notes', [
            'branch_id' => $this->branch->id,
            'supplier_id' => $this->supplier->id,
            'total_amount' => 150, // 10 * 15
        ]);

        $this->assertDatabaseHas('good_received_note_items', [
            'item_id' => $this->item->id,
            'quantity' => 10,
            'subtotal' => 150,
        ]);
    }

    public function test_grn_can_be_updated_if_draft()
    {
        $grn = GoodReceivedNote::create([
            'branch_id' => $this->branch->id,
            'supplier_id' => $this->supplier->id,
            'document_number' => 'GRN-1-00001',
            'date' => '2026-09-20',
            'status' => 'draft',
            'total_amount' => 0,
        ]);

        $payload = [
            'branch_id' => $this->branch->id,
            'supplier_id' => $this->supplier->id,
            'reference_id' => null,
            'date' => '2026-09-21',
            'notes' => 'Updated note',
            'items' => [
                [
                    'item_id' => $this->item->id,
                    'unit_id' => $this->unit->id,
                    'quantity' => 5,
                    'unit_price' => 20,
                ]
            ]
        ];

        $response = $this->actingAs($this->user)->put(route('trade.good-received-notes.update', $grn), $payload);

        $response->assertSessionHas('success');

        $this->assertDatabaseHas('good_received_notes', [
            'id' => $grn->id,
            'date' => '2026-09-21',
            'total_amount' => 100, // 5 * 20
        ]);
    }

    public function test_grn_cannot_be_updated_if_not_draft()
    {
        $grn = GoodReceivedNote::create([
            'branch_id' => $this->branch->id,
            'supplier_id' => $this->supplier->id,
            'document_number' => 'GRN-1-00002',
            'date' => '2026-09-20',
            'status' => 'completed',
            'total_amount' => 0,
        ]);

        $payload = [
            'branch_id' => $this->branch->id,
            'supplier_id' => $this->supplier->id,
            'date' => '2026-09-21',
            'items' => [
                [
                    'item_id' => $this->item->id,
                    'unit_id' => $this->unit->id,
                    'quantity' => 5,
                    'unit_price' => 20,
                ]
            ]
        ];

        $response = $this->actingAs($this->user)->put(route('trade.good-received-notes.update', $grn), $payload);

        $response->assertSessionHas('error');
        $this->assertDatabaseHas('good_received_notes', [
            'id' => $grn->id,
            'date' => '2026-09-20' // Should not be updated
        ]);
    }

    public function test_grn_can_be_deleted_if_draft()
    {
        $grn = GoodReceivedNote::create([
            'branch_id' => $this->branch->id,
            'supplier_id' => $this->supplier->id,
            'document_number' => 'GRN-1-00003',
            'date' => '2026-09-20',
            'status' => 'draft',
            'total_amount' => 0,
        ]);

        $response = $this->actingAs($this->user)->delete(route('trade.good-received-notes.destroy', $grn));

        $response->assertSessionHas('success');
        $this->assertDatabaseMissing('good_received_notes', [
            'id' => $grn->id
        ]);
    }

    public function test_grn_status_can_be_updated()
    {
        $grn = GoodReceivedNote::create([
            'branch_id' => $this->branch->id,
            'supplier_id' => $this->supplier->id,
            'document_number' => 'GRN-1-00004',
            'date' => '2026-09-20',
            'status' => 'draft',
            'total_amount' => 0,
        ]);

        $response = $this->actingAs($this->user)->patch(route('trade.good-received-notes.status', $grn), [
            'status' => 'completed'
        ]);

        $response->assertSessionHas('success');
        $this->assertDatabaseHas('good_received_notes', [
            'id' => $grn->id,
            'status' => 'completed'
        ]);
    }
}
