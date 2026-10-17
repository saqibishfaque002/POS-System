<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\City;
use App\Http\Requests\CustomerRequest;
use Inertia\Inertia;

class CustomerController extends Controller
{
    public function index()
    {
        $customers = Customer::with('city.province')->latest('id')->get();
        $cities = City::with('province')->orderBy('name')->get();

        return Inertia::render('Masters/Customers/Index', [
            'customers' => $customers,
            'cities' => $cities,
        ]);
    }

    public function store(CustomerRequest $request)
    {
        Customer::create($request->validated());

        return back()->with('success', 'Customer created successfully.');
    }

    public function update(CustomerRequest $request, Customer $customer)
    {
        $customer->update($request->validated());

        return back()->with('success', 'Customer updated successfully.');
    }

    public function destroy(Customer $customer)
    {
        $customer->delete();

        return back()->with('success', 'Customer deleted successfully.');
    }
}
