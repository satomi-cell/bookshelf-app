<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Http;
use Tests\TestCase;
use App\Models\User;

class IsbnSearchTest extends TestCase
{
    public function test_isbn検索で書籍情報を取得できる(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        Http::fake([
            'www.googleapis.com/books/v1/volumes*' => Http::response([
                'items' => [
                    [
                        'volumeInfo' => [
                            'title' => '吾輩は猫である',
                            'authors' => ['夏目漱石'],
                            'description' => '書籍の説明です。',
                            'publishedDate' => '2003-01-01',
                            'imageLinks' => [
                                'thumbnail' => 'https://example.com/book.jpg',
                            ],
                        ],
                    ],
                ],
            ], 200),
        ]);

        $response = $this->getJson(
            route('books.isbn', ['isbn' => '9784101010014'])
        );

        $response->assertOk()
            ->assertJson([
                'title' => '吾輩は猫である',
                'author' => '夏目漱石',
                'description' => '書籍の説明です。',
                'published_date' => '2003-01-01',
                'image_url' => 'https://example.com/book.jpg',
            ]);
    }

    public function test_isbnが13桁でない場合は422エラーになる(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->getJson(
            route('books.isbn', ['isbn' => '12345'])
        );

        $response->assertStatus(422);
    }

    public function test_isbn検索で書籍が見つからない場合は404エラーになる(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        Http::fake([
            'www.googleapis.com/books/v1/volumes*' => Http::response([
                'totalItems' => 0,
                'items' => [],
            ], 200),
        ]);

        $response = $this->getJson(
            route('books.isbn', ['isbn' => '9784101010014'])
        );

        $response->assertStatus(404);
    }
    
    public function test_google_books_apiが429を返した場合は503エラーになる(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        Http::fake([
            'www.googleapis.com/books/v1/volumes*' => Http::response([], 429),
        ]);

        $response = $this->getJson(
            route('books.isbn', ['isbn' => '9784101010014'])
        );

        $response->assertStatus(503);
    }

    public function test_google_books_apiが500を返した場合は502エラーになる(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        Http::fake([
            'www.googleapis.com/books/v1/volumes*' => Http::response([], 500),
        ]);

        $response = $this->getJson(
            route('books.isbn', ['isbn' => '9784101010014'])
        );

        $response->assertStatus(502);
    }

}
