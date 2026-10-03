<?php
declare(strict_types=1);

namespace App\Services;

use App\Enums\TicketStatus;
use App\Repositories\TicketRepository;

/** ตาม Sequence 6.3 */
final class DashboardService
{
    public function __construct(private TicketRepository $tickets) {}

    public function getStatistics(): array
    {
        $raw = $this->tickets->countByStatus();
        $byStatus = [];
        foreach (TicketStatus::cases() as $s) {
            $byStatus[$s->value] = (int) ($raw[$s->value] ?? 0);
        }
        return [
            'byStatus'           => $byStatus,
            'total'              => array_sum($byStatus),
            'avgResolutionHours' => $this->tickets->avgResolutionTime(),
            'avgRating'          => $this->tickets->averageRating(),
            'topTechnicians'     => $this->tickets->topTechnicians(5),
            'monthlyRatings'     => $this->tickets->monthlyRatings(6),
        ];
    }
}
