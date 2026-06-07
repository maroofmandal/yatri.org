<?php

namespace App\Http\Controllers;

class PricingController extends Controller
{
    public function index()
    {
        $plans = [
            [
                'name'     => 'Explorer',
                'price'    => 0,
                'period'   => 'free',
                'tagline'  => 'Plan your first trips',
                'features' => ['3 AI plans / month', 'Grounded live data', 'Shareable links', 'Public profile'],
                'cta'      => 'Start free',
                'featured' => false,
            ],
            [
                'name'     => 'Adventurer',
                'price'    => 4.99,
                'period'   => '/mo',
                'tagline'  => 'For frequent travelers',
                'features' => ['Unlimited AI plans', 'AI chat edits', 'Offline itineraries', 'Price-drop alerts', 'No ads'],
                'cta'      => 'Go Adventurer',
                'featured' => true,
            ],
            [
                'name'     => 'Voyager',
                'price'    => 9.99,
                'period'   => '/mo',
                'tagline'  => 'For trip organizers',
                'features' => ['Everything in Adventurer', 'Group trips & collaboration', 'Split-expense tracker', 'Early deal access', 'Priority AI'],
                'cta'      => 'Go Voyager',
                'featured' => false,
            ],
            [
                'name'     => 'Legend',
                'price'    => 19.99,
                'period'   => '/mo',
                'tagline'  => 'For creators & pros',
                'features' => ['Everything in Voyager', 'Creator monetization', 'Verified badge', 'API access', 'Concierge support'],
                'cta'      => 'Go Legend',
                'featured' => false,
            ],
        ];

        return view('pricing', compact('plans'));
    }
}
