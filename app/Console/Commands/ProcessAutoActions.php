<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\WorkOrder;
use Illuminate\Console\Command;

class ProcessAutoActions extends Command
{
    protected $signature   = 'wo:autoprocess';
    protected $description = 'Auto-accept forwarded WOs (>1 day) and auto-confirm QA completed WOs (>2 days)';

    public function handle(): int
    {
        $systemUser = User::where('role', 'section_head')->first();
        $systemId   = $systemUser?->id ?? 1;

        // ── Auto-accept: forwarded WOs not acted on within 1 day ────────────
        $forwarded = WorkOrder::whereIn('status', ['forwarded_ga', 'forwarded_qa', 'forwarded_maintenance'])
            ->where('updated_at', '<', now()->subDay())
            ->get();

        foreach ($forwarded as $wo) {
            $wo->update([
                'status'      => 'accepted',
                'accepted_by' => $systemId,
                'accepted_at' => now(),
            ]);
            $dest = strtoupper($wo->destination);
            $wo->addHistory($systemId, 'accepted',
                "Auto-diterima oleh sistem: WO tidak ditindaklanjuti dalam 1×24 jam oleh {$dest}.");
            $this->info("Auto-accepted WO #{$wo->wo_number} → {$dest}");
        }

        // ── Auto-confirm: QA completed WOs not reviewed within 2 days ───────
        $completed = WorkOrder::where('destination', 'qa')
            ->where('status', 'completed')
            ->where('completed_at', '<', now()->subDays(2))
            ->get();

        foreach ($completed as $wo) {
            $score = $wo->calculateScore();
            $wo->update([
                'status'      => 'finished',
                'finished_at' => now(),
                'score'       => $score,
            ]);
            $wo->addHistory($systemId, 'finished',
                "Auto-confirmed oleh sistem: tidak ada respons dari requester dalam 2 hari. Skor: {$score}.");
            $this->info("Auto-confirmed QA WO #{$wo->wo_number} (score: {$score})");
        }

        $this->info('Done. Forwarded: ' . $forwarded->count() . ', Auto-confirmed: ' . $completed->count());

        return Command::SUCCESS;
    }
}
