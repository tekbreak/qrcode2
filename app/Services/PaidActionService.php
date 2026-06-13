<?php

namespace App\Services;

use App\Enums\PaidActionType;
use App\Enums\QrCodeType;
use App\Models\PaidAction;
use App\Models\QrCode;
use App\Models\ShortLink;
use App\Models\User;
use Illuminate\Http\RedirectResponse;

class PaidActionService
{
    public function enterpriseBypass(User $user): bool
    {
        return $user->hasFreeDynamicEdits();
    }

    public function requiresPayment(User $user, QrCode $qrCode, array $pendingData): bool
    {
        if ($this->enterpriseBypass($user)) {
            return false;
        }

        if (! $qrCode->is_dynamic && ! $qrCode->shortLink) {
            return false;
        }

        return $this->detectActionType($qrCode, $pendingData) !== null;
    }

    public function detectActionType(QrCode $qrCode, array $pendingData): ?PaidActionType
    {
        $shortLink = $qrCode->shortLink;

        if (! $shortLink) {
            return null;
        }

        if ($qrCode->type === QrCodeType::Social) {
            $currentNetworks = $this->normalizeNetworks($qrCode->content_data['networks'] ?? []);
            $pendingNetworks = $this->normalizeNetworks($pendingData['content_data']['networks'] ?? []);

            if ($currentNetworks !== $pendingNetworks) {
                return PaidActionType::EditDynamicQr;
            }

            $pendingLinkType = $pendingData['link_type'] ?? 'redirect';
            if ($pendingLinkType !== ($shortLink->link_type ?? 'redirect')) {
                return PaidActionType::EditDynamicQr;
            }
        }

        $destinationUrl = $pendingData['destination_url'] ?? '';
        if ($destinationUrl !== ($shortLink->destination_url ?? '')) {
            return PaidActionType::EditDynamicQr;
        }

        $newPassword = $pendingData['link_password'] ?? '';
        if (filled($newPassword)) {
            return PaidActionType::ChangePassword;
        }

        $expiresAt = $pendingData['expires_at'] ?? null;
        $currentExpires = $shortLink->expires_at?->format('Y-m-d\TH:i');
        if ($expiresAt !== $currentExpires && filled($expiresAt)) {
            return PaidActionType::SetExpiration;
        }

        $maxScans = $pendingData['max_scans'] ?? null;
        if ($maxScans !== $shortLink->max_scans) {
            return PaidActionType::UpdateScanLimit;
        }

        $isActive = $pendingData['is_active'] ?? true;
        if (! $shortLink->is_active && $isActive) {
            return PaidActionType::ReactivateQr;
        }

        return null;
    }

    protected function normalizeNetworks(array $networks): string
    {
        return json_encode(array_values($networks));
    }

    public function createCheckout(User $user, QrCode $qrCode, PaidActionType $actionType, array $pendingData): RedirectResponse
    {
        $paidAction = PaidAction::create([
            'user_id' => $user->id,
            'qr_code_id' => $qrCode->id,
            'action_type' => $actionType->value,
            'status' => 'pending',
            'pending_data' => $pendingData,
            'amount_cents' => config('qrcode.paid_action_price_cents', 100),
        ]);

        $priceId = config('qrcode.paid_action_stripe_price_id');

        if ($priceId && ! str_starts_with($priceId, 'dev_')) {
            $checkout = $user->checkout([$priceId => 1], [
                'success_url' => route('paid-actions.success', $paidAction).'?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url' => route('paid-actions.cancel', $paidAction),
                'metadata' => [
                    'paid_action_id' => $paidAction->id,
                    'qr_code_id' => $qrCode->id,
                    'action_type' => $actionType->value,
                ],
            ]);

            $session = $checkout->asStripeCheckoutSession();
            $paidAction->update(['stripe_checkout_session_id' => $session->id]);

            return new RedirectResponse($session->url);
        }

        $this->applyAction($paidAction);
        $paidAction->update([
            'status' => 'completed',
            'paid_at' => now(),
        ]);

        session()->flash('status', __('qr.updated'));

        return new RedirectResponse(route('qr-codes.index'));
    }

    public function applyAction(PaidAction $paidAction): void
    {
        $data = $paidAction->pending_data;
        $qrCode = $paidAction->qrCode;

        if (! $qrCode) {
            return;
        }

        $qrCode->update([
            'name' => $data['name'] ?? $qrCode->name,
            'type' => $data['type'] ?? $qrCode->type,
            'is_dynamic' => true,
            'content_data' => $data['content_data'] ?? $qrCode->content_data,
        ]);

        if (isset($data['design'])) {
            $qrCode->design()->updateOrCreate(
                ['qr_code_id' => $qrCode->id],
                $data['design']
            );
        }

        $destinationUrl = $data['destination_url'] ?? ($data['content_data']['url'] ?? '');
        $linkType = $data['link_type'] ?? 'redirect';
        $linkPassword = $data['link_password'] ?? '';
        $expiresAt = $data['expires_at'] ?? null;
        $maxScans = $data['max_scans'] ?? null;
        $customSlug = $data['custom_slug'] ?? null;

        if ($qrCode->shortLink) {
            $qrCode->shortLink->update([
                'destination_url' => $destinationUrl,
                'link_type' => $linkType,
                'password_hash' => $linkPassword ? bcrypt($linkPassword) : $qrCode->shortLink->password_hash,
                'expires_at' => $expiresAt ? \Carbon\Carbon::parse($expiresAt) : $qrCode->shortLink->expires_at,
                'max_scans' => $maxScans,
                'is_active' => $data['is_active'] ?? true,
            ]);
        } else {
            $slug = $customSlug ?: ShortLink::generateSlug();

            ShortLink::create([
                'qr_code_id' => $qrCode->id,
                'slug' => $slug,
                'link_type' => $linkType,
                'destination_url' => $destinationUrl,
                'password_hash' => $linkPassword ? bcrypt($linkPassword) : null,
                'expires_at' => $expiresAt ? \Carbon\Carbon::parse($expiresAt) : null,
                'max_scans' => $maxScans,
                'is_active' => true,
            ]);
        }

        $paidAction->update([
            'status' => 'completed',
            'paid_at' => now(),
        ]);
    }
}
