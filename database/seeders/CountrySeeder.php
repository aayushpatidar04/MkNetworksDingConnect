<?php

namespace Database\Seeders;

use App\Models\Country;
use Illuminate\Database\Seeder;

class CountrySeeder extends Seeder
{
 public function run(): void
 {
 $countries = [
 ['India', 'IN', 'IND', '+91', 'INR', '🇮🇳'],
 ['United Arab Emirates', 'AE', 'ARE', '+971', 'AED', '🇦🇪'],
 ['United Kingdom', 'GB', 'GBR', '+44', 'GBP', '🇬🇧'],
 ['Singapore', 'SG', 'SGP', '+65', 'SGD', '🇸🇬'],
 ['Australia', 'AU', 'AUS', '+61', 'AUD', '🇦🇺'],
 ['Saudi Arabia', 'SA', 'SAU', '+966', 'SAR', '🇸🇦'],
 ['Canada', 'CA', 'CAN', '+1', 'CAD', '🇨🇦'],
 ['United States', 'US', 'USA', '+1', 'USD', '🇺🇸'],
 ];

 foreach ($countries as [$name, $iso, $iso3, $code, $currency, $flag]) {
 Country::create([
 'name' => $name,
 'iso_code' => $iso,
 'iso_code_3' => $iso3,
 'calling_code' => $code,
 'currency' => $currency,
 'flag_emoji' => $flag,
 'is_active' => true,
 ]);
 }
 }
}
