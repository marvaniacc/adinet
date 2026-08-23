<?php

namespace App\Console\Commands;

use App\Enums\ConsultationRequestStatus;
use App\Models\ConsultationRequest;
use App\Models\Setting;
use Illuminate\Console\Command;

class ExpireConsultationRequests extends Command
{
    protected $signature = 'consultation:expire {--days= : Age in days after which unanswered pending requests expire (defaults to the request_expiry_days setting)}';

    protected $description = 'Expire consultation requests that were never answered by the lawyer';

    public function handle(): int
    {
        $days = max(1, (int) ($this->option('days') ?: Setting::get('request_expiry_days', '7')));

        $expired = ConsultationRequest::query()
            ->where('status', ConsultationRequestStatus::Pending)
            ->where('created_at', '<', now()->subDays($days))
            ->update(['status' => ConsultationRequestStatus::Expired->value]);

        $this->info("Expired {$expired} pending consultation request(s) older than {$days} day(s).");

        return self::SUCCESS;
    }
}
