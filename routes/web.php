<?php

/*
 * Copyright (c) 2025 Adam Gąsowski
 */
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});
