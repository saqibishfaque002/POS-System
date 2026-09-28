<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Branch;
use App\Models\SaleReturn;
use App\Models\SaleInvoice;
use App\Models\FoodItem;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;

class SaleReturnController extends Controller
{
    public function index()
    {
        $saleReturns = SaleReturn::with(['branch', 'saleInvoice', 'items.item'])->latest()->get();
        $branches = Branch::where('is_active', true)->get();
        $items = FoodItem::where('is_active', true)->get();

        // Fetch paid/completed SIs to allow creating SRTN from SI
        $paidSis = SaleInvoice::with(['items.item'])
            ->whereIn('status', ['paid', 'approved', 'completed'])
            ->doesntHave('saleReturn') // assuming one return per SI for now
            ->get();

        return Inertia::render('Trade/SaleReturns/Index', [
            'saleReturns' => $saleReturns,
            'branches' => $branches,
            'paidSis' => $paidSis,
            'items' => $items,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'branch_id' => 'required|exists:branches,id',
            'reference_id' => 'nullable|exists:sale_invoices,id',
            'customer_name' => 'nullable|string|max:255',
            'customer_phone' => 'nullable|string|max:50',
            'date' => 'required|date',
            'notes' => 'nullable|string',
            'discount_amount' => 'numeric|min:0',
            'tax_amount' => 'numeric|min:0',
            'shipping_amount' => 'numeric|min:0',
            'items' => 'required|array|min:1',
            'items.*.item_id' => 'required|exists:food_items,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.discount_amount' => 'numeric|min:0',
            'items.*.tax_amount' => 'numeric|min:0',
        ]);

        $lastDoc = SaleReturn::where('branch_id', $validated['branch_id'])->latest('id')->first();
        $sequence = $lastDoc ? intval(explode('-', $lastDoc->document_number)[2] ?? 0) + 1 : 1;
        $documentNumber = 'SRTN-' . $validated['branch_id'] . '-' . str_pad($sequence, 5, '0', STR_PAD_LEFT);

        DB::transaction(function () use ($validated, $documentNumber) {
            $saleReturn = SaleReturn::create([
                'document_number' => $documentNumber,
                'branch_id' => $validated['branch_id'],
                'reference_id' => $validated['reference_id'] ?? null,
                'customer_name' => $validated['customer_name'] ?? null,
                'customer_phone' => $validated['customer_phone'] ?? null,
                'date' => $validated['date'],
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

                $saleReturn->items()->create([
                    'item_id' => $item['item_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'discount_amount' => $item['discount_amount'] ?? 0,
                    'tax_amount' => $item['tax_amount'] ?? 0,
                    'subtotal' => $subtotal,
                ]);
            }

            // Apply global adjustments
            $totalAmount = $totalAmount - ($validated['discount_amount'] ?? 0) + ($validated['tax_amount'] ?? 0) + ($validated['shipping_amount'] ?? 0);

            $saleReturn->update(['total_amount' => $totalAmount]);
        });

        return back()->with('success', 'Sale Return created successfully.');
    }

    public function update(Request $request, SaleReturn $saleReturn)
    {
        if ($saleReturn->status !== 'draft') {
            return back()->with('error', 'Only draft SRTNs can be edited.');
        }

        $validated = $request->validate([
            'branch_id' => 'required|exists:branches,id',
            'reference_id' => 'nullable|exists:sale_invoices,id',
            'customer_name' => 'nullable|string|max:255',
            'customer_phone' => 'nullable|string|max:50',
            'date' => 'required|date',
            'notes' => 'nullable|string',
            'discount_amount' => 'numeric|min:0',
            'tax_amount' => 'numeric|min:0',
            'shipping_amount' => 'numeric|min:0',
            'items' => 'required|array|min:1',
            'items.*.item_id' => 'required|exists:food_items,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.discount_amount' => 'numeric|min:0',
            'items.*.tax_amount' => 'numeric|min:0',
        ]);

        DB::transaction(function () use ($saleReturn, $validated) {
            $saleReturn->update([
                'branch_id' => $validated['branch_id'],
                'reference_id' => $validated['reference_id'] ?? null,
                'customer_name' => $validated['customer_name'] ?? null,
                'customer_phone' => $validated['customer_phone'] ?? null,
                'date' => $validated['date'],
                'notes' => $validated['notes'],
                'discount_amount' => $validated['discount_amount'] ?? 0,
                'tax_amount' => $validated['tax_amount'] ?? 0,
                'shipping_amount' => $validated['shipping_amount'] ?? 0,
            ]);

            $saleReturn->items()->delete();
            
            $totalAmount = 0;
            foreach ($validated['items'] as $item) {
                $subtotal = ($item['quantity'] * $item['unit_price']) - ($item['discount_amount'] ?? 0) + ($item['tax_amount'] ?? 0);
                $totalAmount += $subtotal;

                $saleReturn->items()->create([
                    'item_id' => $item['item_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'discount_amount' => $item['discount_amount'] ?? 0,
                    'tax_amount' => $item['tax_amount'] ?? 0,
                    'subtotal' => $subtotal,
                ]);
            }

            $totalAmount = $totalAmount - ($validated['discount_amount'] ?? 0) + ($validated['tax_amount'] ?? 0) + ($validated['shipping_amount'] ?? 0);
            $saleReturn->update(['total_amount' => $totalAmount]);
        });

        return back()->with('success', 'Sale Return updated successfully.');
    }

    public function updateStatus(Request $request, SaleReturn $saleReturn)
    {
        $validated = $request->validate([
            'status' => 'required|in:draft,approved,completed,cancelled'
        ]);

        if (in_array($saleReturn->status, ['completed']) && in_array($validated['status'], ['draft', 'approved'])) {
            return back()->with('error', 'Cannot revert from completed.');
        }

        $saleReturn->update(['status' => $validated['status']]);

        // Inventory logic is intentionally skipped here because we do not have a Recipe/BOM yet to restock ingredient_items.

        return back()->with('success', 'Sale Return status updated.');
    }

    public function destroy(SaleReturn $saleReturn)
    {
        if ($saleReturn->status !== 'draft') {
            return back()->with('error', 'Only draft SRTNs can be deleted.');
        }
        $saleReturn->delete();
        return back()->with('success', 'Sale Return deleted.');
    }

    public function print(SaleReturn $saleReturn)
    {
        $saleReturn->load(['branch.company', 'items.item', 'saleInvoice']);
        $settings = \App\Models\Setting::pluck('value', 'key')->toArray();

        return Inertia::render('Trade/PrintDocument', [
            'document' => $saleReturn,
            'type' => 'SRTN',
            'settings' => $settings,
            'company' => \App\Models\Company::first(),
        ]);
    }
}
