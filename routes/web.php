<?php

use Illuminate\Http\Request;
use Jenssegers\Agent\Agent;

use Illuminate\Support\Facades\Route;

Route::get('/', function () {

    // $visitor = Tracker::currentSession();
    $agent = new Agent();
    
    // Get device type (phone, tablet, desktop, etc.)
    $deviceType = $agent->device();

    // Get platform (Operating System)
    $platform = $agent->platform();

    // Get browser name
    $browser = $agent->browser();

    return response()->json([
        'device' => $deviceType,
        'platform' => $platform,
        'browser' => $browser,
    ]);

    // dd(Request->userAgent());

    // return request()->userAgent();

    // return $request->userAgent();
});
