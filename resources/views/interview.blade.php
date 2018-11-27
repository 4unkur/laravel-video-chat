@extends('layouts.app')

@section('content')
    <video-chat
        :chat="{{ $chat }}"
    ></video-chat>
@stop