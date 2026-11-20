<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            マイ読書レポート
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            {{-- 基本統計 --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">
                        基本統計
                    </h3>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">

                        {{-- 総レビュー数 --}}
                        <div class="border rounded-lg p-6 text-center">
                            <p class="text-3xl font-bold text-blue-600">
                                {{ $totalReviews }}
                            </p>
                            <p class="text-sm text-gray-500 mt-2">
                                総レビュー数
                            </p>
                        </div>

                        {{-- 読了冊数 --}}
                        <div class="border rounded-lg p-6 text-center">
                            <p class="text-3xl font-bold text-green-600">
                                {{ $completedBooks }}
                            </p>
                            <p class="text-sm text-gray-500 mt-2">
                                読了冊数
                            </p>
                        </div>

                        {{-- 平均評価点 --}}
                        <div class="border rounded-lg p-6 text-center">
                            <p class="text-3xl font-bold text-yellow-500">
                                {{ number_format($averageRating, 1) }}
                            </p>
                            <p class="text-sm text-gray-500 mt-2">
                                平均評価点
                            </p>
                        </div>

                    </div>
                </div>
            </div>
                        
            {{-- 評価分布 --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">
                        評価分布
                    </h3>

                    @foreach(range(1, 5) as $rating)
                        @php
                            $count = $ratingDistribution[$rating] ?? 0;
                            $percentage = $totalReviews > 0
                                ? ($count / $totalReviews) * 100
                                : 0;
                        @endphp

                        <div class="flex items-center gap-3 mb-3">
                            <span class="text-yellow-500 text-sm w-20">
                                {{ str_repeat('★', $rating) }}
                            </span>

                            <div class="flex-1 bg-gray-200 rounded-full h-3">
                                <div
                                    class="bg-yellow-400 h-3 rounded-full"
                                    style="width: {{ $percentage }}%">
                                </div>
                            </div>

                            <span class="text-sm text-gray-600 w-12 text-right">
                                {{ $count }}件
                            </span>
                        </div>
                    @endforeach

                </div>
            </div>
            
            {{-- 高評価書籍TOP5 --}}
                        
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">
                        高評価書籍TOP5
                    </h3>

                    @forelse($topRatedBooks as $review)
                        <div class="border-b py-3 flex justify-between items-center">
                            <a href="{{ route('books.show', $review->book) }}"
                               class="text-blue-600 hover:underline">
                                {{ $review->book->title }}
                            </a>

                            <span class="text-yellow-500">
                                {{ str_repeat('★', $review->rating) }}
                            </span>
                        </div>
                    @empty
                        <p class="text-gray-500">
                            高評価の書籍はまだありません。
                        </p>
                    @endforelse

                </div>
            </div>
            
             {{-- ジャンル別評価傾向TOP5 --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">
                        ジャンル別評価傾向TOP5
                    </h3>

                    @forelse($genreRatings as $genre)
                        <div class="border-b py-3 flex justify-between items-center">
                            <a href="{{ route('genres.show', $genre->id) }}"
                               class="text-blue-600 hover:underline">
                                {{ $genre->name }}
                            </a>

                            <div class="text-right">
                                <p class="text-yellow-500 font-semibold">
                                    ★ {{ number_format($genre->average_rating, 1) }}
                                </p>
                                <p class="text-sm text-gray-500">
                                    {{ $genre->review_count }}件のレビュー
                                </p>
                            </div>
                        </div>
                    @empty
                        <p class="text-gray-500">
                            ジャンル別の評価データはまだありません。
                        </p>
                    @endforelse

                </div>
            </div>


        </div>
    </div>
</x-app-layout>