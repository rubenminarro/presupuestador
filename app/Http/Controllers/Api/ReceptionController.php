<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Reception;
use App\Http\Requests\StoreReceptionRequest;
use App\Http\Requests\UpdateReceptionRequest;
use App\Http\Resources\ShowReceptionResource;
use App\Traits\ApiResponse;
use Illuminate\Support\Facades\Auth;

class ReceptionController extends Controller
{
    
    use ApiResponse;

    public function index(Request $request)
    {
        $receptions = Reception::with([
                'client',
                'vehicle',
                'createdBy',
                'approvedBy',
            ])
            ->where('active', true)
            ->latest()
            ->paginate(15);

        return $this->successResponse(
            'Recepciones obtenidas correctamente.',
            $receptions
        );
    }

    public function store(StoreReceptionRequest $request)
    {
        $data = $request->validated();

        $data['created_by'] = Auth::id();
        $data['status'] = $data['status'] ?? 'pending';

        $reception = Reception::create($data);

        return $this->successResponse(
            'Recepción creada correctamente.',
            new ShowReceptionResource(
                $reception->load([
                    'client',
                    'vehicle',
                    'createdBy',
                    'approvedBy',
                ])
            ),
            201
        );
    }

    public function show(Reception $reception)
    {
        return $this->successResponse(
            'Recepción encontrada.',
            new ShowReceptionResource(
                $reception->load([
                    'client',
                    'vehicle',
                    'createdBy',
                    'approvedBy',
                ])
            )
        );
    }

    public function update(UpdateReceptionRequest $request, Reception $reception) 
    {
        $data = $request->validated();

        $reception->update($data);

        return $this->successResponse(
            'Recepción actualizada correctamente.',
            new ShowReceptionResource(
                $reception->load([
                    'client',
                    'vehicle',
                    'createdBy',
                    'approvedBy',
                ])
            )
        );
    }

    public function activate(Reception $reception)
    {
        $reception->update([
            'active' => !$reception->active
        ]);

        return $this->successResponse(
            $reception->active
                ? 'Recepción activada correctamente.'
                : 'Recepción desactivada correctamente.',
            [
                'id' => $reception->id,
                'active' => $reception->active
            ]
        );
    }
}
