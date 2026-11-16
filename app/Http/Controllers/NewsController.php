<?php

namespace App\Http\Controllers;

use App\Models\Post;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class NewsController extends Controller
{
    public function index()
    {
        $locale = app()->getLocale();
        return view('pages.news', [
            'locale' => $locale,
            'posts' => Post::published()->forLocale($locale)->orderByDesc('published_at')->get(),
        ]);
    }

    public function show(string $slug)
    {
        $locale = app()->getLocale();
        $post = Post::published()->forLocale($locale)->where('slug', $slug)->first();
        if (! $post) throw new NotFoundHttpException();
        return view('pages.news-post', compact('locale', 'post'));
    }
}
