<?php

namespace App\Http\Controllers;

use App\Models\FileAttachment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class FileAttachmentController extends Controller
{
    /**
     * Display a listing of the file attachments.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        // Check if user has permission to view file attachments
        $this->authorize('viewAny', FileAttachment::class);

        $query = FileAttachment::query();

        // Search by file name
        if ($request->has('search')) {
            $search = $request->search;
            $query->where('file_name', 'like', "%{$search}%");
        }

        // Pagination
        $perPage = $request->input('per_page', 15);
        $files = $query->orderBy('created_at', 'desc')->paginate($perPage);

        return response()->json($files);
    }

    /**
     * Store a newly created file attachment in storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        // Check if user has permission to create file attachments
        $this->authorize('create', FileAttachment::class);

        $request->validate([
            'file' => 'required|file|max:10240', // 10MB max
            'type' => 'sometimes|in:receipt,design,job_sheet,other',
        ]);

        // Determine the storage directory based on file type
        $type = $request->input('type', 'other');
        $directory = $type.'s'; // receipts, designs, job_sheets, others

        // Store the file
        $file = $request->file('file');
        $path = $file->store($directory, 'public');

        // Create a file attachment record
        $fileAttachment = new FileAttachment;
        $fileAttachment->file_path = $path;
        $fileAttachment->file_name = $file->getClientOriginalName();
        $fileAttachment->save();

        return response()->json($fileAttachment, 201);
    }

    /**
     * Display the specified file attachment.
     *
     * @return \Illuminate\Http\Response
     */
    public function show(FileAttachment $fileAttachment)
    {
        // Check if user has permission to view this file attachment
        $this->authorize('view', $fileAttachment);

        return response()->json($fileAttachment);
    }

    /**
     * Download the specified file attachment.
     *
     * @return \Illuminate\Http\Response
     */
    public function download(FileAttachment $fileAttachment)
    {
        // Check if user has permission to download this file attachment
        $this->authorize('download', $fileAttachment);

        // Check if the file exists
        if (! Storage::disk('public')->exists($fileAttachment->file_path)) {
            return response()->json([
                'message' => 'File not found',
            ], 404);
        }

        /** @var \Illuminate\Contracts\Filesystem\Filesystem $disk */
        $disk = Storage::disk('public');

        return $disk->download(
            $fileAttachment->file_path,
            $fileAttachment->file_name
        );
    }

    /**
     * Update the specified file attachment in storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, FileAttachment $fileAttachment)
    {
        // Check if user has permission to update this file attachment
        $this->authorize('update', $fileAttachment);

        $validated = $request->validate([
            'file_name' => 'sometimes|required|string|max:255',
        ]);

        $fileAttachment->update($validated);

        return response()->json($fileAttachment);
    }

    /**
     * Replace the file for the specified file attachment.
     *
     * @return \Illuminate\Http\Response
     */
    public function replace(Request $request, FileAttachment $fileAttachment)
    {
        // Check if user has permission to update this file attachment
        $this->authorize('update', $fileAttachment);

        $request->validate([
            'file' => 'required|file|max:10240', // 10MB max
        ]);

        // Delete the old file
        Storage::disk('public')->delete($fileAttachment->file_path);

        // Extract the directory from the existing path
        $pathParts = explode('/', $fileAttachment->file_path);
        array_pop($pathParts); // Remove the filename
        $directory = implode('/', $pathParts);

        // Store the new file
        $file = $request->file('file');
        $path = $file->store($directory, 'public');

        // Update the file attachment record
        $fileAttachment->file_path = $path;
        $fileAttachment->file_name = $file->getClientOriginalName();
        $fileAttachment->save();

        return response()->json($fileAttachment);
    }

    /**
     * Remove the specified file attachment from storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function destroy(FileAttachment $fileAttachment)
    {
        // Check if user has permission to delete this file attachment
        $this->authorize('delete', $fileAttachment);

        // Check if the file is used in any related models
        $usedInPayments = $fileAttachment->payments()->exists();
        $usedInDesigns = $fileAttachment->designs()->exists();

        if ($usedInPayments || $usedInDesigns) {
            return response()->json([
                'message' => 'Cannot delete file that is used in payments or designs',
            ], 422);
        }

        // Delete the file from storage
        Storage::disk('public')->delete($fileAttachment->file_path);

        // Delete the record
        $fileAttachment->delete();

        return response()->json(null, 204);
    }
}
