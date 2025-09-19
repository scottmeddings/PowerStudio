@extends('layouts.app')
@section('title','Dynamic Ad Insertion')
@section('page-title','monetization')

@section('content')
<div class="section-card p-3">
  <div class="d-flex justify-content-between align-items-center mb-2">
    <h5 class="mb-0">Dynamic Ad Insertion</h5>
    @if(session('status')) <span class="badge text-bg-success">{{ session('status') }}</span> @endif
  </div>

  <form method="POST" action="{{ route('monetization.dynamic.save') }}">
    @csrf
    <div class="row g-3">
      <div class="col-md-3">
        <label class="form-label">Enabled</label>
        <div class="form-check form-switch">
          <input class="form-check-input" type="checkbox" name="enabled" value="1"
                 {{ old('enabled', $settings['enabled'] ?? false) ? 'checked' : '' }}>
        </div>
      </div>
      <div class="col-md-3">
        <label class="form-label">Provider</label>
        <input name="provider" class="form-control" placeholder="megaphone/adstitch/custom"
               value="{{ old('provider', $settings['provider'] ?? '') }}">
      </div>
      <div class="col-md-3">
        <label class="form-label">Fill rate (%)</label>
        <input name="fill_rate" type="number" min="0" max="100" class="form-control"
               value="{{ old('fill_rate', $settings['fill_rate'] ?? 70) }}">
      </div>
      <div class="col-md-3">
        <label class="form-label">Default CPM (USD)</label>
        <input name="default_cpm" type="number" min="0" step="0.01" class="form-control"
               value="{{ old('default_cpm', $settings['default_cpm'] ?? 18) }}">
      </div>
    </div>

    <div class="row g-3 mt-2">
      @php $slots = $settings['slots'] ?? ['pre'=>['count'=>1,'max'=>2],'mid'=>['count'=>2,'max'=>3],'post'=>['count'=>1,'max'=>2]]; @endphp
      @foreach(['pre','mid','post'] as $slot)
      <div class="col-md-4">
        <label class="form-label text-capitalize">{{ $slot }}-roll</label>
        <div class="d-flex gap-2">
          <input type="number" min="0" max="10" class="form-control" name="slots[{{ $slot }}][count]"
                 value="{{ old("slots.$slot.count", $slots[$slot]['count'] ?? 0) }}" placeholder="count">
          <input type="number" min="0" max="10" class="form-control" name="slots[{{ $slot }}][max]"
                 value="{{ old("slots.$slot.max", $slots[$slot]['max'] ?? 0) }}" placeholder="max">
        </div>
      </div>
      @endforeach
    </div>

    <div class="row g-3 mt-2">
      <div class="col-md-6">
        <label class="form-label">Target countries (ISO, comma separated)</label>
        <input name="targeting[countries][]" class="form-control"
               value="{{ implode(',', old('targeting.countries', $settings['targeting']['countries'] ?? [])) }}"
               oninput="this.name='targeting[countries]';">
        <div class="mini mt-1">Example: US,AU,GB</div>
      </div>
      <div class="col-md-6">
        <label class="form-label">Exclude episodes (IDs, comma separated)</label>
        <input name="targeting[exclude_episodes][]" class="form-control"
               value="{{ implode(',', old('targeting.exclude_episodes', $settings['targeting']['exclude_episodes'] ?? [])) }}"
               oninput="this.name='targeting[exclude_episodes]';">
      </div>
    </div>

    <div class="row g-3 mt-2">
      <div class="col-md-12">
        <label class="form-label">Webhook URL (optional)</label>
        <input name="webhook_url" type="url" class="form-control"
               value="{{ old('webhook_url', $settings['webhook_url'] ?? '') }}">
      </div>
    </div>

    <div class="text-end mt-3">
      <a href="{{ route('monetization.index') }}" class="btn btn-outline-secondary">Back</a>
      <button class="btn btn-dark">Save settings</button>
    </div>
  </form>
</div>
@endsection
