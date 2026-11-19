<?php

use Database\Seeders\SettingsSeeder;
use Illuminate\Support\Facades\Route;

it('n’affiche aucune trace technique quand APP_DEBUG est désactivé (mode production simulé)', function () {
    $this->seed(SettingsSeeder::class);
    config(['app.debug' => false]);

    Route::middleware('web')->get('/_test-panne', fn () => throw new RuntimeException('secret-interne SQLSTATE /var/www'));

    $this->get('/_test-panne')->assertStatus(500)
        ->assertDontSee('secret-interne')
        ->assertDontSee('SQLSTATE')
        ->assertDontSee('Stack trace')
        ->assertDontSee('vendor/laravel', false)
        ->assertHeader('X-Frame-Options', 'DENY');
});
