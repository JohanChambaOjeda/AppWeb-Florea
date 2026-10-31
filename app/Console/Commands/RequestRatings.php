<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Models\Deal;
use App\Notifications\RatingRequested;
use App\Support\Notifier;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Pide la calificación de los tratos cerrados.
 *
 * Al día siguiente del trato, y otra vez a los 3 días si no contestó. Dos
 * veces y basta: insistir más molesta y no mejora la respuesta.
 *
 * Corre una vez al día desde el scheduler.
 */
class RequestRatings extends Command
{
    protected $signature = 'ratings:request';

    protected $description = 'Pide a las dos partes que califiquen sus tratos recientes';

    /** Pasada esta edad, ya no se insiste: el trato es historia. */
    private const GIVE_UP_DAYS = 14;

    public function handle(): int
    {
        $sent = 0;

        Deal::query()
            ->where('closed_at', '<=', now()->subDay())
            ->where('closed_at', '>=', now()->subDays(self::GIVE_UP_DAYS))
            ->with(['buyerCompany.users', 'sellerCompany.users', 'offer', 'ratings'])
            ->chunkById(100, function ($deals) use (&$sent) {
                foreach ($deals as $deal) {
                    foreach ([$deal->buyerCompany, $deal->sellerCompany] as $company) {
                        $sent += $this->remind($deal, $company) ? 1 : 0;
                    }
                }
            });

        $this->info("Recordatorios enviados: {$sent}.");

        return self::SUCCESS;
    }

    /**
     * Devuelve true si se mandó el aviso.
     */
    private function remind(Deal $deal, Company $company): bool
    {
        if ($deal->ratedBy($company)) {
            return false;
        }

        $reminder = DB::table('rating_reminders')
            ->where('deal_id', $deal->id)
            ->where('company_id', $company->id)
            ->first();

        $times = (int) ($reminder->sent_count ?? 0);

        // El primero al día siguiente; el segundo a los 3 días.
        $due = match ($times) {
            0 => $deal->closed_at->addDay(),
            1 => $deal->closed_at->addDays(3),
            default => null,
        };

        if ($due === null || $due->isFuture()) {
            return false;
        }

        $other = $deal->otherCompany($company);

        foreach ($company->users as $user) {
            Notifier::send($user, new RatingRequested($deal, $other));
        }

        DB::table('rating_reminders')->updateOrInsert(
            ['deal_id' => $deal->id, 'company_id' => $company->id],
            ['sent_count' => $times + 1, 'last_sent_at' => now()],
        );

        return true;
    }
}
