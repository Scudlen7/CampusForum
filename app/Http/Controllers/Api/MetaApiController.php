<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use App\Models\Category;
use App\Models\Tag;
use Illuminate\Http\Request;

class MetaApiController extends Controller
{
    public function categories()
    {
        return response()->json([
            'data' => Category::where('is_active', true)->orderBy('sort_order')->get(['id', 'name', 'slug', 'icon']),
        ]);
    }

    public function tags(Request $request)
    {
        $query = Tag::query()->orderByDesc('post_count');

        if ($request->boolean('hot')) {
            $query->limit(10);
        }

        return response()->json(['data' => $query->get(['id', 'name', 'slug', 'post_count'])]);
    }

    public function banners()
    {
        return response()->json([
            'data' => Banner::active()->get(['id', 'title', 'content', 'link', 'image_path', 'type']),
        ]);
    }
}
