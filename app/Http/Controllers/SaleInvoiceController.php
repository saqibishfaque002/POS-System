<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Branch;
use App\Models\SaleInvoice;
use App\Models\FoodItem;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;

class SaleInvoiceController extends Controller
{
    public function index()
    {
        $saleInvoices = SaleInvoice::with(['branch', 'items.item'])->latest()->get();
        $branches = Branch::where('is_active', true)->get();
        $items = FoodItem::where('is_active', true)->get();

        return Inertia::render('Trade/SaleInvoices/Index', [
            'saleInvoices' => $saleInvoices,
            'branches' => $branches,
            'items' => $items,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'branch_id' => 'required|exists:branches,id',
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

        $lastDoc = SaleInvoice::where('branch_id', $validated['branch_id'])->latest('id')->first();
        $sequence = $lastDoc ? intval(explode('-', $lastDoc->document_number)[2] ?? 0) + 1 : 1;
        $documentNumber = 'SI-' . $validated['branch_id'] . '-' . str_pad($sequence, 5, '0', STR_PAD_LEFT);

        DB::transaction(function () use ($validated, $documentNumber) {
            $saleInvoice = SaleInvoice::create([
                'document_number' => $documentNumber,
                'branch_id' => $validated['branch_id'],
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

                $saleInvoice->items()->create([
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

            $saleInvoice->update(['total_amount' => $totalAmount]);
        });

        return back()->with('success', 'Sale Invoice created successfully.');
    }

    public function update(Request $request, SaleInvoice $saleInvoice)
    {
        if ($saleInvoice->status !== 'draft') {
            return back()->with('error', 'Only draft Sale Invoices can be edited.');
        }

        $validated = $request->validate([
            'branch_id' => 'required|exists:branches,id',
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

        DB::transaction(function () use ($saleInvoice, $validated) {
            $saleInvoice->update([
                'branch_id' => $validated['branch_id'],
                'customer_name' => $validated['customer_name'] ?? null,
                'customer_phone' => $validated['customer_phone'] ?? null,
                'date' => $validated['date'],
                'notes' => $validated['notes'],
                'discount_amount' => $validated['discount_amount'] ?? 0,
                'tax_amount' => $validated['tax_amount'] ?? 0,
                'shipping_amount' => $validated['shipping_amount'] ?? 0,
            ]);

            $saleInvoice->items()->delete();
            
            $totalAmount = 0;
            foreach ($validated['items'] as $item) {
                $subtotal = ($item['quantity'] * $item['unit_price']) - ($item['discount_amount'] ?? 0) + ($item['tax_amount'] ?? 0);
                $totalAmount += $subtotal;

                $saleInvoice->items()->create([
                    'item_id' => $item['item_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'discount_amount' => $item['discount_amount'] ?? 0,
                    'tax_amount' => $item['tax_amount'] ?? 0,
                    'subtotal' => $subtotal,
                ]);
            }

            $totalAmount = $totalAmount - ($validated['discount_amount'] ?? 0) + ($validated['tax_amount'] ?? 0) + ($validated['shipping_amount'] ?? 0);
            $saleInvoice->update(['total_amount' => $totalAmount]);
        });

        return back()->with('success', 'Sale Invoice updated successfully.');
    }

    public function updateStatus(Request $request, SaleInvoice $saleInvoice)
    {
        $validated = $request->validate([
            'status' => 'required|in:draft,approved,paid,cancelled'
        ]);

        if (in_array($saleInvoice->status, ['paid']) && in_array($validated['status'], ['draft', 'approved'])) {
            return back()->with('error', 'Cannot revert from paid.');
        }

        $saleInvoice->update(['status' => $validated['status']]);

        // Inventory deduction is skipped here as we don't have recipes linking FoodItems to IngredientItems yet.

        return back()->with('success', 'Sale Invoice status updated.');
    }

    public function destroy(SaleInvoice $saleInvoice)
    {
        if ($saleInvoice->status !== 'draft') {
            return back()->with('error', 'Only draft SIs can be deleted.');
        }
        $saleInvoice->delete();
        return back()->with('success', 'Sale Invoice deleted.');
    }

    public function print(SaleInvoice $saleInvoice)
    {
        $saleInvoice->load(['branch.company', 'items.item']);
        $settings = \App\Models\Setting::pluck('value', 'key')->toArray();

        return Inertia::render('Trade/PrintDocument', [
            'document' => $saleInvoice,
            'type' => 'SI',
            'settings' => $settings,
            'company' => \App\Models\Company::first(),
        ]);
    }
}
