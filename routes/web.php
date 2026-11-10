<?php

use App\Http\Controllers\BookController;
use App\Http\Controllers\RankingController;
use App\Http\Controllers\FavoriteController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\GenreController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ReadingPlanController;
use App\Http\Controllers\NotificationController;
/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});
Route::get('/books', [BookController::class, 'index'])->name('books.index');
Route::get('/books/create', [BookController::class, 'create'])->middleware('auth')->name('books.create');
Route::get('/books/isbn/{isbn}', [BookController::class, 'fetchByIsbn'])
    ->middleware('auth')
    ->where('isbn', '[0-9]{13}')
    ->name('books.isbn');
Route::post('/books', [BookController::class, 'store'])->middleware('auth')->name('books.store');
Route::get('/ranking', [RankingController::class, 'index'])->name('ranking.index');
Route::get('/books/{book}', [BookController::class, 'show'])->name('books.show');
Route::get('/books/{book}/edit', [BookController::class, 'edit'])->middleware('auth')->name('books.edit');
Route::put('/books/{book}', [BookController::class, 'update'])->middleware('auth')->name('books.update');
Route::delete('/books/{book}', [BookController::class, 'destroy'])->middleware('auth')->name('books.destroy');
Route::get('/favorites', [FavoriteController::class, 'index'])->middleware('auth')->name('favorites.index');
Route::get('/genres', [GenreController::class, 'index'])->middleware('auth')->name('genres.index');
Route::get('/genres/create', [GenreController::class, 'create'])->middleware('auth')->name('genres.create');
Route::post('/genres', [GenreController::class, 'store'])->middleware('auth')->name('genres.store');
Route::get('/genres/{genre}', [GenreController::class, 'show'])->middleware('auth')->name('genres.show');
Route::get('/genres/{genre}/edit', [GenreController::class, 'edit'])->middleware('auth')->name('genres.edit');
Route::put('/genres/{genre}', [GenreController::class, 'update'])->middleware('auth')->name('genres.update');
Route::delete('/genres/{genre}', [GenreController::class, 'destroy'])->middleware('auth')->name('genres.destroy');
Route::post('/books/{book}/favorites', [FavoriteController::class, 'toggle'])->middleware('auth')->name('favorites.toggle');
Route::middleware('auth')->group(function () {
    Route::post('/books/{book}/reviews',[ReviewController::class,'store'])->name('reviews.store');
    Route::post('/reviews/{review}/like',[ReviewController::class,'toggleLike'])->name('reviews.like');
    Route::get('/reviews/{review}/edit',[ReviewController::class,'edit'])->name('reviews.edit');    Route::put('/reviews/{review}',[ReviewController::class,'update'])->name('reviews.update');
    Route::delete('/reviews/{review}',[ReviewController::class,'destroy'])->name('reviews.destroy');
});
Route::get('/reading-plans', [ReadingPlanController::class, 'index'])
    ->middleware('auth')
    ->name('reading-plans.index');
Route::get('/reading-plans/create', [ReadingPlanController::class, 'create'])
    ->middleware('auth')
    ->name('reading-plans.create');
Route::post('/reading-plans', [ReadingPlanController::class, 'store'])
    ->middleware('auth')
    ->name('reading-plans.store');
Route::post('/reading-plans/{readingPlan}/complete', [ReadingPlanController::class, 'complete'])
    ->middleware('auth')
    ->name('reading-plans.complete');
Route::get('/reading-plans/{readingPlan}/edit', [ReadingPlanController::class, 'edit'])
    ->middleware('auth')
    ->name('reading-plans.edit');
Route::delete('/reading-plans/{readingPlan}', [ReadingPlanController::class, 'destroy'])
    ->middleware('auth')
    ->name('reading-plans.destroy');
Route::put('/reading-plans/{readingPlan}', [ReadingPlanController::class, 'update'])
    ->middleware('auth')
    ->name('reading-plans.update');

Route::middleware('auth')->group(function () {
    // 通知一覧
    Route::get('/notifications', [NotificationController::class, 'index'])
        ->name('notifications.index');

    // 通知を既読にする
    Route::post('/notifications/{id}/read', [NotificationController::class, 'markAsRead'])
        ->name('notifications.read');
});
        