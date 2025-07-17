<?php

namespace App\Policies;

use App\Models\Payment;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class PaymentPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function viewAny(User $user)
    {
        // Check if user's department has access to the Payments section
        return $this->hasSectionAccess($user, 'Payments');
    }

    /**
     * Determine whether the user can view the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function view(User $user, Payment $payment)
    {
        // Check if user's department has access to the Payments section
        if (! $this->hasSectionAccess($user, 'Payments')) {
            return false;
        }

        // SuperAdmin, Admin can view any payment
        if (in_array($user->department->name, ['SuperAdmin', 'Admin'])) {
            return true;
        }

        // Sales can view payments for orders they created
        if ($user->department->name === 'Sales') {
            return $payment->order->created_by === $user->id;
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
        // Only SuperAdmin, Admin, and Sales can create payments
        return in_array($user->department->name, ['SuperAdmin', 'Admin', 'Sales']) &&
               $this->hasSectionAccess($user, 'Payments');
    }

    /**
     * Determine whether the user can update the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function update(User $user, Payment $payment)
    {
        // Check if user's department has access to the Payments section
        if (! $this->hasSectionAccess($user, 'Payments')) {
            return false;
        }

        // SuperAdmin can update any payment
        if ($user->department->name === 'SuperAdmin') {
            return true;
        }

        // Admin can update any payment
        if ($user->department->name === 'Admin') {
            return true;
        }

        // Sales can only update payments they created and that are not approved
        if ($user->department->name === 'Sales') {
            return $payment->order->created_by === $user->id &&
                   $payment->status !== 'approved';
        }

        return false;
    }

    /**
     * Determine whether the user can approve payments.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function approve(User $user, Payment $payment)
    {
        // Check if user's department has access to the Payments section
        if (! $this->hasSectionAccess($user, 'Payments')) {
            return false;
        }

        // Only SuperAdmin and Admin can approve payments
        return in_array($user->department->name, ['SuperAdmin', 'Admin']);
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function delete(User $user, Payment $payment)
    {
        // Check if user's department has access to the Payments section
        if (! $this->hasSectionAccess($user, 'Payments')) {
            return false;
        }

        // SuperAdmin can delete any payment
        if ($user->department->name === 'SuperAdmin') {
            return true;
        }

        // Admin can delete any payment that is not approved
        if ($user->department->name === 'Admin') {
            return $payment->status !== 'approved';
        }

        // Sales can only delete pending payments for orders they created
        if ($user->department->name === 'Sales') {
            return $payment->order->created_by === $user->id &&
                   $payment->status === 'pending';
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
