<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class UploadApiController extends Controller
{
    public function image(Request $request)
    {
        $request->validate([
            'file' => ['required', 'image', 'max:5120'],
        ]);

        $file = $request->file('file');
        $path = $this->storeUploadedFile($file, 'posts/'.date('Y/m'), 'public');
        $url = Storage::disk('public')->url($path);
        $filename = $file->getClientOriginalName();

        return response()->json([
            'msg' => '',
            'code' => 0,
            'data' => [
                'errFiles' => [],
                'succMap' => [
                    $filename => $url,
                ],
            ],
            'url' => $url,
            'path' => $path,
        ]);
    }
}
