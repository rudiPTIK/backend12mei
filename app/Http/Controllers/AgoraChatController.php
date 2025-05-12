<?php
// app/Http/Controllers/AgoraChatController.php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use BoogieFromZk\AgoraToken\ChatTokenBuilder2;
use Symfony\Component\HttpFoundation\Response;

class AgoraChatController extends Controller
{
    /**
     * @param  Request  $request
     * @param  string   $channel   // diambil dari path
     */
    public function chatToken(Request $request, string $channel): JsonResponse
    {
        $user = $request->user();
        if (! $user) {
            return response()->json(['error' => 'Unauthenticated'], Response::HTTP_UNAUTHORIZED);
        }

        $userId       = (string) $user->id;
        $appId        = config('services.agora.app_id');
        $appCert      = config('services.agora.app_certificate');
        $expireSecond = 3600;

        Log::info("Generate chat token for user={$userId}, channel={$channel}");

        try {
            $token = ChatTokenBuilder2::buildUserToken(
                $appId,
                $appCert,
                $userId,
                $expireSecond
            );
        } catch (\Throwable $e) {
            Log::error("ChatTokenBuilder2 error: {$e->getMessage()}", ['exception' => $e]);
            return response()->json(
                ['error' => 'Failed to generate chat token'],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }

        if (empty($token)) {
            Log::error("Empty token for user={$userId}");
            return response()->json(
                ['error' => 'Token generation returned empty'],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }

        return response()->json([
            'token'   => $token,
            'uid'     => $userId,
            'channel' => $channel,
        ], Response::HTTP_OK);
    }
}
