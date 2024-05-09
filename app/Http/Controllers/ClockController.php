<?php

namespace App\Http\Controllers;

use App\Http\Requests\ClockReportRequest;
use App\Http\Requests\DeleteClockEntryRequest;
use App\Http\Requests\InsertClockEntryRequest;
use App\Http\Requests\SetDayOffRequest;
use App\Http\Requests\UpdateClockEntryRequest;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use App\Services\ClockService;

/*
/   No começo, só eu e Deus sabiamos como funcionava esse código. Agora, só Deus sabe.
*/

class ClockController extends Controller
{
    private ClockService $clockService;

    public function __construct(ClockService $clockService)
    {
        $this->clockService = $clockService;
    }

    // MAIN CLOCK LOGIC

    public function registerClock(Request $request)
    {
        $user = $request->user();
        if ( ! $user) {
            return response()->json(['message' => 'Usuário não encontrado'], 404);
        }

        $id = $user->id;

        try {
            $timestamp = $this->clockService->registerClock($id);
            return response()->json(['message' => 'Entrada registrada com sucesso em ' . $timestamp]);
        } catch (\Exception $e) {
            Log::error($e);
            return response()->json(['message' => 'Ocorreu um erro'], 500);
        }
    }

    public function getClockReport(ClockReportRequest $request)
    {
        try {
            $report = $this->clockService->getClockReport($request->all());
            return response()->json($report);
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Usuário não encontrado'], 404);
        } catch (\Exception $e) {
            Log::error($e);
            return response()->json(['message' => 'Ocorreu um erro'], 500);
        }
    }

    public function setDayOffForDate(SetDayOffRequest $request)
    {
        try {
            $message = $this->clockService->setDayOffForDate($request->all());
            return response()->json(['message' => $message]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['message' => 'Entrada inválida'], 400);
        } catch (\Exception $e) {
            Log::error($e);
            return response()->json(['message' => 'Ocorreu um erro'], 500);
        }
    }

    //CLOCK CRUD

    public function getAllUserClockEntries(int $id)
    {
        try {
            $clockEvents = $this->clockService->getAllUserClockEntries($id);
            return response()->json($clockEvents);
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Usuário não encontrado'], 404);
        } catch (\Exception $e) {
            Log::error($e);
            return response()->json(['message' => 'Erro ao buscar as entradas'], 500);
        }
    }

    public function getDeletedClockEntries()
    {
        try {
            $clockEvents = $this->clockService->getDeletedEntries();
            return response()->json($clockEvents);
        } catch (\Exception $e) {
            Log::error($e);
            return response()->json(['message' => 'Erro ao buscar as entradas'], 500);
        }
    }

    public function deleteClockEntry(DeleteClockEntryRequest $request)
    {
        try {
            $this->clockService->deleteClockEntry($request['id'], $request['justification']);
            return response()->json(['message' => 'Entrada deletada com sucesso']);
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Entrada não encontrada'], 404);
        } catch (\Exception $e) {
            Log::error($e);
            return response()->json(['message' => 'Erro ao deletar a entrada'], 500);
        }
    }

    public function insertClockEntry(InsertClockEntryRequest $request)
    {
        try {
            $this->clockService->insertClockEntry($request->all());
            return response()->json(['message' => 'Entrada inserida com sucesso'], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Usuário não encontrado'], 404);
        } catch (\Exception $e) {
            Log::error($e);
            return response()->json(['message' => 'Erro ao inserir a entrada'], 500);
        }
    }

    public function updateClockEntry(UpdateClockEntryRequest $request)
    {
        try {
            $this->clockService->updateClockEntry($request->all());
            return response()->json(['message' => 'Entrada alterada com sucesso'], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Entrada não encontrada'], 404);
        } catch (\Exception $e) {
            Log::error($e);
            return response()->json(['message' => 'Erro ao atualizar a entrada'], 500);
        }
    }

    public function deleteAllClockEntries()
    {
        try {
            $message = $this->clockService->deleteAllClockEntries();
            return response()->json(['message' => $message]);
        } catch (\Exception $e) {
            Log::error($e);
            return response()->json(['message' => 'Erro ao deletar as entradas'], 500);
        }
    }
}
