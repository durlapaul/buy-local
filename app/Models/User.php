<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function ownedSpaces()
    {
        return $this->hasMany(Space::class, 'owner_id');
    }

    public function assignedSpaces()
    {
        return $this->belongsToMany(Space::class, 'space_user')
            ->withPivot('role')
            ->withTimestamps();
    }

    public function adminSpaces()
    {
        return $this->assignedSpaces()->wherePivot('role', 'space_admin');
    }

    public function workerSpaces()
    {
        return $this->assignedSpaces()->wherePivot('role', 'space_worker');
    }

    public function managedSpaces()
    {
        return Space::where('owner_id', $this->id)
            ->orWhereHas('users', function ($query) {
                $query->where('user_id', $this->id);
            })->get();
    }

    public function canManageSpace(Space $space): bool
    {
        if ($space->owner_id === $this->id) {
            return true;
        }

        return $this->assignedSpaces()->where('spaces.id', $space->id)->exists();
    }

    public function isAdminOfSpace(Space $space): bool
    {
        if ($space->owner_id === $this->id) {
            return true;
        }

        return $this->adminSpaces()->where('spaces.id', $space->id)->exists();
    }

    public function isWorkerOfSpace(Space $space): bool
    {
        return $this->workerSpaces()->where('spaces.id', $space->id)->exists();
    }

    public function isConsumer(): bool
    {
        return $this->hasRole('consumer');
    }
}
