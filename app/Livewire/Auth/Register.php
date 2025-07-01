<?php

namespace App\Livewire\Auth;

use Livewire\Component;
use Illuminate\Support\Facades\Http;

class Register extends Component
{
    public $name = '';
    public $email = '';
    public $password = '';
    public $password_confirmation = '';
    public $role = 'user'; // user, company
    public $company_name = '';
    public $error = '';
    public $loading = false;

    protected $rules = [
        'name' => 'required|min:2',
        'email' => 'required|email',
        'password' => 'required|min:6',
        'password_confirmation' => 'required|same:password',
    ];

    protected $messages = [
        'name.required' => 'İsim gereklidir.',
        'name.min' => 'İsim en az 2 karakter olmalıdır.',
        'email.required' => 'E-posta adresi gereklidir.',
        'email.email' => 'Geçerli bir e-posta adresi giriniz.',
        'password.required' => 'Şifre gereklidir.',
        'password.min' => 'Şifre en az 6 karakter olmalıdır.',
        'password_confirmation.required' => 'Şifre tekrarı gereklidir.',
        'password_confirmation.same' => 'Şifreler eşleşmiyor.',
    ];

    public function updatedRole()
    {
        // Reset company name when role changes
        if ($this->role !== 'company') {
            $this->company_name = '';
        }
    }

    public function register()
    {
        $this->loading = true;
        $this->error = '';

        // Dynamic validation based on role
        $rules = $this->rules;
        if ($this->role === 'company') {
            $rules['company_name'] = 'required|min:2';
        }
        $this->validate($rules);

        try {
            // Supabase Auth API call
            $userData = [
                'name' => $this->name,
                'role' => $this->role,
            ];

            if ($this->role === 'company') {
                $userData['company_name'] = $this->company_name;
            }

            $response = Http::withHeaders([
                'apikey' => env('SUPABASE_ANON_KEY'),
                'Content-Type' => 'application/json'
            ])->post(env('SUPABASE_URL') . '/auth/v1/signup', [
                'email' => $this->email,
                'password' => $this->password,
                'data' => $userData
            ]);

            if ($response->successful()) {
                $authData = $response->json();
                
                // If email confirmation is disabled, user will be logged in immediately
                if (isset($authData['user']) && $authData['user']['email_confirmed_at']) {
                    // Store user session
                    session([
                        'user_id' => $authData['user']['id'],
                        'user_email' => $authData['user']['email'],
                        'user_name' => $authData['user']['user_metadata']['name'] ?? '',
                        'user_role' => $authData['user']['user_metadata']['role'] ?? 'user',
                        'access_token' => $authData['access_token'] ?? null,
                    ]);

                    session()->flash('success', 'Hesabınız oluşturuldu ve giriş yapıldı!');
                    return redirect('/');
                } else {
                    // Email confirmation required or pending
                    session()->flash('success', 'Hesabınız oluşturuldu! Giriş yapmak için login sayfasını kullanın.');
                    return redirect('/login');
                }
            } else {
                $error = $response->json();
                $this->error = $this->getErrorMessage($error['error_description'] ?? 'Kayıt hatası');
            }
        } catch (\Exception $e) {
            $this->error = 'Bağlantı hatası oluştu. Lütfen tekrar deneyin.';
        } finally {
            $this->loading = false;
        }
    }

    private function getErrorMessage($errorMsg)
    {
        if (str_contains($errorMsg, 'User already registered')) {
            return 'Bu e-posta adresi zaten kayıtlı.';
        }
        if (str_contains($errorMsg, 'Password should be at least')) {
            return 'Şifre en az 6 karakter olmalıdır.';
        }
        if (str_contains($errorMsg, 'Invalid email')) {
            return 'Geçersiz e-posta adresi.';
        }
        return 'Kayıt sırasında bir hata oluştu.';
    }

    public function render()
    {
        return view('livewire.auth.register')->layout('components.layout', [
            'title' => 'Kayıt Ol - MicroJob'
        ]);
    }
}