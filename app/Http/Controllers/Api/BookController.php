<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Book;
use App\Http\Requests\Api\UpdateBookRequest;

class BookController extends Controller
{
    public function index(Request $request)
    {
        $books = Book::with('genres')
            ->withAvg('reviews', 'rating')
            ->withCount('reviews')
            ->when($request->filled('keyword'), function ($query) use ($request) {
                $keyword = $request->input('keyword');

                $query->where(function ($q) use ($keyword) {
                    $q->where('title', 'like', "%{$keyword}%")
                      ->orWhere('author', 'like', "%{$keyword}%");
                });
            })
            ->when($request->filled('genre'), function ($query) use ($request) {
                $query->whereHas('genres', function ($q) use ($request) {
                    $q->where('genres.id', $request->input('genre'));
                });
            })
            ->paginate(10);

        return response()->json($books);
    }

    public function show(Book $book)
    {
        $book->load(['genres', 'reviews']);

        return response()->json($book);
    }

    public function update(UpdateBookRequest $request, Book $book)
    {
        $validated = $request->validated();

        $genres = $validated['genres'];
        unset($validated['genres']);

        $book->update($validated);
        $book->genres()->sync($genres);

        return response()->json($book->load('genres'));
    }

    public function destroy(Book $book)
    {
        $book->delete();

        return response()->json([
            'message' => '書籍を削除しました。',
        ]);
    }
}
