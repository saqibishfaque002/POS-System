<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\PurchaseRequest;
use App\Models\Branch;
use App\Models\Supplier;
use App\Models\IngredientItem;
use App\Models\IngredientUnit;
use Inertia\Inertia;
use Illuminate\Support\Str;

class PurchaseOrderController extends Controller
{
    public function index()
    {
        $purchaseOrders = PurchaseOrder::with(['branch', 'supplier', 'items.item.purchase_unit'])->latest()->get();
        $branches = Branch::where('is_active', true)->get();
        $suppliers = Supplier::where('is_active', true)->get();
        $items = IngredientItem::with(['purchaseUnit', 'consumptionUnit'])->where('is_active', true)->get();
        $units = IngredientUnit::where('is_active', true)->get();
        
        // Fetch approved PRs to allow creating PO from PR
        $approvedPrs = PurchaseRequest::with('items')->where('status', 'approved')->get();

        return Inertia::render('Trade/PurchaseOrders/Index', [
            'purchaseOrders' => $purchaseOrders,
            'branches' => $branches,
            'suppliers' => $suppliers,
            'items' => $items,
            'units' => $units,
            'approvedPrs' => $approvedPrs
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'branch_id' => 'required|exists:branches,id',
            'supplier_id' => 'required|exists:suppliers,id',
            'reference_id' => 'nullable|exists:purchase_requests,id',
            'date' => 'required|date',
            'delivery_date' => 'nullable|date|after_or_equal:date',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.item_id' => 'required|exists:ingredient_items,id',
            'items.*.unit_id' => 'required|exists:ingredient_units,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.tax_percent' => 'required|numeric|min:0',
        ]);

        $documentNumber = 'PO-' . strtoupper(Str::random(6));

        $purchaseOrder = PurchaseOrder::create([
            'document_number' => $documentNumber,
            'branch_id' => $validated['branch_id'],
            'supplier_id' => $validated['supplier_id'],
            'reference_id' => $validated['reference_id'] ?? null,
            'date' => $validated['date'],
            'notes' => $validated['notes'],
            'status' => 'draft',
            'total_amount' => 0, // Will update below
            'created_by' => auth()->id()
        ]);

        $totalAmount = 0;

        foreach ($validated['items'] as $item) {
            $subtotal = $item['quantity'] * $item['unit_price'];
            $taxAmount = $subtotal * ($item['tax_percent'] / 100);
            $netAmount = $subtotal + $taxAmount;
            $totalAmount += $netAmount;

            $purchaseOrder->items()->create([
                'item_id' => $item['item_id'],
                'unit_id' => $item['unit_id'],
                'quantity' => $item['quantity'],
                'unit_price' => $item['unit_price'],
                'subtotal' => $subtotal,
                'tax_percent' => $item['tax_percent'],
                'tax_amount' => $taxAmount,
                'net_amount' => $netAmount,
            ]);
        }

        $purchaseOrder->update(['total_amount' => $totalAmount]);

        return back()->with('success', 'Purchase Order created successfully.');
    }

    public function update(Request $request, PurchaseOrder $purchaseOrder)
    {
        if ($purchaseOrder->status !== 'draft') {
            return back()->with('error', 'Only draft purchase orders can be edited.');
        }

        $validated = $request->validate([
            'branch_id' => 'required|exists:branches,id',
            'supplier_id' => 'required|exists:suppliers,id',
            'reference_id' => 'nullable|exists:purchase_requests,id',
            'date' => 'required|date',
            'delivery_date' => 'nullable|date|after_or_equal:date',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.item_id' => 'required|exists:ingredient_items,id',
            'items.*.unit_id' => 'required|exists:ingredient_units,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.tax_percent' => 'required|numeric|min:0',
        ]);

        $purchaseOrder->update([
            'branch_id' => $validated['branch_id'],
            'supplier_id' => $validated['supplier_id'],
            'reference_id' => $validated['reference_id'] ?? null,
            'date' => $validated['date'],
            'delivery_date' => $validated['delivery_date'] ?? null,
            'notes' => $validated['notes'],
        ]);

        $purchaseOrder->items()->delete();
        
        $totalAmount = 0;
        foreach ($validated['items'] as $item) {
            $subtotal = $item['quantity'] * $item['unit_price'];
            $taxAmount = $subtotal * ($item['tax_percent'] / 100);
            $netAmount = $subtotal + $taxAmount;
            $totalAmount += $netAmount;

            $purchaseOrder->items()->create([
                'item_id' => $item['item_id'],
                'unit_id' => $item['unit_id'],
                'quantity' => $item['quantity'],
                'unit_price' => $item['unit_price'],
                'subtotal' => $subtotal,
                'tax_percent' => $item['tax_percent'],
                'tax_amount' => $taxAmount,
                'net_amount' => $netAmount,
            ]);
        }

        $purchaseOrder->update(['total_amount' => $totalAmount]);

        return back()->with('success', 'Purchase Order updated successfully.');
    }

    public function updateStatus(Request $request, PurchaseOrder $purchaseOrder)
    {
        $validated = $request->validate([
            'status' => 'required|in:draft,issued,completed,cancelled'
        ]);

        $purchaseOrder->update(['status' => $validated['status']]);

        return back()->with('success', 'Purchase Order status updated.');
    }

    public function destroy(PurchaseOrder $purchaseOrder)
    {
        if ($purchaseOrder->status !== 'draft') {
            return back()->with('error', 'Only draft POs can be deleted.');
        }
        $purchaseOrder->delete();
        return back()->with('success', 'Purchase Order deleted.');
    }
}
