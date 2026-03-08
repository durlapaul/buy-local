<?php

use Illuminate\Support\Facades\Route;

Route::get('/email-verified', function () {
    return view('email_verified');
});