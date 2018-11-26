<?php

Broadcast::channel('videocall', function ($user) {
    return ['id' => $user->id, 'name' => $user->name];
});

//Broadcast::channel('chat.{roomId}', function ($user, $roomId) {
//    if ($user->canJoinRoom($roomId)) {
//        return ['id' => $user->id, 'name' => $user->name];
//    }
//});