<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\OrderController;
use App\Models\Product;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/pos', function () {
        return Inertia::render('Pos/Index', [
            'products' => Product::all()
        ]);
    })->name('pos.index');

    Route::post('/orders', [OrderController::class, 'store'])->name('orders.store');

    Route::get('/inventory', function () {
        return Inertia::render('Dashboard'); // Placeholder for inventory
    })->middleware('can:is_admin')->name('inventory.index');

    Route::middleware('can:is_admin')->prefix('catalog')->name('catalog.')->group(function () {
        Route::get('/', [\App\Http\Controllers\CatalogController::class, 'index'])->name('index');
        
        // Food Groups
        Route::post('/food-groups', [\App\Http\Controllers\CatalogController::class, 'storeFoodGroup'])->name('food-groups.store');
        Route::put('/food-groups/{foodGroup}', [\App\Http\Controllers\CatalogController::class, 'updateFoodGroup'])->name('food-groups.update');
        Route::delete('/food-groups/{foodGroup}', [\App\Http\Controllers\CatalogController::class, 'destroyFoodGroup'])->name('food-groups.destroy');

        // Food Items
        Route::post('/food-items', [\App\Http\Controllers\CatalogController::class, 'storeFoodItem'])->name('food-items.store');
        Route::put('/food-items/{foodItem}', [\App\Http\Controllers\CatalogController::class, 'updateFoodItem'])->name('food-items.update');
        Route::delete('/food-items/{foodItem}', [\App\Http\Controllers\CatalogController::class, 'destroyFoodItem'])->name('food-items.destroy');

        // Property Groups
        Route::post('/property-groups', [\App\Http\Controllers\CatalogController::class, 'storePropertyGroup'])->name('property-groups.store');
        Route::put('/property-groups/{propertyGroup}', [\App\Http\Controllers\CatalogController::class, 'updatePropertyGroup'])->name('property-groups.update');
        Route::delete('/property-groups/{propertyGroup}', [\App\Http\Controllers\CatalogController::class, 'destroyPropertyGroup'])->name('property-groups.destroy');

        // Property Items
        Route::post('/property-items', [\App\Http\Controllers\CatalogController::class, 'storePropertyItem'])->name('property-items.store');
        Route::put('/property-items/{propertyItem}', [\App\Http\Controllers\CatalogController::class, 'updatePropertyItem'])->name('property-items.update');
        Route::delete('/property-items/{propertyItem}', [\App\Http\Controllers\CatalogController::class, 'destroyPropertyItem'])->name('property-items.destroy');

        // Variations
        Route::post('/variations', [\App\Http\Controllers\CatalogController::class, 'storeVariation'])->name('variations.store');
        Route::put('/variations/{variation}', [\App\Http\Controllers\CatalogController::class, 'updateVariation'])->name('variations.update');
        Route::delete('/variations/{variation}', [\App\Http\Controllers\CatalogController::class, 'destroyVariation'])->name('variations.destroy');
    });

    // User Management
    Route::middleware('can:is_admin')->prefix('users')->name('users.')->group(function () {
        Route::get('/', [\App\Http\Controllers\UserController::class, 'index'])->name('index');
        Route::post('/', [\App\Http\Controllers\UserController::class, 'store'])->name('store');
        Route::put('/{user}', [\App\Http\Controllers\UserController::class, 'update'])->name('update');
        Route::delete('/{user}', [\App\Http\Controllers\UserController::class, 'destroy'])->name('destroy');
    });

    // Settings & Locations
    Route::middleware('can:is_admin')->group(function () {
        Route::get('/settings', [\App\Http\Controllers\SettingController::class, 'index'])->name('settings.index');
        Route::post('/settings/group', [\App\Http\Controllers\SettingController::class, 'updateGroup'])->name('settings.group.update');
        
        Route::prefix('locations')->name('locations.')->group(function () {
            Route::post('/countries', [\App\Http\Controllers\LocationController::class, 'storeCountry'])->name('countries.store');
            Route::put('/countries/{country}', [\App\Http\Controllers\LocationController::class, 'updateCountry'])->name('countries.update');
            Route::delete('/countries/{country}', [\App\Http\Controllers\LocationController::class, 'destroyCountry'])->name('countries.destroy');

            Route::post('/provinces', [\App\Http\Controllers\LocationController::class, 'storeProvince'])->name('provinces.store');
            Route::put('/provinces/{province}', [\App\Http\Controllers\LocationController::class, 'updateProvince'])->name('provinces.update');
            Route::delete('/provinces/{province}', [\App\Http\Controllers\LocationController::class, 'destroyProvince'])->name('provinces.destroy');

            Route::post('/cities', [\App\Http\Controllers\LocationController::class, 'storeCity'])->name('cities.store');
            Route::put('/cities/{city}', [\App\Http\Controllers\LocationController::class, 'updateCity'])->name('cities.update');
            Route::delete('/cities/{city}', [\App\Http\Controllers\LocationController::class, 'destroyCity'])->name('cities.destroy');
        });

        Route::prefix('restaurant')->name('restaurant.')->group(function () {
            Route::post('/companies', [\App\Http\Controllers\RestaurantSetupController::class, 'storeCompany'])->name('companies.store');
            Route::put('/companies/{company}', [\App\Http\Controllers\RestaurantSetupController::class, 'updateCompany'])->name('companies.update');
            Route::delete('/companies/{company}', [\App\Http\Controllers\RestaurantSetupController::class, 'destroyCompany'])->name('companies.destroy');

            Route::post('/branches', [\App\Http\Controllers\RestaurantSetupController::class, 'storeBranch'])->name('branches.store');
            Route::put('/branches/{branch}', [\App\Http\Controllers\RestaurantSetupController::class, 'updateBranch'])->name('branches.update');
            Route::delete('/branches/{branch}', [\App\Http\Controllers\RestaurantSetupController::class, 'destroyBranch'])->name('branches.destroy');

            Route::post('/floors', [\App\Http\Controllers\RestaurantSetupController::class, 'storeFloor'])->name('floors.store');
            Route::put('/floors/{floor}', [\App\Http\Controllers\RestaurantSetupController::class, 'updateFloor'])->name('floors.update');
            Route::delete('/floors/{floor}', [\App\Http\Controllers\RestaurantSetupController::class, 'destroyFloor'])->name('floors.destroy');

            Route::post('/tables', [\App\Http\Controllers\RestaurantSetupController::class, 'storeTable'])->name('tables.store');
            Route::put('/tables/{table}', [\App\Http\Controllers\RestaurantSetupController::class, 'updateTable'])->name('tables.update');
            Route::delete('/tables/{table}', [\App\Http\Controllers\RestaurantSetupController::class, 'destroyTable'])->name('tables.destroy');
        });
    });
});

Route::get('/', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
