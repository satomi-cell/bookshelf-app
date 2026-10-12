<?php

namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\Book;

class BookApiTest extends TestCase
{
    use RefreshDatabase;
    /**
     * A basic feature test example.
     */
    public function test_書籍一覧APIを取得できる(): void
    {         
        $response = $this->getJson('/api/v1/books');

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'data',
                     'links',
                     'meta',
                 ]);
    }

    public function test_登録された書籍を一覧APIで取得できる(): void
    {
        $book = Book::factory()->create();

        $response = $this->getJson('/api/v1/books');

        $response->assertStatus(200)
                 ->assertJsonFragment([
                     'title' => $book->title,
                 ]);
    }

    public function test_書籍詳細APIを取得できる(): void
    {
        $book = Book::factory()->create();

        $response = $this->getJson("/api/v1/books/{$book->id}");

        $response->assertStatus(200)
                 ->assertJsonPath('data.id', $book->id)
                 ->assertJsonPath('data.title', $book->title);
    }
    
    public function test_存在しない書籍は404を返す(): void
    {
        $response = $this->getJson('/api/v1/books/99999');

        $response->assertStatus(404);
    }

    public function test_書籍詳細APIでレビュー情報を取得できる(): void
    {
        $book = Book::factory()->create();

         $review = \App\Models\Review::factory()->create([
            'book_id' => $book->id,
            'rating' => 5,
            'comment' => 'とても面白い本でした。',
        ]);

        $response = $this->getJson("/api/v1/books/{$book->id}");

        $response->assertStatus(200)
                 ->assertJsonPath('data.reviews.0.user_name', $review->user->name)
                 ->assertJsonPath('data.reviews.0.rating', 5)
                 ->assertJsonPath('data.reviews.0.comment', 'とても面白い本でした。')
                 ->assertJsonPath('data.reviews.0.created_at', $review->created_at->toJSON());
    }

}
