<?php
// app/Http/Controllers/PostController.php
namespace App\Http\Controllers;

use App\Models\Post;
use Illuminate\Http\Request;

class PostController extends Controller
{
    // 一覧表示
    public function index()
    {
        $posts = Post::all();
        return view('posts.index', compact('posts'));
    }
}