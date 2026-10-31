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
      $query = Book::with('genres')
          ->withAvg('reviews', 'rating');

      // キーワード検索（タイトル・著者名）
      if (request()->filled('keyword')) {
          $keyword = request('keyword');

          $query->where(function ($q) use ($keyword) {
              $q->where('title', 'like', '%' . $keyword . '%')
                ->orWhere('author', 'like', '%' . $keyword . '%');
          });
      }

      // ジャンルによる絞り込み
      if (request()->filled('genre')) {
          $genreId = request('genre');

          $query->whereHas('genres', function ($q) use ($genreId) {
              $q->where('genres.id', $genreId);
          });
      }

      // 並び替え
      $sort = request('sort', 'newest');

      switch ($sort) {
          case 'oldest':
              $query->orderBy('created_at')->orderBy('id');
              break;

          case 'rating':
              $query->orderByRaw('reviews_avg_rating IS NULL')
                    ->orderByDesc('reviews_avg_rating')
                    ->orderByDesc('id');
               break;

          case 'title':
              $query->orderBy('title')->orderBy('id');
              break;

          case 'newest':
          default:
              $query->orderByDesc('created_at')->orderByDesc('id');
              break;
       }

       $books = $query->paginate(10)
          ->withQueryString();
       $genres = Genre::all();

      return view('books.index', compact('books', 'genres'));
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
