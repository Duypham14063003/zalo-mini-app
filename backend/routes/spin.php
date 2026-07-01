<?php

use App\Http\Controllers\Api\SpinController;
use Illuminate\Support\Facades\Route;

Route::post('/spin', SpinController::class);
