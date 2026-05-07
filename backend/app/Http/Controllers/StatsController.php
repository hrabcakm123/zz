<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\AnimationUsage;

class StatsController extends Controller
{
    public function animationStats(Request $request)
    {
        $intervalMinutes = (int) env('STATS_INTERVAL_MINUTES', 10);

        $types = ['inverted_pendulum', 'ball_beam'];
        $result = [];

        foreach ($types as $type) {
            $usages = AnimationUsage::where('animation_type', $type)
                ->orderBy('created_at')
                ->get();

            $uniqueCount = 0;
            $lastUseByToken = [];

            foreach ($usages as $use) {
                $token = $use->token;
                $time = $use->created_at;
                if (!isset($lastUseByToken[$token]) || 
                    $time->diffInMinutes($lastUseByToken[$token]) > $intervalMinutes) {
                    $uniqueCount++;
                }
                $lastUseByToken[$token] = $time;
            }

            $result[$type] = [
                'total_unique_uses' => $uniqueCount,
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