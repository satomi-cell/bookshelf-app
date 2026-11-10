<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        // ログインユーザーの通知を新しい順に取得
        $notifications = $request->user()
            ->notifications()
            ->latest()
            ->get();

        // 通知一覧画面を表示
        return view('notifications.index', compact('notifications'));
    }

    
    public function markAsRead(Request $request, string $id)
    {
        // ログインユーザー自身の通知だけを検索
        $notification = $request->user()
            ->notifications()
            ->findOrFail($id);

        // 通知を既読にする
        $notification->markAsRead();

        // 通知一覧に戻る
        return redirect()
            ->route('notifications.index')
            ->with('success', '通知を既読にしました。');
    }

}
