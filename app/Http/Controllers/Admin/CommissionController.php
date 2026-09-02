<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CommissionRule;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CommissionController extends Controller
{
    public function index()
    {
        $rules = CommissionRule::orderByDesc('priority')->orderByDesc('created_at')->get();

        return Inertia::render('Admin/Commissions/Index', compact('rules'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'scope' => 'required|in:global,operator,retailer_tier,country,amount_band',
            'operator_id' => 'nullable|exists:operators,id',
            'country_id' => 'nullable|exists:countries,id',
            'retailer_tier' => 'nullable|in:bronze,silver,gold',
            'min_amount' => 'nullable|numeric|min:0',
            'max_amount' => 'nullable|numeric|min:0',
            'commission_type' => 'required|in:percentage,flat',
            'commission_value' => 'required|numeric|min:0',
            'priority' => 'integer|min:0',
            'effective_from' => 'required|date',
            'effective_until' => 'nullable|date|after:effective_from',
        ]);

        CommissionRule::create($validated);

        return back()->with('success', 'Commission rule created successfully!');
    }

    public function update(Request $request, CommissionRule $rule)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'scope' => 'required|in:global,operator,retailer_tier,country,amount_band',
            'operator_id' => 'nullable|exists:operators,id',
            'country_id' => 'nullable|exists:countries,id',
            'retailer_tier' => 'nullable|in:bronze,silver,gold',
            'min_amount' => 'nullable|numeric|min:0',
            'max_amount' => 'nullable|numeric|min:0',
            'commission_type' => 'required|in:percentage,flat',
            'commission_value' => 'required|numeric|min:0',
            'priority' => 'integer|min:0',
            'effective_from' => 'required|date',
            'effective_until' => 'nullable|date|after:effective_from',
            'is_active' => 'boolean',
        ]);

        $rule->update($validated);

        return back()->with('success', 'Commission rule updated successfully!');
    }

    public function destroy(CommissionRule $rule)
    {
        $rule->delete();

        return back()->with('success', 'Commission rule deleted successfully!');
    }
}
