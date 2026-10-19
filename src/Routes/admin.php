<?php

use Illuminate\Support\Facades\Route;
use Elig45\LaPosteShipping\Http\Controllers\AdminController;

Route::get('/', [AdminController::class, 'index'])->name('index');
Route::post('/preview', [AdminController::class, 'preview'])->name('preview');
Route::get('/rates/{card}/export', [AdminController::class, 'export'])->name('rates.export');
Route::post('/rates', [AdminController::class, 'store'])->name('rates.store');
Route::put('/rates/{card}', [AdminController::class, 'update'])->name('rates.update');
Route::delete('/rates/{card}', [AdminController::class, 'destroy'])->name('rates.destroy');
Route::post('/rates/import', [AdminController::class, 'import'])->name('rates.import');
Route::post('/profiles', [AdminController::class, 'storeProfile'])->name('profiles.store');
Route::delete('/profiles/{profile}', [AdminController::class, 'destroyProfile'])->name('profiles.destroy');
