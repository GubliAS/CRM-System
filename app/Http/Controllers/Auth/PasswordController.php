<?php

namespace App\Http\Controllers\Auth;

use App\Actions\Auth\RecordPasswordHistory;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\UpdatePasswordRequest;
use Illuminate\Http\RedirectResponse;

class PasswordController extends Controller
{
    /**
     * Update the user's password.
     */
    public function update(UpdatePasswordRequest $request, RecordPasswordHistory $history): RedirectResponse
    {
        $history->changePassword($request->user(), $request->string('password')->toString());

        return back();
    }
}
