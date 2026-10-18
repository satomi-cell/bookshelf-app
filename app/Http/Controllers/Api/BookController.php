<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\IndexBookRequest;
use App\Models\Book;
use App\Http\Requests\Api\UpdateBookRequest;
use App\Http\Requests\Api\StoreBookRequest;
use App\Http\Resources\BookResource;

class BookController extends Controller
{
    public function index(IndexBookRequest $request)
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
            ->paginate($request->integer('per_page', 10));
        return BookResource::collection($books);
    }

    public function store(StoreBookRequest $request)
    {
        $validated = $request->validated();

        $genres = $validated['genres'];
        unset($validated['genres']);

        $book = Book::create($validated);
        $book->genres()->sync($genres);

        return (new BookResource($book->load('genres')))
            ->response()
            ->setStatusCode(201);
    }
    
    public function show(Book $book)
    {
        $book->load(['genres', 'reviews.user']); 
        
        return new BookResource($book);
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
