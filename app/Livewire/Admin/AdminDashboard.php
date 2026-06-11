<?php

namespace App\Livewire\Admin;

use App\Models\Plan;
use App\Models\QrCode;
use App\Models\Scan;
use App\Models\User;
use Livewire\Component;
use Livewire\WithPagination;

class AdminDashboard extends Component
{
    use WithPagination;

    public string $userSearch = '';
    public string $tab = 'overview';

    public function updatingUserSearch(): void
    {
        $this->resetPage();
    }

    public function toggleAdmin(int $userId): void
    {
        $user = User::findOrFail($userId);
        if ($user->id === auth()->id()) return;
        $user->update(['is_admin' => ! $user->is_admin]);
    }

    public function deleteUser(int $userId): void
    {
        $user = User::findOrFail($userId);
        if ($user->id === auth()->id()) return;

        $user->qrCodes()->delete();
        $user->delete();

        session()->flash('status', "User {$user->email} deleted.");
    }

    public function render()
    {
        $stats = [
            'total_users' => User::count(),
            'total_qr_codes' => QrCode::count(),
            'total_scans' => Scan::count(),
            'scans_today' => Scan::where('scanned_at', '>=', now()->startOfDay())->count(),
            'new_users_today' => User::where('created_at', '>=', now()->startOfDay())->count(),
            'subscribers' => User::whereHas('subscriptions', fn($q) => $q->where('stripe_status', 'active'))->count(),
        ];

        $usersQuery = User::withCount('qrCodes')
            ->latest();

        if ($this->userSearch) {
            $usersQuery->where(function ($q) {
                $q->where('name', 'like', "%{$this->userSearch}%")
                    ->orWhere('email', 'like', "%{$this->userSearch}%");
            });
        }

        return view('livewire.admin.admin-dashboard', [
            'stats' => $stats,
            'users' => $usersQuery->paginate(20),
        ])->layout('layouts.app', ['title' => __('nav.admin_panel')]);
    }
}
