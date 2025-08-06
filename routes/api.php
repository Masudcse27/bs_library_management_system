<?php

use App\Http\Controllers\AuthenticationController;
use App\Http\Controllers\BookController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\BorrowController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DonationRequestController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\SettingsController;
use App\Models\FeaturedBook;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('login',[AuthenticationController::class, 'login']);

Route::controller(CategoryController::class)->prefix('/category')->middleware('auth:api')->group(function () {
    Route::post('/create', 'create')->middleware('role:admin');
    Route::get('/list', 'list')->middleware('role:admin');
    Route::put('/update/{id}', 'update')->middleware('role:admin');
    Route::delete('/delete/{id}', 'delete')->middleware('role:admin');
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

Route::controller(DonationRequestController::class)->prefix('/donation')->middleware('auth:api')->group(function(){
    Route::post('/create','create');
    Route::get('/list','list');
    Route::get('/retrieve/{id}','retrieve');
    Route::put('/edit/{id}','update');
    Route::delete('/delete/{id}','delete');
    Route::patch('/approve-reject/{id}','approve_reject');
});

Route::controller(ReviewController::class)->prefix('/review')->middleware('auth:api')->group(function(){
    Route::post('/{book_id}/create','create');
    Route::get('/{book_id}/list','list');
    Route::get('/retrieve/{id}','retrieve');
    Route::put('/edit/{id}','update');
    Route::delete('/delete/{id}','delete');
});

Route::controller(BorrowController::class)->prefix('/borrow')->middleware('auth:api')->group(function(){
    Route::post('/create','create');
    Route::get('/list','list');
    Route::get('/retrieve/{id}','retrieve');
    Route::patch('/extend/{id}','extend');
    Route::post('/return/{id}','return');
});

Route::controller(BookingController::class)->prefix('/booking')->middleware('auth:api')->group(function(){
    Route::post('/{borrow_id}/create','create');
    Route::get('/list','list');
    Route::get('/retrieve/{id}','retrieve');
    Route::put('/collect/{id}','collect');
    Route::delete('/delete/{id}','delete');
});

Route::controller(FeaturedBook::class)->prefix('/featured')->middleware('auth:api')->group(function(){
    Route::post('/{book_id}/add','addFeaturedBook');
    Route::get('/list','list');
    Route::post('/remove/{id}','removeFeaturedBook');
});
