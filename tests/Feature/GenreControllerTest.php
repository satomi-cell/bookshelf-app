<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GenreControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_ログインユーザーはジャンルを登録できる(): void
    {
        // テスト用ユーザーを作成
        $user = User::factory()->create();

        // ログインしてジャンルを登録
        $response = $this->actingAs($user)
            ->post(route('genres.store'), [
                'name' => 'テストジャンル',
            ]);

        // ジャンル一覧画面へ戻ることを確認
        $response->assertRedirect(route('genres.index'));

        // 成功メッセージを確認
        $response->assertSessionHas(
            'success',
            'ジャンルを登録しました。'
        );

        // データベースにジャンルが登録されたことを確認
        $this->assertDatabaseHas('genres', [
            'name' => 'テストジャンル',
        ]);
    }

        public function test_同じ名前のジャンルは重複登録できない(): void
    {
        // テスト用ユーザーを作成
        $user = User::factory()->create();

        // 既存のジャンルを登録
        \App\Models\Genre::create([
            'name' => '小説',
        ]);

        // 同じ名前のジャンルを登録しようとする
        $response = $this->actingAs($user)
            ->post(route('genres.store'), [
                'name' => '小説',
            ]);

        // バリデーションエラーを確認
        $response->assertSessionHasErrors(['name']);

        // 同じ名前のジャンルが1件だけであることを確認
        $this->assertDatabaseCount('genres', 1);
    }

        public function test_ログインユーザーはジャンルを更新できる(): void
    {
        // テスト用ユーザーを作成
        $user = User::factory()->create();

        // 更新前のジャンルを作成
        $genre = \App\Models\Genre::create([
            'name' => '小説',
        ]);

        // ジャンル名を更新
        $response = $this->actingAs($user)
            ->put(route('genres.update', $genre), [
                'name' => '文学',
            ]);

        // ジャンル一覧画面へのリダイレクトを確認
        $response->assertRedirect(route('genres.index'));

        // 成功メッセージを確認
        $response->assertSessionHas(
            'success',
            'ジャンルを更新しました。'
        );

        // 更新後のジャンル名が保存されていることを確認
        $this->assertDatabaseHas('genres', [
            'id' => $genre->id,
            'name' => '文学',
        ]);
    }

        public function test_書籍に紐づいていないジャンルは削除できる(): void
    {
        // テスト用ユーザーを作成
        $user = User::factory()->create();

        // 書籍に紐づいていないジャンルを作成
        $genre = \App\Models\Genre::create([
            'name' => 'テストジャンル',
        ]);

        // ログインしてジャンルを削除
        $response = $this->actingAs($user)
            ->delete(route('genres.destroy', $genre));

        // ジャンル一覧画面へのリダイレクトを確認
        $response->assertRedirect(route('genres.index'));

        // 成功メッセージを確認
        $response->assertSessionHas(
            'success',
            'ジャンルを削除しました。'
        );

        // データベースから削除されたことを確認
        $this->assertDatabaseMissing('genres', [
            'id' => $genre->id,
        ]);
    }

        public function test_書籍に紐づいているジャンルは削除できない(): void
    {
        // テスト用ユーザーを作成
        $user = User::factory()->create();

        // ジャンルを作成
        $genre = \App\Models\Genre::create([
            'name' => '小説',
        ]);

        // 書籍を作成してジャンルに紐づける
        $book = \App\Models\Book::factory()->create([
            'user_id' => $user->id,
        ]);

        $book->genres()->attach($genre->id);

        // 紐づいているジャンルを削除しようとする
        $response = $this->actingAs($user)
            ->delete(route('genres.destroy', $genre));

        // ジャンル一覧画面へ戻ることを確認
        $response->assertRedirect(route('genres.index'));

        // エラーメッセージを確認
        $response->assertSessionHas(
            'error',
            '書籍に紐づいているジャンルは削除できません。'
        );

        // ジャンルが削除されずに残っていることを確認
        $this->assertDatabaseHas('genres', [
            'id' => $genre->id,
            'name' => '小説',
        ]);
    }

        public function test_ログインユーザーはジャンル一覧を表示できる(): void
    {
        // テスト用ユーザーを作成
        $user = User::factory()->create();

        // ジャンルを作成
        \App\Models\Genre::create([
            'name' => '小説',
        ]);

        // ログインしてジャンル一覧を表示
        $response = $this->actingAs($user)
            ->get(route('genres.index'));

        // 正常に表示されることを確認
        $response->assertStatus(200);

        // 作成したジャンル名が表示されることを確認
        $response->assertSee('小説');
    }

        public function test_ログインユーザーはジャンル詳細を表示できる(): void
    {
        // テスト用ユーザーを作成
        $user = User::factory()->create();

        // ジャンルを作成
        $genre = \App\Models\Genre::create([
            'name' => '小説',
        ]);

        // ログインしてジャンル詳細画面を表示
        $response = $this->actingAs($user)
            ->get(route('genres.show', $genre));

        // 正常に表示されることを確認
        $response->assertStatus(200);

        // ジャンル名が表示されることを確認
        $response->assertSee('小説');
    }

        public function test_ジャンル詳細の書籍一覧は10件ずつ表示される(): void
    {
        // テスト用ユーザーを作成
        $user = User::factory()->create();

        // ジャンルを作成
        $genre = \App\Models\Genre::create([
            'name' => '小説',
        ]);

        // 書籍を11冊作成
        $books = \App\Models\Book::factory()
            ->count(11)
            ->create([
                'user_id' => $user->id,
            ]);

        // 11冊すべてをジャンルに紐づける
        $genre->books()->attach($books->pluck('id'));

        // 1ページ目を表示
        $response = $this->actingAs($user)
            ->get(route('genres.show', $genre));

        $response->assertStatus(200);
        $response->assertViewHas('books', function ($books) {
            return $books->count() === 10
                && $books->total() === 11;
        });

        // 2ページ目を表示
        $response = $this->actingAs($user)
            ->get(route('genres.show', [
                'genre' => $genre,
                'page' => 2,
            ]));

        $response->assertStatus(200);
        $response->assertViewHas('books', function ($books) {
            return $books->count() === 1
                && $books->total() === 11;
        });
    }

        public function test_未ログインユーザーはジャンル一覧にアクセスできない(): void
    {
        // ログインせずにジャンル一覧へアクセス
        $response = $this->get(route('genres.index'));

        // ログイン画面へリダイレクトされることを確認
        $response->assertRedirect(route('login'));
    }
}