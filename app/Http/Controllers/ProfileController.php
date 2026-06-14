<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use App\Models\User;
use App\Models\ActivityLog;

class ProfileController extends Controller
{
    public function edit()
    {
        $user = auth()->user();

        return view('profile.edit', [
            'user'          => $user,
            'employees'     => $user->isAdmin() ? $user->employees()->where('status', 'active')->orderBy('name')->get() : collect(),
            'pendingSignups'=> $user->isAdmin() ? $user->employees()->where('status', 'pending')->latest()->get() : collect(),
            'abas'          => User::ABAS,
            //feed, admin vê ações da equipe, funcionário vê avisos de aprovação/rejeição
            'activities'    => ActivityLog::with('actor')->where('owner_id', $user->id)->latest()->limit(50)->get(),
            'unreadCount'   => ActivityLog::where('owner_id', $user->id)->whereNull('read_at')->count(),
            //pedidos aguardando aprovação
            'pendingOrders' => $user->isAdmin()
                ? \App\Models\PurchaseOrder::with(['supplier', 'creator'])
                    ->where('approval_status', 'pending_approval')->latest()->get()
                : collect(),
            //movimentações (transferência/devolução) aguardando aprovação
            'pendingMovements' => $user->isAdmin()
                ? \App\Models\Movement::with(['product', 'creator'])
                    ->where('approval_status', 'pending_approval')->latest()->get()
                : collect(),
        ]);
    }

    public function markNotificationsRead()
    {
        $user = auth()->user();
        ActivityLog::where('owner_id', $user->id)->whereNull('read_at')->update(['read_at' => now()]);
        return back()->with('success', 'Notificações marcadas como lidas.');
    }

    //tema claro ou escuro
    public function updateTheme(Request $request)
    {
        $data = $request->validate([
            'theme' => 'required|in:light,dark',
        ]);

        $user = auth()->user();
        $user->theme = $data['theme'];
        $user->save();

        return response()->json(['ok' => true, 'theme' => $user->theme]);
    }

    public function update(Request $request)
    {
        $user = auth()->user();

        $data = $request->validate([
            'name'  => 'required|string|max:255',
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
        ]);

        $user->name = $data['name'];
        $user->email = $data['email'];
        $user->save();

        return back()->with('success', 'Perfil atualizado com sucesso.');
    }

    public function updatePassword(Request $request)
    {
        $user = auth()->user();

        $data = $request->validate([
            'current_password' => 'required',
            'password'         => 'required|min:6|confirmed',
        ], [
            'password.confirmed' => 'A confirmação da nova senha não confere.',
            'password.min'       => 'A nova senha deve ter pelo menos 6 caracteres.',
        ]);

        if (!Hash::check($data['current_password'], $user->password)) {
            return back()->withErrors(['current_password' => 'A senha atual está incorreta.'])->with('pwError', true);
        }

        $user->password = $data['password']; //função matemática: dados de tamanho variável->caracteres de tamanho fixo
        $user->save();

        return back()->with('success', 'Senha alterada com sucesso.');
    }

    //Funcionários (somente admin)

    public function storeEmployee(Request $request)
    {
        $admin = auth()->user();
        abort_unless($admin->isAdmin(), 403);

        $data = $request->validate([
            'name'          => 'required|string|max:255',
            'email'         => 'required|email|max:255|unique:users,email',
            'password'      => 'required|min:6',
            'permissions'   => 'array',
            'permissions.*' => 'string',
        ]);

        $allowed = array_keys(User::ABAS);

        User::create([
            'name'        => $data['name'],
            'email'       => $data['email'],
            'password'    => $data['password'], //função matemática: dados de tamanho variável->caracteres de tamanho fixo
            'owner_id'    => $admin->id,
            'permissions' => array_values(array_intersect($data['permissions'] ?? [], $allowed)),
        ]);

        return back()->with('success', 'Funcionário criado com sucesso.');
    }

    public function updateEmployee(Request $request, User $employee)
    {
        $admin = auth()->user();
        abort_unless($admin->isAdmin(), 403);
        abort_unless($employee->owner_id === $admin->id, 403);

        $data = $request->validate([
            'name'          => 'required|string|max:255',
            'email'         => ['required', 'email', 'max:255', Rule::unique('users')->ignore($employee->id)],
            'password'      => 'nullable|min:6',
            'permissions'   => 'array',
            'permissions.*' => 'string',
        ]);

        $allowed = array_keys(User::ABAS);

        $employee->name        = $data['name'];
        $employee->email       = $data['email'];
        $employee->permissions = array_values(array_intersect($data['permissions'] ?? [], $allowed));
        if (!empty($data['password'])) {
            $employee->password = $data['password']; //função matemática: dados de tamanho variável->caracteres de tamanho fixo
        }
        $employee->save();

        return back()->with('success', 'Funcionário atualizado com sucesso.');
    }

    public function destroyEmployee(User $employee)
    {
        $admin = auth()->user();
        abort_unless($admin->isAdmin(), 403);
        abort_unless($employee->owner_id === $admin->id, 403);

        $employee->delete();

        return back()->with('success', 'Funcionário removido.');
    }

    //Aprovação de novos cadastros
    public function approveSignup(User $user)
    {
        $admin = auth()->user();
        abort_unless($admin->isAdmin(), 403);
        abort_unless($user->owner_id === $admin->id && $user->isPending(), 403);

        $user->update(['status' => 'active']);

        $this->mailUser(
            $user,
            'Nexo Estoque — Acesso aprovado',
            "Olá, {$user->name}!\n\nSeu acesso ao Nexo Estoque foi APROVADO. Você já pode entrar com seu e-mail e senha."
        );

        return back()->with('success', $user->name . ' foi aprovado. Libere as abas dele na seção Equipe.');
    }

    public function rejectSignup(User $user)
    {
        $admin = auth()->user();
        abort_unless($admin->isAdmin(), 403);
        abort_unless($user->owner_id === $admin->id && $user->isPending(), 403);

        $this->mailUser(
            $user,
            'Nexo Estoque — Cadastro recusado',
            "Olá, {$user->name}.\n\nSeu pedido de acesso ao Nexo Estoque não foi aprovado pelo administrador."
        );

        $user->delete(); //libera o e-mail para um novo cadastro futuro

        return back()->with('success', 'Cadastro recusado e removido.');
    }

    private function mailUser(User $user, string $subject, string $body): void
    {
        if (empty($user->email)) {
            return;
        }
        try {
            Mail::raw($body, function ($m) use ($user, $subject) {
                $m->to($user->email)->subject($subject);
            });
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
