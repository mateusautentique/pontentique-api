<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TicketController extends Controller
{
    public function showAllActiveTickets()
    {
        try {
            $tickets = Ticket::where('status', 'pending')
                ->orderBy('created_at', 'asc')
                ->get();
            return response()->json($tickets);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        } catch (\Exception $e) {
            Log::error($e);
            return response()->json(['message' => 'Ocorreu um erro'], 500);
        }
    }

    public function showAllTickets()
    {
        try {
            $tickets = Ticket::orderBy('created_at', 'asc')
                ->get();
            return response()->json($tickets);
        } catch (\Exception $e) {
            Log::error($e);
            return response()->json(['message' => 'Ocorreu um erro'], 500);
        }
    }

    public function showAllUserTickets(Request $request)
    {
        try {
            $tickets = Ticket::where('user_id', $request->user_id)
                ->orderBy('created_at', 'asc')
                ->get();
            return response()->json($tickets);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        } catch (\Exception $e) {
            Log::error($e);
            return response()->json(['message' => 'Ocorreu um erro'], 500);
        }
    }

    public function createTicket(Request $request)
    {
        try {
            $validationRules = $this->createTicketValidationRules($request->type);
            $request->validate($validationRules);
            return $this->newTicket($request);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        } catch (\Exception $e) {
            Log::error($e);
            return response()->json(['message' => 'Ocorreu um erro'], 500);
        }
    }

    public function handleTicket(Request $request)
    {
        $validationRules = $this->handlerTicketValidationRules($request->action);
        $request->validate($validationRules);
    
        $ticket = Ticket::find($request->ticket_id);
        if (!$ticket) {
            return response()->json(['message' => 'O ticket especificado não foi encontrado'], 404);
        }
    
        switch ($request->action) {
            case 'approve':
                return $this->approveTicket($ticket);
            case 'deny':
                return $this->denyTicket($ticket);
            default:
                return response()->json(['message' => 'Ação inválida'], 400);
        }
    }

    //UTILS
    private function newTicket(Request $request)
    {
        try {
            Ticket::create([
                'user_id' => $request->user_id,
                'clock_event_id' => $request->type == 'create' ? null : $request->clock_event_id,
                'status' => 'pending',
                'type' => $request->type,
                'justification' => $request->justification,
                'requested_data' => $request->type == 'delete' ? null : json_encode($request->requested_data),
            ]);
            return response()->json(['message' => 'Ticket criado com sucesso'], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        } catch (\Exception $e) {
            Log::error($e);
            return response()->json(['message' => 'Ocorreu um erro'], 500);
        }
    }

    private function approveTicket(Ticket $ticket)
    {
        try {
            $ticket->approve();
            return response()->json(['message' => 'Ticket aprovado com sucesso'], 200);
        } catch (\Exception $e) {
            Log::error($e);
            return response()->json(['message' => 'Ocorreu um erro'], 500);
        }
    }

    private function denyTicket(Ticket $ticket)
    {
        try {
            $ticket->deny();
            return response()->json(['message' => 'Ticket negado com sucesso'], 200);
        } catch (\Exception $e) {
            Log::error($e);
            return response()->json(['message' => 'Ocorreu um erro'], 500);
        }
    }

    //DATA VALIDATION
    private function createTicketValidationRules($type)
    {
        $rules = [
            'user_id' => 'required',
            'type' => 'required',
            'justification' => 'required',
            'clock_event_id' => 'required',
            'requested_data' => 'required',
        ];
    
        if ($type === 'create' || $type === 'update') {
            $rules['clock_event_id'] = 'nullable';
            $requestedDataRules = $this->requestedDataValidationRules($type);
            foreach ($requestedDataRules as $key => $rule) {
                $rules["requested_data.$key"] = $rule;
            }
        }
    
        if ($type === 'delete') {
            $rules['requested_data'] = 'nullable';
        }
    
        if (!in_array($type, ['create', 'delete', 'update'])) {
            throw new \InvalidArgumentException('Tipo de ticket inválido');
        }
    
        return $rules;
    }
    
    private function requestedDataValidationRules($type)
    {
        return [
            'user_id' => ['required'],
            'timestamp' => ['required'],
            'justification' => ['required'],
            'day_off' => ['required', 'boolean'],
            'doctor' => ['required', 'boolean'],
        ];
    }

    private function handlerTicketValidationRules($request)
    {
        $rules = [
            'action' => 'required|in:approve,deny',
            'ticket_id' => 'required',
        ];

        return $rules;
    }

    //DEGUB
    public function dropAllTickets()
    {
        try {
            Ticket::truncate();
            return response()->json(['message' => 'Tickets deletados com sucesso'], 200);
        } catch (\Exception $e) {
            Log::error($e);
            return response()->json(['message' => 'Ocorreu um erro'], 500);
        }
    }
}
