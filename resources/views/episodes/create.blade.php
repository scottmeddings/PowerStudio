@extends('layouts.app')

@section('title', 'New Episode')
@section('page-title', 'New Episode')

@section('content')
<div class="section-card p-4">
  <form method="POST" action="{{ route('episodes.store') }}">
    @csrf
    @include('episodes._form')
  </form>
</div>
@endsection
