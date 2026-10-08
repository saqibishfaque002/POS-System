<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Company;
use App\Models\Branch;
use App\Models\Floor;
use App\Models\Table;

class RestaurantSetupController extends Controller
{
    // Company
    public function storeCompany(Request $request) {
        $validated = $request->validate(['name' => 'required|string', 'is_active' => 'boolean']);
        if (!isset($validated['is_active'])) $validated['is_active'] = false;
        Company::create($validated);
        return back();
    }
    public function updateCompany(Request $request, Company $company) {
        $validated = $request->validate(['name' => 'required|string', 'is_active' => 'boolean']);
        if (!isset($validated['is_active'])) $validated['is_active'] = false;
        $company->update($validated);
        return back();
    }
    public function destroyCompany(Company $company) {
        $company->delete();
        return back();
    }

    // Branch
    public function storeBranch(Request $request) {
        $validated = $request->validate([
            'company_id' => 'required|exists:companies,id',
            'city_id' => 'required|exists:cities,id',
            'name' => 'required|string',
            'address' => 'nullable|string',
            'phone' => 'nullable|string',
            'is_active' => 'boolean'
        ]);
        if (!isset($validated['is_active'])) $validated['is_active'] = false;
        Branch::create($validated);
        return back();
    }
    public function updateBranch(Request $request, Branch $branch) {
        $validated = $request->validate([
            'company_id' => 'required|exists:companies,id',
            'city_id' => 'required|exists:cities,id',
            'name' => 'required|string',
            'address' => 'nullable|string',
            'phone' => 'nullable|string',
            'is_active' => 'boolean'
        ]);
        if (!isset($validated['is_active'])) $validated['is_active'] = false;
        $branch->update($validated);
        return back();
    }
    public function destroyBranch(Branch $branch) {
        $branch->delete();
        return back();
    }

    // Floor
    public function storeFloor(Request $request) {
        $validated = $request->validate([
            'branch_id' => 'required|exists:branches,id',
            'name' => 'required|string',
            'is_active' => 'boolean'
        ]);
        if (!isset($validated['is_active'])) $validated['is_active'] = false;
        Floor::create($validated);
        return back();
    }
    public function updateFloor(Request $request, Floor $floor) {
        $validated = $request->validate([
            'branch_id' => 'required|exists:branches,id',
            'name' => 'required|string',
            'is_active' => 'boolean'
        ]);
        if (!isset($validated['is_active'])) $validated['is_active'] = false;
        $floor->update($validated);
        return back();
    }
    public function destroyFloor(Floor $floor) {
        $floor->delete();
        return back();
    }

    // Table
    public function storeTable(Request $request) {
        $validated = $request->validate([
            'floor_id' => 'required|exists:floors,id',
            'name' => 'required|string',
            'capacity' => 'required|integer|min:1',
            'is_active' => 'boolean'
        ]);
        if (!isset($validated['is_active'])) $validated['is_active'] = false;
        Table::create($validated);
        return back();
    }
    public function updateTable(Request $request, Table $table) {
        $validated = $request->validate([
            'floor_id' => 'required|exists:floors,id',
            'name' => 'required|string',
            'capacity' => 'required|integer|min:1',
            'is_active' => 'boolean'
        ]);
        if (!isset($validated['is_active'])) $validated['is_active'] = false;
        $table->update($validated);
        return back();
    }
    public function destroyTable(Table $table) {
        $table->delete();
        return back();
    }

    // Payment Type
    public function storePaymentType(Request $request) {
        $validated = $request->validate([
            'name' => 'required|string|unique:payment_types,name',
            'is_active' => 'boolean'
        ]);
        if (!isset($validated['is_active'])) $validated['is_active'] = false;
        \App\Models\PaymentType::create($validated);
        return back();
    }
    public function updatePaymentType(Request $request, \App\Models\PaymentType $paymentType) {
        $validated = $request->validate([
            'name' => 'required|string|unique:payment_types,name,' . $paymentType->id,
            'is_active' => 'boolean'
        ]);
        if (!isset($validated['is_active'])) $validated['is_active'] = false;
        $paymentType->update($validated);
        return back();
    }
    public function destroyPaymentType(\App\Models\PaymentType $paymentType) {
        $paymentType->delete();
        return back();
    }
}
