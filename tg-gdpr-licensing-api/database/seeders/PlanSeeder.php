<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            [
                'slug'           => 'starter',
                'name'           => 'Starter',
                'description'    => 'For a single WordPress site.',
                'max_sites'      => 1,
                'display_price'  => '$49',
                'display_period' => '/year',
                'features'       => [
                    '1 site license',
                    'Cookie banner + auto-scanner',
                    'Consent records & logs',
                    'Email support',
                ],
                'is_popular'     => false,
                'is_active'      => true,
                'sort_order'     => 10,
            ],
            [
                'slug'           => 'pro',
                'name'           => 'Professional',
                'description'    => 'For growing teams and small agencies.',
                'max_sites'      => 5,
                'display_price'  => '$99',
                'display_period' => '/year',
                'features'       => [
                    'Up to 5 sites',
                    'Everything in Starter',
                    'Google Consent Mode v2',
                    'DSAR workflow',
                    'Geo-targeted banners',
                    'Priority support',
                ],
                'is_popular'     => true,
                'is_active'      => true,
                'sort_order'     => 20,
            ],
            [
                'slug'           => 'agency',
                'name'           => 'Agency',
                'description'    => 'For agencies and multi-brand portfolios.',
                'max_sites'      => 25,
                'display_price'  => '$199',
                'display_period' => '/year',
                'features'       => [
                    'Up to 25 sites',
                    'Everything in Professional',
                    'White-label banner',
                    'Agency multi-tenant view',
                    'Dedicated success manager',
                    'SLA-backed uptime',
                ],
                'is_popular'     => false,
                'is_active'      => true,
                'sort_order'     => 30,
            ],
        ];

        foreach ($plans as $row) {
            Plan::updateOrCreate(['slug' => $row['slug']], $row);
        }
    }
}
