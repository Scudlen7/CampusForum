<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;

class CategoryAdminController extends Controller
{
    public function index(Request $request)
    {
        return view('admin.categories', [
            'pageScript' => 'admin-categories',
            'pageProps' => $this->pageProps($request, ['pageTitle' => '分类管理', 'activeMenu' => 'admin']),
        ]);
    }

    public function apiIndex()
    {
        return response()->json(['data' => Category::orderBy('sort_order')->get()]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:32'],
            'slug' => ['required', 'string', 'max:32'],
            'description' => ['nullable', 'string'],
            'icon' => ['nullable', 'string', 'max:64'],
            'sort_order' => ['nullable', 'integer'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        Category::create($data);

        return response()->json(['message' => 'created'], 201);
    }

    public function update(Request $request, Category $category)
    {
        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:32'],
            'slug' => ['sometimes', 'string', 'max:32'],
            'description' => ['nullable', 'string'],
            'icon' => ['nullable', 'string', 'max:64'],
            'sort_order' => ['nullable', 'integer'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $category->update($data);

        return response()->json(['message' => 'updated']);
    }
}
