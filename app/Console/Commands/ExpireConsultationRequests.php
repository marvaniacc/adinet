<?php

namespace App\Console\Commands;

use App\Enums\ConsultationRequestStatus;
use App\Models\ConsultationRequest;
use Illuminate\Console\Command;

class ExpireConsultationRequests extends Command
{
    protected $signature = 'consultation:expire {--days=7 : Age in days after which unanswered pending requests expire}';

    protected $description = 'Expire consultation requests that were never answered by the lawyer';

    public function handle(): int
    {
        $days = max(1, (int) $this->option('days'));

        $expired = ConsultationRequest::query()
            ->where('status', ConsultationRequestStatus::Pending)
            ->where('created_at', '<', now()->subDays($days))
            ->update(['status' => ConsultationRequestStatus::Expired->value]);

        $this->info("Expired {$expired} pending consultation request(s) older than {$days} day(s).");

        return self::SUCCESS;
    }
}
