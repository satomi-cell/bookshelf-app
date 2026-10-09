<?php

use App\Http\Controllers\BookController;
use App\Http\Controllers\RankingController;
use App\Http\Controllers\FavoriteController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\GenreController;
use Illuminate\Support\Facades\Route;

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



