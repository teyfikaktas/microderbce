<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class CompanyController extends Controller
{
    protected $supabaseUrl;
    protected $supabaseKey;
    protected $table = 'companies';

    public function __construct()
    {
        $this->supabaseUrl = config('services.supabase.url');
        $this->supabaseKey = config('services.supabase.service_key');
    }

    protected function client()
    {
        return Http::withHeaders([
            'apikey'        => $this->supabaseKey,
            'Authorization' => 'Bearer ' . $this->supabaseKey,
            'Content-Type'  => 'application/json',
        ]);
    }

    protected function ensureAdmin()
    {
        if (session('user_email') !== 'admin@admin.com') {
            redirect()->route('login')->send(); exit;
        }
    }

    public function index()
    {
        $this->ensureAdmin();
        $resp = $this->client()->get("{$this->supabaseUrl}/rest/v1/{$this->table}", [
            'select' => '*',
            'order'  => 'name.asc',
        ]);
        $companies = $resp->successful() ? $resp->json() : [];
        return view('admin.companies.index', compact('companies'));
    }

    public function create()
    {
        $this->ensureAdmin();
        return view('admin.companies.create');
    }

    public function store(Request $request)
    {
        $this->ensureAdmin();
        $data = $request->validate([
            'name'           => 'required|string|max:255',
            'description'    => 'nullable|string',
            'website'        => 'nullable|url|max:255',
            'logo'           => 'nullable|string|max:255',
            'city'           => 'required|string|max:100',
            'country'        => 'required|string|max:100',
            'employee_count' => 'nullable|string|max:50',
            'industry'       => 'nullable|string|max:100',
        ]);
        $resp = $this->client()
            ->post("{$this->supabaseUrl}/rest/v1/{$this->table}", $data);

        if ($resp->successful()) {
            return redirect()->route('admin.companies.index')
                             ->with('success', 'Company created.');
        }
        return back()->withInput()
                     ->with('error', 'Failed to create: '.$resp->body());
    }

    public function edit($id)
    {
        $this->ensureAdmin();
        $resp = $this->client()
            ->get("{$this->supabaseUrl}/rest/v1/{$this->table}", [
                'select' => '*',
                'id'     => "eq.{$id}",
            ]);
        $rows = $resp->successful() ? $resp->json() : [];
        $company = $rows[0] ?? abort(404);
        return view('admin.companies.edit', compact('company'));
    }

    public function update(Request $request, $id)
    {
        $this->ensureAdmin();
        $data = $request->validate([
            'name'           => 'required|string|max:255',
            'description'    => 'nullable|string',
            'website'        => 'nullable|url|max:255',
            'logo'           => 'nullable|string|max:255',
            'city'           => 'required|string|max:100',
            'country'        => 'required|string|max:100',
            'employee_count' => 'nullable|string|max:50',
            'industry'       => 'nullable|string|max:100',
        ]);
        $resp = $this->client()
            ->patch("{$this->supabaseUrl}/rest/v1/{$this->table}?id=eq.{$id}", $data);

        if ($resp->successful()) {
            return redirect()->route('admin.companies.index')
                             ->with('success', 'Company updated.');
        }
        return back()->withInput()
                     ->with('error', 'Update failed: '.$resp->body());
    }

    public function destroy($id)
    {
        $this->ensureAdmin();
        $resp = $this->client()
            ->delete("{$this->supabaseUrl}/rest/v1/{$this->table}?id=eq.{$id}");

        if ($resp->successful()) {
            return redirect()->route('admin.companies.index')
                             ->with('success', 'Company deleted.');
        }
        return back()->with('error', 'Delete failed: '.$resp->body());
    }
}
