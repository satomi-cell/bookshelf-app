<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Genre;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_他人の書籍を更新できない(): void
    {
        // 書籍の登録者を作成
        $owner = User::factory()->create();

        // 別のユーザーを作成
        $otherUser = User::factory()->create();

        // テスト用ジャンルを作成
        $genre = Genre::create(['name' => 'テストジャンル']);

        // 登録者の書籍を作成
        $book = Book::factory()->create([
            'user_id' => $owner->id,
        ]);

        // 別のユーザーでログインして更新を試みる
        $response = $this->actingAs($otherUser)
            ->put(route('books.update', $book), [
                'title' => '不正に変更された書籍名',
                'author' => $book->author,
                'isbn' => $book->isbn,
                'published_date' => $book->published_date,
                'description' => $book->description,
                'image_url' => $book->image_url,
                'genres' => [$genre->id],
            ]);
        // 403エラーになることを確認
        $response->assertStatus(403);

        // 書籍名が変更されていないことを確認
        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'title' => $book->title,
        ]);
    }

        public function test_他人の書籍を削除できない(): void
    {
        // 書籍の登録者を作成
        $owner = User::factory()->create();

        // 別のユーザーを作成
        $otherUser = User::factory()->create();

        // 登録者の書籍を作成
        $book = Book::factory()->create([
            'user_id' => $owner->id,
        ]);

        // 別のユーザーでログインして削除を試みる
        $response = $this->actingAs($otherUser)
            ->delete(route('books.destroy', $book));

        // 403エラーになることを確認
        $response->assertStatus(403);

        // 書籍が削除されていないことを確認
        $this->assertDatabaseHas('books', [
            'id' => $book->id,
        ]);
    }

        public function test_他人の書籍編集画面を開けない(): void
    {
        // 書籍の登録者を作成
        $owner = User::factory()->create();

        // 別のユーザーを作成
        $otherUser = User::factory()->create();

        // 登録者の書籍を作成
        $book = Book::factory()->create([
            'user_id' => $owner->id,
        ]);

        // 別のユーザーで編集画面を開こうとする
        $response = $this->actingAs($otherUser)
            ->get(route('books.edit', $book));

        // 403エラーになることを確認
        $response->assertStatus(403);
    }
    
}
