<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use Illuminate\Http\Request;

class BannerAdminController extends Controller
{
    public function index(Request $request)
    {
        return view('admin.banners', [
            'pageScript' => 'admin-banners',
            'pageProps' => $this->pageProps($request, ['pageTitle' => 'Banner 管理', 'activeMenu' => 'admin']),
        ]);
    }

    public function apiIndex()
    {
        return response()->json(['data' => Banner::orderBy('sort_order')->get()]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:64'],
            'content' => ['nullable', 'string'],
            'link' => ['nullable', 'string'],
            'type' => ['nullable', 'integer'],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer'],
        ]);

        Banner::create($data);

        return response()->json(['message' => 'created'], 201);
    }

    public function update(Request $request, Banner $banner)
    {
        $data = $request->validate([
            'title' => ['sometimes', 'string', 'max:64'],
            'content' => ['nullable', 'string'],
            'link' => ['nullable', 'string'],
            'type' => ['nullable', 'integer'],
            'is_active' => ['sometimes', 'boolean'],
            'sort_order' => ['nullable', 'integer'],
        ]);

        $banner->update($data);

        return response()->json(['message' => 'updated']);
    }
}
