@extends('components.admin-layout', ['pageTitle' => 'Companies'])

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1>Companies</h1>
    <a href="{{ route('admin.companies.create') }}" class="btn btn-primary">Create Company</a>
</div>

@if(session('success'))
  <div class="alert alert-success">{{ session('success') }}</div>
@endif
@if(session('error'))
  <div class="alert alert-danger">{{ session('error') }}</div>
@endif

<table class="table table-striped">
  <thead>
    <tr>
      <th>ID</th>
      <th>Name</th>
      <th>City</th>
      <th>Industry</th>
      <th>Actions</th>
    </tr>
  </thead>
  <tbody>
    @forelse($companies as $company)
    <tr>
      <td>{{ $company['id'] }}</td>
      <td>{{ $company['name'] }}</td>
      <td>{{ $company['city'] }}</td>
      <td>{{ $company['industry'] ?? '-' }}</td>
      <td class="text-nowrap">
        <a href="{{ route('admin.companies.edit', $company['id']) }}" class="btn btn-sm btn-secondary">Edit</a>
        <form action="{{ route('admin.companies.destroy', $company['id']) }}" method="POST" class="d-inline"
              onsubmit="return confirm('Are you sure?')">
          @csrf
          @method('DELETE')
          <button class="btn btn-sm btn-danger">Delete</button>
        </form>
      </td>
    </tr>
    @empty
    <tr><td colspan="5" class="text-center">No companies found.</td></tr>
    @endforelse
  </tbody>
</table>
@endsection
