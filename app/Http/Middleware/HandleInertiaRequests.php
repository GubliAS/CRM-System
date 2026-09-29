<?php

namespace App\Http\Middleware;

use App\Models\Account;
use App\Models\Contact;
use App\Models\Event;
use App\Models\Lead;
use App\Models\Opportunity;
use App\Models\SupportCase;
use App\Models\Task;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();

        if ($user instanceof User) {
            $user->loadMissing('role');
        }

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $user,
                'role_slug' => $user?->role?->slug,
                'abilities' => $this->abilitiesFor($user),
            ],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
                'import_result' => fn () => $request->session()->get('import_result'),
            ],
        ];
    }

    /**
     * @return array<string, bool>
     */
    private function abilitiesFor(?User $user): array
    {
        if (! $user instanceof User) {
            return [
                'leads' => false,
                'accounts' => false,
                'contacts' => false,
                'opportunities' => false,
                'cases' => false,
                'tasks' => false,
                'events' => false,
            ];
        }

        return [
            'leads' => $user->can('viewAny', Lead::class),
            'accounts' => $user->can('viewAny', Account::class),
            'contacts' => $user->can('viewAny', Contact::class),
            'opportunities' => $user->can('viewAny', Opportunity::class),
            'cases' => $user->can('viewAny', SupportCase::class),
            'tasks' => $user->can('viewAny', Task::class),
            'events' => $user->can('viewAny', Event::class),
        ];
    }
}
