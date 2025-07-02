<?php

namespace App\Livewire\Auth;

use Livewire\Component;
use Illuminate\Support\Facades\Http;

class Login extends Component
{
    public $email = '';
    public $password = '';
    public $error = '';
    public $loading = false;

    protected $rules = [
        'email' => 'required|email',
        'password' => 'required|min:6',
    ];

    protected $messages = [
        'email.required' => 'E-posta adresi gereklidir.',
        'email.email' => 'Geçerli bir e-posta adresi giriniz.',
        'password.required' => 'Şifre gereklidir.',
        'password.min' => 'Şifre en az 6 karakter olmalıdır.',
    ];

    public function login()
    {
        $this->loading = true;
        $this->error = '';

        $this->validate();

        try {
            // Supabase Auth API call
            $response = Http::withHeaders([
                'apikey' => env('SUPABASE_ANON_KEY'),
                'Content-Type' => 'application/json'
            ])->post(env('SUPABASE_URL') . '/auth/v1/token?grant_type=password', [
                'email' => $this->email,
                'password' => $this->password
            ]);

            if ($response->successful()) {
                $authData = $response->json();
                
                // Store user session
                session([
                    'user_id' => $authData['user']['id'],
                    'user_email' => $authData['user']['email'],
                    'user_name' => $authData['user']['user_metadata']['name'] ?? '',
                    'user_role' => $authData['user']['user_metadata']['role'] ?? 'user',
                    'access_token' => $authData['access_token'],
                ]);

                session()->flash('success', 'Başarıyla giriş yaptınız!');
                
                return redirect()->intended('/');
            } else {
                $error = $response->json();
                $this->error = $this->getErrorMessage($error['error_description'] ?? 'Giriş hatası');
            }
        } catch (\Exception $e) {
            $this->error = 'Bağlantı hatası oluştu. Lütfen tekrar deneyin.';
        } finally {
            $this->loading = false;
        }
    }

    private function getErrorMessage($errorMsg)
    {
        if (str_contains($errorMsg, 'Invalid login credentials')) {
            return 'E-posta veya şifre hatalı.';
        }
        if (str_contains($errorMsg, 'Email not confirmed')) {
            return 'E-posta adresinizi doğrulamanız gerekiyor.';
        }
        return 'Giriş yapılırken bir hata oluştu.';
    }

    public function render()
    {
        return view('livewire.auth.login')->layout('components.layout', [
            'title' => 'Giriş Yap - MicroJob'
        ]);
    }
}