<?php

namespace App\Console\Commands;
use Illuminate\Console\Command;
use App\Models\Song;
use Illuminate\Support\Facades\Storage;

class ImportLyricsFromCsv extends Command {
    protected $signature = 'app:import-lyrics';
    protected $description = 'CSVファイルから歌詞と難易度データをインポートします';

public function handle() {
    // 🔍 Laravelの設定に左右されない「絶対パス（storage/app/ファイル名）」を直接作成する
    $csvPath = storage_path('app/lyrics_import.csv');

    // PHP標準の file_exists で、その場所にファイルがあるか直接確認する
    if (!file_exists($csvPath)) {
        $this->error('インポート用のCSVファイルが storage/app/ 内に見つかりません。');
        return Command::FAILURE;
    }

    // fopen にも、上で作った絶対パスをそのまま渡す
    $file = fopen($csvPath, 'r');
    
    // 1行目（ヘッダー）をスキップ
    fgetcsv($file);

    $updatedCount = 0;
    while (($row = fgetcsv($file)) !== FALSE) {
        // CSVの各列を変数に分解
        [$trackId, $title, $difficulty, $lyrics] = $row;
        
        // track_idをキーにDBの該当曲を検索し、更新を行う
        $song = Song::where('track_id', $trackId)->first();
        if ($song) {
            $song->update([
                'difficulty' => (int)$difficulty,
                'opening_lyrics' => $lyrics
            ]);
            $updatedCount++;
        }
    }
    fclose($file);

    $this->info("歌詞データのインポートが完了しました。 更新: {$updatedCount} 件");
    return Command::SUCCESS;
}
}