<?php

namespace App;

use Illuminate\Notifications\Notifiable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'email', 'password',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    public function chatRooms()
    {
        return $this->belongsToMany(Chat::class);
    }

    public function canJoinRoom($hash)
    {
        $chat = Chat::where('hash', $hash)->first();

        return $chat->users()->where('id', $this->id)->count();
    }
}
