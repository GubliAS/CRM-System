<?php

use App\Http\Controllers\AboutController;
use App\Http\Controllers\AccountController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\LeadController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome');
});

Route::get('/about', AboutController::class)->name('about');

Route::get('/home', function () {
    return Inertia::render('Home');
})->middleware(['auth', 'verified'])->name('home');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::post('/leads/{lead}/status', [LeadController::class, 'changeStatus'])->name('leads.status');
    Route::post('/leads/{lead}/owner', [LeadController::class, 'changeOwner'])->name('leads.owner');
    Route::post('/leads/{lead}/convert', [LeadController::class, 'convert'])->name('leads.convert');
    Route::resource('leads', LeadController::class);

    Route::resource('accounts', AccountController::class);
    Route::resource('contacts', ContactController::class);
});

require __DIR__.'/opportunities.php';

require __DIR__.'/auth.php';
