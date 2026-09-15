<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\PurchaseRequest;
use App\Models\FoodItem;
use App\Models\Branch;

class PurchaseRequestController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $isAdmin = in_array($user->role, ['admin', 'manager']);
        $branchIds = $isAdmin ? [] : $user->branches()->pluck('branches.id')->toArray();

        $query = PurchaseRequest::with(['items.item', 'branch']);
        if (!$isAdmin) {
            $query->whereIn('branch_id', $branchIds);
        }

        return Inertia::render('Trade/PurchaseRequests/Index', [
            'purchaseRequests' => $query->latest()->get(),
            'branches' => $isAdmin ? Branch::orderBy('name')->get() : $user->branches,
            'items' => FoodItem::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'branch_id' => 'required|exists:branches,id',
            'date' => 'required|date',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.item_id' => 'required|exists:food_items,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0',
        ]);

        $lastDoc = PurchaseRequest::where('branch_id', $validated['branch_id'])->latest('id')->first();
        $sequence = $lastDoc ? intval(explode('-', $lastDoc->document_number)[2] ?? 0) + 1 : 1;
        $docNumber = 'PR-' . $validated['branch_id'] . '-' . str_pad($sequence, 5, '0', STR_PAD_LEFT);

        $totalAmount = 0;
        foreach ($validated['items'] as &$item) {
            $item['subtotal'] = $item['quantity'] * $item['unit_price'];
            $totalAmount += $item['subtotal'];
        }

        $pr = PurchaseRequest::create([
            'branch_id' => $validated['branch_id'],
            'document_number' => $docNumber,
            'date' => $validated['date'],
            'notes' => $validated['notes'],
            'total_amount' => $totalAmount,
            'created_by' => auth()->id(),
            'status' => 'approved' // Automatically approved for now
        ]);

        $pr->items()->createMany($validated['items']);

        return back()->with('success', 'Purchase Request created successfully.');
    }

    public function destroy(PurchaseRequest $purchaseRequest)
    {
        $purchaseRequest->delete();
        return back();
    }
}
