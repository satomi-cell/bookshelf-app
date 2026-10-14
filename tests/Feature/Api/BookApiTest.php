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

    public function test_書籍詳細APIでジャンル情報を取得できる(): void
    {    
        $book = Book::factory()->create();

        $genre = \App\Models\Genre::factory()->create([
            'name' => 'ミステリー',
        ]);

        $book->genres()->attach($genre->id);

        $response = $this->getJson("/api/v1/books/{$book->id}");

        $response->assertStatus(200)
                 ->assertJsonPath('data.genres.0.id', $genre->id)
                 ->assertJsonPath('data.genres.0.name', 'ミステリー');
    }
    
    public function test_書籍一覧APIでキーワード検索ができる(): void
    {
        Book::factory()->create([
            'title' => 'Laravel入門',
        ]);

        Book::factory()->create([
            'title' => 'PHP基礎',
        ]);

        $response = $this->getJson('/api/v1/books?keyword=Laravel');

        $response->assertStatus(200)
                 ->assertJsonCount(1, 'data')
                 ->assertJsonPath('data.0.title', 'Laravel入門');
    }

    public function test_書籍一覧APIでジャンル絞り込みができる(): void
    {
        $genre = \App\Models\Genre::factory()->create([
            'name' => 'ミステリー',
        ]);

        $book1 = Book::factory()->create([
            'title' => 'ミステリー小説',
        ]);

        $book2 = Book::factory()->create([
            'title' => '料理の本',
        ]);

        $book1->genres()->attach($genre->id);

        $response = $this->getJson("/api/v1/books?genre={$genre->id}");

        $response->assertStatus(200)
                 ->assertJsonCount(1, 'data')
                 ->assertJsonPath('data.0.title', 'ミステリー小説');
    }

    public function test_書籍一覧APIでページネーションができる(): void
    {
        Book::factory()->count(11)->create();

        $response = $this->getJson('/api/v1/books?page=1');

        $response->assertStatus(200)
                 ->assertJsonCount(10, 'data')
                 ->assertJsonPath('meta.total', 11)
                 ->assertJsonPath('meta.last_page', 2);

        $response = $this->getJson('/api/v1/books?page=2');

        $response->assertStatus(200)
                 ->assertJsonCount(1, 'data')
                 ->assertJsonPath('meta.current_page', 2);
    }

    public function test_書籍一覧APIでレビュー平均評価と件数を取得できる(): void
    {
        $book = Book::factory()->create();

        \App\Models\Review::factory()->create([
            'book_id' => $book->id,
            'rating' => 4,
        ]);

        \App\Models\Review::factory()->create([
            'book_id' => $book->id,
            'rating' => 2,
        ]);

        $response = $this->getJson('/api/v1/books');

        $response->assertStatus(200)
                 ->assertJsonPath('data.0.reviews_count', 2)
                 ->assertJsonPath('data.0.average_rating', 3);
    }

    public function test_書籍更新APIで書籍情報を更新できる(): void
    {
        $book = Book::factory()->create();

        $genre = \App\Models\Genre::factory()->create();

        $response = $this->putJson("/api/v1/books/{$book->id}", [
            'title' => '更新後の書籍タイトル',
            'author' => '更新後の著者',
            'isbn' => $book->isbn,
            'published_date' => '2026-01-01',
            'description' => '更新後の説明',
            'image_url' => null,
            'genres' => [$genre->id],
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'title' => '更新後の書籍タイトル',
            'author' => '更新後の著者',
        ]);

        $this->assertDatabaseHas('book_genre', [
            'book_id' => $book->id,
            'genre_id' => $genre->id,
        ]);
    }
    
    public function test_書籍削除APIで書籍を削除できる(): void
    {
        $book = Book::factory()->create();

        $response = $this->deleteJson("/api/v1/books/{$book->id}");

        $response->assertStatus(200)
                 ->assertJson([
                     'message' => '書籍を削除しました。',
                 ]);

        $this->assertDatabaseMissing('books', [
            'id' => $book->id,
        ]);
    }

    public function test_書籍更新APIで書籍名が空欄の場合はエラーになる(): void
    {
        $book = Book::factory()->create();
        $genre = \App\Models\Genre::factory()->create();

        $response = $this->putJson("/api/v1/books/{$book->id}", [
            'title' => '',
            'author' => 'テスト著者',
            'isbn' => $book->isbn,
            'published_date' => '2026-01-01',
            'description' => 'テスト説明',
            'image_url' => null,
            'genres' => [$genre->id],
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['title']);

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'title' => $book->title,
        ]);
    }

    public function test_書籍更新APIでISBNが重複するとエラーになる(): void
    {
        $book = Book::factory()->create();
        $otherBook = Book::factory()->create();
        $genre = \App\Models\Genre::factory()->create();

        $response = $this->putJson("/api/v1/books/{$book->id}", [
            'title' => '更新後のタイトル',
            'author' => 'テスト著者',
            'isbn' => $otherBook->isbn,
            'published_date' => '2026-01-01',
            'description' => 'テスト説明',
            'image_url' => null,
            'genres' => [$genre->id],
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['isbn']);

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'isbn' => $book->isbn,
        ]);
    }

}
