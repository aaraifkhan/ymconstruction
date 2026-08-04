<?php

use App\Http\Controllers\Api\ZkTecoAdmsController;
use App\Http\Controllers\PortalController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('portal');
});

Route::get('/login', fn () => redirect()->route('filament.admin.auth.login'))->name('login');

Route::get('/iclock/cdata', [ZkTecoAdmsController::class, 'cdataGet'])->name('iclock.cdata.get');
Route::post('/iclock/cdata', [ZkTecoAdmsController::class, 'cdataPost'])->name('iclock.cdata.post');
Route::get('/iclock/getrequest', [ZkTecoAdmsController::class, 'getrequest'])->name('iclock.getrequest');
Route::post('/iclock/devicecmd', [ZkTecoAdmsController::class, 'devicecmd'])->name('iclock.devicecmd');

Route::middleware('auth')->group(function (): void {
    Route::get('/portal', [PortalController::class, 'index'])->name('portal');
    Route::get('/portal/company/{company:slug}', [PortalController::class, 'enterCompany'])->name('portal.company');
    Route::get('/admin', [PortalController::class, 'superAdmin'])->name('portal.super-admin');
});
