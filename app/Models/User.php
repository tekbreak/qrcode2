<?php

namespace App\Models;

use App\Enums\Feature;
use App\Enums\PlanTier;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Cashier\Billable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements MustVerifyEmail
{
    use Billable, HasApiTokens, HasFactory, HasRoles, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'google_id',
        'avatar',
        'locale',
        'is_admin',
        'current_team_id',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_admin' => 'boolean',
        ];
    }

    public function teams(): BelongsToMany
    {
        return $this->belongsToMany(Team::class)->withPivot('role')->withTimestamps();
    }

    public function ownedTeams(): HasMany
    {
        return $this->hasMany(Team::class, 'owner_id');
    }

    public function qrCodes(): HasMany
    {
        return $this->hasMany(QrCode::class);
    }

    public function creditBalance(): HasOne
    {
        return $this->hasOne(CreditBalance::class);
    }

    public function creditTransactions(): HasMany
    {
        return $this->hasMany(CreditTransaction::class);
    }

    public function createCreditBalance(?PlanTier $tier = null): CreditBalance
    {
        $tier ??= PlanTier::Free;

        return $this->creditBalance()->create([
            'balance' => $tier->monthlyCredits(),
            'monthly_allowance' => $tier->monthlyCredits(),
            'resets_at' => now()->addMonth()->startOfMonth(),
        ]);
    }

    public function currentTeam()
    {
        if ($this->current_team_id) {
            return Team::find($this->current_team_id);
        }

        return $this->ownedTeams()->first() ?? $this->teams()->first();
    }

    public function planTier(): PlanTier
    {
        if (! $this->subscribed()) {
            return PlanTier::Free;
        }

        $subscription = $this->subscription();
        $plan = Plan::where(function ($query) use ($subscription) {
            $query->where('stripe_monthly_price_id', $subscription->stripe_price)
                ->orWhere('stripe_yearly_price_id', $subscription->stripe_price);
        })->first();

        return $plan ? PlanTier::from($plan->slug) : PlanTier::Free;
    }

    public function hasFeature(Feature|string $feature): bool
    {
        return $this->planTier()->hasFeature($feature);
    }

    public function hasUnlimitedCredits(): bool
    {
        return $this->planTier()->hasUnlimitedCredits();
    }

    public function hasCredits(int $amount = 1): bool
    {
        return $this->hasUnlimitedCredits() || ($this->creditBalance?->balance ?? 0) >= $amount;
    }

    public function qrCodeCount(bool $isDynamic = false): int
    {
        return $this->qrCodes()->where('is_dynamic', $isDynamic)->count();
    }

    public function canCreateQrCode(bool $isDynamic = false): bool
    {
        $tier = $this->planTier();
        $max = $isDynamic ? $tier->maxDynamicQrCodes() : $tier->maxStaticQrCodes();

        if ($max === null) {
            return true;
        }

        return $this->qrCodeCount($isDynamic) < $max;
    }
}
