<?php

use App\Http\Controllers\AdminDashboardController;
use App\Http\Controllers\AuthenticationController;
use App\Http\Controllers\BookController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\BorrowController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DonationRequestController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\FeaturedBookController;
use App\Http\Controllers\UserDashboardController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/login', [AuthenticationController::class, 'login']);

Route::middleware('auth:api')->group(function () {
    Route::get('/user', [AuthenticationController::class, 'userData']);
    Route::get('/logout', [AuthenticationController::class, 'logout']);
    Route::controller(CategoryController::class)->prefix('/category')->group(function () {
        Route::post('/create', 'create')->middleware('role:admin');
        Route::get('/list', 'list');
        Route::put('/update/{id}', 'update')->middleware('role:admin');
        Route::delete('/delete/{id}', 'delete')->middleware('role:admin');
    });

    Route::controller(BookController::class)->prefix('/book')->group(function () {
        Route::post('/create', 'create')->middleware('role:admin');
        Route::get('list', 'list');
        Route::get('/retrieve/{id}', 'retrieve');
        Route::post('/edit/{id}', 'update')->middleware('role:admin');
        Route::delete('/delete/{id}', 'delete')->middleware('role:admin');
        Route::get('/popular-books', 'popular_books');
        Route::get('/new-collection', 'new_collection');
        Route::get('/{id}/is_available', 'is_available');
        Route::get('/recommended-books', 'recommended_books');
        Route::get('/related-books/{id}', 'relatedBooks');
    });

    Route::controller(SettingsController::class)->prefix('settings')->group(function () {
        Route::get('/get-settings', 'get_setting');
        Route::middleware('role:admin')->group(function () {
            Route::patch('/borrow-day-limit', 'borrow_day_limit');
            Route::patch('/borrow-extend-limit', 'borrow_extend_limit');
            Route::patch('/borrow-limit', 'borrow_limit');
            Route::patch('/booking-duration', 'booking_duration');
            Route::patch('/booking-days-limit', 'booking_days_limit');
        });

    });

    Route::controller(DonationRequestController::class)->prefix('/donation')->group(function () {
        Route::post('/create', 'create');
        Route::get('/list', 'list');
        Route::get('/retrieve/{id}', 'retrieve');
        Route::put('/edit/{id}', 'update');
        Route::delete('/delete/{id}', 'delete');
        Route::patch('/approve-reject/{id}', 'approve_reject')->middleware('role:admin');
    });

    Route::controller(ReviewController::class)->prefix('/review')->group(function () {
        Route::post('/{book_id}/create', 'create');
        Route::get('/{book_id}/list', 'list');
        Route::get('/retrieve/{id}', 'retrieve');
        Route::put('/edit/{id}', 'update');
        Route::delete('/delete/{id}', 'delete');
        Route::get('/rating-star-count/{bookId}', 'ratingStarCount');
    });

    Route::controller(BorrowController::class)->prefix('/borrow')->group(function () {
        Route::post('/create', 'create');
        Route::get('/list', 'list');
        Route::get('/retrieve/{id}', 'retrieve');
        Route::patch('/extend/{id}', 'extend');
        Route::post('/return/{id}', 'return');
    });

    Route::controller(BookingController::class)->prefix('/booking')->group(function () {
        Route::post('/{borrow_id}/create', 'create');
        Route::get('/list', 'list');
        Route::get('/retrieve/{id}', 'retrieve');
        Route::put('/collect/{id}', 'collect');
        Route::delete('/delete/{id}', 'delete');
    });

    Route::controller(FeaturedBookController::class)->prefix('/featured-books')->group(function () {
        Route::post('/{book_id}/add', 'addFeaturedBook')->middleware('role:admin');
        Route::get('/list', 'list');
        Route::delete('/remove/{id}', 'removeFeaturedBook')->middleware('role:admin');
    });

    Route::controller(AdminDashboardController::class)->prefix('/admin-dashboard')->middleware('role:admin')->group(function () {
        Route::get('/statistics', 'statistics');
        Route::get('/borrows-chart', 'borrows_chart');
        Route::get('/recent-borrows', 'recent_borrows');
        Route::get('/overdue-borrows', 'overdue_borrows');
        Route::get('/new-borrows', 'newBorrows');
        Route::get('/borrows/{id}/approve', 'approveBorrow');
        Route::get('/borrows/{id}/reject', 'rejectBorrow');
        Route::get('/overdue-borrows', 'overdue_borrows');
    });
    Route::controller(UserDashboardController::class)->prefix('/user-dashboard')->group(function () {
        Route::get('/statistics', 'statistics');
        Route::get('/borrowed-books', 'borrowedBooks');
    });
});
