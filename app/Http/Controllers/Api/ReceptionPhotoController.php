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
use Illuminate\Support\Facades\DB;
use Exception;

class ReceptionPhotoController extends Controller
{
    
    use ApiResponse;
    
    public function index(Reception $reception)
    {
        return $this->successResponse(
            'Fotos obtenidas correctamente.',
            ReceptionPhotoResource::collection($reception->photos),
            200
        );
    }

    public function store(UploadReceptionPhotosRequest $request, Reception $reception) 
    {

        $data = $request->validated();
        $uploadedFiles = [];
        $photos = [];

        DB::beginTransaction();

        try {
            foreach ($data['photos'] as $photoData) {
                $file = $photoData['file'];
                
                $path = $file->store('receptions', 'public');
                $uploadedFiles[] = $path;

                $photo = $reception->photos()->create([
                    'path' => $path,
                    'original_name' => $file->getClientOriginalName(),
                    'description' => $photoData['description'] ?? null,
                ]);

                $photos[] = $photo;
            }

            DB::commit();

            return $this->successResponse(
                'Fotos cargadas correctamente.',
                ReceptionPhotoResource::collection(collect($photos)),
                201
            );

        } catch (Exception $e) {
            DB::rollBack();
            
            foreach ($uploadedFiles as $path) {
                Storage::disk('public')->delete($path);
            }

            return $this->errorResponse('Error al cargar las fotos.', 500); 
        }
    }

    public function update(UpdateReceptionPhotoRequest $request, Reception $reception, ReceptionPhoto $photo)
    {

        $data = $request->validated();
        $oldPath = null;

        if ($request->hasFile('file')) {
            $oldPath = $photo->path;
            $file = $request->file('file');
            
            $photo->path = $file->store('receptions', 'public');
            $photo->original_name = $file->getClientOriginalName();
        }

        if (array_key_exists('description', $data)) {
            $photo->description = $data['description'];
        }

        $photo->save();

        if ($oldPath) {
            Storage::disk('public')->delete($oldPath);
        }

        return $this->successResponse(
            'Foto actualizada correctamente.',
            new ReceptionPhotoResource($photo),
            200
        );
    }

    public function destroy(Reception $reception, ReceptionPhoto $photo)
    {
        
        if ($photo->path && Storage::disk('public')->exists($photo->path)) {
            Storage::disk('public')->delete($photo->path);
        }

        $photo->delete();

        return $this->successResponse(
            'Foto eliminada correctamente.'
        );
    }

}
