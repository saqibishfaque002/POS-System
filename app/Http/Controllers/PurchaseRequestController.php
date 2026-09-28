<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\PurchaseRequest;
use App\Models\FoodItem;
use App\Models\Branch;
use App\Http\Requests\Trade\PurchaseRequestRequest;
use App\Services\TradeDocumentService;
use Illuminate\Support\Facades\DB;

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

    public function store(PurchaseRequestRequest $request, TradeDocumentService $service)
    {
        $validated = $request->validated();

        $lastDoc = PurchaseRequest::where('branch_id', $validated['branch_id'])->latest('id')->first();
        $sequence = $lastDoc ? intval(explode('-', $lastDoc->document_number)[2] ?? 0) + 1 : 1;
        $docNumber = 'PR-' . $validated['branch_id'] . '-' . str_pad($sequence, 5, '0', STR_PAD_LEFT);

        DB::transaction(function () use ($validated, $docNumber, $service) {
            $pr = PurchaseRequest::create([
                'branch_id' => $validated['branch_id'],
                'document_number' => $docNumber,
                'date' => $validated['date'],
                'notes' => $validated['notes'],
                'total_amount' => 0,
                'created_by' => auth()->id(),
                'status' => 'draft'
            ]);

            $service->savePurchaseRequestItems($pr, $validated['items']);
        });

        return back()->with('success', 'Purchase Request created successfully.');
    }

    public function update(PurchaseRequestRequest $request, PurchaseRequest $purchaseRequest, TradeDocumentService $service)
    {
        if ($purchaseRequest->status !== 'draft') {
            return back()->with('error', 'Only draft purchase requests can be edited.');
        }

        $validated = $request->validated();

        DB::transaction(function () use ($purchaseRequest, $validated, $service) {
            $purchaseRequest->update([
                'branch_id' => $validated['branch_id'],
                'date' => $validated['date'],
                'notes' => $validated['notes'],
            ]);

            $service->savePurchaseRequestItems($purchaseRequest, $validated['items']);
        });

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

    public function print(PurchaseRequest $purchaseRequest)
    {
        $purchaseRequest->load(['branch.company', 'items.item.purchaseUnit', 'items.unit']);
        $settings = \App\Models\Setting::pluck('value', 'key')->toArray();

        return Inertia::render('Trade/PrintDocument', [
            'document' => $purchaseRequest,
            'type' => 'PR',
            'settings' => $settings,
            'company' => \App\Models\Company::first(),
        ]);
    }
}
