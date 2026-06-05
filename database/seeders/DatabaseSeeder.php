<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);
    }
}

use Illuminate\Support\Facades\Http;
use App\Models\Song;

// iTunes APIでヒゲダン（アーティストID: 1157143412など）の曲を検索
$response = Http::get('https://itunes.apple.com/lookup', [
    'id' => '1157143412', // 実際のID
    'entity' => 'song',
    'limit' => 200,
    'country' => 'JP'
]);

$results = $response->json()['results'];

foreach ($results as $track) {
    if ($track['wrapperType'] === 'track') {
        Song::updateOrCreate(
            ['track_id' => $track['trackId']], // 重複を防ぐ一意識別子
            [
                'title' => $track['trackName'],
                'cover_image_path' => $track['artworkUrl100'], // ジャケ写URL
                'artist_id' => 1, // ヒゲダン
                'difficulty' => 1, // 初期値として初級を入れておく
            ]
        );
    }
}
