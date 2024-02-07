<?php

namespace App\Http\Controllers;

use App\Services\AFDRegistryService;

class AFDController extends Controller
{
    private AFDRegistryService $afdRegistryService;

    public function __construct(AFDRegistryService $afdRegistryService)
    {
        $this->afdRegistryService = $afdRegistryService;
    }

    public function getAFD()
    {
        try {
            return $this->afdRegistryService->getAFD();
        } catch (\Exception $e) {
            return response()->json(['message' => 'Ocorreu um erro'], 500);
        }
    }
}
