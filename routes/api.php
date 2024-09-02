<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\V1\Auth\SessionController;
use App\Http\Controllers\API\V1\ForgotPasswordController;
use App\Http\Controllers\API\V1\ResetPasswordController;




Route::prefix('v1')->group(function () {
    Route::prefix('auth')->group(function () {

        Route::post('/register', [SessionController::class, 'register']);
        Route::post('/login', [SessionController::class, 'login'])->name('login');
        
        Route::middleware('auth:api')->group(function () {
            Route::post('/logout', [SessionController::class, 'logout']);
        });

        Route::post('password/email', [ForgotPasswordController::class, 'sendResetLinkEmail']);
        Route::post('password/reset', [ResetPasswordController::class, 'reset']);


    });
});
