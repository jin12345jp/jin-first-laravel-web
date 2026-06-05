<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Artist extends Model
{
    use HasFactory;

    // 🔍【ここを追加】一括保存（Mass Assignment）を許可するカラムを指定する
    protected $fillable = [
        'name',
    ];

    // 楽曲テーブルとのリレーション（1対多の「1」側）
    public function songs()
    {
        return $this->hasMany(Song::class);
    }
}