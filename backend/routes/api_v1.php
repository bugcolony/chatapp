<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\ChannelController;
use App\Http\Controllers\Api\V1\MemberController;
use App\Http\Controllers\Api\V1\MessageAttachmentController;
use App\Http\Controllers\Api\V1\MessageController;
use App\Http\Controllers\Api\V1\PreferenceController;
use App\Http\Controllers\Api\V1\RTCController;
use App\Http\Controllers\Api\V1\ServerController;
use App\Http\Controllers\Api\V1\ServerInviteController;
use App\Http\Controllers\Api\V1\VoiceChannelController;
use App\Http\Controllers\Api\V1\VoicePresenceController;
use App\Http\Controllers\Api\V1\WebSocketTicketController;
use App\Http\Middleware\ChannelMember;
use App\Http\Middleware\ServerMember;
use Illuminate\Support\Facades\Route;

Route::group(['middleware' => 'throttle:5,1'], static function () {
    Route::post('/login', [AuthController::class, 'login']);
    //    Route::post('/register', [AuthController::class, 'register']);
    Route::get('/invites/{code}', [ServerInviteController::class, 'show']);
});

Route::post('/rtc/events', RTCController::class);

Route::group(['middleware' => 'auth:sanctum'], static function () {
    Route::prefix('me')->group(static function () {
        Route::get('', [AuthController::class, 'user']);
        Route::prefix('preferences')->group(static function () {
            Route::post('pinned-servers', [PreferenceController::class, 'pinnedServers']);
        });
    });

    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/ws/ticket', WebSocketTicketController::class)->middleware(['throttle:10,1']);
    //    Route::post('/friends', [FriendController::class, 'index']
    Route::prefix('servers')->group(static function () {
        Route::get('', [ServerController::class, 'index']);
        Route::post('', [ServerController::class, 'store']);

        Route::prefix('{server}')->middleware([ServerMember::class])->group(static function () {
            Route::get('', [ServerController::class, 'show']);
            Route::get('/members', [MemberController::class, 'index']);
            Route::get('/channels', [ChannelController::class, 'index']);
            Route::post('/channels', [ChannelController::class, 'store']);
            Route::get('/voice-presence', VoicePresenceController::class);
            Route::post('/invites', [ServerInviteController::class, 'store'])->middleware(['throttle:10,1']);
            Route::post('/leave', [MemberController::class, 'destroy']);
        });
    });

    Route::prefix('channels')->group(static function () {
        Route::prefix('{channel}')->middleware([ChannelMember::class])->group(static function () {
            //            Route::get('', [ChannelController::class, 'show']);
            Route::patch('', [ChannelController::class, 'update']);
            Route::delete('', [ChannelController::class, 'destroy']);
            Route::get('/messages', [MessageController::class, 'index']);
            Route::post('/messages', [MessageController::class, 'store']);
            Route::post('/credentials', VoiceChannelController::class);
        });
    });

    Route::get('/messages/{message}/attachment', MessageAttachmentController::class)
        ->name('messages.attachment');

    Route::prefix('invites')->group(static function () {
        Route::post('{code}/join', [ServerInviteController::class, 'join']);
    });
});
