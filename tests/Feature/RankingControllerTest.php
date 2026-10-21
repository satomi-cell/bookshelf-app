<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RankingControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_平均評価の高い書籍がランキング上位に表示される(): void
    {
        // テスト用ユーザーを作成
        $user = User::factory()->create();

        // 評価5点の書籍
        $highBook = Book::create([
            'user_id' => $user->id,
            'title' => '高評価の書籍',
            'author' => 'テスト著者',
            'isbn' => '9784000000099',
            'published_date' => '2026-01-01',
        ]);

        // 評価3点の書籍
        $lowBook = Book::create([
            'user_id' => $user->id,
            'title' => '低評価の書籍',
            'author' => 'テスト著者',
            'isbn' => '9784000000082',
            'published_date' => '2026-01-01',
        ]);

        // それぞれにレビューを登録
        Review::create([
            'user_id' => $user->id,
            'book_id' => $highBook->id,
            'rating' => 5,
            'comment' => 'とても良い書籍です',
        ]);

        Review::create([
            'user_id' => $user->id,
            'book_id' => $lowBook->id,
            'rating' => 3,
            'comment' => '普通の書籍です',
        ]);

        // ランキング画面を開く
        $response = $this->get(route('ranking.index'));

        // 正常に表示されることを確認
        $response->assertStatus(200);

        // 高評価の書籍が低評価の書籍より先に表示されること
        $response->assertSeeInOrder([
            '高評価の書籍',
            '低評価の書籍',
        ]);
    }

    
    public function test_レビューがない書籍はランキングに表示されない(): void
    {
        // テスト用ユーザーを作成
        $user = User::factory()->create();

        // レビューがある書籍
        $reviewedBook = Book::create([
            'user_id' => $user->id,
            'title' => 'レビューありの書籍',
            'author' => 'テスト著者',
            'isbn' => '9784000000099',
            'published_date' => '2026-01-01',
        ]);

        // レビューがない書籍
        $noReviewBook = Book::create([
            'user_id' => $user->id,
            'title' => 'レビューなしの書籍',
            'author' => 'テスト著者',
            'isbn' => '9784000000082',
            'published_date' => '2026-01-01',
        ]);

        // 1冊目にだけレビューを登録
        Review::create([
            'user_id' => $user->id,
            'book_id' => $reviewedBook->id,
            'rating' => 5,
            'comment' => 'おすすめの書籍です',
        ]);

        // ランキング画面を開く
        $response = $this->get(route('ranking.index'));

        // 正常に表示されること
        $response->assertStatus(200);

        // レビューがある書籍は表示される
        $response->assertSee('レビューありの書籍');

        // レビューがない書籍は表示されない
        $response->assertDontSee('レビューなしの書籍');
    }

}
