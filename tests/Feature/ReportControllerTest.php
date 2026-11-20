<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Genre;

class ReportControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 未ログインユーザーはマイ読書レポートを閲覧できない
     */
    public function test_guest_is_redirected_to_login(): void
    {
        $response = $this->get('/reports');

        $response->assertRedirect('/login');
    }

    /**
     * ログインユーザーの基本統計が正しく集計される
     */
    public function test_basic_statistics_are_calculated_correctly(): void
    {
        $user = User::factory()->create();

        $books = Book::factory()->count(3)->create();

        foreach ([5, 3, 4] as $index => $rating) {
            Review::factory()->create([
                'user_id' => $user->id,
                'book_id' => $books[$index]->id,
                'rating' => $rating,
            ]);
        }

        $response = $this->actingAs($user)->get('/reports');

        $response->assertOk();

        $response->assertViewHas('totalReviews', 3);
        $response->assertViewHas('completedBooks', 3);
        $response->assertViewHas('averageRating', 4);
    }

    /**
     * 評価分布が正しく集計される
     */
    public function test_rating_distribution_is_calculated_correctly(): void
    {
        $user = User::factory()->create();

        $books = Book::factory()->count(3)->create();

        foreach ([5, 3, 4] as $index => $rating) {
            Review::factory()->create([
                'user_id' => $user->id,
                'book_id' => $books[$index]->id,
                'rating' => $rating,
            ]);
        }

        $response = $this->actingAs($user)->get('/reports');

        $response->assertOk();

        $response->assertViewHas('ratingDistribution', function ($distribution) {
            return $distribution->toArray() === [
                1 => 0,
                2 => 0,
                3 => 1,
                4 => 1,
                5 => 1,
            ];
        });
    }


    /**
     * 高評価書籍TOP5が正しく取得される
     */
    public function test_top_rated_books_are_calculated_correctly(): void
    {
        $user = User::factory()->create();

        $books = Book::factory()->count(7)->create();

        foreach ([5, 3, 4, 5, 2, 4, 5] as $index => $rating) {
            Review::factory()->create([
                'user_id' => $user->id,
                'book_id' => $books[$index]->id,
                'rating' => $rating,
            ]);
        }

        $response = $this->actingAs($user)->get('/reports');

        $response->assertOk();

        $response->assertViewHas('topRatedBooks', function ($topRatedBooks) {
            return $topRatedBooks->count() === 5
                && $topRatedBooks->pluck('rating')->toArray() === [
                    5, 5, 5, 4, 4,
                ];
        });
    }


    /**
     * ジャンル別評価傾向が正しく集計される
     */
    public function test_genre_ratings_are_calculated_correctly(): void
    {
        $user = User::factory()->create();

        $novel = Genre::factory()->create(['name' => '小説']);
        $technical = Genre::factory()->create(['name' => '技術書']);
        $business = Genre::factory()->create(['name' => 'ビジネス']);

        $ratings = [
            [$novel, 5],
            [$novel, 3],
            [$technical, 5],
            [$technical, 4],
            [$business, 3],
        ];

        foreach ($ratings as [$genre, $rating]) {
            $book = Book::factory()->create();
            $book->genres()->attach($genre->id);

            Review::factory()->create([
                'user_id' => $user->id,
                'book_id' => $book->id,
                'rating' => $rating,
            ]);
        }

        $response = $this->actingAs($user)->get('/reports');

        $response->assertOk();

        $response->assertViewHas('genreRatings', function ($genreRatings) {
            return $genreRatings->pluck('name')->toArray() === [
                '技術書',
                '小説',
                'ビジネス',
            ]
            && $genreRatings->pluck('average_rating')
                ->map(fn ($rating) => round((float) $rating, 1))
                ->toArray() === [4.5, 4.0, 3.0]
            && $genreRatings->pluck('review_count')
                ->map(fn ($count) => (int) $count)
                ->toArray() === [2, 2, 1];
        });
    }


    /**
     * レビューが0件でもレポートが正常に表示される
     */
    public function test_report_displays_correctly_without_reviews(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/reports');

        $response->assertOk();

        $response->assertViewHas('totalReviews', 0);
        $response->assertViewHas('completedBooks', 0);
        $response->assertViewHas('averageRating', 0);

        $response->assertViewHas('ratingDistribution', function ($distribution) {
            return $distribution->toArray() === [
                1 => 0,
                2 => 0,
                3 => 0,
                4 => 0,
                5 => 0,
            ];
        });

        $response->assertViewHas('topRatedBooks', function ($books) {
            return $books->isEmpty();
        });

        $response->assertViewHas('genreRatings', function ($genres) {
            return $genres->isEmpty();
        });

        $response->assertSee('高評価の書籍はまだありません。');
        $response->assertSee('ジャンル別の評価データはまだありません。');
    }


    /**
     * 他ユーザーのレビューが集計に含まれない
     */
    public function test_other_users_reviews_are_not_included(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $books = Book::factory()->count(5)->create();

        foreach ([5, 3] as $index => $rating) {
            Review::factory()->create([
                'user_id' => $user->id,
                'book_id' => $books[$index]->id,
                'rating' => $rating,
            ]);
        }

        foreach ([1, 1, 1] as $index => $rating) {
            Review::factory()->create([
                'user_id' => $otherUser->id,
                'book_id' => $books[$index + 2]->id,
                'rating' => $rating,
            ]);
        }

        $response = $this->actingAs($user)->get('/reports');

        $response->assertOk();

        $response->assertViewHas('totalReviews', 2);
        $response->assertViewHas('completedBooks', 2);
        $response->assertViewHas('averageRating', 4);

        $response->assertViewHas('ratingDistribution', function ($distribution) {
            return $distribution->toArray() === [
                1 => 0,
                2 => 0,
                3 => 1,
                4 => 0,
                5 => 1,
            ];
        });

        $response->assertViewHas('topRatedBooks', function ($reviews) use ($user) {
            return $reviews->count() === 1
                && $reviews->every(fn ($review) => $review->user_id === $user->id);
        });

        $response->assertViewHas('genreRatings', function ($genres) {
            return $genres->isEmpty();
        });
    }

}
