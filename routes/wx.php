<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Wx\AuthController;

/*
|--------------------------------------------------------------------------
| Wx Routes
|--------------------------------------------------------------------------
|
|
|
*/

Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/regCaptcha', [AuthController::class, 'regCaptcha']);
