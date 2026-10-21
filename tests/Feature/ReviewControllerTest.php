<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReviewControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_ログインユーザーはレビューを投稿できる(): void
    {
        // テスト用ユーザーを作成
        $user = User::factory()->create();

        // レビュー対象の書籍を作成
        $book = Book::create([
            'user_id' => $user->id,
            'title' => 'レビューテスト書籍',
            'author' => 'テスト著者',
            'isbn' => '9784000000099',
            'published_date' => '2026-01-01',
        ]);

        // ログインしてレビューを投稿
        $response = $this->actingAs($user)
            ->post(route('reviews.store', $book), [
                'rating' => 5,
                'comment' => 'とても面白い書籍でした。',
            ]);

        // 書籍詳細画面へ戻ることを確認
        $response->assertRedirect(route('books.show', $book));

        // 成功メッセージを確認
        $response->assertSessionHas(
            'success',
            'レビューを投稿しました。'
        );

        // データベースにレビューが保存されていることを確認
        $this->assertDatabaseHas('reviews', [
            'user_id' => $user->id,
            'book_id' => $book->id,
            'rating' => 5,
            'comment' => 'とても面白い書籍でした。',
        ]);
    }

        public function test_投稿者本人はレビューを更新できる(): void
    {
        // テスト用ユーザーを作成
        $user = User::factory()->create();

        // テスト用書籍を作成
        $book = Book::create([
            'user_id' => $user->id,
            'title' => 'レビュー更新テスト書籍',
            'author' => 'テスト著者',
            'isbn' => '9784000000100',
            'published_date' => '2026-01-01',
        ]);

        // 更新対象のレビューを作成
        $review = \App\Models\Review::create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'rating' => 3,
            'comment' => '更新前のコメント',
        ]);

        // ログインしてレビューを更新
        $response = $this->actingAs($user)
            ->put(route('reviews.update', $review), [
                'rating' => 5,
                'comment' => '更新後のコメント',
            ]);

        // 書籍詳細画面へ戻ることを確認
        $response->assertRedirect(route('books.show', $book));

        // データベースの更新内容を確認
        $this->assertDatabaseHas('reviews', [
            'id' => $review->id,
            'user_id' => $user->id,
            'book_id' => $book->id,
            'rating' => 5,
            'comment' => '更新後のコメント',
        ]);
    }

        public function test_他人のレビューは更新できない(): void
    {
        // レビュー投稿者と別のユーザーを作成
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();

        // テスト用書籍を作成
        $book = Book::create([
            'user_id' => $owner->id,
            'title' => 'レビュー認可テスト書籍',
            'author' => 'テスト著者',
            'isbn' => '9784000000101',
            'published_date' => '2026-01-01',
        ]);

        // 投稿者のレビューを作成
        $review = \App\Models\Review::create([
            'user_id' => $owner->id,
            'book_id' => $book->id,
            'rating' => 3,
            'comment' => '元のコメント',
        ]);

        // 別のユーザーでログインして更新を試みる
        $response = $this->actingAs($otherUser)
            ->put(route('reviews.update', $review), [
                'rating' => 5,
                'comment' => '不正に変更したコメント',
            ]);

        // 更新が禁止されることを確認
        $response->assertForbidden();

        // 元のレビューが変更されていないことを確認
        $this->assertDatabaseHas('reviews', [
            'id' => $review->id,
            'rating' => 3,
            'comment' => '元のコメント',
        ]);
    }

        public function test_投稿者本人はレビューを削除できる(): void
    {
        // テスト用ユーザーを作成
        $user = User::factory()->create();

        // テスト用書籍を作成
        $book = Book::create([
            'user_id' => $user->id,
            'title' => 'レビュー削除テスト書籍',
            'author' => 'テスト著者',
            'isbn' => '9784000000102',
            'published_date' => '2026-01-01',
        ]);

        // 削除対象のレビューを作成
        $review = \App\Models\Review::create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'rating' => 4,
            'comment' => '削除するレビュー',
        ]);

        // ログインしてレビューを削除
        $response = $this->actingAs($user)
            ->delete(route('reviews.destroy', $review));

        // 書籍詳細画面へ戻ることを確認
        $response->assertRedirect(route('books.show', $book));

        // 成功メッセージを確認
        $response->assertSessionHas(
            'success',
            'レビューを削除しました。'
        );

        // データベースからレビューが削除されたことを確認
        $this->assertDatabaseMissing('reviews', [
            'id' => $review->id,
        ]);
    }

        public function test_他人のレビューは削除できない(): void
    {
        // レビュー投稿者と別のユーザーを作成
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();

        // テスト用書籍を作成
        $book = Book::create([
            'user_id' => $owner->id,
            'title' => 'レビュー削除認可テスト書籍',
            'author' => 'テスト著者',
            'isbn' => '9784000000103',
            'published_date' => '2026-01-01',
        ]);

        // 投稿者のレビューを作成
        $review = \App\Models\Review::create([
            'user_id' => $owner->id,
            'book_id' => $book->id,
            'rating' => 4,
            'comment' => '削除してはいけないレビュー',
        ]);

        // 別のユーザーでログインして削除を試みる
        $response = $this->actingAs($otherUser)
            ->delete(route('reviews.destroy', $review));

        // 403エラーが返されることを確認
        $response->assertForbidden();

        // レビューが削除されずに残っていることを確認
        $this->assertDatabaseHas('reviews', [
            'id' => $review->id,
            'user_id' => $owner->id,
            'comment' => '削除してはいけないレビュー',
        ]);
    }

        public function test_ログインユーザーはレビューにいいねできる(): void
    {
        // テスト用ユーザーを作成
        $user = User::factory()->create();

        // テスト用書籍を作成
        $book = Book::create([
            'user_id' => $user->id,
            'title' => 'いいねテスト書籍',
            'author' => 'テスト著者',
            'isbn' => '9784000000104',
            'published_date' => '2026-01-01',
        ]);

        // テスト用レビューを作成
        $review = \App\Models\Review::create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'rating' => 5,
            'comment' => 'いいねテスト用レビュー',
        ]);

        // ログインしてレビューにいいねする
        $response = $this->actingAs($user)
            ->from(route('books.show', $book))
            ->post(route('reviews.like', $review));

        // 書籍詳細画面に戻ることを確認
        $response->assertRedirect(route('books.show', $book));

        // いいねがデータベースに登録されたことを確認
        $this->assertDatabaseHas('review_likes', [
            'user_id' => $user->id,
            'review_id' => $review->id,
        ]);
    }

        public function test_ログインユーザーはレビューのいいねを解除できる(): void
    {
        // テスト用ユーザーを作成
        $user = User::factory()->create();

        // テスト用書籍を作成
        $book = Book::create([
            'user_id' => $user->id,
            'title' => 'いいね解除テスト書籍',
            'author' => 'テスト著者',
            'isbn' => '9784000000105',
            'published_date' => '2026-01-01',
        ]);

        // テスト用レビューを作成
        $review = \App\Models\Review::create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'rating' => 4,
            'comment' => 'いいね解除テスト用レビュー',
        ]);

        // あらかじめレビューにいいねを登録
        $user->likedReviews()->attach($review->id);

        // いいねが登録されていることを確認
        $this->assertDatabaseHas('review_likes', [
            'user_id' => $user->id,
            'review_id' => $review->id,
        ]);

        // ログインして、もう一度いいねボタンを押す
        $response = $this->actingAs($user)
            ->from(route('books.show', $book))
            ->post(route('reviews.like', $review));

        // 書籍詳細画面に戻ることを確認
        $response->assertRedirect(route('books.show', $book));

        // いいねがデータベースから削除されたことを確認
        $this->assertDatabaseMissing('review_likes', [
            'user_id' => $user->id,
            'review_id' => $review->id,
        ]);
    }
}
