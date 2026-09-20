<?php

namespace App\Livewire\Teams;

use App\Enums\Feature;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Locked;
use Livewire\Component;

class TeamManager extends Component
{
    use AuthorizesRequests;

    #[Locked]
    public ?Team $team = null;

    public string $teamName = '';
    public string $inviteEmail = '';

    public function mount()
    {
        if (! auth()->user()->hasFeature(Feature::Teams)) {
            abort(403, __('qr.teams_not_available'));
        }

        $this->team = auth()->user()->currentTeam();

        if ($this->team) {
            $this->authorize('view', $this->team);
            $this->teamName = $this->team->name;
        }
    }

    public function createTeam()
    {
        $this->validate(['teamName' => 'required|string|max:255']);

        $user = auth()->user();
        $team = Team::create([
            'name' => $this->teamName,
            'owner_id' => $user->id,
        ]);

        $team->users()->attach($user->id, ['role' => 'owner']);
        $user->forceFill(['current_team_id' => $team->id])->save();

        $this->team = $team;
        session()->flash('status', 'Team created successfully.');
    }

    public function inviteMember()
    {
        if (! $this->team) return;

        $this->authorize('manageMembers', $this->team);

        $this->validate(['inviteEmail' => 'required|email']);

        $invitee = User::where('email', $this->inviteEmail)->first();
        if (! $invitee) {
            $this->addError('inviteEmail', 'No user found with this email.');
            return;
        }

        if ($this->team->users()->where('user_id', $invitee->id)->exists()) {
            $this->addError('inviteEmail', 'User is already a team member.');
            return;
        }

        $this->team->users()->attach($invitee->id, ['role' => 'member']);
        $this->reset('inviteEmail');

        session()->flash('status', "{$invitee->name} has been added to the team.");
    }

    public function updateRole(int $userId, string $role)
    {
        if (! $this->team || $userId === $this->team->owner_id) return;

        $this->authorize('updateRoles', $this->team);

        if (! in_array($role, ['admin', 'member'])) return;

        $this->team->users()->updateExistingPivot($userId, ['role' => $role]);
    }

    public function removeMember(int $userId)
    {
        if (! $this->team || $userId === $this->team->owner_id) return;

        $this->authorize('manageMembers', $this->team);

        $this->team->users()->detach($userId);
        $removed = User::find($userId);
        if ($removed?->current_team_id === $this->team->id) {
            $removed->forceFill(['current_team_id' => null])->save();
        }

        session()->flash('status', 'Member removed from team.');
    }

    public function render()
    {
        $members = $this->team ? $this->team->users()->withPivot('role')->get() : collect();

        return view('livewire.teams.team-manager', [
            'members' => $members,
        ]);
    }
}
