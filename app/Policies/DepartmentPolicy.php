<?php

namespace App\Policies;

use App\Models\Department;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class DepartmentPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function viewAny(User $user)
    {
        // Only SuperAdmin can view departments
        return $user->department->name === 'SuperAdmin';
    }

    /**
     * Determine whether the user can view the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function view(User $user, Department $department)
    {
        // Only SuperAdmin can view departments
        return $user->department->name === 'SuperAdmin';
    }

    /**
     * Determine whether the user can create models.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function create(User $user)
    {
        // Only SuperAdmin can create departments
        return $user->department->name === 'SuperAdmin';
    }

    /**
     * Determine whether the user can update the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function update(User $user, Department $department)
    {
        // Only SuperAdmin can update departments
        return $user->department->name === 'SuperAdmin';
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function delete(User $user, Department $department)
    {
        // Only SuperAdmin can delete departments
        // Prevent deletion of the SuperAdmin department
        return $user->department->name === 'SuperAdmin' &&
               $department->name !== 'SuperAdmin';
    }
}
