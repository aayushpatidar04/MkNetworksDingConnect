<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Country;
use App\Models\Operator;
use App\Services\DingConnectService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;


class OperatorController extends Controller
{
    public function index(Request $request)
    {
        $query = Operator::with('country');

        if ($search = $request->input('search')) {
            $query->where('name', 'like', "%{$search}%");
        }

        if ($countryId = $request->input('country_id')) {
            $query->where('country_id', $countryId);
        }

        $operators = $query->orderBy('display_order')->paginate(50);
        $countries = Country::where('is_active', true)->get(['id', 'name', 'iso_code']);

        return Inertia::render('Admin/Operators/Index', compact('operators', 'countries'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:100|unique:operators',
            'ding_operator_id' => 'required|string|max:100',
            'country_id' => 'required|exists:countries,id',
            'logo_url' => 'nullable|url',
            'display_order' => 'integer|min:0',
        ]);

        Operator::create($validated);

        return back()->with('success', 'Operator added successfully!');
    }

    public function update(Request $request, Operator $operator)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => ['required', 'string', 'max:100', Rule::unique('operators')->ignore($operator->id)],
            'ding_operator_id' => 'required|string|max:100',
            'country_id' => 'required|exists:countries,id',
            'logo_url' => 'nullable|url',
            'display_order' => 'integer|min:0',
            'is_active' => 'boolean',
        ]);

        $operator->update($validated);

        return back()->with('success', 'Operator updated successfully!');
    }

    public function destroy(Operator $operator)
    {
        $operator->delete();

        return back()->with('success', 'Operator deleted successfully!');
    }

    public function syncFromDing(DingConnectService $dingService)
    {
        $result = $dingService->getCountries();

        if (!$result['success']) {
            return back()->with('error', 'Failed to sync operators: ' . $result['error']);
        }

        $synced = 0;
        foreach ($result['data'] as $countryData) {
            foreach ($countryData['Operators'] ?? [] as $operatorData) {
                Operator::updateOrCreate(
                    ['ding_operator_id' => $operatorData['OperatorID']],
                    [
                        'name' => $operatorData['Name'],
                        'slug' => str($operatorData['Name'])->slug(),
                        'country_id' => $countryData['CountryID'], // You'd need to map this
                        'is_active' => true,
                    ]
                );
                $synced++;
            }
        }

        return back()->with('success', "Synced {$synced} operators from DingConnect!");
    }
}
