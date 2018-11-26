@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="card mb-lg-4">
            <div class="card-header">Chat Rooms</div>
            <div class="card-body">
                <h5 class="card-title">Select from list</h5>
                <div class="card-text">
                    @if ($rooms->count())
                        <ul class="list-group">
                            @foreach ($rooms as $room)
                                <li class="list-group-item">
                                    <a href="{{ route('chat.show', [$room]) }}">
                                        ID: {{ $room->id }}
                                        <small class="text-muted float-right">{{ $room->created_at->diffForHumans() }}</small>
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </div>
        </div>

        <form action="{{ route('chat.store') }}" method="post" class="form">
            <div class="card">
                <div class="card-header">New Room</div>
                <div class="card-body">
                    <h5 class="card-title">Choose participant</h5>
                    <div class="card-text">
                        @csrf
                        <div class="form-group">
                            <select name="user_id" class="form-control">
                                @foreach ($users as $user)
                                    <option value="{{ $user->id }}">{{ $user->name }} ({{ $user->email }})</option>
                                @endforeach
                            </select>
                        </div>
                        <button type="submit" class="btn btn-success">Create room</button>
                    </div>
                </div>
            </div>
        </form>
    </div>
@stop