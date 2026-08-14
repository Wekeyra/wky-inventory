<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Notifications\AturSemulaKataLaluan;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['workspace_id', 'name', 'email', 'google_id', 'peranan', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

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

    public function isAdmin(): bool
    {
        return $this->peranan === 'admin';
    }

    /**
     * Notifikasi terbina Laravel diganti supaya emelnya mengikut bahasa sistem.
     *
     * Emel yang tiba dalam bahasa berlainan daripada skrin yang baru sahaja
     * diminta pengguna kelihatan seperti emel palsu — tepat pada saat pengguna
     * paling berhati-hati tentang pautan yang diterimanya.
     */
    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new AturSemulaKataLaluan($token));
    }

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function movements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }
}
