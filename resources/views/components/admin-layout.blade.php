{{-- resources/views/components/admin-layout.blade.php --}}
<div class="d-flex vh-100">

  {{-- Sidebar --}}
  <nav class="bg-light border-end" style="width:240px;">
    <div class="p-3">
      <h4>{{ config('app.name') }} Admin</h4>
    </div>
    <ul class="nav flex-column px-2">
      <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}" 
           href="{{ route('admin.dashboard') }}">Dashboard</a>
      </li>
      <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('admin.companies.*') ? 'active' : '' }}" 
           href="{{ route('admin.companies.index') }}">Companies</a>
      </li>
      <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('admin.jobs.*') ? 'active' : '' }}" 
           href="{{ route('admin.jobs.index') }}">Job Postings</a>
      </li>
      <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('admin.users.*') ? 'active' : '' }}" 
           href="{{ route('admin.users.index') }}">Users</a>
      </li>
    </ul>
  </nav>

  {{-- Main Content --}}
  <div class="flex-fill d-flex flex-column">

    {{-- Topbar --}}
    <header class="navbar navbar-light bg-white border-bottom px-4">
      <div class="container-fluid d-flex justify-content-between">
        <h1 class="h5 m-0">{{ $pageTitle ?? '' }}</h1>
        <div class="d-flex align-items-center">
          <span class="me-3">Admin, {{ auth()->user()->name ?? 'Guest' }}</span>
          <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button class="btn btn-sm btn-outline-danger">Logout</button>
          </form>
        </div>
      </div>
    </header>

    {{-- Flash Messages --}}
    <main class="p-4 overflow-auto">
      @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
      @endif
      @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
      @endif

      {{-- Buraya child içeriği gelecek --}}
      {{ $slot }}
    </main>

  </div>
</div>
