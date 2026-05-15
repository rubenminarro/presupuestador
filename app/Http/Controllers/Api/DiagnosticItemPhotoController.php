<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DiagnosticItem;
use App\Models\DiagnosticItemPhoto;
use Illuminate\Support\Facades\Storage;
use App\Http\Requests\UploadDiagnosticItemPhotosRequest;
use App\Http\Requests\UpdateDiagnosticItemPhotoRequest;
use App\Http\Resources\DiagnosticItemPhotoResource;
use App\Traits\ApiResponse;

class DiagnosticItemPhotoController extends Controller
{
    use ApiResponse;

    public function index(DiagnosticItem $diagnosticItem)
    {
        return $this->successResponse(
            'Fotos obtenidas correctamente.',
            DiagnosticItemPhotoResource::collection(
                $diagnosticItem->photos
            )
        );
    }

    public function store(UploadDiagnosticItemPhotosRequest $request, DiagnosticItem $diagnosticItem) 
    {

        $data = $request->validated();

        $photos = [];

        foreach ($data['photos'] as $photoData) {

            $file = $photoData['file'];

            $path = $file->store('diagnostic-items', 'public');

            $photo = DiagnosticItemPhoto::create([
                'diagnostic_item_id' => $diagnosticItem->id,
                'path' => $path,
                'original_name' => $file->getClientOriginalName(),
                'description' => $photoData['description'] ?? null,
            ]);

            $photos[] = $photo;
        }

        return $this->successResponse(
            'Fotos cargadas correctamente.',
            DiagnosticItemPhotoResource::collection(
                collect($photos)
            ),
            201
        );
    }

    public function update(UpdateDiagnosticItemPhotoRequest $request, DiagnosticItem $diagnosticItem, DiagnosticItemPhoto $diagnosticItemPhoto) 
    {

        $data = $request->validated();

        if ($request->hasFile('file')) {

            Storage::disk('public')->delete(
                $diagnosticItemPhoto->path
            );

            $file = $request->file('file');

            $path = $file->store(
                'diagnostic-items',
                'public'
            );

            $diagnosticItemPhoto->path = $path;

            $diagnosticItemPhoto->original_name = 
                $file->getClientOriginalName();
        }

        if (array_key_exists('description', $data)) {

            $diagnosticItemPhoto->description =
                $data['description'];
        }

        $diagnosticItemPhoto->save();

        return $this->successResponse(
            'Foto actualizada correctamente.',
            new DiagnosticItemPhotoResource(
                $diagnosticItemPhoto
            )
        );
    }

    public function destroy(DiagnosticItem $diagnosticItem, DiagnosticItemPhoto $diagnosticItemPhoto) 
    {

        if (
            $diagnosticItemPhoto->path &&
            Storage::disk('public')->exists(
                $diagnosticItemPhoto->path
            )
        ) {
            Storage::disk('public')->delete(
                $diagnosticItemPhoto->path
            );
        }

        $diagnosticItemPhoto->delete();

        return $this->successResponse(
            'Foto eliminada correctamente.'
        );
    }
}