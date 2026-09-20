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
            'items' => \App\Models\IngredientItem::with('purchaseUnit')->where('is_active', true)->orderBy('name')->get(),
            'units' => \App\Models\IngredientUnit::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'branch_id' => 'required|exists:branches,id',
            'date' => 'required|date',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.item_id' => 'required|exists:ingredient_items,id',
            'items.*.unit_id' => 'required|exists:ingredient_units,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
        ]);

        $lastDoc = PurchaseRequest::where('branch_id', $validated['branch_id'])->latest('id')->first();
        $sequence = $lastDoc ? intval(explode('-', $lastDoc->document_number)[2] ?? 0) + 1 : 1;
        $docNumber = 'PR-' . $validated['branch_id'] . '-' . str_pad($sequence, 5, '0', STR_PAD_LEFT);

        $totalAmount = 0; // PRs may not have a total amount initially now

        $pr = PurchaseRequest::create([
            'branch_id' => $validated['branch_id'],
            'document_number' => $docNumber,
            'date' => $validated['date'],
            'notes' => $validated['notes'],
            'total_amount' => $totalAmount,
            'created_by' => auth()->id(),
            'status' => 'draft'
        ]);

        $pr->items()->createMany($validated['items']);

        return back()->with('success', 'Purchase Request created successfully.');
    }

    public function update(Request $request, PurchaseRequest $purchaseRequest)
    {
        if ($purchaseRequest->status !== 'draft') {
            return back()->with('error', 'Only draft purchase requests can be edited.');
        }

        $validated = $request->validate([
            'branch_id' => 'required|exists:branches,id',
            'date' => 'required|date',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.item_id' => 'required|exists:ingredient_items,id',
            'items.*.unit_id' => 'required|exists:ingredient_units,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
        ]);

        $purchaseRequest->update([
            'branch_id' => $validated['branch_id'],
            'date' => $validated['date'],
            'notes' => $validated['notes'],
        ]);

        $purchaseRequest->items()->delete();
        foreach ($validated['items'] as $item) {
            $purchaseRequest->items()->create([
                'item_id' => $item['item_id'],
                'quantity' => $item['quantity'],
                'unit_id' => $item['unit_id']
            ]);
        }

        return back()->with('success', 'Purchase Request updated successfully.');
    }

    public function updateStatus(Request $request, PurchaseRequest $purchaseRequest)
    {
        $validated = $request->validate([
            'status' => 'required|in:draft,pending_approval,approved,rejected'
        ]);

        $purchaseRequest->update(['status' => $validated['status']]);

        return back()->with('success', 'Purchase Request status updated.');
    }

    public function destroy(PurchaseRequest $purchaseRequest)
    {
        $purchaseRequest->delete();
        return back();
    }
}
