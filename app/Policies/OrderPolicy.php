<?php

namespace App\Policies;

use App\Models\Order;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class OrderPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function viewAny(User $user)
    {
        // Check if user's department has access to the Orders section
        return $this->hasSectionAccess($user, 'Orders');
    }

    /**
     * Determine whether the user can view the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function view(User $user, Order $order)
    {
        // Check if user's department has access to the Orders section
        if (! $this->hasSectionAccess($user, 'Orders')) {
            return false;
        }

        // SuperAdmin, Admin, and Sales can view any order
        if (in_array($user->department->name, ['SuperAdmin', 'Admin', 'Sales'])) {
            return true;
        }

        // Designers can view orders they are assigned to
        if ($user->department->name === 'Designer') {
            return $order->orderDesign && $order->orderDesign->designer_id === $user->id;
        }

        // Production staff can view orders they have jobs for
        if ($user->production_role) {
            return $order->jobs()->where('assigned_to', $user->id)->exists();
        }

        return false;
    }

    /**
     * Determine whether the user can create models.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function create(User $user)
    {
        // Only SuperAdmin, Admin, and Sales can create orders
        return in_array($user->department->name, ['SuperAdmin', 'Admin', 'Sales']) &&
               $this->hasSectionAccess($user, 'Orders');
    }

    /**
     * Determine whether the user can update the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function update(User $user, Order $order)
    {
        // Check if user's department has access to the Orders section
        if (! $this->hasSectionAccess($user, 'Orders')) {
            return false;
        }

        // SuperAdmin can update any order
        if ($user->department->name === 'SuperAdmin') {
            return true;
        }

        // Admin can update any order
        if ($user->department->name === 'Admin') {
            return true;
        }

        // Sales can only update orders they created and that are not completed
        if ($user->department->name === 'Sales') {
            return $order->created_by === $user->id &&
                   ! in_array($order->status, ['completed', 'delivered']);
        }

        return false;
    }

    /**
     * Determine whether the user can update the order status.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function updateStatus(User $user, Order $order)
    {
        // Check if user's department has access to the Orders section
        if (! $this->hasSectionAccess($user, 'Orders')) {
            return false;
        }

        // SuperAdmin can update any order status
        if ($user->department->name === 'SuperAdmin') {
            return true;
        }

        // Admin can update any order status
        if ($user->department->name === 'Admin') {
            return true;
        }

        // Sales can update status for orders they created
        if ($user->department->name === 'Sales') {
            return $order->created_by === $user->id;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function delete(User $user, Order $order)
    {
        // Check if user's department has access to the Orders section
        if (! $this->hasSectionAccess($user, 'Orders')) {
            return false;
        }

        // SuperAdmin can delete any order
        if ($user->department->name === 'SuperAdmin') {
            return true;
        }

        // Admin can delete any order
        if ($user->department->name === 'Admin') {
            return true;
        }

        // Sales can only delete pending orders they created
        if ($user->department->name === 'Sales') {
            return $order->created_by === $user->id && $order->status === 'pending';
        }

        return false;
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
