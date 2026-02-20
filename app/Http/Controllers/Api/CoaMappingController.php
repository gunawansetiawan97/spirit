<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CoaMapping;
use App\Traits\HasIndexQuery;
use Illuminate\Http\Request;

class CoaMappingController extends Controller
{
    use HasIndexQuery;

    public function index(Request $request)
    {
        return $this->paginatedResponse(
            CoaMapping::with('coa:id,code,name'),
            $request,
            [
                'searchFields' => ['module', 'mapping_key', 'description', 'coa.code', 'coa.name'],
                'filterFields' => ['module'],
                'defaultSort' => 'module',
            ]
        );
    }

    public function store(Request $request)
    {
        $request->validate(CoaMapping::storeRules($request));

        $mapping = CoaMapping::create($request->only([
            'module', 'mapping_key', 'coa_id', 'description', 'is_active',
        ]));

        return $this->storeResponse($mapping); 
    }

    public function show(CoaMapping $coa_mapping)
    {
        return $this->showResponse($coa_mapping, ['coa:id,uuid,code,name']);
    }

    public function update(Request $request, CoaMapping $coa_mapping)
    {
        $request->validate(CoaMapping::updateRules($coa_mapping->id, $request));

        $coa_mapping->update($request->only([
            'module', 'mapping_key', 'coa_id', 'description', 'is_active',
        ]));

        return $this->updateResponse($coa_mapping);  
    }

    public function destroy(CoaMapping $coa_mapping)
    {
        $coa_mapping->delete();

        return $this->destroyResponse($coa_mapping); 
    }
}
