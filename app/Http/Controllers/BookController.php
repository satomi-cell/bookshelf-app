<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\Genre;
use App\Http\Requests\StoreBookRequest;
use App\Http\Requests\UpdateBookRequest;
use App\Http\Resources\BookResource;
use Illuminate\Support\Facades\Http;

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
   
   public function fetchByIsbn(string $isbn)
   {
       // ISBNが数字13桁であることを確認
       if (!preg_match('/^[0-9]{13}$/', $isbn)) {
           return response()->json([
               'error' => 'ISBNは13桁の数字で入力してください。',
           ], 422);
       }

       try {
           // Google Books APIから書籍情報を取得
          $response = Http::timeout(10)->get(
              'https://www.googleapis.com/books/v1/volumes',
              [
                 'q' => 'isbn:' . $isbn,
                 'key' => config('services.google_books.api_key'),
              ]
          ); 
           if ($response->status() === 429) {
              return response()->json([
                  'error' => 'Google Books APIの利用上限に達しています。',
              ], 503);
           }

           if (!$response->successful()) {
              return response()->json([
                  'error' => '書籍情報の取得に失敗しました。',
               ], 502);
           }
           $items = $response->json('items', []);

           if (empty($items)) {
               return response()->json([
                   'error' => '該当する書籍が見つかりませんでした。',
               ], 404);
           }

           $info = $items[0]['volumeInfo'] ?? [];

           // 出版日は年月日がそろっている場合のみ設定
           $publishedDate = $info['publishedDate'] ?? null;

           if ($publishedDate &&
               !preg_match('/^\d{4}-\d{2}-\d{2}$/', $publishedDate)) {
               $publishedDate = null;
           }

           return response()->json([
               'title' => $info['title'] ?? '',
               'author' => implode(', ', $info['authors'] ?? []),
               'description' => $info['description'] ?? '',
               'published_date' => $publishedDate,
               'image_url' => $info['imageLinks']['thumbnail'] ?? '',
           ]);

       } catch (\Illuminate\Http\Client\ConnectionException $e) {
           return response()->json([
               'error' => 'Google Books APIとの通信に失敗しました。',
           ], 502);
       }
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
