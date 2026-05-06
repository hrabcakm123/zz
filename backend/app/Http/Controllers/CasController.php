<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CasLog;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Redis;

class CasController extends Controller
{
    public function executeCommand(Request $request)
    {
        $command = $request->input('command');
        if (empty($command)) {
            return response()->json(['error' => 'No command provided.'], 400);
        }

        $sessionToken = $request->cookie('octave_session');
        $isNewSession = false;
        if (!$sessionToken) {
            $sessionToken = (string) Str::uuid();
            $isNewSession = true;
        }

        $redisKey = 'octave_session:' . $sessionToken;
        $workspaceData = Redis::get($redisKey);
        $loadCmd = '';
        $tempFile = '';

        if ($workspaceData) {
            $tempFile = tempnam(sys_get_temp_dir(), 'octave_');
            file_put_contents($tempFile, $workspaceData);
            $loadCmd = "load('" . addslashes($tempFile) . "'); ";
        }

        $octaveCmd = $loadCmd . $command;
        $process = new Process(['octave-cli', '--eval', $octaveCmd]);
        $process->setTimeout(10);

        try {
            $process->mustRun();
            $output = $process->getOutput();

            $saveTempFile = tempnam(sys_get_temp_dir(), 'octave_save_');
            $saveCmd = $octaveCmd . "; save('" . addslashes($saveTempFile) . "');";
            $saveProcess = new Process(['octave-cli', '--eval', $saveCmd]);
            $saveProcess->mustRun();

            if (file_exists($saveTempFile)) {
                Redis::setex($redisKey, (int) env('WORKSPACE_TTL', 7200), file_get_contents($saveTempFile));
                unlink($saveTempFile);
            }

            if ($tempFile && file_exists($tempFile)) unlink($tempFile);

            CasLog::create([
                'timestamp' => now(),
                'command'   => $command,
                'status'    => 'success',
                'error'     => null,
            ]);

            $delay = (float) env('DELAY_COEFFICIENT', 0);
            if ($delay > 0) usleep((int)($delay * 1000000));

            $response = response()->json(['output' => trim($output)]);
        } catch (ProcessFailedException $e) {
            $errorOutput = $process->getErrorOutput();
            if ($tempFile && file_exists($tempFile)) unlink($tempFile);

            CasLog::create([
                'timestamp' => now(),
                'command'   => $command,
                'status'    => 'error',
                'error'     => $errorOutput,
            ]);

            $response = response()->json([
                'error'   => 'Octave execution failed',
                'details' => $errorOutput,
            ], 500);
        }

        $response->cookie('octave_session', $sessionToken, 120);

        return $response;
    }

    public function exportLogs()
    {
        $logs = CasLog::orderBy('timestamp')->get();

        $csvData = "timestamp,command,status,error\n";
        foreach ($logs as $log) {
            $timestamp = $log->timestamp;
            $command   = '"' . str_replace('"', '""', $log->command) . '"';
            $status    = $log->status;
            $error     = '"' . str_replace('"', '""', $log->error ?? '') . '"';

            $csvData .= "{$timestamp},{$command},{$status},{$error}\n";
        }

        $fileName = 'cas_logs_' . now()->format('Y-m-d_His') . '.csv';

        return response($csvData, 200, [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$fileName}\"",
        ]);
    }
}