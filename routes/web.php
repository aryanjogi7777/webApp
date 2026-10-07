<?php

use App\Http\Controllers\DefaultingAccountController;
use App\Http\Controllers\DefaultingAccountReportController;
use Illuminate\Support\Facades\Route;

Route::get('/', [DefaultingAccountController::class, 'lookup'])->name('home');

Route::get('/accounts/import', [DefaultingAccountController::class, 'importForm'])->name('accounts.import');
Route::post('/accounts/import', [DefaultingAccountController::class, 'import'])->name('accounts.import.store');
Route::get('/accounts/export', [DefaultingAccountController::class, 'exportForm'])->name('accounts.export');
Route::get('/accounts/export/download', [DefaultingAccountController::class, 'export'])->name('accounts.export.download');
Route::get('/accounts/reports', [DefaultingAccountReportController::class, 'index'])->name('accounts.reports');
Route::get('/accounts/reports/download', [DefaultingAccountReportController::class, 'download'])->name('accounts.reports.download');

Route::resource('accounts', DefaultingAccountController::class)->except('show');
