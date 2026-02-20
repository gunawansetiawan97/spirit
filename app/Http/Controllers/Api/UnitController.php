<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Unit;
use App\Traits\HasIndexQuery;
use Illuminate\Http\Request;

class UnitController extends Controller
{
    use HasIndexQuery;

    public function index(Request $request)
    {
        return $this->paginatedResponse(Unit::query(), $request, [
            'searchFields' => ['code'],
            'defaultSort' => 'code',
        ]);
    }

    public function store(Request $request)
    {
        $request->validate(Unit::storeRules());

        $unit = Unit::create($request->only(['code', 'name', 'is_active']));

        return $this->storeResponse($unit);
    }

    public function show(Unit $unit)
    {
        return $this->showResponse($unit);
    }

    public function update(Request $request, Unit $unit)
    {
        $request->validate(Unit::updateRules($unit->id));

        $unit->update($request->only(['code', 'name', 'is_active']));

        return $this->updateResponse($unit); 
    }

    public function destroy(Unit $unit)
    {
        $unit->delete();

        return $this->destroyResponse($unit);
    }

    public function all()
    {
        $types = Unit::where('is_active', true)
            ->orderBy('code')
            ->get(['id', 'uuid', 'code', 'name']);

        return response()->json([
            'status' => 'success',
            'data' => $types,
        ]);
    }
}
