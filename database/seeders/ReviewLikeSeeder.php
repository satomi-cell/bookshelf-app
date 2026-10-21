<?php

namespace Database\Seeders;

use App\Models\Review;
use App\Models\User;
use Illuminate\Database\Seeder;

class ReviewLikeSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::whereIn('email', [
            'yamada@example.com',
            'suzuki@example.com',
            'tanaka@example.com',
            'sato@example.com',
            'takahashi@example.com',
        ])->orderBy('id')->get();

        $reviews = Review::orderBy('id')->get();

        foreach ($reviews as $index => $review) {
            // レビューごとに0〜3件のいいねを設定
            $likeCount = $index % 4;

            // 投稿者本人を除いたユーザーを取得
            $otherUsers = $users->where('id', '!=', $review->user_id);

            // 指定件数のユーザーIDを取得
            $userIds = $otherUsers
                ->take($likeCount)
                ->pluck('id')
                ->all();

            // 既存のいいねを残し、重複せずに追加
            $review->likedByUsers()->syncWithoutDetaching($userIds);
        }
    }
}
