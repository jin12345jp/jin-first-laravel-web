<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Song extends Model
{
    use HasFactory;

    // 🔍【ここを追加】一括保存（Mass Assignment）を許可するカラムを指定する
    protected $fillable = [
        'artist_id',
        'track_id',
        'title',
        'opening_lyrics',
        'difficulty',
        'cover_image_url',
    ];

    // アーティストテーブルとのリレーション（1対多の「多」側）
    public function artist()
    {
        return $this->belongsTo(Artist::class);
    }
}
