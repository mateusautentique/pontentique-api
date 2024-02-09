<?php

namespace App\Http\Controllers;

use App\Services\LogRegistryService;

class LogController extends Controller
{
    private LogRegistryService $logRegistryService;

    public function __construct(LogRegistryService $logRegistryService)
    {
        $this->logRegistryService = $logRegistryService;
    }

    public function getSystemLogs()
    {
        try {
            return $this->logRegistryService->getLogs();
        } catch (\Exception $e) {
            return response()->json(['message' => 'Ocorreu um erro'], 500);
        }
    }
}
