@extends('components.admin-layout', ['pageTitle' => 'Create Company'])

@section('content')
<h1>Create Company</h1>

@if(session('error'))
  <div class="alert alert-danger">{{ session('error') }}</div>
@endif

<form action="{{ route('admin.companies.store') }}" method="POST">
  @csrf
  <div class="mb-3">
    <label class="form-label">Name</label>
    <input name="name" value="{{ old('name') }}" class="form-control @error('name') is-invalid @enderror">
    @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
  </div>
  <div class="mb-3">
    <label class="form-label">City</label>
    <input name="city" value="{{ old('city') }}" class="form-control @error('city') is-invalid @enderror">
    @error('city') <div class="invalid-feedback">{{ $message }}</div> @enderror
  </div>
  <div class="mb-3">
    <label class="form-label">Country</label>
    <input name="country" value="{{ old('country') }}" class="form-control @error('country') is-invalid @enderror">
    @error('country') <div class="invalid-feedback">{{ $message }}</div> @enderror
  </div>
  <div class="mb-3">
    <label class="form-label">Industry</label>
    <input name="industry" value="{{ old('industry') }}" class="form-control">
  </div>
  <div class="mb-3">
    <label class="form-label">Website</label>
    <input name="website" value="{{ old('website') }}" class="form-control">
  </div>
  <button class="btn btn-success">Save</button>
  <a href="{{ route('admin.companies.index') }}" class="btn btn-secondary">Cancel</a>
</form>
@endsection
