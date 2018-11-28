<?php

namespace App\Http\Controllers;

use App\Chat;
use App\User;
use Illuminate\Http\Request;

class ChatController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        $users = User::where('id', '!=', auth()->id())->get();
        $rooms = auth()->user()->chatRooms()->get();

        return view('index', compact('users', 'rooms'));
    }

    public function store(Request $request)
    {
        $data = $this->validate($request, [
            'user_id' => 'numeric|required|exists:users,id'
        ]);

        $chat = new Chat;
        $chat->hash = md5(mt_rand(0, PHP_INT_MAX) . time());
        $chat->save();
        $chat->users()->saveMany([
            User::find($data['user_id']),
            auth()->user()
        ]);

        return redirect()->route('chat.show', [$chat]);
    }

    public function show(Chat $chat)
    {
        return view('interview', compact('chat'));
    }
}
