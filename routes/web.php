<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\CaseController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ImportController;
use App\Http\Controllers\LeadController;
use App\Http\Controllers\OpportunityController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\TaskController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome');
});

Route::get('/home', HomeController::class)->middleware(['auth', 'verified'])->name('home');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('/search', [SearchController::class, 'index'])->name('search.index');
    Route::get('/search/suggest', [SearchController::class, 'suggest'])->name('search.suggest');

    Route::post('/home/recommendations/dismiss', [HomeController::class, 'dismiss'])
        ->name('home.recommendations.dismiss');

    Route::post('/tasks/{task}/complete', [TaskController::class, 'complete'])->name('tasks.complete');
    Route::post('/tasks/{task}/assign', [TaskController::class, 'assign'])->name('tasks.assign');

    Route::get('/opportunities/export', [OpportunityController::class, 'export'])->name('opportunities.export');
    Route::post('/opportunities/{opportunity}/clone', [OpportunityController::class, 'clone'])->name('opportunities.clone');
    Route::post('/opportunities/{opportunity}/owner', [OpportunityController::class, 'changeOwner'])->name('opportunities.owner');
    Route::resource('opportunities', OpportunityController::class);

    Route::get('/leads/export', [LeadController::class, 'export'])->name('leads.export');
    Route::post('/leads/{lead}/status', [LeadController::class, 'changeStatus'])->name('leads.status');
    Route::post('/leads/{lead}/owner', [LeadController::class, 'changeOwner'])->name('leads.owner');
    Route::post('/leads/{lead}/convert', [LeadController::class, 'convert'])->name('leads.convert');
    Route::resource('leads', LeadController::class);

    Route::post('/cases/{case}/status', [CaseController::class, 'changeStatus'])->name('cases.status');
    Route::post('/cases/{case}/owner', [CaseController::class, 'changeOwner'])->name('cases.owner');
    Route::post('/cases/{case}/close', [CaseController::class, 'close'])->name('cases.close');
    Route::post('/cases/{case}/reopen', [CaseController::class, 'reopen'])->name('cases.reopen');
    Route::resource('cases', CaseController::class);

    Route::get('/accounts/export', [AccountController::class, 'export'])->name('accounts.export');
    Route::resource('accounts', AccountController::class);

    Route::get('/contacts/export', [ContactController::class, 'export'])->name('contacts.export');
    Route::resource('contacts', ContactController::class);

    Route::get('/import', [ImportController::class, 'create'])->name('import.create');
    Route::post('/import', [ImportController::class, 'store'])->name('import.store');
    Route::get('/import/errors/{token}', [ImportController::class, 'errors'])->name('import.errors');

    Route::post('/reports/preview', [ReportController::class, 'preview'])->name('reports.preview');
    Route::get('/reports/{report}/export', [ReportController::class, 'export'])->name('reports.export');
    Route::resource('reports', ReportController::class);

    Route::post('/dashboards/preview-widget', [DashboardController::class, 'previewWidget'])->name('dashboards.preview-widget');
    Route::post('/dashboards/{dashboard}/clone', [DashboardController::class, 'clone'])->name('dashboards.clone');
    Route::resource('dashboards', DashboardController::class);
});

require __DIR__.'/tasks.php';

require __DIR__.'/events.php';

require __DIR__.'/auth.php';
