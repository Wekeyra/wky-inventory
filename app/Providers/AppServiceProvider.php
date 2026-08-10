<?php

namespace App\Providers;

use Anthropic\Client;
use App\Services\Invoice\ClaudeInvoiceExtractor;
use App\Services\Invoice\InvoiceExtractor;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(Client::class, fn () => new Client(
            apiKey: (string) config('anthropic.api_key'),
            requestOptions: ['timeout' => (float) config('anthropic.timeout')],
        ));

        // Diikat pada antara muka supaya ujian boleh menggantikan pengekstrak
        // sebenar dengan yang palsu tanpa memanggil API.
        $this->app->bind(InvoiceExtractor::class, ClaudeInvoiceExtractor::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
