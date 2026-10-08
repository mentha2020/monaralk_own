<?php

use App\Http\Controllers\ProfileController;
use App\Modules\Catalog\Http\Controllers\HomeController;
use App\Modules\Catalog\Http\Controllers\VehicleController;
use App\Modules\Catalog\Http\Controllers\VehicleExportController;
use App\Modules\Catalog\Http\Controllers\VehicleSpecSheetController;
use Filament\Http\Middleware\Authenticate;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');

Route::get('/vehicles', [VehicleController::class, 'index'])->name('vehicles.index');
Route::get('/vehicles/{vehicle}', [VehicleController::class, 'show'])->name('vehicles.show');
Route::get('/vehicles/{vehicle}/spec-sheet', [VehicleController::class, 'specSheet'])->name('vehicles.spec-sheet');

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::prefix('admin')->name('admin.')->middleware([Authenticate::class])->group(function () {
    Route::get('/inventory/export', VehicleExportController::class)->name('inventory.export');
    Route::get('/vehicles/{vehicle}/spec-sheet', VehicleSpecSheetController::class)->name('vehicles.spec-sheet');
});

require __DIR__.'/auth.php';
