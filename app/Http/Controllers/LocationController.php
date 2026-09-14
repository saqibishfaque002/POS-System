<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Country;
use App\Models\Province;
use App\Models\City;

class LocationController extends Controller
{
    public function index()
    {
        return Inertia::render('Settings/Index', [
            'countries' => Country::orderBy('name')->get(),
            'provinces' => Province::with('country')->orderBy('name')->get(),
            'cities' => City::with('province.country')->orderBy('name')->get(),
        ]);
    }

    // Country
    public function storeCountry(Request $request) {
        $validated = $request->validate(['name' => 'required|string', 'is_active' => 'boolean']);
        if (!isset($validated['is_active'])) $validated['is_active'] = false;
        Country::create($validated);
        return back();
    }
    public function updateCountry(Request $request, Country $country) {
        $validated = $request->validate(['name' => 'required|string', 'is_active' => 'boolean']);
        if (!isset($validated['is_active'])) $validated['is_active'] = false;
        $country->update($validated);
        return back();
    }
    public function destroyCountry(Country $country) {
        $country->delete();
        return back();
    }

    // Province
    public function storeProvince(Request $request) {
        $validated = $request->validate(['country_id' => 'required|exists:countries,id', 'name' => 'required|string', 'is_active' => 'boolean']);
        if (!isset($validated['is_active'])) $validated['is_active'] = false;
        Province::create($validated);
        return back();
    }
    public function updateProvince(Request $request, Province $province) {
        $validated = $request->validate(['country_id' => 'required|exists:countries,id', 'name' => 'required|string', 'is_active' => 'boolean']);
        if (!isset($validated['is_active'])) $validated['is_active'] = false;
        $province->update($validated);
        return back();
    }
    public function destroyProvince(Province $province) {
        $province->delete();
        return back();
    }

    // City
    public function storeCity(Request $request) {
        $validated = $request->validate(['province_id' => 'required|exists:provinces,id', 'name' => 'required|string', 'is_active' => 'boolean']);
        if (!isset($validated['is_active'])) $validated['is_active'] = false;
        City::create($validated);
        return back();
    }
    public function updateCity(Request $request, City $city) {
        $validated = $request->validate(['province_id' => 'required|exists:provinces,id', 'name' => 'required|string', 'is_active' => 'boolean']);
        if (!isset($validated['is_active'])) $validated['is_active'] = false;
        $city->update($validated);
        return back();
    }
    public function destroyCity(City $city) {
        $city->delete();
        return back();
    }
}
