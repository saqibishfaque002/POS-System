<?php
// Auth bypass for testing
$user = \App\Models\User::first();
if (!$user) {
    $user = \App\Models\User::factory()->create(['role' => 'admin']);
}
auth()->login($user);

echo "Testing Locations CRUD...\n";

// 1. Create Country
echo "-- Creating Country --\n";
$request = \Illuminate\Http\Request::create('/locations/countries', 'POST', ['name' => 'Canada', 'is_active' => true]);
$response = app()->handle($request);
echo "Status: " . $response->getStatusCode() . "\n";
$country = \App\Models\Country::where('name', 'Canada')->first();
echo "Country created: " . ($country ? 'Yes' : 'No') . "\n";

// 2. Create Province
echo "-- Creating Province --\n";
$request = \Illuminate\Http\Request::create('/locations/provinces', 'POST', ['country_id' => $country->id, 'name' => 'Ontario', 'is_active' => true]);
$response = app()->handle($request);
echo "Status: " . $response->getStatusCode() . "\n";
$province = \App\Models\Province::where('name', 'Ontario')->first();
echo "Province created: " . ($province ? 'Yes' : 'No') . "\n";

// 3. Create City
echo "-- Creating City --\n";
$request = \Illuminate\Http\Request::create('/locations/cities', 'POST', ['province_id' => $province->id, 'name' => 'Toronto', 'is_active' => true]);
$response = app()->handle($request);
echo "Status: " . $response->getStatusCode() . "\n";
$city = \App\Models\City::where('name', 'Toronto')->first();
echo "City created: " . ($city ? 'Yes' : 'No') . "\n";

// 5. Update City
echo "-- Updating City --\n";
$request = \Illuminate\Http\Request::create('/locations/cities/' . $city->id, 'PUT', ['province_id' => $province->id, 'name' => 'Toronto Updated', 'is_active' => false]);
$response = app()->handle($request);
echo "Status: " . $response->getStatusCode() . "\n";
$city->refresh();
echo "City updated name: " . $city->name . " | is_active: " . ($city->is_active ? 'true' : 'false') . "\n";

// 6. Delete City
echo "-- Deleting City --\n";
$request = \Illuminate\Http\Request::create('/locations/cities/' . $city->id, 'DELETE');
$response = app()->handle($request);
echo "Status: " . $response->getStatusCode() . "\n";
$cityExists = \App\Models\City::where('id', $city->id)->exists();
echo "City deleted: " . (!$cityExists ? 'Yes' : 'No') . "\n";

// 7. Delete Province & Country
$request = \Illuminate\Http\Request::create('/locations/provinces/' . $province->id, 'DELETE');
app()->handle($request);
$request = \Illuminate\Http\Request::create('/locations/countries/' . $country->id, 'DELETE');
app()->handle($request);

echo "Cleanup complete.\n";
