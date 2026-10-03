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
use App\Http\Requests\Trade\PurchaseOrderRequest;
use App\Services\TradeDocumentService;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PurchaseOrderController extends Controller
{
    public function index()
    {
        $purchaseOrders = PurchaseOrder::with(['branch', 'supplier', 'items.item.purchaseUnit'])->latest()->get();
        $user = auth()->user();
        $isAdmin = in_array($user->role, ['admin', 'manager']);
        $branches = $isAdmin ? Branch::where('is_active', true)->orderBy('name')->get() : $user->branches()->where('is_active', true)->orderBy('name')->get();
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

    public function store(PurchaseOrderRequest $request, TradeDocumentService $service)
    {
        $validated = $request->validated();

        $lastDoc = PurchaseOrder::where('branch_id', $validated['branch_id'])->latest('id')->first();
        $sequence = $lastDoc ? intval(explode('-', $lastDoc->document_number)[2] ?? 0) + 1 : 1;
        $documentNumber = 'PO-' . $validated['branch_id'] . '-' . str_pad($sequence, 5, '0', STR_PAD_LEFT);

        DB::transaction(function () use ($validated, $documentNumber, $service) {
            $purchaseOrder = PurchaseOrder::create([
                'document_number' => $documentNumber,
                'branch_id' => $validated['branch_id'],
                'supplier_id' => $validated['supplier_id'],
                'reference_id' => $validated['reference_id'] ?? null,
                'date' => $validated['date'],
                'delivery_date' => $validated['delivery_date'] ?? null,
                'notes' => $validated['notes'],
                'status' => 'draft',
                'total_amount' => 0,
                'created_by' => auth()->id()
            ]);

            $service->savePurchaseOrderItems($purchaseOrder, $validated['items']);
        });

        return back()->with('success', 'Purchase Order created successfully.');
    }

    public function update(PurchaseOrderRequest $request, PurchaseOrder $purchaseOrder, TradeDocumentService $service)
    {
        if ($purchaseOrder->status !== 'draft') {
            return back()->with('error', 'Only draft purchase orders can be edited.');
        }

        $validated = $request->validated();

        DB::transaction(function () use ($purchaseOrder, $validated, $service) {
            $purchaseOrder->update([
                'branch_id' => $validated['branch_id'],
                'supplier_id' => $validated['supplier_id'],
                'reference_id' => $validated['reference_id'] ?? null,
                'date' => $validated['date'],
                'delivery_date' => $validated['delivery_date'] ?? null,
                'notes' => $validated['notes'],
            ]);

            $service->savePurchaseOrderItems($purchaseOrder, $validated['items']);
        });

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

    public function print(PurchaseOrder $purchaseOrder)
    {
        $purchaseOrder->load(['branch.company', 'supplier', 'items.item.purchaseUnit', 'items.unit']);
        $settings = \App\Models\Setting::pluck('value', 'key')->toArray();

        return Inertia::render('Trade/PrintDocument', [
            'document' => $purchaseOrder,
            'type' => 'PO',
            'settings' => $settings,
            'company' => \App\Models\Company::first(),
        ]);
    }
}
