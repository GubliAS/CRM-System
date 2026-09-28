<?php

use App\Http\Controllers\OpportunityController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {
    Route::post('/opportunities/{opportunity}/clone', [OpportunityController::class, 'storeClone'])->name('opportunities.clone');
    Route::patch('/opportunities/{opportunity}/owner', [OpportunityController::class, 'changeOwner'])->name('opportunities.owner');
    Route::resource('opportunities', OpportunityController::class);
});
