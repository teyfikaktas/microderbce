{{-- resources/views/admin/jobs/create.blade.php --}}
<x-admin-layout :pageTitle="'Create New Job'">

  <div class="card">
    <div class="card-body">
      <form action="{{ route('admin.jobs.store') }}" method="POST">
        @csrf

        {{-- Company --}}
        <div class="mb-3">
          <label for="company_id" class="form-label">Company</label>
          <select name="company_id" id="company_id" class="form-select @error('company_id') is-invalid @enderror">
            <option value="">-- Select Company --</option>
            @foreach($companies as $company)
              <option value="{{ $company->id }}" {{ old('company_id') == $company->id ? 'selected' : '' }}>
                {{ $company->name }}
              </option>
            @endforeach
          </select>
          @error('company_id')
            <div class="invalid-feedback">{{ $message }}</div>
          @enderror
        </div>

        {{-- Title --}}
        <div class="mb-3">
          <label for="title" class="form-label">Title</label>
          <input type="text" name="title" id="title"
                 class="form-control @error('title') is-invalid @enderror"
                 value="{{ old('title') }}">
          @error('title')
            <div class="invalid-feedback">{{ $message }}</div>
          @enderror
        </div>

        {{-- Position --}}
        <div class="mb-3">
          <label for="position" class="form-label">Position</label>
          <input type="text" name="position" id="position"
                 class="form-control @error('position') is-invalid @enderror"
                 value="{{ old('position') }}">
          @error('position')
            <div class="invalid-feedback">{{ $message }}</div>
          @enderror
        </div>

        {{-- Şehir ve Diğer Alanlar… --}}
        {{-- (Diğer inputları önceki örnekteki gibi ekleyebilirsin) --}}

        <button type="submit" class="btn btn-primary">Create Job</button>
        <a href="{{ route('admin.jobs.index') }}" class="btn btn-secondary ms-2">Cancel</a>
      </form>
    </div>
  </div>

</x-admin-layout>
