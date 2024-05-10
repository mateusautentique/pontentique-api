<?php

namespace App\Http\Controllers;

use App\Services\AFDService;
use Illuminate\Support\Facades\Log;

class AFDController extends Controller
{
    private AFDService $AFDService;

    public function __construct(AFDService $AFDService)
    {
        $this->AFDService = $AFDService;
    }

    public function getAFDT()
    {
        try {
            return $this->AFDService->generateAFDT();
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return response()->json(['message' => 'Ocorreu um erro'], 500);
        }
    }

    public function getACJEF()
    {
        try {
            return $this->AFDService->generateACJEF();
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return response()->json(['message' => 'Ocorreu um erro'], 500);
        }
    }
}
