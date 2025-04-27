<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use BoogieFromZk\AgoraToken\ChatTokenBuilder2;

class AgoraChatController extends Controller
{
    public function chatToken(Request $request)
    {
        $channel = $request->query('channel');
        if (! $channel) {
            return response()->json(['error' => 'channel is required'], 422);
        }

        $userId  = (string) Auth::id();
        $appId   = config('services.agora.app_id');
        $appCert = config('services.agora.app_certificate');
        $expire  = 3600;

        // Debug: cek nilai kredensial
        Log::info("ChatToken debug: appId={$appId}, appCert=" . ($appCert ? 'set' : 'null'));

        try {
            $token = ChatTokenBuilder2::buildUserToken(
                $appId,
                $appCert,
                $userId,
                $expire
            );
        } catch (\Throwable $e) {
            Log::error('ChatTokenBuilder2 threw exception: '.$e->getMessage());
            return response()->json(['error'=>'Token generation failed'], 500);
        }

        // Debug: token kosong?
        if (empty($token)) {
            Log::error('ChatTokenBuilder2 returned empty token');
            return response()->json(['error'=>'Token is empty'], 500);
        }

        return response()->json([
            'token'   => $token,
            'channel' => $channel,
            'uid'     => $userId,
        ], 200);
    }
}
