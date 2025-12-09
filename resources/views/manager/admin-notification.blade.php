@extends('layouts.managerdashboardlayout')

@section('title', 'Notifications')

@section('content')
<div class="container">
    <h1 class="mb-4">Notifications</h1>

    @if($notifications->isEmpty())
        <div class="alert alert-info">
            You have no notifications at this time.
        </div>
    @else
        <div class="list-group">
            @foreach($notifications as $notification)
                <div class="list-group-item list-group-item-action mb-2 {{ $notification->is_read ? '' : 'fw-bold bg-light' }}">
                    <div class="d-flex w-100 justify-content-between">
                        <h5 class="mb-1">{{ $notification->title }}</h5>
                        <small class="text-muted">{{ $notification->created_at->diffForHumans() }}</small>
                    </div>
                    <p class="mb-1">{{ $notification->message }}</p>
                </div>
            @endforeach
        </div>
    @endif
</div>
@endsection
