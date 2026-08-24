<?php

namespace App\Console\Commands;

use App\Contracts\SmsProvider;
use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Support\PersianDate;
use Illuminate\Console\Command;

class SendAppointmentReminders extends Command
{
    protected $signature = 'adinet:send-reminders';

    protected $description = 'Send SMS reminders for appointments scheduled ~24 hours from now';

    public function handle(SmsProvider $sms): int
    {
        $sent = 0;

        Appointment::query()
            ->where('status', AppointmentStatus::Scheduled)
            ->where('reminder_sent', false)
            ->whereBetween('scheduled_at', [now()->addHours(23), now()->addHours(25)])
            ->with(['client:id,mobile,first_name', 'lawyerProfile.user:id,mobile', 'lawyerProfile:id,display_name'])
            ->chunk(50, function ($appointments) use ($sms, &$sent) {
                foreach ($appointments as $appointment) {
                    try {
                        $when = PersianDate::format($appointment->scheduled_at, withTime: true);
                        $lawyer = $appointment->lawyerProfile->display_name;
                        $clientName = $appointment->client->fullName();

                        // Remind the client
                        $sms->send(
                            $appointment->client->mobile,
                            "آدینت - یادآوری: جلسه مشاوره شما با {$lawyer} فردا ساعت {$when} برگزار می‌شود."
                        );

                        // Remind the lawyer
                        if ($appointment->lawyerProfile?->user?->mobile) {
                            $sms->send(
                                $appointment->lawyerProfile->user->mobile,
                                "آدینت - یادآوری: جلسه مشاوره با {$clientName} فردا ساعت {$when}."
                            );
                        }

                        $appointment->forceFill(['reminder_sent' => true])->save();
                        $sent++;
                    } catch (\Throwable $e) {
                        report($e);
                    }
                }
            });

        $this->info("Sent {$sent} appointment reminder(s).");

        return self::SUCCESS;
    }
}
