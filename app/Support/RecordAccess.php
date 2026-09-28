<?php

namespace App\Support;

use App\Models\Account;
use App\Models\Contact;
use App\Models\Event;
use App\Models\Lead;
use App\Models\Note;
use App\Models\Opportunity;
use App\Models\SupportCase;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class RecordAccess
{
    /**
     * @var list<class-string<Model>>
     */
    public const SALES_MODELS = [
        Lead::class,
        Account::class,
        Contact::class,
        Opportunity::class,
        Task::class,
        Event::class,
        Note::class,
    ];

    /**
     * @var list<class-string<Model>>
     */
    public const ASSIGNMENT_MODELS = [
        Task::class,
        Event::class,
    ];

    public static function before(User $user): ?bool
    {
        return $user->role?->slug === 'admin' ? true : null;
    }

    /**
     * @param  class-string<Model>  $model
     */
    public static function viewAny(User $user, string $model): bool
    {
        return match ($user->role?->slug) {
            'sales-manager', 'sales-rep' => self::isSales($model),
            'service-rep' => in_array($model, [SupportCase::class, Account::class, Contact::class, Task::class], true),
            'read-only' => self::isPolicied($model),
            default => false,
        };
    }

    public static function view(User $user, Model $record): bool
    {
        if (self::blockedByPrivacy($user, $record)) {
            return false;
        }

        $model = $record::class;

        return match ($user->role?->slug) {
            'sales-manager' => self::isSales($model),
            'sales-rep' => self::isSales($model) && self::owns($user, $record),
            'service-rep' => self::serviceCanView($user, $record),
            'read-only' => self::isPolicied($model),
            default => false,
        };
    }

    /**
     * @param  class-string<Model>  $model
     */
    public static function create(User $user, string $model): bool
    {
        return match ($user->role?->slug) {
            'sales-manager', 'sales-rep' => self::isSales($model),
            'service-rep' => in_array($model, [SupportCase::class, Task::class], true),
            default => false,
        };
    }

    public static function update(User $user, Model $record): bool
    {
        if (self::blockedByPrivacy($user, $record)) {
            return false;
        }

        $model = $record::class;

        return match ($user->role?->slug) {
            'sales-manager' => self::isSales($model),
            'sales-rep' => self::isSales($model) && self::owns($user, $record),
            'service-rep' => self::serviceCanUpdate($user, $record),
            default => false,
        };
    }

    public static function delete(User $user, Model $record): bool
    {
        return self::update($user, $record);
    }

    /**
     * @param  Builder<Model>  $query
     * @param  class-string<Model>  $model
     */
    public static function constrain(Builder $query, User $user, string $model): void
    {
        if ($user->role?->slug === 'admin') {
            return;
        }

        if (! self::viewAny($user, $model)) {
            $query->whereRaw('1 = 0');

            return;
        }

        $slug = $user->role?->slug;

        if ($slug === 'sales-rep' || ($slug === 'service-rep' && $model === Task::class)) {
            self::constrainOwned($query, $user, $model);
        }

        if ($model === Event::class && $slug !== 'sales-rep') {
            self::constrainPrivateEvents($query, $user);
        }
    }

    public static function owns(User $user, Model $record): bool
    {
        if ((int) $record->getAttribute('owner_id') === (int) $user->id) {
            return true;
        }

        if (in_array($record::class, self::ASSIGNMENT_MODELS, true)) {
            return (int) $record->getAttribute('assigned_to_id') === (int) $user->id;
        }

        return false;
    }

    private static function blockedByPrivacy(User $user, Model $record): bool
    {
        if (! $record instanceof Event || ! $record->is_private) {
            return false;
        }

        return ! self::owns($user, $record);
    }

    private static function serviceCanView(User $user, Model $record): bool
    {
        if ($record instanceof SupportCase || $record instanceof Account || $record instanceof Contact) {
            return true;
        }

        if ($record instanceof Task) {
            return self::owns($user, $record);
        }

        return false;
    }

    private static function serviceCanUpdate(User $user, Model $record): bool
    {
        if ($record instanceof SupportCase) {
            return true;
        }

        if ($record instanceof Task) {
            return self::owns($user, $record);
        }

        return false;
    }

    /**
     * @param  class-string<Model>  $model
     */
    private static function isSales(string $model): bool
    {
        return in_array($model, self::SALES_MODELS, true);
    }

    /**
     * @param  class-string<Model>  $model
     */
    private static function isPolicied(string $model): bool
    {
        return self::isSales($model) || $model === SupportCase::class;
    }

    /**
     * @param  Builder<Model>  $query
     * @param  class-string<Model>  $model
     */
    private static function constrainOwned(Builder $query, User $user, string $model): void
    {
        if (in_array($model, self::ASSIGNMENT_MODELS, true)) {
            $query->where(function (Builder $inner) use ($user): void {
                $inner->where('owner_id', $user->id)
                    ->orWhere('assigned_to_id', $user->id);
            });

            return;
        }

        $query->where('owner_id', $user->id);
    }

    /**
     * @param  Builder<Model>  $query
     */
    private static function constrainPrivateEvents(Builder $query, User $user): void
    {
        $query->where(function (Builder $inner) use ($user): void {
            $inner->where('is_private', false)
                ->orWhere('owner_id', $user->id)
                ->orWhere('assigned_to_id', $user->id);
        });
    }
}
