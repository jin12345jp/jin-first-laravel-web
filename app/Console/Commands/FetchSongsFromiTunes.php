<?php

namespace App\Console\Commands;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use App\Models\Song;

class FetchSongsFromiTunes extends Command {
    protected $signature = 'app:fetch-itunes-songs';
    protected $description = 'iTunes APIからOfficial髭男dismの楽曲データを同期します';

    public function handle() {
        $this->info('iTunes APIからデータ情報を取得中...');

        // 🔍【ここを追加】DBに「Official髭男dism」がいなければ作成し、あれば取得する
        $artist = \App\Models\Artist::firstOrCreate([
            'name' => 'Official髭男dism'
        ]);

        $response = Http::timeout(10)->get('https://itunes.apple.com/search', [
            'term' => 'Official髭男dism', // 名前で検索
            'entity' => 'song',
            'limit' => 200,
            'country' => 'JP'
        ]);
        if ($response->failed()) {
            $this->error('APIとの通信に失敗しました。');
            return Command::FAILURE;
        }

        // 🔍 ここに下の1行を追加して、APIから何が返ってきているか強制表示する
        dump($response->json());

    $results = $response->json()['results'] ?? [];
    $count = 0;

        foreach ($results as $track) {
            // 1. wrapperTypeがtrackでないものはスキップ
            if (($track['wrapperType'] ?? '') !== 'track') continue;

            // 🔍【ここを追加】必要なデータ（楽曲ID、曲名、ジャケ写URL）がどれか1つでも欠けていたらスキップする
            if (!isset($track['trackId']) || !isset($track['trackName']) || !isset($track['artworkUrl100'])) {
                continue;
            }

            // データの存在が保証されたので、安全に保存処理を実行できる
            Song::updateOrCreate(
                ['track_id' => $track['trackId']],
                [
                    'artist_id' => 1, 
                    'title' => $track['trackName'],
                    'cover_image_url' => str_replace('100x100bb', '500x500bb', $track['artworkUrl100']),
                    'difficulty' => 1 
                ]
            );
            $count++;
        }
    $this->info("同期が完了しました。合計: {$count} 曲");
    return Command::SUCCESS;
    }
}
