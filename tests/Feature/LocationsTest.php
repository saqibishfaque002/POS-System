<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Country;
use App\Models\Province;
use App\Models\City;

class LocationsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create(['role' => 'admin']);
        $this->actingAs($this->user);
    }

    public function test_countries_crud()
    {
        // List
        $response = $this->get('/settings');
        $response->assertStatus(200);

        // Create
        $response = $this->post('/locations/countries', [
            'name' => 'Test Country',
            'is_active' => true,
        ]);
        $response->assertRedirect();
        $this->assertDatabaseHas('countries', ['name' => 'Test Country', 'is_active' => true]);
        $country = Country::first();

        // Validation Error
        $response = $this->post('/locations/countries', []);
        $response->assertSessionHasErrors('name');

        // Edit
        $response = $this->put("/locations/countries/{$country->id}", [
            'name' => 'Updated Country',
            'is_active' => false,
        ]);
        $this->assertDatabaseHas('countries', ['name' => 'Updated Country', 'is_active' => false]);

        // Delete
        $response = $this->delete("/locations/countries/{$country->id}");
        $this->assertDatabaseMissing('countries', ['id' => $country->id]);
    }

    public function test_provinces_crud()
    {
        $country = Country::create(['name' => 'Test Country', 'is_active' => true]);

        // Create
        $response = $this->post('/locations/provinces', [
            'country_id' => $country->id,
            'name' => 'Test Province',
            'is_active' => true,
        ]);
        $this->assertDatabaseHas('provinces', ['name' => 'Test Province']);
        $province = Province::first();

        // Validation Error
        $response = $this->post('/locations/provinces', ['country_id' => 999]);
        $response->assertSessionHasErrors(['name', 'country_id']);

        // Edit
        $response = $this->put("/locations/provinces/{$province->id}", [
            'country_id' => $country->id,
            'name' => 'Updated Province',
            'is_active' => false,
        ]);
        $this->assertDatabaseHas('provinces', ['name' => 'Updated Province', 'is_active' => false]);

        // Delete
        $response = $this->delete("/locations/provinces/{$province->id}");
        $this->assertDatabaseMissing('provinces', ['id' => $province->id]);
    }

    public function test_cities_crud()
    {
        $country = Country::create(['name' => 'Test Country', 'is_active' => true]);
        $province = Province::create(['country_id' => $country->id, 'name' => 'Test Province', 'is_active' => true]);

        // Create
        $response = $this->post('/locations/cities', [
            'province_id' => $province->id,
            'name' => 'Test City',
            'is_active' => true,
        ]);
        $this->assertDatabaseHas('cities', ['name' => 'Test City']);
        $city = City::first();

        // Validation Error
        $response = $this->post('/locations/cities', ['province_id' => 999]);
        $response->assertSessionHasErrors(['name', 'province_id']);

        // Edit
        $response = $this->put("/locations/cities/{$city->id}", [
            'province_id' => $province->id,
            'name' => 'Updated City',
            'is_active' => false,
        ]);
        $this->assertDatabaseHas('cities', ['name' => 'Updated City', 'is_active' => false]);

        // Delete
        $response = $this->delete("/locations/cities/{$city->id}");
        $this->assertDatabaseMissing('cities', ['id' => $city->id]);
    }
}
