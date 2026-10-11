<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\Genre;
use App\Http\Requests\StoreBookRequest;
use App\Http\Requests\UpdateBookRequest;
use App\Http\Resources\BookResource;

class BookController extends Controller
{
     public function index()
    {
        $books = Book::with('genres')
            ->withAvg('reviews', 'rating')
            ->orderByDesc('created_at')
            ->paginate(10);

        return view('books.index', compact('books'));
    }

    public function create()
   {
        $genres = Genre::all();
         
        return view('books.create', compact('genres'));
   }

   public function store(StoreBookRequest $request)
    {
        $validated = $request->validated();

        $genres = $validated['genres'];
        unset($validated['genres']);

        $book = auth()->user()->books()->create($validated);

        $book->genres()->sync($genres);

        return redirect()
            ->route('books.index')
            ->with('success', '書籍を登録しました。');
    }
   
   public function show(Book $book)
   {
        $book->load(['genres', 'reviews']);

        return view('books.show', compact('book'));
   }

   public function edit(Book $book)
    {
        if ($book->user_id !== auth()->id()) {
            abort(403);
        }

        $genres = Genre::all();

        $book->load('genres');

        return view('books.edit', compact('book', 'genres'));
    }

    public function update(UpdateBookRequest $request, Book $book)
    {
        if ($book->user_id !== auth()->id()) {
            abort(403);
        }

        $validated = $request->validated();

        $genres = $validated['genres'];
        unset($validated['genres']);

        $book->update($validated);

        $book->genres()->sync($genres);

        return redirect()
            ->route('books.show', $book)
            ->with('success', '書籍を更新しました。');
    }

    public function destroy(Book $book)
    {
        if ($book->user_id !== auth()->id()) {
            abort(403);
        }

        $book->delete();

        return redirect()
            ->route('books.index')
            ->with('success', '書籍を削除しました。');
    }
}
