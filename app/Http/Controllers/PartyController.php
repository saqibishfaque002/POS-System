<?php

namespace App\Http\Controllers;

use App\Models\Party;
use App\Models\City;
use App\Http\Requests\PartyRequest;
use Inertia\Inertia;

class PartyController extends Controller
{
    public function index()
    {
        $parties = Party::with('city.province')->latest('id')->get();
        $cities = City::with('province')->orderBy('name')->get();

        return Inertia::render('Masters/Parties/Index', [
            'parties' => $parties,
            'cities' => $cities,
        ]);
    }

    public function store(PartyRequest $request)
    {
        $validated = $request->validated();
        if (empty($validated['party_type'])) {
            $validated['party_type'] = 'delivery_partner';
        }
        $orderTypeIds = $validated['order_type_ids'] ?? [];
        $paymentTypeIds = $validated['payment_type_ids'] ?? [];
        unset($validated['order_type_ids'], $validated['payment_type_ids']);

        $party = Party::create($validated);

        if ($request->has('order_type_ids')) {
            $party->orderTypes()->sync($orderTypeIds);
        }
        if ($request->has('payment_type_ids')) {
            $party->paymentTypes()->sync($paymentTypeIds);
        }

        return back()->with('success', 'Party created successfully.');
    }

    public function update(PartyRequest $request, Party $party)
    {
        $validated = $request->validated();
        if (empty($validated['party_type'])) {
            $validated['party_type'] = 'delivery_partner';
        }
        $orderTypeIds = $validated['order_type_ids'] ?? [];
        $paymentTypeIds = $validated['payment_type_ids'] ?? [];
        unset($validated['order_type_ids'], $validated['payment_type_ids']);

        $party->update($validated);

        if ($request->has('order_type_ids')) {
            $party->orderTypes()->sync($orderTypeIds);
        }
        if ($request->has('payment_type_ids')) {
            $party->paymentTypes()->sync($paymentTypeIds);
        }

        return back()->with('success', 'Party updated successfully.');
    }

    public function destroy(Party $party)
    {
        $inUse = $party->posOrders()->exists();

        if ($inUse) {
            return back()->with('error', 'Cannot delete this party as it is linked to POS orders.');
        }

        $party->orderTypes()->detach();
        $party->paymentTypes()->detach();
        $party->delete();

        return back()->with('success', 'Party deleted successfully.');
    }
}
