<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware('auth0')->group(function () {
    Route::get('/protected', function () {
        return response()->json(['message' => 'This is a protected route.']);
    });
});

Route::get('/unprotected', function () {
    return response()->json(['message' => 'This is an unprotected route.']);
});
