<?php

Broadcast::channel('interview.{hash}', function (App\User $user, $hash) {
    if ($user->canJoinRoom($hash)) {
        return ['id' => $user->id, 'name' => $user->name];
    }
});