<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SupplierController extends Controller
{
    public function index()
    {
        $suppliers = Supplier::latest()->get();

        return Inertia::render('Trade/Suppliers/Index', [
            'suppliers' => $suppliers,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'contact_person' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:255',
            'address' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        if (!isset($validated['is_active'])) $validated['is_active'] = false;

        Supplier::create($validated);

        return back()->with('success', 'Supplier created successfully.');
    }

    public function update(Request $request, Supplier $supplier)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'contact_person' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:255',
            'address' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        if (!isset($validated['is_active'])) $validated['is_active'] = false;

        $supplier->update($validated);

        return back()->with('success', 'Supplier updated successfully.');
    }

    public function destroy(Supplier $supplier)
    {
        // Check if supplier is used in any PO/PI/GRN
        $inUse = \App\Models\PurchaseOrder::where('supplier_id', $supplier->id)->exists() ||
                 \App\Models\PurchaseInvoice::where('supplier_id', $supplier->id)->exists() ||
                 \App\Models\GoodReceivedNote::where('supplier_id', $supplier->id)->exists() ||
                 \App\Models\PurchaseReturn::where('supplier_id', $supplier->id)->exists();

        if ($inUse) {
            return back()->with('error', 'Cannot delete this supplier as it is linked to trade documents.');
        }

        $supplier->delete();
        return back()->with('success', 'Supplier deleted successfully.');
    }
}
