<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Song;

class GameController extends Controller 
{
    // 1. 難易度選択画面（トップ）
    public function index() {
        return view('game.index');
    }

    // 2. 問題画面の表示
    public function question(Request $request) {
        $difficulty = $request->input('difficulty', session('difficulty', 1));
        session(['difficulty' => $difficulty]); // 難易度を固定保持

        // 🎮 【追加】画面から送られてきたモード（未指定ならセルフ）をセッションに固定保持
        $mode = $request->input('mode', session('game_mode', 'self'));
        session(['game_mode' => $mode]);

        $playedIds = session('played_song_ids', []);

        $query = Song::whereNotNull('opening_lyrics');
        if ($difficulty == 3) {
            $query->whereIn('difficulty', [1, 2, 3]);
        } else {
            $query->where('difficulty', $difficulty);
        }
        
        $song = $query->whereNotIn('id', $playedIds)->inRandomOrder()->first();

        if (!$song) {
            return redirect()->route('game.index')->with('status', '全問クリアしました！難易度を変えて再挑戦しよう。');
        }

        $playedIds[] = $song->id;
        session(['played_song_ids' => $playedIds]);

        return view('game.question', compact('song'));
    }

    // 3. 解答画面の表示
    public function answer(Request $request, $id) {
        $song = Song::findOrFail($id);
        
        // 🎮 【追加】現在のモードをセッションから取得
        $mode = session('game_mode', 'self');

        // 🔥 モードが「テキスト入力（input）」の場合のみ、判定ロジックを実行
        if ($mode === 'input') {
            $userInput = $request->input('user_input', '');
            $normalizedInput = str_replace([' ', '　'], '', trim($userInput));
            $normalizedLyrics = str_replace([' ', '　'], '', trim($song->opening_lyrics));

            $percent = 0;
            similar_text($normalizedInput, $normalizedLyrics, $percent);
            $isCorrect = ($percent >= 50);

            // 入力モード用のデータをViewに渡す
            return view('game.answer', compact('song', 'userInput', 'percent', 'isCorrect', 'mode'));
        }

        // 🧠 「セルフジャッジ（self）」の場合は最小限のデータだけ渡す
        return view('game.answer', compact('song', 'mode'));
    }

    // 4. セッションのリセット
    public function reset() {
        // 🎮 リセット対象に game_mode も追加
        session()->forget(['played_song_ids', 'difficulty', 'game_mode']);
        return redirect()->route('game.index');
    }
}