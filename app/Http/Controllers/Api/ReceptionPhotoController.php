<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Reception;
use App\Models\ReceptionPhoto;
use Illuminate\Support\Facades\Storage;
use App\Http\Requests\UploadReceptionPhotosRequest;
use App\Http\Requests\UpdateReceptionPhotoRequest;
use App\Http\Resources\ReceptionPhotoResource;
use App\Traits\ApiResponse;

class ReceptionPhotoController extends Controller
{
    
    use ApiResponse;
    
    public function index(Reception $reception)
    {
        return $this->successResponse(
            'Fotos obtenidas correctamente.',
            ReceptionPhotoResource::collection(
                $reception->photos
            )
        );
    }

    public function store(UploadReceptionPhotosRequest $request, Reception $reception) 
    {

        $data = $request->validated();

        $photos = [];

        foreach ($data['photos'] as $photoData) {

            $file = $photoData['file'];

            $path = $file->store('receptions', 'public');

            $photo = ReceptionPhoto::create([
                'reception_id' => $reception->id,
                'path' => $path,
                'original_name' => $file->getClientOriginalName(),
                'description' => $photoData['description'] ?? null,
            ]);

            $photos[] = $photo;
        }

        return $this->successResponse(
            'Fotos cargadas correctamente.',
            ReceptionPhotoResource::collection(collect($photos)),
            201
        );
    }

    public function update(UpdateReceptionPhotoRequest $request, Reception $reception, ReceptionPhoto $receptionPhoto)
    {

        $data = $request->validated();

        if ($request->hasFile('file')) {

            Storage::disk('public')->delete($receptionPhoto->path);

            $file = $request->file('file');

            $path = $file->store('receptions', 'public');

            $receptionPhoto->path = $path;
            $receptionPhoto->original_name = $file->getClientOriginalName();
        }

        if (array_key_exists('description', $data)) {
            $receptionPhoto->description = $data['description'];
        }

        $receptionPhoto->save();

        return $this->successResponse(
            'Foto actualizada correctamente.',
            new ReceptionPhotoResource($receptionPhoto)
        );
    }

    public function destroy(Reception $reception, ReceptionPhoto $receptionPhoto)
    {
        if ($receptionPhoto->path && Storage::disk('public')->exists($receptionPhoto->path)) {
            Storage::disk('public')->delete($receptionPhoto->path);
        }

        $receptionPhoto->delete();

        return $this->successResponse(
            'Foto eliminada correctamente.'
        );
    }

}
