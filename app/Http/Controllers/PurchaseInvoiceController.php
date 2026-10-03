<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Branch;
use App\Models\PurchaseInvoice;
use App\Models\Supplier;
use App\Models\GoodReceivedNote;
use App\Http\Requests\Trade\PurchaseInvoiceRequest;
use App\Services\TradeDocumentService;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;

class PurchaseInvoiceController extends Controller
{
    public function index()
    {
        $purchaseInvoices = PurchaseInvoice::with(['branch', 'supplier', 'goodReceivedNote'])->latest()->get();
        $user = auth()->user();
        $isAdmin = in_array($user->role, ['admin', 'manager']);
        $branches = $isAdmin ? Branch::where('is_active', true)->orderBy('name')->get() : $user->branches()->where('is_active', true)->orderBy('name')->get();
        $suppliers = Supplier::where('is_active', true)->get();
        
        // Fetch approved GRNs to allow creating PI from GRN
        $approvedGrns = GoodReceivedNote::with(['items.item.purchaseUnit', 'items.unit'])
            ->where('status', 'completed')
            ->doesntHave('purchaseInvoice')
            ->get();

        $items = \App\Models\IngredientItem::with(['purchaseUnit', 'consumptionUnit'])->where('is_active', true)->get();
        $units = \App\Models\IngredientUnit::where('is_active', true)->get();

        return Inertia::render('Trade/PurchaseInvoices/Index', [
            'purchaseInvoices' => $purchaseInvoices,
            'branches' => $branches,
            'suppliers' => $suppliers,
            'approvedGrns' => $approvedGrns,
            'items' => $items,
            'units' => $units,
        ]);
    }

    public function store(PurchaseInvoiceRequest $request, TradeDocumentService $service)
    {
        $validated = $request->validated();

        $lastDoc = PurchaseInvoice::where('branch_id', $validated['branch_id'])->latest('id')->first();
        $sequence = $lastDoc ? intval(explode('-', $lastDoc->document_number)[2] ?? 0) + 1 : 1;
        $documentNumber = 'PI-' . $validated['branch_id'] . '-' . str_pad($sequence, 5, '0', STR_PAD_LEFT);

        DB::transaction(function () use ($validated, $documentNumber, $service) {
            $purchaseInvoice = PurchaseInvoice::create([
                'document_number' => $documentNumber,
                'branch_id' => $validated['branch_id'],
                'supplier_id' => $validated['supplier_id'],
                'reference_id' => $validated['reference_id'] ?? null,
                'date' => $validated['date'],
                'due_date' => $validated['due_date'] ?? null,
                'notes' => $validated['notes'],
                'discount_amount' => $validated['discount_amount'] ?? 0,
                'tax_amount' => $validated['tax_amount'] ?? 0,
                'shipping_amount' => $validated['shipping_amount'] ?? 0,
                'status' => 'draft',
                'created_by' => auth()->id()
            ]);

            $service->savePurchaseInvoiceItems($purchaseInvoice, $validated['items'], $validated);
        });

        return back()->with('success', 'Purchase Invoice created successfully.');
    }

    public function update(PurchaseInvoiceRequest $request, PurchaseInvoice $purchaseInvoice, TradeDocumentService $service)
    {
        if ($purchaseInvoice->status !== 'draft') {
            return back()->with('error', 'Only draft PI can be edited.');
        }

        $validated = $request->validated();

        DB::transaction(function () use ($purchaseInvoice, $validated, $service) {
            $purchaseInvoice->update([
                'branch_id' => $validated['branch_id'],
                'supplier_id' => $validated['supplier_id'],
                'reference_id' => $validated['reference_id'] ?? null,
                'date' => $validated['date'],
                'due_date' => $validated['due_date'] ?? null,
                'notes' => $validated['notes'],
                'discount_amount' => $validated['discount_amount'] ?? 0,
                'tax_amount' => $validated['tax_amount'] ?? 0,
                'shipping_amount' => $validated['shipping_amount'] ?? 0,
            ]);

            $service->savePurchaseInvoiceItems($purchaseInvoice, $validated['items'], $validated);
        });

        return back()->with('success', 'Purchase Invoice updated successfully.');
    }

    public function updateStatus(Request $request, PurchaseInvoice $purchaseInvoice)
    {
        $validated = $request->validate([
            'status' => 'required|in:draft,approved,paid,cancelled'
        ]);

        if (in_array($purchaseInvoice->status, ['approved', 'paid']) && in_array($validated['status'], ['draft'])) {
            return back()->with('error', 'Cannot revert to draft after approval.');
        }

        DB::transaction(function () use ($purchaseInvoice, $validated) {
            $oldStatus = $purchaseInvoice->status;
            $purchaseInvoice->update(['status' => $validated['status']]);

            // If this PI is NOT linked to a GRN, we must handle stock tracking.
            // When status becomes 'approved', stock goes IN.
            if (empty($purchaseInvoice->reference_id) && $validated['status'] === 'approved' && $oldStatus === 'draft') {
                foreach ($purchaseInvoice->items as $item) {
                    \App\Models\InventoryLedger::create([
                        'branch_id' => $purchaseInvoice->branch_id,
                        'item_id' => $item->item_id,
                        'document_type' => PurchaseInvoice::class,
                        'document_id' => $purchaseInvoice->id,
                        'document_date' => $purchaseInvoice->date,
                        'quantity' => $item->quantity, // Positive for IN
                        'unit_price' => $item->unit_price,
                        'notes' => 'Received via Direct PI ' . $purchaseInvoice->document_number,
                        'created_by' => auth()->id()
                    ]);
                }
            }
        });

        return back()->with('success', 'Purchase Invoice status updated.');
    }

    public function destroy(PurchaseInvoice $purchaseInvoice)
    {
        if ($purchaseInvoice->status !== 'draft') {
            return back()->with('error', 'Only draft PIs can be deleted.');
        }
        $purchaseInvoice->delete();
        return back()->with('success', 'Purchase Invoice deleted.');
    }

    public function print(PurchaseInvoice $purchaseInvoice)
    {
        $purchaseInvoice->load(['branch.company', 'supplier', 'items.item.purchaseUnit', 'items.unit', 'goodReceivedNote']);
        $settings = \App\Models\Setting::pluck('value', 'key')->toArray();

        return Inertia::render('Trade/PrintDocument', [
            'document' => $purchaseInvoice,
            'type' => 'PI',
            'settings' => $settings,
            'company' => \App\Models\Company::first(),
        ]);
    }
}
