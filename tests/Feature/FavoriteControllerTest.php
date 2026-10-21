<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FavoriteControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_ログインユーザーはお気に入りを登録できる(): void
    {
        // テスト用ユーザーを作成
        $user = User::factory()->create();

        // お気に入り登録する書籍を作成
        $book = Book::create([
            'user_id' => $user->id,
            'title' => 'お気に入りテスト書籍',
            'author' => 'テスト著者',
            'isbn' => '9784000000099',
            'published_date' => '2026-01-01',
        ]);

        // ログインしてお気に入り登録
        $response = $this->actingAs($user)
            ->from(route('books.show', $book))
            ->post(route('favorites.toggle', $book));

        // 元の書籍詳細画面に戻ること
        $response->assertRedirect(route('books.show', $book));

        // お気に入りがデータベースに登録されていること
        $this->assertDatabaseHas('favorites', [
            'user_id' => $user->id,
            'book_id' => $book->id,
        ]);
    }

    
    public function test_ログインユーザーはお気に入りを解除できる(): void
    {
        // テスト用ユーザーを作成
        $user = User::factory()->create();

        // テスト用書籍を作成
        $book = Book::create([
            'user_id' => $user->id,
            'title' => 'お気に入り解除テスト書籍',
            'author' => 'テスト著者',
            'isbn' => '9784000000099',
            'published_date' => '2026-01-01',
        ]);

        // あらかじめお気に入りに登録
        $user->favoriteBooks()->attach($book->id);

        // お気に入り解除の操作
        $response = $this->actingAs($user)
            ->from(route('books.show', $book))
            ->post(route('favorites.toggle', $book));

        // 書籍詳細画面へ戻ること
        $response->assertRedirect(route('books.show', $book));

        // お気に入りが削除されていること
        $this->assertDatabaseMissing('favorites', [
            'user_id' => $user->id,
            'book_id' => $book->id,
        ]);
    }

    
    public function test_自分のお気に入り書籍だけが一覧に表示される(): void
    {
        // テスト用ユーザーを2人作成
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        // 自分のお気に入りにする書籍
        $myBook = Book::create([
            'user_id' => $user->id,
            'title' => '自分のお気に入り書籍',
            'author' => 'テスト著者A',
            'isbn' => '9784000000099',
            'published_date' => '2026-01-01',
        ]);

        // 他のユーザーがお気に入りにする書籍
        $otherBook = Book::create([
            'user_id' => $otherUser->id,
            'title' => '他人のお気に入り書籍',
            'author' => 'テスト著者B',
            'isbn' => '9784000000082',
            'published_date' => '2026-01-02',
        ]);

        // それぞれのお気に入りを登録
        $user->favoriteBooks()->attach($myBook->id);
        $otherUser->favoriteBooks()->attach($otherBook->id);

        // 自分のアカウントでお気に入り一覧を開く
        $response = $this->actingAs($user)
            ->get(route('favorites.index'));

        // 一覧画面が正常に表示されること
        $response->assertStatus(200);

        // 自分のお気に入りだけが表示されること
        $response->assertSee('自分のお気に入り書籍');
        $response->assertDontSee('他人のお気に入り書籍');

        // 書籍詳細画面へのリンクが存在すること
        $response->assertSee(route('books.show', $myBook));
    }


}
