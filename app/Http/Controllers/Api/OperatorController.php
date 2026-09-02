<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Operator;
use App\Models\Country;
use Illuminate\Http\Request;

class OperatorController extends Controller
{
    public function index(Request $request)
    {
        $operators = Operator::where('is_active', true)
            ->with('country')
            ->when($request->country_id, fn($q, $id) => $q->where('country_id', $id))
            ->orderBy('name')
            ->get(['id', 'name', 'slug', 'logo_url', 'country_id', 'ding_operator_id']);

        return response()->json(['operators' => $operators]);
    }

    public function countries()
    {
        $countries = Country::where('is_active', true)
            ->with(['operators' => fn($q) => $q->where('is_active', true)])
            ->get(['id', 'name', 'iso_code', 'calling_code', 'flag_emoji']);

        return response()->json(['countries' => $countries]);
    }
}
