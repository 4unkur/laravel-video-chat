@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">Dashboard</div>

                <div class="card-body">
                    @if (session('status'))
                        <div class="alert alert-success" role="alert">
                            {{ session('status') }}
                        </div>
                    @endif

                        <span id="myid"> </span>
                        <video id="selfview" autoplay></video>
                        <video id="remoteview" autoplay></video>
                        <button id="endCall" style="display: none;" onclick="endCurrentCall()">End Call </button>
                        <div id="list">
                            <ul id="users">

                            </ul>
                        </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
