<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use App\Models\User;
use Livewire\Component;

abstract class AdminComponent extends Component
{
    /** Returns the authenticated user with correct type for static analysis. */
    protected function authUser(): User
    {
        /** @var User */
        return auth()->user();
    }
}
