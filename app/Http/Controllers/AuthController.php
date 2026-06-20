<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Company;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;

class AuthController extends Controller
{
    public function showRegister()
    {
        return view('auth.register');
    }

    public function register(Request $request)
    {
        $mode = $request->input('mode') === 'join' ? 'join' : 'create';

        $rules = [
            'name'         => 'required|string|max:250',
            'email'        => 'required|email|max:250|unique:users,email',
            'password'     => 'required|string|min:6|confirmed',
            'company_name' => 'required|string|max:250',
        ];

        if ($mode === 'create') {
            // Normaliza o CNPJ para SÓ dígitos ANTES de validar.
            // Sem isso o unique compararia "11.111.111/1111-11" (com máscara)
            // contra o valor salvo no banco (só dígitos) e nunca acharia duplicata.
            if ($request->filled('cnpj')) {
                $request->merge([
                    'cnpj' => preg_replace('/\D/', '', (string) $request->input('cnpj')),
                ]);
            }

            // CNPJ obrigatório só para quem cria a empresa + único na tabela companies
            $rules['cnpj'] = [
                'required',
                'string',
                function ($attr, $value, $fail) {
                    if (strlen((string) $value) !== 14) {
                        $fail('Informe um CNPJ válido (14 dígitos).');
                    }
                },
                Rule::unique('companies', 'cnpj'),
            ];

            // Nome da empresa único SÓ ao criar.
            // (No modo "join" o nome PRECISA existir, então não pode ter unique lá.)
            $rules['company_name'] = ['required', 'string', 'max:250', Rule::unique('companies', 'name')];
        }

        $messages = [
            'email.unique'        => 'Este e-mail já está em uso.',
            'cnpj.unique'         => 'Já existe uma empresa cadastrada com esse CNPJ.',
            'company_name.unique' => 'Já existe uma empresa com esse nome. Para entrar nela, use a opção "Entrar numa empresa".',
        ];

        $data = $request->validate($rules, $messages);

        //cria = dono
        if ($mode === 'create') {
            $user = User::create([
                'name'        => $data['name'],
                'email'       => $data['email'],
                'password'    => Hash::make($data['password']),
                'owner_id'    => null,
                'status'      => 'active',
                'permissions' => null,
            ]);

            $company = Company::create([
                'name'          => $data['company_name'],
                'cnpj'          => preg_replace('/\D/', '', $data['cnpj']),
                'owner_user_id' => $user->id,
                'plan'          => 'free',
                'status'        => 'active',
            ]);

            $user->company_id = $company->id;
            $user->save();

            $user->sendEmailVerificationNotification();

            return redirect()->route('verification.notice')->with('verify_email', $user->email);
        }

        //entrar na empres-funcionario
        $company = Company::whereRaw('LOWER(TRIM(name)) = ?', [mb_strtolower(trim($data['company_name']))])->first();

        if (!$company) {
            return back()
                ->withErrors(['company_name' => 'Empresa não encontrada. Confira o nome exatamente como o administrador cadastrou.'])
                ->withInput();
        }

        $user = User::create([
            'name'        => $data['name'],
            'email'       => $data['email'],
            'password'    => Hash::make($data['password']),
            'owner_id'    => $company->owner_user_id, //funcionário do dono da empresa
            'company_id'  => $company->id,
            'status'      => 'pending',               //aguarda aprovação do admin
            'permissions' => [],
        ]);

        $user->sendEmailVerificationNotification();

        $owner = User::find($company->owner_user_id);
        $this->notifyAdminNewSignup($user, $owner);

        return redirect()->route('verification.notice')->with('verify_email', $user->email);
    }

    public function showPending()
    {
        return view('auth.pending');
    }

    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $user = Auth::user();

            //1e-mail precisa estar confirmado
            if (!$user->hasVerifiedEmail()) {
                $email = $user->email;
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();
                $request->session()->flash('verify_email', $email);
                return redirect()->route('verification.notice');
            }

            //2funcionário precisa estar aprovado
            if ($user->isPending()) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();
                return redirect()->route('register.pending');
            }
            if ($user->isRejected()) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();
                return back()->withErrors(['email' => 'Seu acesso foi recusado pelo administrador.'])->withInput();
            }

            $request->session()->regenerate();
            return redirect()->intended(route('dashboard'));
        }

        return back()
            ->withErrors(['email' => 'Email ou senha incorretos.'])
            ->withInput();
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login');
    }

    //Notifica o admin
    private function notifyAdminNewSignup(User $user, ?User $admin): void
    {
        if (!$admin) {
            return;
        }
        $desc = 'Novo cadastro: ' . $user->name . ' (' . $user->email . ') quer entrar na equipe.';

        try {
            ActivityLog::create([
                'owner_id'    => $admin->id,
                'actor_id'    => $user->id,
                'action'      => 'solicitou acesso',
                'subject'     => 'novo cadastro',
                'description' => $desc,
            ]);

            if (!empty($admin->email)) {
                Mail::raw(
                    $desc . "\n\nAprove ou rejeite no seu Perfil, na seção \"Novos cadastros\".",
                    function ($m) use ($admin) {
                        $m->to($admin->email)->subject('Nexo Estoque — Novo cadastro aguardando aprovação');
                    }
                );
            }
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
