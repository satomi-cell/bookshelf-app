<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Genre;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_ログインユーザーは書籍を登録できる(): void
    {
        // テスト用のユーザーとジャンルを作成
        $user = User::factory()->create();
        $genre = Genre::create([
            'name' => 'テストジャンル',
        ]);

        // ログインした状態で書籍を登録
        $response = $this->actingAs($user)->post(route('books.store'), [
            'title' => 'テスト書籍',
            'author' => 'テスト著者',
            'isbn' => '9784000000099',
            'published_date' => '2026-01-01',
            'description' => 'テスト用の説明です。',
            'image_url' => 'https://example.com/book.jpg',
            'genres' => [$genre->id],
        ]);

        // 書籍一覧へ移動し、成功メッセージが表示されること
        $response->assertRedirect(route('books.index'));
        $response->assertSessionHas('success', '書籍を登録しました。');

        // 書籍がデータベースに保存されていること
        $this->assertDatabaseHas('books', [
            'user_id' => $user->id,
            'title' => 'テスト書籍',
            'isbn' => '9784000000099',
        ]);

        // 書籍とジャンルが紐づいていること
        $book = Book::where('isbn', '9784000000099')->firstOrFail();

        $this->assertDatabaseHas('book_genre', [
            'book_id' => $book->id,
            'genre_id' => $genre->id,
        ]);
    }

    
    public function test_タイトルが未入力の場合は書籍を登録できない(): void
    {
        $user = User::factory()->create();

        $genre = Genre::create([
            'name' => 'テストジャンル',
        ]);

        $response = $this->actingAs($user)->post(route('books.store'), [
            'title' => '',
            'author' => 'テスト著者',
            'isbn' => '9784000000099',
            'published_date' => '2026-01-01',
            'genres' => [$genre->id],
        ]);

        $response->assertSessionHasErrors(['title']);

        $this->assertDatabaseMissing('books', [
            'isbn' => '9784000000099',
        ]);
    }

    
    public function test_ログインユーザーは自分の書籍を更新できる(): void
    {
        $user = User::factory()->create();

        $genre = Genre::create([
            'name' => 'テストジャンル',
        ]);

        // 自分の書籍を作成
        $book = Book::create([
            'user_id' => $user->id,
            'title' => '更新前の書籍',
            'author' => 'テスト著者',
            'isbn' => '9784000000099',
            'published_date' => '2026-01-01',
        ]);

        // 書籍を更新
        $response = $this->actingAs($user)->put(
            route('books.update', $book),
            [
                'title' => '更新後の書籍',
                'author' => '更新後の著者',
                'isbn' => '9784000000099',
                'published_date' => '2026-02-01',
                'genres' => [$genre->id],
            ]
        );

        // 書籍詳細画面へ移動すること
        $response->assertRedirect(route('books.show', $book));

        // 成功メッセージを確認
        $response->assertSessionHas('success', '書籍を更新しました。');

        // 更新内容が保存されていること
        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'title' => '更新後の書籍',
            'author' => '更新後の著者',
        ]);

        // ジャンルが紐づいていること
        $this->assertDatabaseHas('book_genre', [
            'book_id' => $book->id,
            'genre_id' => $genre->id,
        ]);
    }

    
    public function test_ログインユーザーは自分の書籍を削除できる(): void
    {
        // テスト用ユーザーを作成
        $user = User::factory()->create();

        // ユーザーが所有する書籍を作成
        $book = Book::create([
            'user_id' => $user->id,
            'title' => '削除する書籍',
            'author' => 'テスト著者',
            'isbn' => '9784000000099',
            'published_date' => '2026-01-01',
        ]);

        // ログインして書籍を削除
        $response = $this->actingAs($user)->delete(
            route('books.destroy', $book)
        );

        // 書籍一覧へリダイレクトされること
        $response->assertRedirect(route('books.index'));

        // 成功メッセージを確認
        $response->assertSessionHas(
            'success',
            '書籍を削除しました。'
        );

        // データベースから削除されていること
        $this->assertDatabaseMissing('books', [
            'id' => $book->id,
        ]);
    }


    public function test_書籍一覧を表示できる(): void
    {
        $user = User::factory()->create();

        $book = $user->books()->create([
            'title' => '一覧表示テスト書籍',
            'author' => 'テスト著者',
            'isbn' => '9784000000001',
            'published_date' => '2026-01-01',
        ]);

        $response = $this->get(route('books.index'));

        $response->assertOk();
        $response->assertViewIs('books.index');
        $response->assertSee($book->title);
    }

    public function test_書籍名で検索できる(): void
    {
        $user = User::factory()->create();

        $user->books()->create([
            'title' => 'Laravel入門',
            'author' => 'テスト著者',
            'isbn' => '9784000000001',
            'published_date' => '2026-01-01',
        ]);

        $user->books()->create([
            'title' => 'Python入門',
            'author' => 'テスト著者',
            'isbn' => '9784000000002',
            'published_date' => '2026-01-01',
        ]);

        $response = $this->get(route('books.index', [
            'keyword' => 'Laravel',
        ]));

        $response->assertOk();
        $response->assertSee('Laravel入門');
        $response->assertDontSee('Python入門');
    }

    public function test_著者名で検索できる(): void
    {
        $user = User::factory()->create();

        $user->books()->create([
            'title' => '検索対象書籍',
            'author' => '山田太郎',
            'isbn' => '9784000000001',
            'published_date' => '2026-01-01',
        ]);

        $user->books()->create([
            'title' => '検索対象外書籍',
            'author' => '鈴木花子',
            'isbn' => '9784000000002',
            'published_date' => '2026-01-01',
        ]);

        $response = $this->get(route('books.index', [
            'keyword' => '山田太郎',
        ]));

        $response->assertOk();
        $response->assertSee('検索対象書籍');
        $response->assertDontSee('検索対象外書籍');
    }

    public function test_ジャンルで絞り込める(): void
    {
        $user = User::factory()->create();

        $genre1 = Genre::create(['name' => '技術書']);
        $genre2 = Genre::create(['name' => '小説']);

        $book1 = $user->books()->create([
            'title' => '技術書テスト',
            'author' => 'テスト著者',
            'isbn' => '9784000000001',
            'published_date' => '2026-01-01',
        ]);

        $book2 = $user->books()->create([
            'title' => '小説テスト',
            'author' => 'テスト著者',
            'isbn' => '9784000000002',
            'published_date' => '2026-01-01',
        ]);

        $book1->genres()->attach($genre1->id);
        $book2->genres()->attach($genre2->id);

        $response = $this->get(route('books.index', [
            'genre' => $genre1->id,
        ]));

        $response->assertOk();
        $response->assertSee('技術書テスト');
        $response->assertDontSee('小説テスト');
    }

    public function test_書籍登録画面を表示できる(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->get(route('books.create'));

        $response->assertOk();
        $response->assertViewIs('books.create');
        $response->assertViewHas('genres');
    }

    public function test_書籍詳細画面を表示できる(): void
    {
        $user = User::factory()->create();

        $book = $user->books()->create([
            'title' => '詳細表示テスト書籍',
            'author' => 'テスト著者',
            'isbn' => '9784000000001',
            'published_date' => '2026-01-01',
        ]);

        $response = $this->get(route('books.show', $book));

        $response->assertOk();
        $response->assertViewIs('books.show');
        $response->assertSee($book->title);
    }

    public function test_自分の書籍の編集画面を表示できる(): void
    {
        $user = User::factory()->create();

        $book = $user->books()->create([
            'title' => '編集画面テスト書籍',
            'author' => 'テスト著者',
            'isbn' => '9784000000001',
            'published_date' => '2026-01-01',
        ]);

        $response = $this->actingAs($user)
            ->get(route('books.edit', $book));

        $response->assertOk();
        $response->assertViewIs('books.edit');
        $response->assertViewHas('book');
        $response->assertViewHas('genres');
    }

}
