<?php

namespace App\Policies;

use App\Models\Product;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ProductPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function viewAny(User $user)
    {
        // Check if user's department has access to the Products section
        return $this->hasSectionAccess($user, 'Products');
    }

    /**
     * Determine whether the user can view the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function view(User $user, Product $product)
    {
        // Check if user's department has access to the Products section
        return $this->hasSectionAccess($user, 'Products');
    }

    /**
     * Determine whether the user can create models.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function create(User $user)
    {
        // Only SuperAdmin and Admin can create products
        return in_array($user->department->name, ['SuperAdmin', 'Admin']) &&
               $this->hasSectionAccess($user, 'Products');
    }

    /**
     * Determine whether the user can update the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function update(User $user, Product $product)
    {
        // Only SuperAdmin and Admin can update products
        return in_array($user->department->name, ['SuperAdmin', 'Admin']) &&
               $this->hasSectionAccess($user, 'Products');
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function delete(User $user, Product $product)
    {
        // Only SuperAdmin and Admin can delete products
        return in_array($user->department->name, ['SuperAdmin', 'Admin']) &&
               $this->hasSectionAccess($user, 'Products');
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
