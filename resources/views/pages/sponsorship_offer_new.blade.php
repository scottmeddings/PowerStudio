@extends('layouts.app')
@section('title','New Sponsorship Offer')
@section('page-title','sponsorships')
@section('content')
<div class="section-card p-3">
  <h5 class="mb-3">Create sponsorship offer</h5>
  <form method="POST" action="{{ route('monetization.sponsorships.store') }}">@csrf
    <div class="row g-3">
      <div class="col-md-6">
        <label class="form-label">Title</label>
        <input name="title" class="form-control" required>
      </div>
      <div class="col-md-3">
        <label class="form-label">CPM (USD)</label>
        <input name="cpm_usd" type="number" step="0.01" min="0" class="form-control" required>
      </div>
      <div class="col-md-3">
        <label class="form-label">Min downloads</label>
        <input name="min_downloads" type="number" min="0" class="form-control">
      </div>
      <div class="col-md-4">
        <label class="form-label">Slots (pre/mid/post)</label>
        <div class="d-flex gap-2">
          <input name="pre_slots"  type="number" min="0" max="5" class="form-control" value="0">
          <input name="mid_slots"  type="number" min="0" max="10" class="form-control" value="1">
          <input name="post_slots" type="number" min="0" max="5" class="form-control" value="0">
        </div>
      </div>
      <div class="col-md-4">
        <label class="form-label">Start</label>
        <input name="start_at" type="date" class="form-control">
      </div>
      <div class="col-md-4">
        <label class="form-label">End</label>
        <input name="end_at" type="date" class="form-control">
      </div>
      <div class="col-12">
        <label class="form-label">Notes</label>
        <textarea name="notes" class="form-control" rows="3"></textarea>
      </div>
    </div>
    <div class="text-end mt-3">
      <button class="btn btn-dark">Create offer</button>
    </div>
  </form>
</div>
@endsection
