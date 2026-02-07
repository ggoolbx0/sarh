<?php

use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\TrapController;
use App\Livewire\EmployeeDashboard;
use App\Livewire\MessagingChat;
use App\Livewire\MessagingInbox;
use App\Livewire\WhistleblowerForm;
use App\Livewire\WhistleblowerTrack;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

/*
|--------------------------------------------------------------------------
| Employee PWA Routes (Authenticated)
|--------------------------------------------------------------------------
| Main dashboard and authenticated features.
*/
Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', EmployeeDashboard::class)->name('dashboard');
    Route::get('/messaging', MessagingInbox::class)->name('messaging.inbox');
    Route::get('/messaging/{conversation}', MessagingChat::class)->name('messaging.chat');
});

/*
|--------------------------------------------------------------------------
| Whistleblower Routes (NO Authentication — Anonymous)
|--------------------------------------------------------------------------
| These routes must remain public. No auth, no IP logging, no sessions tracking.
*/
Route::get('/whistleblower', WhistleblowerForm::class)->name('whistleblower.form');
Route::get('/whistleblower/track', WhistleblowerTrack::class)->name('whistleblower.track');

/*
|--------------------------------------------------------------------------
| Attendance API Routes (PWA — Authenticated)
|--------------------------------------------------------------------------
| These routes serve the PWA check-in/check-out flow.
| They require authentication (session-based or Sanctum).
*/
Route::middleware(['auth'])->prefix('attendance')->name('attendance.')->group(function () {
    Route::post('/check-in',  [AttendanceController::class, 'checkIn'])->name('check_in');
    Route::post('/check-out', [AttendanceController::class, 'checkOut'])->name('check_out');
    Route::get('/today',      [AttendanceController::class, 'todayStatus'])->name('today');
});

/*
|--------------------------------------------------------------------------
| Trap System Routes (PWA — Authenticated)
|--------------------------------------------------------------------------
| Trap trigger endpoint. The PWA sends trap interactions here.
| The response is a convincing fake payload — no real data exposed.
*/
Route::middleware(['auth'])->prefix('traps')->name('traps.')->group(function () {
    Route::post('/trigger', [TrapController::class, 'trigger'])->name('trigger');
});
