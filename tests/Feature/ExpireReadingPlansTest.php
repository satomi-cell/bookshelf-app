<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class ExpireReadingPlansTest extends TestCase
{
    use RefreshDatabase;
    /**
     * A basic feature test example.
     */

   public function test_期日を過ぎた読書計画は期限切れに更新される(): void
   {
       // テスト用ユーザーを作成
       $user = User::factory()->create();

       // テスト用書籍を作成
       $book = Book::create([
           'user_id' => $user->id,
           'title' => '期限切れ更新テスト書籍',
           'author' => 'テスト著者',
           'isbn' => '9784000000099',
           'published_date' => '2026-01-01',
       ]);

       // 期日が昨日の進行中の読書計画を作成
       $readingPlan = $user->readingPlans()->create([
           'book_id' => $book->id,
            'target_date' => now('Asia/Tokyo')->subDay()->toDateString(),
           'status' => 'in_progress',
       ]);

       // 期限切れ更新コマンドを実行
       $this->artisan('app:expire-reading-plans')
           ->assertExitCode(0);

       // 状態が期限切れに変更されていること
       $this->assertDatabaseHas('reading_plans', [
           'id' => $readingPlan->id,
           'status' => 'expired',
       ]);
   }

     public function test_期日が未来の読書計画は進行中のままになる(): void
   {
       $user = User::factory()->create();

       $book = Book::create([
           'user_id' => $user->id,
           'title' => '未来の期日テスト書籍',
           'author' => 'テスト著者',
           'isbn' => '9784000000100',
           'published_date' => '2026-01-01',
       ]);

       $readingPlan = $user->readingPlans()->create([
           'book_id' => $book->id,
           'target_date' => now('Asia/Tokyo')->addDays(7)->toDateString(),
           'status' => 'in_progress',
       ]);

       $this->artisan('app:expire-reading-plans')
           ->assertExitCode(0);

       $this->assertDatabaseHas('reading_plans', [
           'id' => $readingPlan->id,
           'status' => 'in_progress',
       ]);
   }
 
   public function test_完了済みの読書計画は期限切れに変更されない(): void
   {
       $user = User::factory()->create();

       $book = Book::create([
           'user_id' => $user->id,
           'title' => '完了済みテスト書籍',
           'author' => 'テスト著者',
           'isbn' => '9784000000101',
           'published_date' => '2026-01-01',
       ]);

       $readingPlan = $user->readingPlans()->create([
           'book_id' => $book->id,
           'target_date' => now('Asia/Tokyo')->subDays(7)->toDateString(),
           'status' => 'completed',
           'completed_at' => now(),
       ]);

       $this->artisan('app:expire-reading-plans')
           ->assertExitCode(0);

       $this->assertDatabaseHas('reading_plans', [
           'id' => $readingPlan->id,
           'status' => 'completed',
       ]);
   }

}
