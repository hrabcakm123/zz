<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\AnimationUsage;

class StatsController extends Controller
{
    public function animationStats(Request $request)
    {
        $types = ['inverted_pendulum', 'ball_beam'];
        $result = [];

        foreach ($types as $type) {
            $usages = AnimationUsage::where('animation_type', $type)
                ->orderBy('created_at', 'desc')
                ->get();

            $result[$type] = [
                'total_unique_uses' => $usages->count(),
                'details'           => $usages->map(function ($u) {
                    return [
                        'timestamp' => $u->created_at->toDateTimeString(),
                        'token'     => substr($u->token, 0, 8) . '...',
                        'city'      => $u->city,
                        'country'   => $u->country,
                    ];
                }),
            ];
        }

        return response()->json($result);
    }
}