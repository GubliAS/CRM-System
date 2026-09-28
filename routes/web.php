<?php

use App\Http\Controllers\AboutController;
use App\Http\Controllers\AccountController;
use App\Http\Controllers\CaseController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\LeadController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SearchController;
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

    Route::get('/search', [SearchController::class, 'index'])->name('search.index');
    Route::get('/search/suggest', [SearchController::class, 'suggest'])->name('search.suggest');

    Route::post('/leads/{lead}/status', [LeadController::class, 'changeStatus'])->name('leads.status');
    Route::post('/leads/{lead}/owner', [LeadController::class, 'changeOwner'])->name('leads.owner');
    Route::post('/leads/{lead}/convert', [LeadController::class, 'convert'])->name('leads.convert');
    Route::resource('leads', LeadController::class);

    Route::post('/cases/{case}/status', [CaseController::class, 'changeStatus'])->name('cases.status');
    Route::post('/cases/{case}/owner', [CaseController::class, 'changeOwner'])->name('cases.owner');
    Route::post('/cases/{case}/close', [CaseController::class, 'close'])->name('cases.close');
    Route::post('/cases/{case}/reopen', [CaseController::class, 'reopen'])->name('cases.reopen');
    Route::resource('cases', CaseController::class);

    Route::resource('accounts', AccountController::class);
    Route::resource('contacts', ContactController::class);
});

require __DIR__.'/opportunities.php';

require __DIR__.'/auth.php';
