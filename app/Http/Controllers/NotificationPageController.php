<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class NotificationPageController extends Controller
{
    public function index(Request $request)
    {
        return view('notifications.index', [
            'pageScript' => 'notifications',
            'pageProps' => $this->pageProps($request, [
                'pageTitle' => '消息中心',
                'activeMenu' => 'notifications',
            ]),
        ]);
    }
}
