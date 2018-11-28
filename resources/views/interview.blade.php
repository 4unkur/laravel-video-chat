@extends('layouts.app')

@section('content')
    <video-chat
        :chat="{{ $chat }}"
    ></video-chat>
@stop

@section('js')
    <script src="/js/app.js" defer></script>
@stop