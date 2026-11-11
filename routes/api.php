<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\BookController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
Route::get('/v1/books', [BookController::class, 'index']);
Route::post('/v1/books', [BookController::class, 'store'])
    ->middleware('auth:sanctum');
Route::get('/v1/books/{book}', [BookController::class, 'show']);
Route::put('/v1/books/{book}', [BookController::class, 'update'])
    ->middleware('auth:sanctum');
Route::delete('/v1/books/{book}', [BookController::class, 'destroy'])
    ->middleware('auth:sanctum');
