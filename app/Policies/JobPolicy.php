<?php

namespace App\Policies;

use App\Models\Job;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class JobPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function viewAny(User $user)
    {
        // Check if user's department has access to the Jobs section
        return $this->hasSectionAccess($user, 'Jobs');
    }

    /**
     * Determine whether the user can view the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function view(User $user, Job $job)
    {
        // Check if user's department has access to the Jobs section
        if (! $this->hasSectionAccess($user, 'Jobs')) {
            return false;
        }

        // SuperAdmin, Admin, and Sales can view any job
        if (in_array($user->department->name, ['SuperAdmin', 'Admin', 'Sales'])) {
            return true;
        }

        // Production staff can view jobs assigned to them
        if ($user->production_role) {
            return $job->assigned_to === $user->id;
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
        // Only SuperAdmin, Admin, and Sales can create jobs
        return in_array($user->department->name, ['SuperAdmin', 'Admin', 'Sales']) &&
               $this->hasSectionAccess($user, 'Jobs');
    }

    /**
     * Determine whether the user can update the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function update(User $user, Job $job)
    {
        // Check if user's department has access to the Jobs section
        if (! $this->hasSectionAccess($user, 'Jobs')) {
            return false;
        }

        // SuperAdmin can update any job
        if ($user->department->name === 'SuperAdmin') {
            return true;
        }

        // Admin can update any job
        if ($user->department->name === 'Admin') {
            return true;
        }

        // Sales can update jobs for orders they created
        if ($user->department->name === 'Sales') {
            return $job->order->created_by === $user->id;
        }

        return false;
    }

    /**
     * Determine whether the user can start the job.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function start(User $user, Job $job)
    {
        // Check if user's department has access to the QR Scanning section
        if (! $this->hasSectionAccess($user, 'QR Scanning')) {
            return false;
        }

        // SuperAdmin can start any job
        if ($user->department->name === 'SuperAdmin') {
            return true;
        }

        // Admin can start any job
        if ($user->department->name === 'Admin') {
            return true;
        }

        // Production staff can only start jobs assigned to them
        if ($user->production_role) {
            return $job->assigned_to === $user->id && $job->status === 'pending';
        }

        return false;
    }

    /**
     * Determine whether the user can complete the job.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function complete(User $user, Job $job)
    {
        // Check if user's department has access to the QR Scanning section
        if (! $this->hasSectionAccess($user, 'QR Scanning')) {
            return false;
        }

        // SuperAdmin can complete any job
        if ($user->department->name === 'SuperAdmin') {
            return true;
        }

        // Admin can complete any job
        if ($user->department->name === 'Admin') {
            return true;
        }

        // Production staff can only complete jobs assigned to them
        if ($user->production_role) {
            return $job->assigned_to === $user->id && $job->status === 'in_progress';
        }

        return false;
    }

    /**
     * Determine whether the user can scan the job QR code.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function scan(User $user, Job $job)
    {
        // Check if user's department has access to the QR Scanning section
        if (! $this->hasSectionAccess($user, 'QR Scanning')) {
            return false;
        }

        // SuperAdmin can scan any job QR code
        if ($user->department->name === 'SuperAdmin') {
            return true;
        }

        // Admin can scan any job QR code
        if ($user->department->name === 'Admin') {
            return true;
        }

        // Production staff can only scan QR codes for jobs assigned to them
        if ($user->production_role) {
            return $job->assigned_to === $user->id;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function delete(User $user, Job $job)
    {
        // Check if user's department has access to the Jobs section
        if (! $this->hasSectionAccess($user, 'Jobs')) {
            return false;
        }

        // SuperAdmin can delete any job
        if ($user->department->name === 'SuperAdmin') {
            return true;
        }

        // Admin can delete any job that is pending
        if ($user->department->name === 'Admin') {
            return $job->status === 'pending';
        }

        // Sales can delete pending jobs for orders they created
        if ($user->department->name === 'Sales') {
            return $job->order->created_by === $user->id && $job->status === 'pending';
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
