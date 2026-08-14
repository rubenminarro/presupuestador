<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DiagnosticItem;
use App\Models\DiagnosticItemPhoto;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use App\Http\Requests\UploadDiagnosticItemPhotosRequest;
use App\Http\Requests\UpdateDiagnosticItemPhotoRequest;
use App\Http\Resources\DiagnosticItemPhotoResource;
use App\Traits\ApiResponse;

class DiagnosticItemPhotoController extends Controller
{
    use ApiResponse;

    public function index(DiagnosticItem $diagnosticItem)
    {
        
        $photos = $diagnosticItem
            ->photos()
            ->latest()
            ->get();
    
        return $this->successResponse(
            'Fotos obtenidas correctamente.',
            DiagnosticItemPhotoResource::collection($photos)
        );
    }

    public function store(UploadDiagnosticItemPhotosRequest $request, DiagnosticItem $diagnosticItem) 
    {

        $data = $request->validated();

        $photos = [];
        $storedPaths = [];

        try {
            DB::transaction(function () use (
                $data,
                $diagnosticItem,
                &$photos,
                &$storedPaths
            ) {
                foreach ($data['photos'] as $photoData) {
                    $file = $photoData['file'];

                    $path = $file->store(
                        'diagnostic-items',
                        'public'
                    );

                    $storedPaths[] = $path;

                    $photos[] = DiagnosticItemPhoto::create([
                        'diagnostic_item_id' => $diagnosticItem->id,
                        'path' => $path,
                        'original_name' => $file->getClientOriginalName(),
                        'description' => $photoData['description'] ?? null,
                    ]);
                }
            });
        } catch (\Throwable $e) {
            foreach ($storedPaths as $path) {
                Storage::disk('public')->delete($path);
            }

            throw $e;
        }

        return $this->successResponse(
            'Fotos cargadas correctamente.',
            DiagnosticItemPhotoResource::collection(
                collect($photos)
            ),
            201
        );
    }

    public function update(UpdateDiagnosticItemPhotoRequest $request, DiagnosticItem $diagnosticItem, DiagnosticItemPhoto $diagnosticItemPhoto) {
        
        $data = $request->validated();

        if (
            !array_key_exists('file', $data) &&
            !array_key_exists('description', $data)
        ) {
            return $this->errorResponse(
                'No se proporcionaron datos para actualizar.',
                null,
                422
            );
        }

        $oldPath = $diagnosticItemPhoto->path;
        $newPath = null;

        try {
            DB::transaction(function () use (
                $data,
                $diagnosticItemPhoto,
                &$newPath
            ) {
                if (array_key_exists('file', $data)) {
                    $file = $data['file'];

                    $newPath = $file->store(
                        'diagnostic-items',
                        'public'
                    );

                    $diagnosticItemPhoto->path = $newPath;
                    $diagnosticItemPhoto->original_name =
                        $file->getClientOriginalName();
                }

                if (array_key_exists('description', $data)) {
                    $diagnosticItemPhoto->description =
                        $data['description'];
                }

                $diagnosticItemPhoto->save();
            });

        } catch (\Throwable $e) {

            if ($newPath) {
                Storage::disk('public')->delete($newPath);
            }

            throw $e;
        }

        if (
            $newPath &&
            $oldPath &&
            $oldPath !== $newPath
        ) {
            Storage::disk('public')->delete($oldPath);
        }

        return $this->successResponse(
            'Foto actualizada correctamente.',
            new DiagnosticItemPhotoResource(
                $diagnosticItemPhoto
            )
        );
    }

    public function destroy(DiagnosticItem $diagnosticItem, DiagnosticItemPhoto $diagnosticItemPhoto) 
    {

        $path = $diagnosticItemPhoto->path;

        if ($path) {
            Storage::disk('public')->delete($path);
        }

        $diagnosticItemPhoto->delete();

        return $this->successResponse(
            'Foto eliminada correctamente.'
        );
    }
}