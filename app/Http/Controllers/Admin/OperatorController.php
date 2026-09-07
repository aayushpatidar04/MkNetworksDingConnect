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
use Illuminate\Support\Facades\Log;


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
        // Only sync UK (GB) and India (IN)
        $synced = 0;
        $failedCountries = [];

        $countryCodes = Country::whereIn('iso_code', ['GB', 'IN'])->where('is_active', true)->pluck('iso_code')->filter()->values();


        foreach ($countryCodes as $isoCode) {
            $operatorResult = $dingService->getProviders($isoCode);

            if (!$operatorResult['success']) {
                $failedCountries[] = $isoCode;
                continue;
            }

            foreach ($operatorResult['data'] as $operatorData) {
                $operatorId = $operatorData['OperatorID'] ?? $operatorData['Id'] ?? null;

                if (!$operatorId) {
                    continue;
                }

                $localCountry = Country::where('iso_code', $isoCode)->first();
                if (!$localCountry) {
                    continue;
                }

                Operator::updateOrCreate(
                    ['ding_operator_id' => (string) $operatorId],
                    [
                        'name' => $operatorData['Name'] ?? 'Unknown',
                        'slug' => strtolower(Str::slug($operatorData['Name'] ?? 'unknown')),
                        'country_id' => $localCountry->id,
                        'is_active' => true,
                    ]
                );
                $synced++;
            }
        }

        $msg = "Synced {$synced} operators from DingConnect";
        if (count($failedCountries) > 0) {
            $msg .= " (failed for: " . implode(', ', $failedCountries) . ")";
        }

        return back()->with('success', $msg);
    }

    /**
     * Helper: Convert ISO country code to flag emoji
     */
    private function getFlagEmoji(string $isoCode): string
    {
        $offset = ord('A');
        $emoji = '';
        $chars = str_split($isoCode);
        foreach ($chars as $char) {
            $emoji .= mb_chr(ord($char) - $offset + 0x1F1E6);
        }
        return $emoji;
    }
}
