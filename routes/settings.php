<?php

use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    // Settings pages removed - all settings now managed through SSO
    // Theme toggle is available in the navbar
    // Redirect old settings URLs to home
    Route::redirect('settings', '/')->name('profile.edit');
    Route::redirect('settings/profile', '/');
    Route::redirect('settings/password', '/')->name('user-password.edit');
    Route::redirect('settings/appearance', '/')->name('appearance.edit');
    Route::redirect('settings/two-factor', '/')->name('two-factor.show');
});
