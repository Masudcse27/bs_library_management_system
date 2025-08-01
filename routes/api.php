<?php

use App\Http\Controllers\BookController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\SettingsController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::controller(CategoryController::class)->prefix('/category')->group(function () {
    Route::post('/create', 'create');
    Route::get('/list', 'list');
    Route::put('/update/{id}', 'update');
    Route::delete('/delete/{id}', 'delete');
});

Route::controller(BookController::class)->prefix('/book')->group(function(){
    Route::post('/create','create');
    Route::get('list','list');
    Route::get('/retrieve/{id}','retrieve');
    Route::put('/edit/{id}','update');
    Route::delete('/delete/{id}','delete');
    Route::get('/popular-books','popular_books');
    Route::get('/new-collection', 'new_collection');
    Route::get('/{id}/is_available','is_available');
});

Route::controller(SettingsController::class)->prefix('settings')->group(function (){
    Route::get('/get-settings', 'get_setting');
    Route::patch('/borrow-day-limit','borrow_day_limit');
    Route::patch('/borrow-extend-limit','borrow_extend_limit');
    Route::patch('/borrow-limit','borrow_limit');
    Route::patch('/booking-duration','booking_duration');
    Route::patch('/booking-days-limit','booking_days_limit');
});