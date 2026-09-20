<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Branch;
use App\Models\GoodReceivedNote;
use App\Models\Supplier;
use App\Models\IngredientItem;
use App\Models\IngredientUnit;
use App\Models\PurchaseOrder;
use Inertia\Inertia;
use Illuminate\Support\Str;

class GoodReceivedNoteController extends Controller
{
    public function index()
    {
        $goodsRecievedNotes = GoodReceivedNote::with(['branch', 'supplier', 'items.item.purchaseUnit'])->latest()->get();
        $branches = Branch::where('is_active', true)->get();
        $suppliers = Supplier::where('is_active', true)->get();
        $items = IngredientItem::with(['purchaseUnit', 'consumptionUnit'])->where('is_active', true)->get();
        $units = IngredientUnit::where('is_active', true)->get();
        
        // Fetch approved PO to allow creating PO from PR
        $approvedPos = PurchaseOrder::with('items')->where('status', 'completed')->get();

        return Inertia::render('Trade/GoodsRecievedNotes/Index', [
            'goodsRecievedNotes' => $goodsRecievedNotes,
            'branches' => $branches,
            'suppliers' => $suppliers,
            'items' => $items,
            'units' => $units,
            'approvedPos' => $approvedPos
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'branch_id' => 'required|exists:branches,id',
            'supplier_id' => 'required|exists:suppliers,id',
            'reference_id' => 'nullable|exists:purchase_orders,id',
            'date' => 'required|date',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.item_id' => 'required|exists:ingredient_items,id',
            'items.*.unit_id' => 'required|exists:ingredient_units,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0',
        ]);

        $lastDoc = GoodReceivedNote::where('branch_id', $validated['branch_id'])->latest('id')->first();
        $sequence = $lastDoc ? intval(explode('-', $lastDoc->document_number)[2] ?? 0) + 1 : 1;
        $documentNumber = 'GRN-' . $validated['branch_id'] . '-' . str_pad($sequence, 5, '0', STR_PAD_LEFT);

        $goodsRecievedNotes = GoodReceivedNote::create([
            'document_number' => $documentNumber,
            'branch_id' => $validated['branch_id'],
            'supplier_id' => $validated['supplier_id'],
            'reference_id' => $validated['reference_id'] ?? null,
            'date' => $validated['date'],
            'notes' => $validated['notes'],
            'status' => 'draft',
            'created_by' => auth()->id()
        ]);

        $totalAmount = 0;

        foreach ($validated['items'] as $item) {
            $subtotal = $item['quantity'] * $item['unit_price'];
            $totalAmount += $subtotal;

            $goodsRecievedNotes->items()->create([
                'item_id' => $item['item_id'],
                'unit_id' => $item['unit_id'],
                'quantity' => $item['quantity'],
                'unit_price' => $item['unit_price'],
                'subtotal' => $subtotal,
            ]);
        }

        $goodsRecievedNotes->update(['total_amount' => $totalAmount]);

        return back()->with('success', 'GRN created successfully.');
    }

    public function update(Request $request, GoodReceivedNote $goodReceivedNote)
    {
        if ($goodReceivedNote->status !== 'draft') {
            return back()->with('error', 'Only draft GRN can be edited.');
        }

        $validated = $request->validate([
            'branch_id' => 'required|exists:branches,id',
            'supplier_id' => 'required|exists:suppliers,id',
            'reference_id' => 'nullable|exists:purchase_orders,id',
            'date' => 'required|date',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.item_id' => 'required|exists:ingredient_items,id',
            'items.*.unit_id' => 'required|exists:ingredient_units,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0',
        ]);

        $goodReceivedNote->update([
            'branch_id' => $validated['branch_id'],
            'supplier_id' => $validated['supplier_id'],
            'reference_id' => $validated['reference_id'] ?? null,
            'date' => $validated['date'],
            'notes' => $validated['notes'],
        ]);

        $goodReceivedNote->items()->delete();
        
        $totalAmount = 0;
        foreach ($validated['items'] as $item) {
            $subtotal = $item['quantity'] * $item['unit_price'];
            $totalAmount += $subtotal;

            $goodReceivedNote->items()->create([
                'item_id' => $item['item_id'],
                'unit_id' => $item['unit_id'],
                'quantity' => $item['quantity'],
                'unit_price' => $item['unit_price'],
                'subtotal' => $subtotal,
            ]);
        }

        $goodReceivedNote->update(['total_amount' => $totalAmount]);

        return back()->with('success', 'GRN updated successfully.');
    }

    public function updateStatus(Request $request, GoodReceivedNote $goodReceivedNote)
    {
        $validated = $request->validate([
            'status' => 'required|in:draft,issued,completed,cancelled'
        ]);

        $goodReceivedNote->update(['status' => $validated['status']]);

        return back()->with('success', 'GRN status updated.');
    }

    public function destroy(GoodReceivedNote $goodReceivedNote)
    {
        if ($goodReceivedNote->status !== 'draft') {
            return back()->with('error', 'Only draft GRNs can be deleted.');
        }
        $goodReceivedNote->delete();
        return back()->with('success', 'GRN deleted.');
    }

    public function print(GoodReceivedNote $goodReceivedNote)
    {
        $goodReceivedNote->load(['branch.company', 'supplier', 'items.item.purchaseUnit', 'items.unit']);
        $settings = \App\Models\Setting::pluck('value', 'key')->toArray();

        return Inertia::render('Trade/PrintDocument', [
            'document' => $goodReceivedNote,
            'type' => 'GRN',
            'settings' => $settings,
            'company' => \App\Models\Company::first(),
        ]);
    }
}
