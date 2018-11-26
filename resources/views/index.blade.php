@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="card">
            <div class="card-header">Chat Rooms</div>
            <div class="card-body">
                <h5 class="card-title">Primary card title</h5>
                <p class="card-text">
                <ul>
                    <li></li>
                </ul>
                </p>
            </div>
        </div>


        <form action="{{ route('chat.store') }}" method="post" class="form">

            <div class="card">
                <div class="card-header">New Room</div>
                <div class="card-body">
                    <h5 class="card-title">Primary card title</h5>
                    <p class="card-text">
                    @csrf
                    <div class="form-group">
                        <select name="user" class="form-control">
                            @foreach ($users as $user)
                                <option value="{{ $user->id }}">{{ $user->name }} ({{ $user->email }})</option>
                            @endforeach
                        </select>
                    </div>

                    <button type="submit" class="btn btn-success">Create room</button>
                    </p>
                </div>
            </div>
        </form>

    </div>
@stop