<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class JobController extends Controller
{
    protected $supabaseUrl;
    protected $supabaseKey;
    protected $table = 'job_postings';

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
            return redirect()->route('login')->send();
        }
    }

    public function index()
    {
        $this->ensureAdmin();

        $response = $this->client()
            ->get("{$this->supabaseUrl}/rest/v1/{$this->table}", [
                'select' => '*',
                'order'  => 'created_at.desc',
            ]);

        $jobs = $response->successful() ? $response->json() : [];

        return view('admin.jobs.index', compact('jobs'));
    }

    public function create()
    {
        $this->ensureAdmin();

        $resp = $this->client()
            ->get("{$this->supabaseUrl}/rest/v1/companies", [
                'select' => 'id,name',
                'order'  => 'name.asc',
            ]);

        $companies = $resp->successful() ? $resp->json() : [];

        return view('admin.jobs.create', compact('companies'));
    }

    public function store(Request $request)
    {
        $this->ensureAdmin();

        $data = $request->validate([
            'company_id'       => 'required|integer',
            'title'            => 'required|string|max:255',
            'position'         => 'required|string|max:100',
            'description'      => 'required|string',
            'requirements'     => 'nullable|string',
            'city'             => 'required|string|max:100',
            'country'          => 'required|string|max:100',
            'work_type'        => 'required|in:fulltime,parttime,contract,internship',
            'experience_level' => 'required|in:junior,mid,senior,lead',
            'salary_min'       => 'nullable|numeric|min:0',
            'salary_max'       => 'nullable|numeric|min:0|gte:salary_min',
            'currency'         => 'required|string|max:10',
            'is_active'        => 'sometimes|boolean',
        ]);

        $data['is_active'] = $request->has('is_active');

        $resp = $this->client()
            ->post("{$this->supabaseUrl}/rest/v1/{$this->table}", $data);

        if ($resp->successful()) {
            return redirect()
                ->route('admin.jobs.index')
                ->with('success', 'Job posting created.');
        }

        return back()
            ->withInput()
            ->with('error', 'Create failed: ' . $resp->body());
    }

    public function edit($id)
    {
        $this->ensureAdmin();

        $jobResp = $this->client()
            ->get("{$this->supabaseUrl}/rest/v1/{$this->table}", [
                'select' => '*',
                'id'     => "eq.{$id}",
            ]);

        $jobs = $jobResp->successful() ? $jobResp->json() : [];
        $job  = $jobs[0] ?? abort(404);

        $compResp  = $this->client()
            ->get("{$this->supabaseUrl}/rest/v1/companies", [
                'select' => 'id,name',
                'order'  => 'name.asc',
            ]);
        $companies = $compResp->successful() ? $compResp->json() : [];

        return view('admin.jobs.edit', compact('job', 'companies'));
    }

    public function update(Request $request, $id)
    {
        $this->ensureAdmin();

        $data = $request->validate([
            'company_id'       => 'required|integer',
            'title'            => 'required|string|max:255',
            'position'         => 'required|string|max:100',
            'description'      => 'required|string',
            'requirements'     => 'nullable|string',
            'city'             => 'required|string|max:100',
            'country'          => 'required|string|max:100',
            'work_type'        => 'required|in:fulltime,parttime,contract,internship',
            'experience_level' => 'required|in:junior,mid,senior,lead',
            'salary_min'       => 'nullable|numeric|min:0',
            'salary_max'       => 'nullable|numeric|min:0|gte:salary_min',
            'currency'         => 'required|string|max:10',
            'is_active'        => 'sometimes|boolean',
        ]);

        $data['is_active'] = $request->has('is_active');

        $resp = $this->client()
            ->patch("{$this->supabaseUrl}/rest/v1/{$this->table}?id=eq.{$id}", $data);

        if ($resp->successful()) {
            return redirect()
                ->route('admin.jobs.index')
                ->with('success', 'Job posting updated.');
        }

        return back()
            ->withInput()
            ->with('error', 'Update failed: ' . $resp->body());
    }

    public function destroy($id)
    {
        $this->ensureAdmin();

        $resp = $this->client()
            ->delete("{$this->supabaseUrl}/rest/v1/{$this->table}?id=eq.{$id}");

        if ($resp->successful()) {
            return redirect()
                ->route('admin.jobs.index')
                ->with('success', 'Job posting deleted.');
        }

        return back()->with('error', 'Delete failed: ' . $resp->body());
    }
}
