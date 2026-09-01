<?php

use App\Http\Controllers\BrandingLogoController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/invoices')->name('home');
Route::get('branding/logo', BrandingLogoController::class)->name('branding.logo');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::redirect('dashboard', '/invoices')->name('dashboard');
    Route::livewire('invoices', 'pages::invoices.index')->name('invoices.index');
    Route::livewire('invoices/create', 'pages::invoices.form')->name('invoices.create');
    Route::livewire('invoices/{invoice}/edit', 'pages::invoices.form')->name('invoices.edit');
    Route::livewire('invoices/{invoice}', 'pages::invoices.show')->name('invoices.show');
    Route::livewire('clients', 'pages::clients.index')->name('clients.index');
});

require __DIR__.'/settings.php';
