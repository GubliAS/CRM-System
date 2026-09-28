<?php

use App\Http\Controllers\EventController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {
    Route::get('/calendar', [EventController::class, 'index'])->name('events.index');
    Route::patch('/events/{event}/reschedule', [EventController::class, 'reschedule'])->name('events.reschedule');
    Route::resource('events', EventController::class)->except(['index']);
});
