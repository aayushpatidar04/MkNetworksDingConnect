<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Country;
use App\Models\Operator;
use App\Services\DingConnectService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Illuminate\Support\Str;


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
        $countries = Country::where('is_active', true)->get(['id', 'name', 'iso_code', 'flag_emoji']);

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
        // Step 1: Map local countries to Ding country IDs
        $this->mapCountryIds($dingService);

        // Step 2: Fetch operators from DingConnect
        $result = $dingService->getCountries();

        if (!$result['success']) {
            return back()->with('error', 'Failed to sync operators: ' . $result['error']);
        }

        $synced = 0;
        $skipped = 0;

        foreach ($result['data'] as $countryData) {
            $dingCountryId = $countryData['CountryID'] ?? null;
            $localCountry = null;

            if ($dingCountryId) {
                $localCountry = Country::where('ding_country_id', (string) $dingCountryId)->first();
            }

            if (!$localCountry) {
                $skipped++;
                continue;
            }

            foreach ($countryData['Operators'] ?? [] as $operatorData) {
                Operator::updateOrCreate(
                    ['ding_operator_id' => (string) $operatorData['OperatorID']],
                    [
                        'name' => $operatorData['Name'],
                        'slug' => strtolower(Str::slug($operatorData['Name'])),
                        'country_id' => $localCountry->id,
                        'is_active' => true,
                    ]
                );
                $synced++;
            }
        }

        $msg = "Synced {$synced} operators";
        if ($skipped > 0) {
            $msg .= " ({$skipped} countries skipped — no local match)";
        }

        return back()->with('success', $msg);
    }

    private function mapCountryIds(DingConnectService $dingService): void
    {
        $result = $dingService->getCountries();

        if (!$result['success']) {
            return;
        }

        foreach ($result['data'] as $countryData) {
            $dingId = $countryData['CountryID'] ?? null;
            $isoCode = $countryData['ISOCode'] ?? $countryData['CountryCode'] ?? null;

            if (!$dingId || !$isoCode) {
                continue;
            }

            Country::where('iso_code', strtoupper($isoCode))
                ->update(['ding_country_id' => (string) $dingId]);
        }
    }
}
