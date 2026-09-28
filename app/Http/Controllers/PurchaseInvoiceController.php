<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Branch;
use App\Models\PurchaseInvoice;
use App\Models\Supplier;
use App\Models\GoodReceivedNote;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;

class PurchaseInvoiceController extends Controller
{
    public function index()
    {
        $purchaseInvoices = PurchaseInvoice::with(['branch', 'supplier', 'goodReceivedNote'])->latest()->get();
        $branches = Branch::where('is_active', true)->get();
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

    public function store(Request $request)
    {
        $validated = $request->validate([
            'branch_id' => 'required|exists:branches,id',
            'supplier_id' => 'required|exists:suppliers,id',
            'reference_id' => 'nullable|exists:good_received_notes,id',
            'date' => 'required|date',
            'due_date' => 'nullable|date',
            'notes' => 'nullable|string',
            'discount_amount' => 'numeric|min:0',
            'tax_amount' => 'numeric|min:0',
            'shipping_amount' => 'numeric|min:0',
            'items' => 'required|array|min:1',
            'items.*.item_id' => 'required|exists:ingredient_items,id',
            'items.*.unit_id' => 'required|exists:ingredient_units,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.discount_amount' => 'numeric|min:0',
            'items.*.tax_amount' => 'numeric|min:0',
        ]);

        $lastDoc = PurchaseInvoice::where('branch_id', $validated['branch_id'])->latest('id')->first();
        $sequence = $lastDoc ? intval(explode('-', $lastDoc->document_number)[2] ?? 0) + 1 : 1;
        $documentNumber = 'PI-' . $validated['branch_id'] . '-' . str_pad($sequence, 5, '0', STR_PAD_LEFT);

        DB::transaction(function () use ($validated, $documentNumber) {
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

            $totalAmount = 0;

            foreach ($validated['items'] as $item) {
                $subtotal = ($item['quantity'] * $item['unit_price']) - ($item['discount_amount'] ?? 0) + ($item['tax_amount'] ?? 0);
                $totalAmount += $subtotal;

                $purchaseInvoice->items()->create([
                    'item_id' => $item['item_id'],
                    'unit_id' => $item['unit_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'discount_amount' => $item['discount_amount'] ?? 0,
                    'tax_amount' => $item['tax_amount'] ?? 0,
                    'subtotal' => $subtotal,
                ]);
            }

            // Apply global adjustments
            $totalAmount = $totalAmount - ($validated['discount_amount'] ?? 0) + ($validated['tax_amount'] ?? 0) + ($validated['shipping_amount'] ?? 0);

            $purchaseInvoice->update(['total_amount' => $totalAmount]);
        });

        return back()->with('success', 'Purchase Invoice created successfully.');
    }

    public function update(Request $request, PurchaseInvoice $purchaseInvoice)
    {
        if ($purchaseInvoice->status !== 'draft') {
            return back()->with('error', 'Only draft PI can be edited.');
        }

        $validated = $request->validate([
            'branch_id' => 'required|exists:branches,id',
            'supplier_id' => 'required|exists:suppliers,id',
            'reference_id' => 'nullable|exists:good_received_notes,id',
            'date' => 'required|date',
            'due_date' => 'nullable|date',
            'notes' => 'nullable|string',
            'discount_amount' => 'numeric|min:0',
            'tax_amount' => 'numeric|min:0',
            'shipping_amount' => 'numeric|min:0',
            'items' => 'required|array|min:1',
            'items.*.item_id' => 'required|exists:ingredient_items,id',
            'items.*.unit_id' => 'required|exists:ingredient_units,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.discount_amount' => 'numeric|min:0',
            'items.*.tax_amount' => 'numeric|min:0',
        ]);

        DB::transaction(function () use ($purchaseInvoice, $validated) {
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

            $purchaseInvoice->items()->delete();
            
            $totalAmount = 0;
            foreach ($validated['items'] as $item) {
                $subtotal = ($item['quantity'] * $item['unit_price']) - ($item['discount_amount'] ?? 0) + ($item['tax_amount'] ?? 0);
                $totalAmount += $subtotal;

                $purchaseInvoice->items()->create([
                    'item_id' => $item['item_id'],
                    'unit_id' => $item['unit_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'discount_amount' => $item['discount_amount'] ?? 0,
                    'tax_amount' => $item['tax_amount'] ?? 0,
                    'subtotal' => $subtotal,
                ]);
            }

            $totalAmount = $totalAmount - ($validated['discount_amount'] ?? 0) + ($validated['tax_amount'] ?? 0) + ($validated['shipping_amount'] ?? 0);
            $purchaseInvoice->update(['total_amount' => $totalAmount]);
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
