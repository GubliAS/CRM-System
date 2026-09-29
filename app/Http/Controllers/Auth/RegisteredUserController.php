<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterUserRequest;
use App\Models\Role;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/Register');
    }

    /**
     * Handle an incoming registration request.
     */
    public function store(RegisterUserRequest $request): RedirectResponse
    {
        $role = $this->resolveRegistrationRole();

        $user = User::create([
            'name' => $request->string('name')->toString(),
            'email' => $request->string('email')->toString(),
            'password' => $request->string('password')->toString(),
            'role_id' => $role->id,
        ]);

        event(new Registered($user));

        Auth::login($user);

        return redirect(route('home', absolute: false));
    }

    /**
     * First user becomes Administrator; later users become Sales Representative.
     *
     * @throws ValidationException
     */
    private function resolveRegistrationRole(): Role
    {
        $slug = User::query()->count() === 0 ? 'admin' : 'sales-rep';

        $role = Role::query()->where('slug', $slug)->first();

        if (! $role instanceof Role) {
            throw ValidationException::withMessages([
                'email' => 'User roles are not set up yet. Ask an administrator to run the CRM seeder.',
            ]);
        }

        return $role;
    }
}
