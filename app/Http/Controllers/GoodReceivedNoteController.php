<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Branch;
use App\Models\GoodReceivedNote;
use App\Models\Supplier;
use App\Models\IngredientItem;
use App\Models\IngredientUnit;
use App\Models\PurchaseOrder;
use App\Models\InventoryLedger;
use App\Http\Requests\Trade\GoodReceivedNoteRequest;
use App\Services\TradeDocumentService;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
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

    public function store(GoodReceivedNoteRequest $request, TradeDocumentService $service)
    {
        $validated = $request->validated();

        $lastDoc = GoodReceivedNote::where('branch_id', $validated['branch_id'])->latest('id')->first();
        $sequence = $lastDoc ? intval(explode('-', $lastDoc->document_number)[2] ?? 0) + 1 : 1;
        $documentNumber = 'GRN-' . $validated['branch_id'] . '-' . str_pad($sequence, 5, '0', STR_PAD_LEFT);

        DB::transaction(function () use ($validated, $documentNumber, $service) {
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

            $service->saveGoodReceivedNoteItems($goodsRecievedNotes, $validated['items']);
        });

        return back()->with('success', 'GRN created successfully.');
    }

    public function update(GoodReceivedNoteRequest $request, GoodReceivedNote $goodReceivedNote, TradeDocumentService $service)
    {
        if ($goodReceivedNote->status !== 'draft') {
            return back()->with('error', 'Only draft GRN can be edited.');
        }

        $validated = $request->validated();

        DB::transaction(function () use ($goodReceivedNote, $validated, $service) {
            $goodReceivedNote->update([
                'branch_id' => $validated['branch_id'],
                'supplier_id' => $validated['supplier_id'],
                'reference_id' => $validated['reference_id'] ?? null,
                'date' => $validated['date'],
                'notes' => $validated['notes'],
            ]);

            $service->saveGoodReceivedNoteItems($goodReceivedNote, $validated['items']);
        });

        return back()->with('success', 'GRN updated successfully.');
    }

    public function updateStatus(Request $request, GoodReceivedNote $goodReceivedNote)
    {
        $validated = $request->validate([
            'status' => 'required|in:draft,issued,completed,cancelled'
        ]);

        if ($goodReceivedNote->status === 'completed' && $validated['status'] !== 'completed') {
            return back()->with('error', 'Cannot change status of a completed GRN.');
        }

        DB::transaction(function () use ($goodReceivedNote, $validated) {
            $goodReceivedNote->update(['status' => $validated['status']]);

            if ($validated['status'] === 'completed') {
                foreach ($goodReceivedNote->items as $item) {
                    InventoryLedger::create([
                        'branch_id' => $goodReceivedNote->branch_id,
                        'item_id' => $item->item_id,
                        'document_type' => GoodReceivedNote::class,
                        'document_id' => $goodReceivedNote->id,
                        'document_date' => $goodReceivedNote->date,
                        'quantity' => $item->quantity, // Positive for IN
                        'unit_price' => $item->unit_price,
                        'notes' => 'Received via GRN ' . $goodReceivedNote->document_number,
                        'created_by' => auth()->id()
                    ]);
                }
            }
        });

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
