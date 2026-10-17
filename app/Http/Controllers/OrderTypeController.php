<?php

namespace App\Http\Controllers;

use App\Models\OrderType;
use App\Http\Requests\OrderTypeRequest;
use Inertia\Inertia;

class OrderTypeController extends Controller
{
    public function index()
    {
        $orderTypes = OrderType::orderBy('id')->get();

        return Inertia::render('Masters/OrderTypes/Index', [
            'orderTypes' => $orderTypes,
        ]);
    }

    public function store(OrderTypeRequest $request)
    {
        OrderType::create($request->validated());

        return back()->with('success', 'Order Type created successfully.');
    }

    public function update(OrderTypeRequest $request, OrderType $orderType)
    {
        $orderType->update($request->validated());

        return back()->with('success', 'Order Type updated successfully.');
    }

    public function destroy(OrderType $orderType)
    {
        $inUse = $orderType->posOrders()->exists() || $orderType->recipeIngredients()->exists();

        if ($inUse) {
            return back()->with('error', 'Cannot delete this order type as it is linked to orders or recipes.');
        }

        $orderType->delete();

        return back()->with('success', 'Order Type deleted successfully.');
    }
}
