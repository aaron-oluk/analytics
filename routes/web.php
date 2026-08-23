<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SiteController;
use App\Http\Controllers\SiteExportController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::redirect('/dashboard', '/sites')->name('dashboard');
    Route::get('/sites/{site}/export', [SiteExportController::class, 'show'])->name('sites.export');
    Route::get('/sites/{site}/export/{dataset}', [SiteExportController::class, 'download'])->name('sites.export.download');
    Route::resource('sites', SiteController::class);
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
