@extends('layouts.app')

@section('title', 'Notifications - ASBL Forum')

@section('content')
    <main class="container section">
        <div id="notifications-page-root" data-user-id="{{ auth()->id() }}"></div>
    </main>
@endsection