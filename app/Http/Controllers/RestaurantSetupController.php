<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Arr;
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
            'delivery_charges' => 'nullable|numeric|min:0',
            'service_charge_type' => 'nullable|in:percentage,fixed',
            'service_charge_value' => 'nullable|numeric|min:0',
            'table_order_types' => 'nullable|array',
            'table_order_types.*' => 'integer|exists:order_types,id',
            'is_active' => 'boolean',
            'payment_types' => 'nullable|array',
            'payment_types.*.id' => 'required_with:payment_types|exists:payment_types,id',
            'payment_types.*.tax_percentage' => 'nullable|numeric|min:0',
            'payment_types.*.selected' => 'nullable|boolean',
        ]);
        if (!isset($validated['is_active'])) $validated['is_active'] = false;
        
        $branch = Branch::create(Arr::except($validated, ['payment_types']));
        if ($request->has('payment_types')) {
            $this->syncPayments($branch, $request->input('payment_types'));
        }
        return back();
    }
    public function updateBranch(Request $request, Branch $branch) {
        $validated = $request->validate([
            'company_id' => 'required|exists:companies,id',
            'city_id' => 'required|exists:cities,id',
            'name' => 'required|string',
            'address' => 'nullable|string',
            'phone' => 'nullable|string',
            'delivery_charges' => 'nullable|numeric|min:0',
            'service_charge_type' => 'nullable|in:percentage,fixed',
            'service_charge_value' => 'nullable|numeric|min:0',
            'table_order_types' => 'nullable|array',
            'table_order_types.*' => 'integer|exists:order_types,id',
            'is_active' => 'boolean',
            'payment_types' => 'nullable|array',
            'payment_types.*.id' => 'required_with:payment_types|exists:payment_types,id',
            'payment_types.*.tax_percentage' => 'nullable|numeric|min:0',
            'payment_types.*.selected' => 'nullable|boolean',
        ]);
        if (!isset($validated['is_active'])) $validated['is_active'] = false;
        
        $branch->update(Arr::except($validated, ['payment_types']));
        if ($request->has('payment_types')) {
            $this->syncPayments($branch, $request->input('payment_types'));
        }
        return back();
    }
    public function destroyBranch(Branch $branch) {
        $branch->delete();
        return back();
    }

    protected function syncPayments(Branch $branch, $paymentTypes) {
        $syncData = [];
        if (!empty($paymentTypes) && is_array($paymentTypes)) {
            foreach ($paymentTypes as $pt) {
                $isSelected = array_key_exists('selected', $pt) ? (bool)$pt['selected'] : true;
                if ($isSelected && !empty($pt['id'])) {
                    $syncData[$pt['id']] = ['tax_percentage' => $pt['tax_percentage'] ?? 0];
                }
            }
        }
        $branch->paymentTypes()->sync($syncData);
    }

    public function syncBranchPayments(Request $request, Branch $branch) {
        $validated = $request->validate([
            'payment_types' => 'array',
            'payment_types.*.id' => 'required|exists:payment_types,id',
            'payment_types.*.tax_percentage' => 'required|numeric|min:0'
        ]);

        $this->syncPayments($branch, $validated['payment_types'] ?? []);
        return back()->with('success', 'Branch payment methods updated successfully.');
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
