<?php

namespace App\Policies;

use App\Models\OrderDesign;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class OrderDesignPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function viewAny(User $user)
    {
        // Check if user's department has access to the Design Upload section
        return $this->hasSectionAccess($user, 'Design Upload');
    }

    /**
     * Determine whether the user can view the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function view(User $user, OrderDesign $design)
    {
        // Check if user's department has access to the Design Upload section
        if (! $this->hasSectionAccess($user, 'Design Upload')) {
            return false;
        }

        // SuperAdmin, Admin, and Sales can view any design
        if (in_array($user->department->name, ['SuperAdmin', 'Admin', 'Sales'])) {
            return true;
        }

        // Designers can view designs assigned to them
        if ($user->department->name === 'Designer') {
            return $design->designer_id === $user->id;
        }

        // Production staff can view designs for orders they have jobs for
        if ($user->production_role) {
            return $design->order->jobs()->where('assigned_to', $user->id)->exists();
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
        // Only SuperAdmin, Admin, and Sales can create designs
        return in_array($user->department->name, ['SuperAdmin', 'Admin', 'Sales']) &&
               $this->hasSectionAccess($user, 'Design Upload');
    }

    /**
     * Determine whether the user can update the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function update(User $user, OrderDesign $design)
    {
        // Check if user's department has access to the Design Upload section
        if (! $this->hasSectionAccess($user, 'Design Upload')) {
            return false;
        }

        // SuperAdmin can update any design
        if ($user->department->name === 'SuperAdmin') {
            return true;
        }

        // Admin can update any design
        if ($user->department->name === 'Admin') {
            return true;
        }

        // Sales can update designs for orders they created
        if ($user->department->name === 'Sales') {
            return $design->order->created_by === $user->id;
        }

        // Designers can update designs assigned to them
        if ($user->department->name === 'Designer') {
            return $design->designer_id === $user->id &&
                   ! in_array($design->status, ['finalized', 'completed']);
        }

        return false;
    }

    /**
     * Determine whether the user can finalize the design.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function finalize(User $user, OrderDesign $design)
    {
        // Check if user's department has access to the Design Upload section
        if (! $this->hasSectionAccess($user, 'Design Upload')) {
            return false;
        }

        // SuperAdmin can finalize any design
        if ($user->department->name === 'SuperAdmin') {
            return true;
        }

        // Admin can finalize any design
        if ($user->department->name === 'Admin') {
            return true;
        }

        // Designers can finalize designs assigned to them
        if ($user->department->name === 'Designer') {
            return $design->designer_id === $user->id &&
                   in_array($design->status, ['new', 'in_progress']);
        }

        return false;
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function delete(User $user, OrderDesign $design)
    {
        // Check if user's department has access to the Design Upload section
        if (! $this->hasSectionAccess($user, 'Design Upload')) {
            return false;
        }

        // SuperAdmin can delete any design
        if ($user->department->name === 'SuperAdmin') {
            return true;
        }

        // Admin can delete any design that is not finalized or completed
        if ($user->department->name === 'Admin') {
            return ! in_array($design->status, ['finalized', 'completed']);
        }

        // Sales can delete designs for orders they created that are not finalized or completed
        if ($user->department->name === 'Sales') {
            return $design->order->created_by === $user->id &&
                   ! in_array($design->status, ['finalized', 'completed']);
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
