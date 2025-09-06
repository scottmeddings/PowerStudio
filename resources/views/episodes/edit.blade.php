{{-- resources/views/episodes/edit.blade.php --}}
@extends('layouts.app')

@section('title', 'Edit Episode')
@section('page-title', 'Edit Episode')

@section('content')
<div class="section-card p-4">

  {{-- MAIN UPDATE FORM (PUT) --}}
  <form id="episodeForm"
        method="POST"
        action="{{ route('episodes.update', $episode) }}"
        enctype="multipart/form-data">
    @csrf
    @method('PUT')

    @include('episodes._form', ['episode' => $episode]) {{-- this partial must NOT contain any <form> --}}
  </form>

  {{-- DEDICATED FORMS (NOT NESTED) --}}
  <form id="publishForm" method="POST" action="{{ route('episodes.publish', $episode) }}">
    @csrf @method('PATCH')
  </form>

  <form id="unpublishForm" method="POST" action="{{ route('episodes.unpublish', $episode) }}">
    @csrf @method('PATCH')
  </form>

  <form id="deleteEpisodeForm" method="POST" action="{{ route('episodes.destroy', $episode) }}">
    @csrf @method('DELETE')
  </form>

</div>

@endsection
