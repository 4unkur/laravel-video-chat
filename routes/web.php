<?php

Auth::routes();

Route::get('/', 'ChatController@index')->name('home');
Route::post('chat', 'ChatController@store')->name('chat.store');

/*
app.post("/pusher/auth", (req, res) => {
    const socketId = req.body.socket_id;
    const channel = req.body.channel_name;
    var presenceData = {
        user_id:
        Math.random()
        .toString(36)
        .slice(2) + Date.now()
    };
    const auth = pusher.authenticate(socketId, channel, presenceData);
    res.send(auth);
});
*/