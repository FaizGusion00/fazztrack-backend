<?php

namespace App\Policies;

use App\Models\OrderItem;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class OrderItemPolicy
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
    public function view(User $user, OrderItem $orderItem)
    {
        // Check if user's department has access to the Orders section
        return $this->hasSectionAccess($user, 'Orders');
    }

    /**
     * Determine whether the user can create models.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function create(User $user)
    {
        // Only SuperAdmin, Admin, and Sales can create order items
        return in_array($user->department->name, ['SuperAdmin', 'Admin', 'Sales']) &&
               $this->hasSectionAccess($user, 'Orders');
    }

    /**
     * Determine whether the user can update the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function update(User $user, OrderItem $orderItem)
    {
        // Check if user's department has access to the Orders section
        if (! $this->hasSectionAccess($user, 'Orders')) {
            return false;
        }

        // SuperAdmin can update any order item
        if ($user->department->name === 'SuperAdmin') {
            return true;
        }

        // Admin can update any order item if the order is not completed
        if ($user->department->name === 'Admin') {
            return $orderItem->order->status !== 'completed';
        }

        // Sales can update order items for orders they created if the order is pending
        if ($user->department->name === 'Sales') {
            return $orderItem->order->created_by === $user->id &&
                   $orderItem->order->status === 'pending';
        }

        return false;
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function delete(User $user, OrderItem $orderItem)
    {
        // Check if user's department has access to the Orders section
        if (! $this->hasSectionAccess($user, 'Orders')) {
            return false;
        }

        // SuperAdmin can delete any order item
        if ($user->department->name === 'SuperAdmin') {
            return true;
        }

        // Admin can delete any order item if the order is not completed
        if ($user->department->name === 'Admin') {
            return $orderItem->order->status !== 'completed';
        }

        // Sales can delete order items for orders they created if the order is pending
        if ($user->department->name === 'Sales') {
            return $orderItem->order->created_by === $user->id &&
                   $orderItem->order->status === 'pending';
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
