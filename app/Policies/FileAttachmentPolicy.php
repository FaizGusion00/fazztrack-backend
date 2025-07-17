<?php

namespace App\Policies;

use App\Models\FileAttachment;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class FileAttachmentPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function viewAny(User $user)
    {
        // Check if user's department has access to the Files section
        return $this->hasSectionAccess($user, 'Files');
    }

    /**
     * Determine whether the user can view the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function view(User $user, FileAttachment $fileAttachment)
    {
        // Check if user's department has access to the Files section
        return $this->hasSectionAccess($user, 'Files');
    }

    /**
     * Determine whether the user can download the file.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function download(User $user, FileAttachment $fileAttachment)
    {
        // Check if user's department has access to the Files section
        return $this->hasSectionAccess($user, 'Files');
    }

    /**
     * Determine whether the user can create models.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function create(User $user)
    {
        // Check if user's department has access to the Files section
        return $this->hasSectionAccess($user, 'Files');
    }

    /**
     * Determine whether the user can update the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function update(User $user, FileAttachment $fileAttachment)
    {
        // SuperAdmin can update any file
        if ($user->department->name === 'SuperAdmin') {
            return true;
        }

        // Admin can update any file
        if ($user->department->name === 'Admin') {
            return true;
        }

        // Sales can update files they uploaded
        if ($user->department->name === 'Sales') {
            return $fileAttachment->uploaded_by === $user->id;
        }

        // Designers can update design files they uploaded
        if ($user->department->name === 'Designer') {
            return $fileAttachment->uploaded_by === $user->id &&
                   $fileAttachment->attachment_type === 'design';
        }

        return false;
    }

    /**
     * Determine whether the user can replace the file.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function replace(User $user, FileAttachment $fileAttachment)
    {
        // SuperAdmin can replace any file
        if ($user->department->name === 'SuperAdmin') {
            return true;
        }

        // Admin can replace any file
        if ($user->department->name === 'Admin') {
            return true;
        }

        // Sales can replace files they uploaded
        if ($user->department->name === 'Sales') {
            return $fileAttachment->uploaded_by === $user->id;
        }

        // Designers can replace design files they uploaded
        if ($user->department->name === 'Designer') {
            return $fileAttachment->uploaded_by === $user->id &&
                   $fileAttachment->attachment_type === 'design';
        }

        return false;
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function delete(User $user, FileAttachment $fileAttachment)
    {
        // Check if user's department has access to the Files section
        if (! $this->hasSectionAccess($user, 'Files')) {
            return false;
        }

        // SuperAdmin can delete any file
        if ($user->department->name === 'SuperAdmin') {
            return true;
        }

        // Admin can delete any file
        if ($user->department->name === 'Admin') {
            return true;
        }

        // Sales can delete files they uploaded
        if ($user->department->name === 'Sales') {
            return $fileAttachment->uploaded_by === $user->id;
        }

        // Designers can delete design files they uploaded if not finalized
        if ($user->department->name === 'Designer') {
            // If it's a design file, check if it's associated with a finalized design
            if ($fileAttachment->attachment_type === 'design' &&
                $fileAttachment->attachable_type === 'App\\Models\\OrderDesign') {

                $design = $fileAttachment->attachable;

                return $fileAttachment->uploaded_by === $user->id &&
                       ! in_array($design->status, ['finalized', 'completed']);
            }

            return $fileAttachment->uploaded_by === $user->id;
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
