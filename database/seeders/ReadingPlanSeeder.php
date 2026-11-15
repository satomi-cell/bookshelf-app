<?php

namespace Database\Seeders;

use App\Models\Book;
use App\Models\ReadingPlan;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class ReadingPlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $yamada = User::where('email', 'yamada@example.com')->firstOrFail();
        $suzuki = User::where('email', 'suzuki@example.com')->firstOrFail();

        $books = Book::orderBy('id')->take(6)->get();

        if ($books->count() < 6) {
            throw new \RuntimeException('読書計画の作成には6冊以上の書籍が必要です。');
        }

        ReadingPlan::create([
            'user_id' => $yamada->id,
            'book_id' => $books[0]->id,
            'target_date' => Carbon::today()->addDays(3),
            'status' => 'in_progress',
        ]);

        ReadingPlan::create([
            'user_id' => $yamada->id,
            'book_id' => $books[1]->id,
            'target_date' => Carbon::today(),
            'status' => 'in_progress',
        ]);

        ReadingPlan::create([
            'user_id' => $yamada->id,
            'book_id' => $books[2]->id,
            'target_date' => Carbon::today()->subDays(3),
            'status' => 'expired',
        ]);

        ReadingPlan::create([
            'user_id' => $yamada->id,
            'book_id' => $books[3]->id,
            'target_date' => Carbon::today()->addDays(7),
            'status' => 'in_progress',
        ]);

        ReadingPlan::create([
            'user_id' => $yamada->id,
            'book_id' => $books[4]->id,
            'target_date' => Carbon::today()->subDays(10),
            'status' => 'completed',
            'completed_at' => Carbon::today()->subDays(5),
        ]);

        ReadingPlan::create([
            'user_id' => $suzuki->id,
            'book_id' => $books[5]->id,
            'target_date' => Carbon::today()->addDays(5),
            'status' => 'in_progress',
        ]);
    }
}
