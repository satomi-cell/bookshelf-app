<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Book;
use App\Models\ReadingPlan;

class ReadingPlanController extends Controller
{
    public function index(Request $request)
    {
        $currentStatus = $request->query('status', '');

        $query = $request->user()
            ->readingPlans()
            ->with('book');

        if (in_array($currentStatus, ['in_progress', 'completed', 'expired'], true)) {
            $query->where('status', $currentStatus);
        }

        $readingPlans = $query->latest()->get();

        return view('reading-plans.index', compact('readingPlans', 'currentStatus'));
    }

    public function create()
    {
        $books = Book::orderBy('title')->get();

        return view('reading-plans.create', compact('books'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'book_id' => ['required', 'integer', 'exists:books,id'],
            'target_date' => ['required', 'date', 'after_or_equal:today'],
        ]);

        $hasInProgressPlan = $request->user()
            ->readingPlans()
            ->where('book_id', $validated['book_id'])
            ->where('status', 'in_progress')
            ->exists();

        if ($hasInProgressPlan) {
            return back()
                ->withErrors([
                    'book_id' => 'この書籍には進行中の読書計画がすでに登録されています。',
                ])
                ->withInput();
        }

        $request->user()->readingPlans()->create([
            'book_id' => $validated['book_id'],
            'target_date' => $validated['target_date'],
            'status' => 'in_progress',
        ]);

        return redirect()
            ->route('reading-plans.index')
            ->with('success', '読書計画を登録しました。');
    }

    public function complete(ReadingPlan $readingPlan)
    {
        if ($readingPlan->user_id !== auth()->id()) {
            abort(403);
        }

        if ($readingPlan->status === \App\Enums\ReadingPlanStatus::Completed) {
            return redirect()->route('reading-plans.index');
        }

        $readingPlan->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);

        return redirect()
            ->route('reading-plans.index')
            ->with('success', '読了として記録しました。');
    }

    public function edit(ReadingPlan $readingPlan)
    {
        if ($readingPlan->user_id !== auth()->id()) {
            abort(403);
        }

        if ($readingPlan->status === \App\Enums\ReadingPlanStatus::Completed) {
            abort(403);
        }

        return view('reading-plans.edit', compact('readingPlan'));
    }

    public function update(Request $request, ReadingPlan $readingPlan)
    {
        if ($readingPlan->user_id !== auth()->id()) {
             abort(403);
        }

        if ($readingPlan->status === \App\Enums\ReadingPlanStatus::Completed) {
             abort(403);
        }

        $validated = $request->validate([
             'target_date' => ['required', 'date', 'after_or_equal:today'],
        ]);
        $readingPlan->update([
             'target_date' => $validated['target_date'],
        ]);

        return redirect()
            ->route('reading-plans.index')
            ->with('success', '読書計画を更新しました。');
    }

    public function destroy(ReadingPlan $readingPlan)
    {
        if ($readingPlan->user_id !== auth()->id()) {
            abort(403);
        }

        $readingPlan->delete();

        return redirect()
            ->route('reading-plans.index')
            ->with('success', '読書計画を削除しました。');
    }
}