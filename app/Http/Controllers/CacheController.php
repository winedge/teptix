<?php
namespace App\Http\Controllers;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Http\JsonResponse;

class CacheController extends Controller
{
    public function clearAllCache(): JsonResponse
    {
        Artisan::call('cron:clear-cache');

        return response()->json([
            'status' => true,
            'message' => '✅ Cache cleared successfully.'
        ]);
    }
}
