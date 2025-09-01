@extends('layouts.app')

@section('title', 'Edit Episode')
@section('page-title', 'Edit Episode')

@section('content')
<div class="section-card p-4">
  <form method="POST" action="{{ route('episodes.update', $episode) }}">
    @csrf @method('PUT')
    @include('episodes._form', ['episode' => $episode])
  </form>
</div>
@endsection
