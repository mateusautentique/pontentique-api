<?php

namespace App\Http\Controllers;

use App\Services\AFDService;
use Illuminate\Http\Request;

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
            return response()->json(['message' => 'Ocorreu um erro'], 500);
        }
    }
}
