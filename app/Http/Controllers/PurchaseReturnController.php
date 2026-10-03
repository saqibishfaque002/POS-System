<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Branch;
use App\Models\PurchaseReturn;
use App\Models\Supplier;
use App\Models\PurchaseInvoice;
use App\Http\Requests\Trade\PurchaseReturnRequest;
use App\Services\TradeDocumentService;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;

class PurchaseReturnController extends Controller
{
    public function index()
    {
        $purchaseReturns = PurchaseReturn::with(['branch', 'supplier', 'purchaseInvoice'])->latest()->get();
        $user = auth()->user();
        $isAdmin = in_array($user->role, ['admin', 'manager']);
        $branches = $isAdmin ? Branch::where('is_active', true)->orderBy('name')->get() : $user->branches()->where('is_active', true)->orderBy('name')->get();
        $suppliers = Supplier::where('is_active', true)->get();
        
        // Fetch approved PIs to allow creating PR from PI
        $approvedPis = PurchaseInvoice::with(['items.item.purchaseUnit', 'items.unit'])
            ->whereIn('status', ['approved', 'paid'])
            ->doesntHave('purchaseReturn') // assuming one return per PI for now, or omit this to allow multiple
            ->get();

        $items = \App\Models\IngredientItem::with(['purchaseUnit', 'consumptionUnit'])->where('is_active', true)->get();
        $units = \App\Models\IngredientUnit::where('is_active', true)->get();

        return Inertia::render('Trade/PurchaseReturns/Index', [
            'purchaseReturns' => $purchaseReturns,
            'branches' => $branches,
            'suppliers' => $suppliers,
            'approvedPis' => $approvedPis,
            'items' => $items,
            'units' => $units,
        ]);
    }

    public function store(PurchaseReturnRequest $request, TradeDocumentService $service)
    {
        $validated = $request->validated();

        $lastDoc = PurchaseReturn::where('branch_id', $validated['branch_id'])->latest('id')->first();
        $sequence = $lastDoc ? intval(explode('-', $lastDoc->document_number)[2] ?? 0) + 1 : 1;
        $documentNumber = 'PRTN-' . $validated['branch_id'] . '-' . str_pad($sequence, 5, '0', STR_PAD_LEFT);

        DB::transaction(function () use ($validated, $documentNumber, $service) {
            $purchaseReturn = PurchaseReturn::create([
                'document_number' => $documentNumber,
                'branch_id' => $validated['branch_id'],
                'supplier_id' => $validated['supplier_id'],
                'reference_id' => $validated['reference_id'] ?? null,
                'date' => $validated['date'],
                'notes' => $validated['notes'],
                'discount_amount' => $validated['discount_amount'] ?? 0,
                'tax_amount' => $validated['tax_amount'] ?? 0,
                'shipping_amount' => $validated['shipping_amount'] ?? 0,
                'status' => 'draft',
                'created_by' => auth()->id()
            ]);

            $service->savePurchaseReturnItems($purchaseReturn, $validated['items'], $validated);
        });

        return back()->with('success', 'Purchase Return created successfully.');
    }

    public function update(PurchaseReturnRequest $request, PurchaseReturn $purchaseReturn, TradeDocumentService $service)
    {
        if ($purchaseReturn->status !== 'draft') {
            return back()->with('error', 'Only draft PR can be edited.');
        }

        $validated = $request->validated();

        DB::transaction(function () use ($purchaseReturn, $validated, $service) {
            $purchaseReturn->update([
                'branch_id' => $validated['branch_id'],
                'supplier_id' => $validated['supplier_id'],
                'reference_id' => $validated['reference_id'] ?? null,
                'date' => $validated['date'],
                'notes' => $validated['notes'],
                'discount_amount' => $validated['discount_amount'] ?? 0,
                'tax_amount' => $validated['tax_amount'] ?? 0,
                'shipping_amount' => $validated['shipping_amount'] ?? 0,
            ]);

            $service->savePurchaseReturnItems($purchaseReturn, $validated['items'], $validated);
        });

        return back()->with('success', 'Purchase Return updated successfully.');
    }

    public function updateStatus(Request $request, PurchaseReturn $purchaseReturn)
    {
        $validated = $request->validate([
            'status' => 'required|in:draft,approved,completed,cancelled'
        ]);

        if (in_array($purchaseReturn->status, ['completed']) && in_array($validated['status'], ['draft', 'approved'])) {
            return back()->with('error', 'Cannot revert from completed.');
        }

        DB::transaction(function () use ($purchaseReturn, $validated) {
            $oldStatus = $purchaseReturn->status;
            $purchaseReturn->update(['status' => $validated['status']]);

            // When PR is marked completed, deduct stock
            if ($validated['status'] === 'completed' && $oldStatus !== 'completed') {
                foreach ($purchaseReturn->items as $item) {
                    \App\Models\InventoryLedger::create([
                        'branch_id' => $purchaseReturn->branch_id,
                        'item_id' => $item->item_id,
                        'document_type' => PurchaseReturn::class,
                        'document_id' => $purchaseReturn->id,
                        'document_date' => $purchaseReturn->date,
                        'quantity' => -abs($item->quantity), // Negative for OUT
                        'unit_price' => $item->unit_price,
                        'notes' => 'Returned via PR ' . $purchaseReturn->document_number,
                        'created_by' => auth()->id()
                    ]);
                }
            }
        });

        return back()->with('success', 'Purchase Return status updated.');
    }

    public function destroy(PurchaseReturn $purchaseReturn)
    {
        if ($purchaseReturn->status !== 'draft') {
            return back()->with('error', 'Only draft PRs can be deleted.');
        }
        $purchaseReturn->delete();
        return back()->with('success', 'Purchase Return deleted.');
    }

    public function print(PurchaseReturn $purchaseReturn)
    {
        $purchaseReturn->load(['branch.company', 'supplier', 'items.item.purchaseUnit', 'items.unit', 'purchaseInvoice']);
        $settings = \App\Models\Setting::pluck('value', 'key')->toArray();

        return Inertia::render('Trade/PrintDocument', [
            'document' => $purchaseReturn,
            'type' => 'PRTN',
            'settings' => $settings,
            'company' => \App\Models\Company::first(),
        ]);
    }
}
