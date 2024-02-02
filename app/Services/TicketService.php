<?php

namespace App\Services;

use App\Models\Ticket;
use Illuminate\Database\Eloquent\Collection;

class TicketService
{
    public function showAllActiveTickets(): Collection
    {
        return Ticket::where('status', 'pending')
            ->orderBy('created_at', 'asc')
            ->get();
    }

    public function showAllTickets(): Collection
    {
        return Ticket::orderBy('created_at', 'asc')
            ->get();
    }

    public function showAllUserTickets(int $user_id): Collection
    {
        return Ticket::where('user_id', $user_id)
            ->orderBy('created_at', 'asc')
            ->get();
    }

    public function createTicket(array $data): string
    {
        if (isset($data['requested_data'])) {
            $data['requested_data'] = json_encode($data['requested_data']);
        }
    
        Ticket::create($data);
        return 'Ticket criado com sucesso';
    }

    public function handleTicket(Ticket $ticket, string $action): string
    {
        switch ($action) {
            case 'approve':
                $ticket->approve();
                return 'Ticket aprovado com sucesso';
            case 'deny':
                $ticket->deny();
                return 'Ticket negado com sucesso';
            default:
                return 'Ação inválida';
        }
    }
}
