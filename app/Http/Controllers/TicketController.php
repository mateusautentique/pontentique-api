<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Services\TicketService;
use App\Http\Requests\CreateTicketRequest;
use App\Http\Requests\HandleTicketRequest;
use App\Http\Requests\RequestedDataRequest;

class TicketController extends Controller
{
    private TicketService $ticketService;

    public function __construct(TicketService $ticketService)
    {
        $this->ticketService = $ticketService;
    }

    public function showAllActiveTickets()
    {
        try {
            $tickets = $this->ticketService->showAllActiveTickets();
            return response()->json($tickets);
        } catch (\Exception $e) {
            Log::error($e);
            return response()->json(['message' => 'Ocorreu um erro'], 500);
        }
    }

    public function showAllTickets()
    {
        try {
            $tickets = $this->ticketService->showAllTickets();
            return response()->json($tickets);
        } catch (\Exception $e) {
            Log::error($e);
            return response()->json(['message' => 'Ocorreu um erro'], 500);
        }
    }

    public function showAllUserTickets(int $user_id)
    {
        try {
            $tickets = $this->ticketService->showAllUserTickets($user_id);
            return response()->json($tickets);
        } catch (\Exception $e) {
            Log::error($e);
            return response()->json(['message' => 'Ocorreu um erro'], 500);
        }
    }

    public function createTicket(CreateTicketRequest $request)
    {
        try {
            $message = $this->ticketService->createTicket($request->validated());
            return response()->json(['message' => $message], 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['errors' => 'Input inválido'], 422);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        } catch (\Exception $e) {
            Log::error($e);
            return response()->json(['message' => 'Ocorreu um erro'], 500);
        }
    }

    public function handleTicket(HandleTicketRequest $request)
    {
        try {
            $ticket = Ticket::find($request->ticket_id);
            if (!$ticket) {
                return response()->json(['message' => 'Ticket não encontrado'], 404);
            }
            if ($ticket->status !== 'pending') {
                return response()->json(['message' => 'Esse ticket já foi processado'], 400);
            }
            

            $action = $request->action;
            $message = $this->ticketService->handleTicket($ticket, $action);
            
            return response()->json(['message' => $message], 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['errors' => 'Input inválido'], 422);
        } catch (\Exception $e) {
            Log::error($e);
            return response()->json(['message' => 'Ocorreu um erro'], 500);
        }
    }

    //DEBUG
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
