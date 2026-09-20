<?php

namespace App\Models;

use App\Enums\Feature;
use App\Enums\PlanTier;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
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
        'selected_plan',
        'plan_selected_at',
        'account_deletion_scheduled_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'plan_selected_at' => 'datetime',
            'account_deletion_scheduled_at' => 'datetime',
            'password' => 'hashed',
            'is_admin' => 'boolean',
        ];
    }

    public function hasSelectedPlan(): bool
    {
        if ($this->plan_selected_at !== null) {
            return true;
        }

        return $this->subscribed();
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

    public function categories(): HasMany
    {
        return $this->hasMany(Category::class);
    }

    public function currentTeam(): ?Team
    {
        // Resolve through the membership relation so a stale or tampered
        // current_team_id can never point at a team the user does not belong to.
        if ($this->current_team_id) {
            $team = $this->teams()->whereKey($this->current_team_id)->first()
                ?? $this->ownedTeams()->whereKey($this->current_team_id)->first();

            if ($team) {
                return $team;
            }
        }

        return $this->ownedTeams()->first() ?? $this->teams()->first();
    }

    public function planTier(): PlanTier
    {
        if (! $this->subscribed()) {
            return PlanTier::Starter;
        }

        $subscription = $this->subscription();
        $plan = Plan::where(function ($query) use ($subscription) {
            $query->where('stripe_monthly_price_id', $subscription->stripe_price)
                ->orWhere('stripe_yearly_price_id', $subscription->stripe_price);
        })->first();

        return $plan ? PlanTier::from($plan->slug) : PlanTier::Starter;
    }

    public function hasFeature(Feature|string $feature): bool
    {
        return $this->planTier()->hasFeature($feature);
    }

    public function hasFreeDynamicEdits(): bool
    {
        return $this->planTier() === PlanTier::Enterprise;
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
