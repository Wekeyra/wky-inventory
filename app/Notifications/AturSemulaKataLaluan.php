<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Lang;

/**
 * Emel pautan set semula kata laluan.
 *
 * Notifikasi terbina Laravel hanya berbahasa Inggeris. Sistem ini dwibahasa,
 * dan emel yang tiba dalam bahasa yang berlainan daripada skrin yang baru
 * sahaja diminta pengguna kelihatan seperti emel palsu — tepat pada saat
 * pengguna paling berhati-hati tentang pautan yang diterimanya.
 */
class AturSemulaKataLaluan extends Notification
{
    public function __construct(public string $token) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $pautan = route('password.reset', [
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ]);

        // Tempoh sah dibaca daripada konfigurasi broker dan bukan ditulis
        // tetap, supaya emel tidak mendakwa 60 minit sedangkan konfigurasi
        // sudah ditukar kepada sesuatu yang lain.
        $minit = config('auth.passwords.'.config('auth.defaults.passwords').'.expire');

        return (new MailMessage)
            ->subject(Lang::get('wky.emel.reset_tajuk', ['app' => config('app.name')]))
            ->greeting(Lang::get('wky.emel.reset_salam'))
            ->line(Lang::get('wky.emel.reset_baris1'))
            ->action(Lang::get('wky.emel.reset_butang'), $pautan)
            ->line(Lang::get('wky.emel.reset_tempoh', ['minit' => $minit]))
            ->line(Lang::get('wky.emel.reset_abai'))
            ->salutation(Lang::get('wky.emel.penutup', ['app' => config('app.name')]));
    }
}
