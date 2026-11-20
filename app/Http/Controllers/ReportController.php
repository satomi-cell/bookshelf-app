<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function index()
    {
        // ログインユーザーのレビューを取得
        $reviews = auth()->user()->reviews();

        // 総レビュー数
        $totalReviews = (clone $reviews)->count();

        // 読了冊数（ユニーク書籍数）
        $completedBooks = (clone $reviews)->distinct()->count('book_id');

        // 平均評価点
        $averageRating = (clone $reviews)->avg('rating') ?? 0;

        // 評価分布（星1〜5のレビュー件数）
        $ratingDistribution = (clone $reviews)
            ->selectRaw('rating, COUNT(*) as total')
            ->groupBy('rating')
            ->pluck('total', 'rating');

        $ratingDistribution = collect(range(1, 5))
            ->mapWithKeys(fn ($rating) => [
                $rating => (int) ($ratingDistribution[$rating] ?? 0),
            ]);
            
        // 高評価書籍TOP5（評価4以上）
        $topRatedBooks = auth()->user()->reviews()
            ->with('book')
            ->where('rating', '>=', 4)
            ->orderByDesc('rating')
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        // ジャンル別評価傾向TOP5
        $genreRatings = DB::table('reviews')
            ->join('book_genre', 'reviews.book_id', '=', 'book_genre.book_id')
            ->join('genres', 'book_genre.genre_id', '=', 'genres.id')
            ->where('reviews.user_id', auth()->id())
            ->select(
                'genres.id',
                'genres.name'
            )
            ->selectRaw('AVG(reviews.rating) as average_rating')
            ->selectRaw('COUNT(reviews.id) as review_count')
            ->groupBy('genres.id', 'genres.name')
            ->orderByDesc('average_rating')
            ->orderByDesc('review_count')
            ->limit(5)
            ->get();

        // 集計結果をレポート画面に渡す
        return view('reports.index', compact(
            'totalReviews',
            'completedBooks',
            'averageRating',
            'ratingDistribution',
            'topRatedBooks',
            'genreRatings'
        ));
    }
}