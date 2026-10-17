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
        Party::create($request->validated());

        return back()->with('success', 'Party created successfully.');
    }

    public function update(PartyRequest $request, Party $party)
    {
        $party->update($request->validated());

        return back()->with('success', 'Party updated successfully.');
    }

    public function destroy(Party $party)
    {
        $inUse = $party->posOrders()->exists();

        if ($inUse) {
            return back()->with('error', 'Cannot delete this party as it is linked to POS orders.');
        }

        $party->delete();

        return back()->with('success', 'Party deleted successfully.');
    }
}
