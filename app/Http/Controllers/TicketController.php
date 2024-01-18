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

    public function showUserTickets(Ticket $ticket)
    {
        try {
            $tickets = Ticket::where('user_id', $ticket->user_id)
                ->orderBy('created_at', 'asc')
                ->get();
            return response()->json($tickets);
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
            $this->newTicket($request);
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
            $ticket = new Ticket();
            $ticket->user_id = $request->user_id;
            $ticket->clock_id = $request->type == 'create' ? null : $request->clock_id;
            $ticket->status = 'pending';
            $ticket->type = $request->type;
            $ticket->justification = $request->justification;
            $ticket->requested_data = $request->type == 'delete' ? null : $request->requested_data;
            $ticket->save();
            return response()->json(['message' => 'Ticket criado com sucesso'], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['message' => 'Invalid input'], 400);
        } catch (\Exception $e) {
            Log::error($e);
            return response()->json(['message' => 'Ocorreu um erro'], 500);
        }
    }

    private function approveTicket(Ticket $ticket)
    {
        try {
            $clockController = new ClockController();
            switch ($ticket->type) {
                case 'create':
                    $clockController->insertClockEntry($ticket->requested_data);
                    break;
                case 'update':
                    $clockController->updateClockEntry($ticket->requested_data);
                    break;
                case 'delete':
                    $clockController->deleteClockEntry($ticket->clock_id);
                    break;
                $ticket->status = 'approved';
                $ticket->save();    
            }
            return response()->json(['message' => 'Ticket aprovado com sucesso'], 200);
        } catch (\Exception $e) {
            Log::error($e);
            return response()->json(['message' => 'Ocorreu um erro'], 500);
        }
    }

    private function denyTicket(Ticket $ticket)
    {
        try {
            $ticket->status = 'rejected';
            $ticket->save();
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
        ];

        switch ($type) {
            case 'update':
                $rules['clock_id'] = 'required';
                $rules['requested_data'] = 'required';
                break;
            case 'create':
                $rules['clock_id'] = 'nullable';
                $rules['requested_data'] = 'required';
                break;
            case 'delete':
                $rules['clock_id'] = 'required';
                $rules['requested_data'] = 'nullable';
                break;
            default:
                throw new \InvalidArgumentException('Tipo de ticket inválido');
        }

        return $rules;
    }

    private function handlerTicketValidationRules($request)
    {
        $rules = [
            'action' => 'required|in:approve,deny',
            'ticket_id' => 'required',
        ];

        return $rules;
    }
}
