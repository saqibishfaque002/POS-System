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

        // Menus
        Route::get('/menus', [\App\Http\Controllers\MenuController::class, 'index'])->name('menus.index');
        Route::post('/menus', [\App\Http\Controllers\MenuController::class, 'store'])->name('menus.store');
        Route::put('/menus/{menu}', [\App\Http\Controllers\MenuController::class, 'update'])->name('menus.update');
        Route::delete('/menus/{menu}', [\App\Http\Controllers\MenuController::class, 'destroy'])->name('menus.destroy');
    });

    // Ingredients Management
    Route::middleware('can:is_admin')->prefix('ingredients')->name('ingredients.')->group(function () {
        Route::get('/', [\App\Http\Controllers\IngredientController::class, 'index'])->name('index');
        
        // Ingredient Units
        Route::post('/ingredient-units', [\App\Http\Controllers\IngredientController::class, 'storeIngredientUnit'])->name('ingredient-units.store');
        Route::put('/ingredient-units/{ingredientUnit}', [\App\Http\Controllers\IngredientController::class, 'updateIngredientUnit'])->name('ingredient-units.update');
        Route::delete('/ingredient-units/{ingredientUnit}', [\App\Http\Controllers\IngredientController::class, 'destroyIngredientUnit'])->name('ingredient-units.destroy');

        // Ingredient Groups
        Route::post('/ingredient-groups', [\App\Http\Controllers\IngredientController::class, 'storeIngredientGroup'])->name('ingredient-groups.store');
        Route::put('/ingredient-groups/{ingredientGroup}', [\App\Http\Controllers\IngredientController::class, 'updateIngredientGroup'])->name('ingredient-groups.update');
        Route::delete('/ingredient-groups/{ingredientGroup}', [\App\Http\Controllers\IngredientController::class, 'destroyIngredientGroup'])->name('ingredient-groups.destroy');

        // Ingredient Items
        Route::post('/ingredient-items', [\App\Http\Controllers\IngredientController::class, 'storeIngredientItem'])->name('ingredient-items.store');
        Route::put('/ingredient-items/{ingredientItem}', [\App\Http\Controllers\IngredientController::class, 'updateIngredientItem'])->name('ingredient-items.update');
        Route::delete('/ingredient-items/{ingredientItem}', [\App\Http\Controllers\IngredientController::class, 'destroyIngredientItem'])->name('ingredient-items.destroy');
    });

    // Recipes Management
    Route::middleware('can:is_admin')->prefix('recipes')->name('recipes.')->group(function () {
        Route::get('/', [\App\Http\Controllers\RecipeController::class, 'index'])->name('index');
        Route::post('/', [\App\Http\Controllers\RecipeController::class, 'store'])->name('store');
        Route::put('/{recipe}', [\App\Http\Controllers\RecipeController::class, 'update'])->name('update');
        Route::delete('/{recipe}', [\App\Http\Controllers\RecipeController::class, 'destroy'])->name('destroy');
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
            Route::post('/branches/{branch}/sync-payments', [\App\Http\Controllers\RestaurantSetupController::class, 'syncBranchPayments'])->name('branches.payments.sync');

            Route::post('/floors', [\App\Http\Controllers\RestaurantSetupController::class, 'storeFloor'])->name('floors.store');
            Route::put('/floors/{floor}', [\App\Http\Controllers\RestaurantSetupController::class, 'updateFloor'])->name('floors.update');
            Route::delete('/floors/{floor}', [\App\Http\Controllers\RestaurantSetupController::class, 'destroyFloor'])->name('floors.destroy');

            Route::post('/tables', [\App\Http\Controllers\RestaurantSetupController::class, 'storeTable'])->name('tables.store');
            Route::put('/tables/{table}', [\App\Http\Controllers\RestaurantSetupController::class, 'updateTable'])->name('tables.update');
            Route::delete('/tables/{table}', [\App\Http\Controllers\RestaurantSetupController::class, 'destroyTable'])->name('tables.destroy');

            Route::post('/payment-types', [\App\Http\Controllers\RestaurantSetupController::class, 'storePaymentType'])->name('payment-types.store');
            Route::put('/payment-types/{paymentType}', [\App\Http\Controllers\RestaurantSetupController::class, 'updatePaymentType'])->name('payment-types.update');
            Route::delete('/payment-types/{paymentType}', [\App\Http\Controllers\RestaurantSetupController::class, 'destroyPaymentType'])->name('payment-types.destroy');

            // Order Types (Restaurant Fulfillment Master)
            Route::post('/order-types', [\App\Http\Controllers\OrderTypeController::class, 'store'])->name('order-types.store');
            Route::put('/order-types/{orderType}', [\App\Http\Controllers\OrderTypeController::class, 'update'])->name('order-types.update');
            Route::delete('/order-types/{orderType}', [\App\Http\Controllers\OrderTypeController::class, 'destroy'])->name('order-types.destroy');

            // Parties & Accounts (Financial Master)
            Route::post('/parties', [\App\Http\Controllers\PartyController::class, 'store'])->name('parties.store');
            Route::put('/parties/{party}', [\App\Http\Controllers\PartyController::class, 'update'])->name('parties.update');
            Route::delete('/parties/{party}', [\App\Http\Controllers\PartyController::class, 'destroy'])->name('parties.destroy');
        });
    });

    // Trade & Procurement
    Route::prefix('trade')->name('trade.')->group(function () {
        Route::get('/purchase-requests', [\App\Http\Controllers\PurchaseRequestController::class, 'index'])->name('purchase-requests.index');
        Route::post('/purchase-requests', [\App\Http\Controllers\PurchaseRequestController::class, 'store'])->name('purchase-requests.store');
        Route::get('/purchase-requests/{purchaseRequest}', [\App\Http\Controllers\PurchaseRequestController::class, 'show'])->name('purchase-requests.show');
        Route::get('/purchase-requests/{purchaseRequest}/print', [\App\Http\Controllers\PurchaseRequestController::class, 'print'])->name('purchase-requests.print');
        Route::put('/purchase-requests/{purchaseRequest}', [\App\Http\Controllers\PurchaseRequestController::class, 'update'])->name('purchase-requests.update');
        Route::patch('/purchase-requests/{purchaseRequest}/status', [\App\Http\Controllers\PurchaseRequestController::class, 'updateStatus'])->name('purchase-requests.status');
        Route::delete('/purchase-requests/{purchaseRequest}', [\App\Http\Controllers\PurchaseRequestController::class, 'destroy'])->name('purchase-requests.destroy');
        
        // Placeholders for other docs
        Route::get('/purchase-orders', [\App\Http\Controllers\PurchaseOrderController::class, 'index'])->name('purchase-orders.index');
        Route::post('/purchase-orders', [\App\Http\Controllers\PurchaseOrderController::class, 'store'])->name('purchase-orders.store');
        Route::get('/purchase-orders/{purchaseOrder}/print', [\App\Http\Controllers\PurchaseOrderController::class, 'print'])->name('purchase-orders.print');
        Route::resource('purchase-orders', \App\Http\Controllers\PurchaseOrderController::class)->except(['create', 'show', 'edit']);
        Route::patch('/purchase-orders/{purchase_order}/status', [\App\Http\Controllers\PurchaseOrderController::class, 'updateStatus'])->name('purchase-orders.status');
        Route::get('/purchase-orders/{purchase_order}/print', [\App\Http\Controllers\PurchaseOrderController::class, 'print'])->name('purchase-orders.print');

        Route::resource('good-received-notes', \App\Http\Controllers\GoodReceivedNoteController::class)->except(['create', 'show', 'edit']);
        Route::patch('/good-received-notes/{good_received_note}/status', [\App\Http\Controllers\GoodReceivedNoteController::class, 'updateStatus'])->name('good-received-notes.status');
        Route::get('/good-received-notes/{good_received_note}/print', [\App\Http\Controllers\GoodReceivedNoteController::class, 'print'])->name('good-received-notes.print');

        Route::resource('purchase-invoices', \App\Http\Controllers\PurchaseInvoiceController::class)->except(['create', 'show', 'edit']);
        Route::patch('/purchase-invoices/{purchase_invoice}/status', [\App\Http\Controllers\PurchaseInvoiceController::class, 'updateStatus'])->name('purchase-invoices.status');
        Route::get('/purchase-invoices/{purchase_invoice}/print', [\App\Http\Controllers\PurchaseInvoiceController::class, 'print'])->name('purchase-invoices.print');

        Route::resource('purchase-returns', \App\Http\Controllers\PurchaseReturnController::class)->except(['create', 'show', 'edit']);
        Route::patch('/purchase-returns/{purchase_return}/status', [\App\Http\Controllers\PurchaseReturnController::class, 'updateStatus'])->name('purchase-returns.status');
        Route::get('/purchase-returns/{purchase_return}/print', [\App\Http\Controllers\PurchaseReturnController::class, 'print'])->name('purchase-returns.print');
        Route::resource('sale-invoices', \App\Http\Controllers\SaleInvoiceController::class)->except(['create', 'show', 'edit']);
        Route::patch('/sale-invoices/{sale_invoice}/status', [\App\Http\Controllers\SaleInvoiceController::class, 'updateStatus'])->name('sale-invoices.status');
        Route::get('/sale-invoices/{sale_invoice}/print', [\App\Http\Controllers\SaleInvoiceController::class, 'print'])->name('sale-invoices.print');
        Route::resource('sale-returns', \App\Http\Controllers\SaleReturnController::class)->except(['create', 'show', 'edit']);
        Route::patch('/sale-returns/{sale_return}/status', [\App\Http\Controllers\SaleReturnController::class, 'updateStatus'])->name('sale-returns.status');
        Route::get('/sale-returns/{sale_return}/print', [\App\Http\Controllers\SaleReturnController::class, 'print'])->name('sale-returns.print');
        
        Route::resource('suppliers', \App\Http\Controllers\SupplierController::class)->except(['create', 'show', 'edit']);
        Route::resource('customers', \App\Http\Controllers\CustomerController::class)->except(['create', 'show', 'edit']);
    });

    // Production Module
    Route::prefix('production')->name('production.')->group(function () {
        Route::get('/', [\App\Http\Controllers\ProductionController::class, 'index'])->name('index');
        Route::post('/', [\App\Http\Controllers\ProductionController::class, 'store'])->name('store');
        Route::put('/{production}', [\App\Http\Controllers\ProductionController::class, 'update'])->name('update');
        Route::delete('/{production}', [\App\Http\Controllers\ProductionController::class, 'destroy'])->name('destroy');
        Route::patch('/{production}/status', [\App\Http\Controllers\ProductionController::class, 'updateStatus'])->name('status');
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
