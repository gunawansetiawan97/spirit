<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\NumberSequence;
use App\Services\NumberSequenceService;
use App\Traits\HasIndexQuery;
use Illuminate\Http\Request;

class NumberSequenceController extends Controller
{
    use HasIndexQuery;

    public function index(Request $request)
    {
        return $this->paginatedResponse(NumberSequence::query(), $request, [
            'searchFields' => ['code', 'name', 'prefix'],
            'filterFields' => ['reset_type', 'scope_type'],
            'defaultSort' => 'code',
        ]);
    }

    public function store(Request $request)
    {
        $request->validate(NumberSequence::storeRules());

        $sequence = NumberSequence::create($request->only([
            'code', 'name', 'prefix', 'separator', 'format',
            'reset_type', 'scope_type', 'sequence_length', 'is_active',
        ]));

        return $this->storeResponse($sequence);
    }

    public function show(NumberSequence $number_sequence)
    {
        return $this->showResponse($number_sequence);
    }

    public function update(Request $request, NumberSequence $number_sequence)
    {
        $request->validate(NumberSequence::updateRules($number_sequence->id));

        $number_sequence->update($request->only([
            'code', 'name', 'prefix', 'separator', 'format',
            'reset_type', 'scope_type', 'sequence_length', 'is_active',
        ]));

        return $this->updateResponse($number_sequence);
    }

    public function destroy(NumberSequence $number_sequence)
    {
        if ($number_sequence->counters()->sum('last_number') > 0) {
            return response()->json([
                'status' => 'error',
                'message' => 'Penomoran tidak dapat dihapus karena sudah pernah digunakan',
            ], 422);
        }

        $number_sequence->delete();

        return $this->destroyResponse($number_sequence);
    }

    public function all()
    {
        $sequences = NumberSequence::where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'uuid', 'code', 'name', 'prefix']);

        return response()->json([
            'status' => 'success',
            'data' => $sequences,
        ]);
    }

    /**
     * Preview next number by sequence code (for frontend form auto-fill).
     * GET /api/number-sequences/peek-by-code?code=branch
     */
    public function peekByCode(Request $request)
    {
        $request->validate(['code' => 'required|string']);

        $sequence = NumberSequence::where('code', $request->code)->where('is_active', true)->first();

        if (!$sequence) {
            return response()->json([
                'status' => 'success',
                'data' => ['next_number' => null],
            ]);
        }

        $branchId = $request->branch_id ?? auth()->user()?->branch_id;

        $preview = NumberSequenceService::peek(
            $sequence->code,
            $branchId,
            $request->date ? \Carbon\Carbon::parse($request->date) : null
        );

        return response()->json([
            'status' => 'success',
            'data' => ['next_number' => $preview],
        ]);
    }

    /**
     * Preview next number without consuming it.
     */
    public function peek(Request $request, NumberSequence $number_sequence)
    {
        $branchId = $request->branch_id ?? auth()->user()?->branch_id;

        $preview = NumberSequenceService::peek(
            $number_sequence->code,
            $branchId,
            $request->date ? \Carbon\Carbon::parse($request->date) : null
        );

        return response()->json([
            'status' => 'success',
            'data' => ['next_number' => $preview],
        ]);
    }

    /**
     * Generate and consume the next number.
     */
    public function generate(Request $request, NumberSequence $number_sequence)
    {
        $branchId = $request->branch_id ?? auth()->user()?->branch_id;

        $number = NumberSequenceService::generate(
            $number_sequence->code,
            $branchId,
            $request->date ? \Carbon\Carbon::parse($request->date) : null
        );

        return response()->json([
            'status' => 'success',
            'data' => ['number' => $number],
        ]);
    }

    /**
     * Preview format pattern with sample data (for configuration UI).
     */
    public function previewFormat(Request $request)
    {
        $request->validate([
            'prefix' => 'required|string|max:20',
            'separator' => 'nullable|string|max:5',
            'format' => 'required|string|max:100',
            'sequence_length' => 'required|integer|min:1|max:10',
            'scope_type' => 'required|in:global,branch',
            'branch_code' => 'nullable|string',
        ]);

        $preview = NumberSequenceService::previewFormat(
            $request->prefix,
            $request->separator ?? '/',
            $request->format,
            $request->sequence_length,
            $request->scope_type,
            $request->branch_code
        );

        return response()->json([
            'status' => 'success',
            'data' => ['preview' => $preview],
        ]);
    }
}
