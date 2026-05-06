<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CasLog;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

class CasController extends Controller
{
    public function executeCommand(Request $request)
    {
        $command = $request->input('command');
        if (empty($command)) {
            return response()->json(['error' => 'No command provided.'], 400);
        }

        $process = new Process(['octave-cli', '--eval', $command]);
        $process->setTimeout(10);

        try {
            $process->mustRun();
            $output = $process->getOutput();

            CasLog::create([
                'timestamp' => now(),
                'command'   => $command,
                'status'    => 'success',
                'error'     => null,
            ]);

            $delayCoefficient = (float) env('DELAY_COEFFICIENT', 0);
            if ($delayCoefficient > 0) {
                usleep((int)($delayCoefficient * 1000000));
            }

            return response()->json(['output' => trim($output)]);
        } catch (ProcessFailedException $e) {
            $errorOutput = $process->getErrorOutput();

            CasLog::create([
                'timestamp' => now(),
                'command'   => $command,
                'status'    => 'error',
                'error'     => $errorOutput,
            ]);

            return response()->json([
                'error'   => 'Octave execution failed',
                'details' => $errorOutput,
            ], 500);
        }
    }
}
