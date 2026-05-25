<?php

namespace App\Jobs;

use App\Enums\CreditAction;
use App\Models\QrCode;
use App\Models\ShortLink;
use App\Models\User;
use App\Services\CreditService;
use App\Services\QrCodeGeneratorService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class BulkGenerateQrCodesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 600;

    public function __construct(
        public int $userId,
        public array $items,
        public array $designOptions = [],
    ) {}

    public function handle(): void
    {
        $user = User::findOrFail($this->userId);
        $creditService = app(CreditService::class);
        $generator = app(QrCodeGeneratorService::class);

        $batchDir = 'bulk/' . now()->format('Y-m-d') . '/' . uniqid();
        Storage::disk('public')->makeDirectory($batchDir);

        foreach ($this->items as $item) {
            $creditCost = CreditAction::BulkGeneration->cost();
            if ($item['is_dynamic'] ?? false) {
                $creditCost += CreditAction::EditDynamicQr->cost();
            }

            if (! $creditService->canAfford($user, CreditAction::BulkGeneration, $creditCost)) {
                break;
            }

            $qrCode = QrCode::create([
                'user_id' => $user->id,
                'team_id' => $user->current_team_id,
                'name' => $item['name'],
                'type' => $item['type'] ?? 'url',
                'is_dynamic' => $item['is_dynamic'] ?? true,
                'content_data' => $item['content_data'],
            ]);

            $qrCode->design()->create(array_merge([
                'fg_color' => '#000000',
                'bg_color' => '#FFFFFF',
                'dot_style' => 'square',
            ], $this->designOptions));

            if ($qrCode->is_dynamic) {
                ShortLink::create([
                    'qr_code_id' => $qrCode->id,
                    'slug' => ShortLink::generateSlug(),
                    'destination_url' => $item['content_data']['url'] ?? '',
                    'is_active' => true,
                ]);
            }

            $png = $generator->generatePng($qrCode, 800);
            $filename = str($item['name'])->slug() . '.png';
            Storage::disk('public')->put("{$batchDir}/{$filename}", $png);

            $creditService->deduct($user, CreditAction::BulkGeneration, $creditCost, "Bulk: {$item['name']}");
        }
    }
}
