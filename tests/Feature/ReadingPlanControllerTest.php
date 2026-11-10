<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class ReadingPlanControllerTest extends TestCase
{
    use RefreshDatabase;
    /**
     * A basic feature test example.
     */
    public function test_テスト用データベースに接続されている(): void
    {
        $this->assertSame(
            'testing',
             config('database.connections.mysql.database')
        );
    }

    public function test_ログインユーザーは読書計画を登録できる(): void
    {
        // テスト用ユーザーを作成
        $user = User::factory()->create();

        // テスト用書籍を作成
        $book = Book::create([
            'user_id' => $user->id,
            'title' => '読書計画テスト書籍',
            'author' => 'テスト著者',
            'isbn' => '9784000000099',
            'published_date' => '2026-01-01',
        ]);

        // ログインして読書計画を登録
        $response = $this->actingAs($user)
            ->post(route('reading-plans.store'), [
                'book_id' => $book->id,
                'target_date' => now()->addDays(7)->toDateString(),
            ]);

        // 読書計画一覧へリダイレクトされること
        $response->assertRedirect(route('reading-plans.index'));

        // データベースに読書計画が登録されていること
        $this->assertDatabaseHas('reading_plans', [
            'user_id' => $user->id,
            'book_id' => $book->id,
            'status' => 'in_progress',
       ]);
   }

   public function test_期日が未入力の場合は読書計画を登録できない(): void
   {
       // テスト用ユーザーを作成
       $user = User::factory()->create();

       // テスト用書籍を作成
       $book = Book::create([
           'user_id' => $user->id,
           'title' => '期日未入力テスト書籍',
           'author' => 'テスト著者',
           'isbn' => '9784000000099',
           'published_date' => '2026-01-01',
       ]);

       // 期日を入力せずに登録
       $response = $this->actingAs($user)
           ->post(route('reading-plans.store'), [
               'book_id' => $book->id,
               'target_date' => '',
        ]);

       // 期日のバリデーションエラーを確認
       $response->assertSessionHasErrors([
            'target_date' => '期日を入力してください。',
       ]);

       // 読書計画が登録されていないことを確認
       $this->assertDatabaseCount('reading_plans', 0);
   }

   public function test_過去の日付では読書計画を登録できない(): void
   {
       // テスト用ユーザーを作成
       $user = User::factory()->create();

       // テスト用書籍を作成
       $book = Book::create([
           'user_id' => $user->id,
           'title' => '過去日付テスト書籍',
           'author' => 'テスト著者',
           'isbn' => '9784000000099',
           'published_date' => '2026-01-01',
       ]);

       // 昨日の日付を指定して登録
       $response = $this->actingAs($user)
           ->post(route('reading-plans.store'), [
               'book_id' => $book->id,
               'target_date' => now()->subDay()->toDateString(),
           ]);

       // 過去の日付に対するエラーを確認
       $response->assertSessionHasErrors([
           'target_date' => '期日は今日以降の日付を指定してください。',
       ]);

       // 読書計画が登録されていないことを確認
       $this->assertDatabaseCount('reading_plans', 0);
   }

   public function test_同じ書籍に進行中の読書計画を重複登録できない(): void
   {
       // テスト用ユーザーを作成
       $user = User::factory()->create();

       // テスト用書籍を作成
       $book = Book::create([
           'user_id' => $user->id,
           'title' => '重複登録テスト書籍',
           'author' => 'テスト著者',
           'isbn' => '9784000000099',
           'published_date' => '2026-01-01',
       ]);

       // 1件目の読書計画を登録
       $this->actingAs($user)
           ->post(route('reading-plans.store'), [
               'book_id' => $book->id,
               'target_date' => now()->addDays(7)->toDateString(),
           ]);

       // 同じ書籍に2件目を登録しようとする
       $response = $this->post(route('reading-plans.store'), [
           'book_id' => $book->id,
           'target_date' => now()->addDays(14)->toDateString(),
       ]);

       // 重複登録のエラーを確認
       $response->assertSessionHasErrors([
           'book_id' => 'この書籍には進行中の読書計画がすでに登録されています。',
       ]);

       // 読書計画が1件だけであることを確認
       $this->assertDatabaseCount('reading_plans', 1);
   }

   
   public function test_ログインユーザーは自分の読書計画を編集できる(): void
   {
       // テスト用ユーザーと書籍を作成
       $user = User::factory()->create();

       $book = Book::create([
           'user_id' => $user->id,
           'title' => '編集テスト書籍',
           'author' => 'テスト著者',
           'isbn' => '9784000000099',
           'published_date' => '2026-01-01',
       ]);

       // 編集対象の読書計画を作成
       $readingPlan = $user->readingPlans()->create([
           'book_id' => $book->id,
           'target_date' => now()->addDays(7)->toDateString(),
           'status' => 'in_progress',
        ]);

       // 期日を14日後に変更
       $newTargetDate = now()->addDays(14)->toDateString();

       $response = $this->actingAs($user)
           ->put(route('reading-plans.update', $readingPlan), [
               'target_date' => $newTargetDate,
           ]);

       // 一覧画面へリダイレクトされること
       $response->assertRedirect(route('reading-plans.index'));

       // 変更後の期日が保存されていること
       $this->assertDatabaseHas('reading_plans', [
           'id' => $readingPlan->id,
           'target_date' => $newTargetDate,
           'status' => 'in_progress',
       ]);
   }

   public function test_他のユーザーの読書計画は編集できない(): void
   {
       // 読書計画の所有者を作成
       $owner = User::factory()->create();

       // 別のユーザーを作成
       $otherUser = User::factory()->create();

       // テスト用書籍を作成
       $book = Book::create([
           'user_id' => $owner->id,
           'title' => '認可テスト書籍',
           'author' => 'テスト著者',
           'isbn' => '9784000000099',
           'published_date' => '2026-01-01',
       ]);

       // 所有者の読書計画を作成
       $readingPlan = $owner->readingPlans()->create([
           'book_id' => $book->id,
           'target_date' => now()->addDays(7)->toDateString(),
           'status' => 'in_progress',
       ]);

       // 別のユーザーが編集しようとする
       $response = $this->actingAs($otherUser)
           ->put(route('reading-plans.update', $readingPlan), [
               'target_date' => now()->addDays(14)->toDateString(),
           ]);

       // 権限がないため403エラーになること
       $response->assertForbidden();

       // 元の期日が変更されていないこと
       $this->assertDatabaseHas('reading_plans', [
           'id' => $readingPlan->id,
           'target_date' => $readingPlan->target_date->toDateString(),
           'status' => 'in_progress',
       ]);
   }

   public function test_ログインユーザーは自分の読書計画を完了できる(): void
   {
       // テスト用ユーザーを作成
       $user = User::factory()->create();

       // テスト用書籍を作成
       $book = Book::create([
           'user_id' => $user->id,
           'title' => '完了テスト書籍',
           'author' => 'テスト著者',
           'isbn' => '9784000000099',
           'published_date' => '2026-01-01',
       ]);

       // 進行中の読書計画を作成
       $readingPlan = $user->readingPlans()->create([
           'book_id' => $book->id,
           'target_date' => now()->addDays(7)->toDateString(),
           'status' => 'in_progress',
       ]);

       // 読書計画を完了にする
       $response = $this->actingAs($user)
           ->post(route('reading-plans.complete', $readingPlan));

       // 一覧画面へリダイレクトされること
       $response->assertRedirect(route('reading-plans.index'));

       // 状態が完了に変更されていること
       $this->assertDatabaseHas('reading_plans', [
           'id' => $readingPlan->id,
           'status' => 'completed',
       ]);
  
       // 完了日時が記録されていること
       $this->assertNotNull(
           $readingPlan->fresh()->completed_at
       );
   }

   public function test_他のユーザーの読書計画は完了できない(): void
   {
       // 読書計画の所有者と別のユーザーを作成
       $owner = User::factory()->create();
       $otherUser = User::factory()->create();

       // テスト用書籍を作成
       $book = Book::create([
           'user_id' => $owner->id,
           'title' => '完了認可テスト書籍',
           'author' => 'テスト著者',
           'isbn' => '9784000000099',
           'published_date' => '2026-01-01',
       ]);

       // 所有者の読書計画を作成
       $readingPlan = $owner->readingPlans()->create([
           'book_id' => $book->id,
           'target_date' => now()->addDays(7)->toDateString(),
           'status' => 'in_progress',
       ]);

       // 別のユーザーが完了しようとする
       $response = $this->actingAs($otherUser)
           ->post(route('reading-plans.complete', $readingPlan));

       // 権限がないため403エラーになること
       $response->assertForbidden();

       // 状態が変更されていないこと
       $this->assertDatabaseHas('reading_plans', [
           'id' => $readingPlan->id,
           'status' => 'in_progress',
           'completed_at' => null,
       ]);
   }

   public function test_ログインユーザーは自分の読書計画を削除できる(): void
   {
       // テスト用ユーザーを作成
       $user = User::factory()->create();

       // テスト用書籍を作成
       $book = Book::create([
           'user_id' => $user->id,
           'title' => '削除テスト書籍',
           'author' => 'テスト著者',
           'isbn' => '9784000000099',
           'published_date' => '2026-01-01',
       ]);

       // 削除対象の読書計画を作成
       $readingPlan = $user->readingPlans()->create([
           'book_id' => $book->id,
           'target_date' => now()->addDays(7)->toDateString(),
           'status' => 'in_progress',
       ]);

       // 読書計画を削除する
       $response = $this->actingAs($user)
           ->delete(route('reading-plans.destroy', $readingPlan));

       // 一覧画面へリダイレクトされること
       $response->assertRedirect(route('reading-plans.index'));

       // データベースから削除されていること
       $this->assertDatabaseMissing('reading_plans', [
           'id' => $readingPlan->id,
       ]);
   }

   public function test_他のユーザーの読書計画は削除できない(): void
   {
       // 読書計画の所有者と別のユーザーを作成
       $owner = User::factory()->create();
       $otherUser = User::factory()->create();

       // テスト用書籍を作成
       $book = Book::create([
           'user_id' => $owner->id,
           'title' => '削除認可テスト書籍',
           'author' => 'テスト著者',
           'isbn' => '9784000000099',
           'published_date' => '2026-01-01',
       ]);

       // 所有者の読書計画を作成
       $readingPlan = $owner->readingPlans()->create([
           'book_id' => $book->id,
           'target_date' => now()->addDays(7)->toDateString(),
           'status' => 'in_progress',
       ]);

       // 別のユーザーが削除しようとする
       $response = $this->actingAs($otherUser)
           ->delete(route('reading-plans.destroy', $readingPlan));

       // 権限がないため403エラーになること
       $response->assertForbidden();

       // 読書計画が削除されずに残っていること
       $this->assertDatabaseHas('reading_plans', [
           'id' => $readingPlan->id,
           'user_id' => $owner->id,
           'status' => 'in_progress',
       ]);
   }

   public function test_未ログインユーザーは読書計画一覧を閲覧できない(): void
   {
       // ログインせずに読書計画一覧へアクセス
       $response = $this->get(route('reading-plans.index'));

       // ログイン画面へリダイレクトされること
       $response->assertRedirect(route('login'));
   }

   public function test_完了済みの読書計画は編集できない(): void
   {
       // テスト用ユーザーを作成
       $user = User::factory()->create();

       // テスト用書籍を作成
       $book = Book::create([
           'user_id' => $user->id,
           'title' => '完了済み編集制限テスト書籍',
           'author' => 'テスト著者',
           'isbn' => '9784000000099',
           'published_date' => '2026-01-01',
       ]);

       // 完了済みの読書計画を作成
       $readingPlan = $user->readingPlans()->create([
           'book_id' => $book->id,
           'target_date' => now()->addDays(7)->toDateString(),
           'status' => 'completed',
           'completed_at' => now(),
       ]);

       // 完了済みの読書計画を編集しようとする
       $response = $this->actingAs($user)
           ->put(route('reading-plans.update', $readingPlan), [
               'target_date' => now()->addDays(14)->toDateString(),
           ]);

       // 編集が禁止されていること
       $response->assertForbidden();

       // 元の期日が変更されていないこと
       $this->assertDatabaseHas('reading_plans', [
           'id' => $readingPlan->id,
           'target_date' => $readingPlan->target_date->toDateString(),
           'status' => 'completed',
        ]);
   }

   public function test_期限切れの読書計画は期日を再設定できる(): void
   {
       // テスト用ユーザーを作成
       $user = User::factory()->create();

       // テスト用書籍を作成
       $book = Book::create([
           'user_id' => $user->id,
           'title' => '期限切れ再設定テスト書籍',
           'author' => 'テスト著者',
           'isbn' => '9784000000099',
           'published_date' => '2026-01-01',
       ]);

        // 期限切れの読書計画を作成
       $readingPlan = $user->readingPlans()->create([
           'book_id' => $book->id,
           'target_date' => now()->subDays(7)->toDateString(),
           'status' => 'expired',
       ]);

       // 新しい期日を設定
       $newTargetDate = now()->addDays(14)->toDateString();

       $response = $this->actingAs($user)
           ->put(route('reading-plans.update', $readingPlan), [
               'target_date' => $newTargetDate,
           ]);

       // 一覧画面へリダイレクトされること
       $response->assertRedirect(route('reading-plans.index'));

       // 期日と状態が更新されていること
       $this->assertDatabaseHas('reading_plans', [
           'id' => $readingPlan->id,
           'target_date' => $newTargetDate,
           'status' => 'in_progress',
       ]);
   }

   public function test_読書計画一覧を進行中で絞り込める(): void
   {
       // テスト用ユーザーを作成
       $user = User::factory()->create();

       // テスト用書籍を2冊作成
       $book1 = Book::create([
           'user_id' => $user->id,
           'title' => '進行中テスト書籍',
           'author' => 'テスト著者',
           'isbn' => '9784000000099',
           'published_date' => '2026-01-01',
       ]);

       $book2 = Book::create([
           'user_id' => $user->id,
           'title' => '完了済みテスト書籍',
           'author' => 'テスト著者',
           'isbn' => '9784000000100',
           'published_date' => '2026-01-01',
       ]);

       // 進行中の読書計画を作成
       $user->readingPlans()->create([
           'book_id' => $book1->id,
           'target_date' => now()->addDays(7)->toDateString(),
           'status' => 'in_progress',
       ]);

       // 完了済みの読書計画を作成
       $user->readingPlans()->create([
           'book_id' => $book2->id,
           'target_date' => now()->addDays(7)->toDateString(),
           'status' => 'completed',
           'completed_at' => now(),
       ]);

       // 進行中で絞り込んだ一覧を取得
       $response = $this->actingAs($user)
           ->get(route('reading-plans.index', [
               'status' => 'in_progress',
           ]));

       // 正常に表示されること
       $response->assertOk();
  
       // 進行中の書籍だけが表示されること
       $response->assertSee('進行中テスト書籍');
       $response->assertDontSee('完了済みテスト書籍');
   }

   public function test_読書計画一覧を完了で絞り込める(): void
   {
       // テスト用ユーザーを作成
       $user = User::factory()->create();

       // テスト用書籍を2冊作成
       $book1 = Book::create([
           'user_id' => $user->id,
           'title' => '進行中の書籍',
           'author' => 'テスト著者',
           'isbn' => '9784000000099',
           'published_date' => '2026-01-01',
       ]);

       $book2 = Book::create([
           'user_id' => $user->id,
           'title' => '完了済みの書籍',
           'author' => 'テスト著者',
           'isbn' => '9784000000100',
           'published_date' => '2026-01-01',
        ]);

       // 進行中の読書計画を作成
       $user->readingPlans()->create([
           'book_id' => $book1->id,
           'target_date' => now()->addDays(7)->toDateString(),
           'status' => 'in_progress',
       ]);

       // 完了済みの読書計画を作成
       $user->readingPlans()->create([
           'book_id' => $book2->id,
           'target_date' => now()->addDays(7)->toDateString(),
           'status' => 'completed',
           'completed_at' => now(),
       ]);

       // 完了で絞り込んだ一覧を取得
       $response = $this->actingAs($user)
           ->get(route('reading-plans.index', [
               'status' => 'completed',
           ]));

       // 正常に表示されること
       $response->assertOk();
   
       // 完了済みの書籍だけが表示されること
       $response->assertSee('完了済みの書籍');
       $response->assertDontSee('進行中の書籍');
   }

   public function test_読書計画一覧を期限切れで絞り込める(): void
   {
       // テスト用ユーザーを作成
       $user = User::factory()->create();

       // テスト用書籍を2冊作成
       $book1 = Book::create([
           'user_id' => $user->id,
           'title' => '期限切れテスト書籍',
           'author' => 'テスト著者',
           'isbn' => '9784000000099',
           'published_date' => '2026-01-01',
       ]);

       $book2 = Book::create([
           'user_id' => $user->id,
           'title' => '進行中テスト書籍',
           'author' => 'テスト著者',
           'isbn' => '9784000000100',
           'published_date' => '2026-01-01',
       ]);

       // 期限切れの読書計画を作成
       $user->readingPlans()->create([
           'book_id' => $book1->id,
           'target_date' => now()->subDays(7)->toDateString(),
           'status' => 'expired',
       ]);

       // 進行中の読書計画を作成
       $user->readingPlans()->create([
           'book_id' => $book2->id,
           'target_date' => now()->addDays(7)->toDateString(),
           'status' => 'in_progress',
       ]);

       // 期限切れで絞り込んだ一覧を取得
       $response = $this->actingAs($user)
           ->get(route('reading-plans.index', [
               'status' => 'expired',
           ]));

       // 正常に表示されること
       $response->assertOk();
 
       // 期限切れの書籍だけが表示されること
       $response->assertSee('期限切れテスト書籍');
       $response->assertDontSee('進行中テスト書籍');
   }

}
