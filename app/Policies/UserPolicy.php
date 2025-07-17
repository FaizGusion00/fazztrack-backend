<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class UserPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function viewAny(User $user)
    {
        // Only SuperAdmin and Admin can view users
        return in_array($user->department->name, ['SuperAdmin', 'Admin']);
    }

    /**
     * Determine whether the user can view the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function view(User $user, User $model)
    {
        // Users can view their own profile
        if ($user->id === $model->id) {
            return true;
        }

        // SuperAdmin and Admin can view any user
        return in_array($user->department->name, ['SuperAdmin', 'Admin']);
    }

    /**
     * Determine whether the user can create models.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function create(User $user)
    {
        // Only SuperAdmin can create users
        return $user->department->name === 'SuperAdmin';
    }

    /**
     * Determine whether the user can update the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function update(User $user, User $model)
    {
        // Users can update their own profile (except department and role)
        if ($user->id === $model->id) {
            return true;
        }

        // Only SuperAdmin can update other users
        return $user->department->name === 'SuperAdmin';
    }

    /**
     * Determine whether the user can update their password.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function updatePassword(User $user, User $model)
    {
        // Users can update their own password
        if ($user->id === $model->id) {
            return true;
        }

        // Only SuperAdmin can update other users' passwords
        return $user->department->name === 'SuperAdmin';
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function delete(User $user, User $model)
    {
        // Users cannot delete themselves
        if ($user->id === $model->id) {
            return false;
        }

        // Only SuperAdmin can delete users
        // Prevent deletion of the only SuperAdmin user
        if ($user->department->name === 'SuperAdmin') {
            // If the user to be deleted is a SuperAdmin, check if they're the last one
            if ($model->department->name === 'SuperAdmin') {
                $superAdminCount = User::whereHas('department', function ($query) {
                    $query->where('name', 'SuperAdmin');
                })->count();

                return $superAdminCount > 1;
            }

            return true;
        }

        return false;
    }
}
