<?php

use App\Http\Controllers\BookController;
use App\Http\Controllers\CategoryController;
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
});
