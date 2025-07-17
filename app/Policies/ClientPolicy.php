<?php

namespace App\Policies;

use App\Models\Client;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ClientPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function viewAny(User $user)
    {
        // Check if user's department has access to the Clients section
        return $this->hasSectionAccess($user, 'Clients');
    }

    /**
     * Determine whether the user can view the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function view(User $user, Client $client)
    {
        // Check if user's department has access to the Clients section
        return $this->hasSectionAccess($user, 'Clients');
    }

    /**
     * Determine whether the user can create models.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function create(User $user)
    {
        // Only SuperAdmin, Admin, and Sales can create clients
        return in_array($user->department->name, ['SuperAdmin', 'Admin', 'Sales']) &&
               $this->hasSectionAccess($user, 'Clients');
    }

    /**
     * Determine whether the user can update the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function update(User $user, Client $client)
    {
        // Only SuperAdmin, Admin, and Sales can update clients
        return in_array($user->department->name, ['SuperAdmin', 'Admin', 'Sales']) &&
               $this->hasSectionAccess($user, 'Clients');
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function delete(User $user, Client $client)
    {
        // Only SuperAdmin and Admin can delete clients
        return in_array($user->department->name, ['SuperAdmin', 'Admin']) &&
               $this->hasSectionAccess($user, 'Clients');
    }

    /**
     * Check if the user's department has access to the specified section.
     *
     * @param  string  $sectionName
     * @return bool
     */
    private function hasSectionAccess(User $user, $sectionName)
    {
        // SuperAdmin has access to all sections
        if ($user->department->name === 'SuperAdmin') {
            return true;
        }

        // Check if the user's department has access to the specified section
        return $user->department->sections()
            ->where('name', $sectionName)
            ->exists();
    }
}
