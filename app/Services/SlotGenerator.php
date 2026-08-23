<?php

namespace App\Services;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\AvailabilitySlot;
use App\Models\LawyerProfile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Generates concrete bookable time slots from a lawyer's recurring
 * weekly availability, minus past times and already-booked appointments.
 */
class SlotGenerator
{
    public function __construct(
        private readonly int $daysAhead = 14,
        private readonly int $stepMinutes = 30,
        private readonly int $minimumLeadMinutes = 60,
    ) {}

    /**
     * @return Collection<int, array{datetime: Carbon, date: string, time: string}>
     */
    public function upcomingFor(LawyerProfile $lawyer): Collection
    {
        $slots = AvailabilitySlot::query()
            ->where('lawyer_profile_id', $lawyer->id)
            ->where('is_active', true)
            ->get();

        if ($slots->isEmpty()) {
            return collect();
        }

        $booked = Appointment::query()
            ->where('lawyer_profile_id', $lawyer->id)
            ->whereIn('status', [AppointmentStatus::Scheduled])
            ->whereBetween('scheduled_at', [now(), now()->addDays($this->daysAhead)])
            ->get(['scheduled_at', 'duration_minutes']);

        $now = now()->addMinutes($this->minimumLeadMinutes);
        $generated = collect();

        for ($dayOffset = 0; $dayOffset <= $this->daysAhead; $dayOffset++) {
            $day = now()->copy()->startOfDay()->addDays($dayOffset);

            foreach ($slots->where('weekday', $day->dayOfWeek) as $slot) {
                $cursor = $day->copy()->setTimeFromTimeString($slot->start_time.':00');
                $end = $day->copy()->setTimeFromTimeString($slot->end_time.':00');

                while ($cursor->lt($end)) {
                    if ($cursor->gte($now) && ! $this->overlapsAny($cursor, $booked)) {
                        $generated->push([
                            'datetime' => $cursor->copy(),
                            'date' => $cursor->toDateString(),
                            'time' => $cursor->format('H:i'),
                        ]);
                    }

                    $cursor->addMinutes($this->stepMinutes);
                }
            }
        }

        return $generated->sortBy('datetime')->values();
    }

    private function overlapsAny(Carbon $candidate, Collection $booked): bool
    {
        $start = $candidate->copy();
        $end = $candidate->copy()->addMinutes(30); // slot occupies one step

        return $booked->contains(function (Appointment $appointment) use ($start, $end) {
            $bStart = Carbon::instance($appointment->scheduled_at);
            $bEnd = $bStart->copy()->addMinutes($appointment->duration_minutes);

            return $start < $bEnd && $end > $bStart;
        });
    }
}
