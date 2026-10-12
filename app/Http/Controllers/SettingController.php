<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Models\Country;
use App\Models\Province;
use App\Models\City;
use App\Models\Company;
use App\Models\Branch;
use App\Models\Floor;
use App\Models\Table;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class SettingController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $isAdmin = in_array($user->role, ['admin', 'manager']);
        $branchIds = $isAdmin ? [] : $user->branches()->pluck('branches.id')->toArray();

        return Inertia::render('Settings/Index', [
            'countries' => Country::orderBy('name')->get(),
            'provinces' => Province::with('country')->orderBy('name')->get(),
            'cities' => City::with('province.country')->orderBy('name')->get(),
            'companies' => Company::orderBy('name')->get(),
            'branches' => $isAdmin 
                ? Branch::with(['company', 'city.province.country', 'paymentTypes'])->orderBy('name')->get()
                : Branch::with(['company', 'city.province.country', 'paymentTypes'])->whereIn('id', $branchIds)->orderBy('name')->get(),
            'floors' => $isAdmin
                ? Floor::with('branch.company')->orderBy('name')->get()
                : Floor::with('branch.company')->whereIn('branch_id', $branchIds)->orderBy('name')->get(),
            'tables' => $isAdmin
                ? Table::with('floor.branch')->orderBy('name')->get()
                : Table::with('floor.branch')->whereHas('floor', function($q) use ($branchIds) {
                    $q->whereIn('branch_id', $branchIds);
                })->orderBy('name')->get(),
            'settings' => Setting::pluck('value', 'key')->toArray(),
            'paymentTypes' => \App\Models\PaymentType::orderBy('name')->get(),
        ]);
    }

    public function updateGroup(Request $request)
    {
        $group = $request->input('group');
        $data = $request->except(['_token', 'group', '_method']);

        foreach ($data as $key => $value) {
            if (in_array($key, ['logo', 'favicon'])) {
                if ($request->hasFile($key)) {
                    $file = $request->file($key);
                    
                    $existingSetting = Setting::where('key', $key)->first();
                    if ($existingSetting && $existingSetting->value) {
                        if (Storage::disk('public')->exists($existingSetting->value)) {
                            Storage::disk('public')->delete($existingSetting->value);
                        }
                    }

                    $path = $file->store('settings', 'public');
                    
                    Setting::updateOrCreate(
                        ['key' => $key],
                        ['group' => $group, 'value' => $path]
                    );
                }
                // Skip updating logo/favicon if no new file is uploaded
                continue;
            }

            // For all other fields (store_name, tax_rate, etc.)
            Setting::updateOrCreate(
                ['key' => $key],
                ['group' => $group, 'value' => is_array($value) ? json_encode($value) : $value]
            );
        }

        return redirect()->back()->with('success', 'Settings updated successfully.');
    }
}
