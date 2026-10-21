<?php

namespace Database\Seeders;

use App\Models\Book;
use App\Models\User;
use Illuminate\Database\Seeder;

class FavoriteSeeder extends Seeder
{
    public function run(): void
    {
        $favorites = [
            'yamada@example.com' => [
                '9784101010014',
                '9784873115658',
                '9784309226712',
                '9784822289607',
            ],
            'suzuki@example.com' => [
                '9784422100524',
                '9784863940246',
                '9784478025819',
            ],
            'tanaka@example.com' => [
                '9784101010021',
                '9784163902302',
                '9784822251468',
                '9784101010014',
            ],
            'sato@example.com' => [
                '9784873115658',
                '9784048930598',
                '9784822289607',
                '9784309226712',
                '9784478025819',
            ],
            'takahashi@example.com' => [
                '9784863940246',
                '9784048930598',
                '9784163902302',
            ],
        ];

        foreach ($favorites as $email => $isbns) {
            $user = User::where('email', $email)->firstOrFail();

            $bookIds = Book::whereIn('isbn', $isbns)
                ->pluck('id')
                ->all();

            $user->favoriteBooks()->syncWithoutDetaching($bookIds);
        }
    }
}
