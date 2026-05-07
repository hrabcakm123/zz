<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

class AnimationController extends Controller
{
    public function pendulum(Request $request)
    {
        $params = [
            'M'            => $request->input('M', 0.5),
            'm'            => $request->input('m', 0.2),
            'b'            => $request->input('b', 0.1),
            'I'            => $request->input('I', 0.006),
            'g'            => $request->input('g', 9.8),
            'l'            => $request->input('l', 0.3),
            'initAngle'    => $request->input('initialAngle', 0.3),
            'initPosition' => $request->input('initialPosition', 0),
            'ref1'         => $request->input('reference1', 0.2),
            'ref2'         => $request->input('reference2', 0.5),
            'T'            => $request->input('duration', 10),
            'dt'           => $request->input('dt', 0.05),
        ];

        $template = file_get_contents(base_path('octave-scripts/pendulum_template.m'));
        $script = $template;
        foreach ($params as $key => $value) {
            $script = str_replace('{{' . $key . '}}', $value, $script);
        }

        $tempFile = tempnam(sys_get_temp_dir(), 'pendulum_');
        file_put_contents($tempFile, $script);

        $process = new Process(['octave-cli', $tempFile]);
        $process->setTimeout(30);
        try {
            $process->mustRun();
            $csvOutput = $process->getOutput();
        } catch (ProcessFailedException $e) {
            if (file_exists($tempFile)) unlink($tempFile);
            return response()->json([
                'error'   => 'Octave simulation failed',
                'details' => $process->getErrorOutput(),
            ], 500);
        }
        unlink($tempFile);

        $lines = array_filter(explode("\n", trim($csvOutput)));
        if (count($lines) < 2) {
            return response()->json(['error' => 'No simulation data produced.'], 500);
        }
        $header = str_getcsv(array_shift($lines));
        $frames = [];
        foreach ($lines as $line) {
            $data = str_getcsv($line);
            if (count($data) === count($header)) {
                $frames[] = array_combine($header, $data);
            }
        }

        if (empty($frames)) {
            return response()->json(['error' => 'No valid frames parsed.'], 500);
        }

        $delaySeconds = (float) env('SIMULATION_DELAY_SECONDS', 1);
        if ($delaySeconds > 0) {
            sleep((int) $delaySeconds);
        }

        return response()->json([
            'type'       => 'inverted_pendulum',
            'parameters' => $params,
            'frames'     => $frames,
        ]);
    }
}