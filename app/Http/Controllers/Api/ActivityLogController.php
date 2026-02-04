<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Http\Request;

class ActivityLogController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'loggable_type' => 'required|string',
            'loggable_id' => 'required|integer',
        ]);

        // Map short names to full model class
        $typeMap = [
            'branch' => 'App\\Models\\Branch',
            'user' => 'App\\Models\\User',
            'role' => 'App\\Models\\Role',
        ];

        $loggableType = $typeMap[$request->loggable_type] ?? $request->loggable_type;

        $logs = ActivityLog::with('user:id,name')
            ->where('loggable_type', $loggableType)
            ->where('loggable_id', $request->loggable_id)
            ->orderByDesc('created_at')
            ->paginate($request->per_page ?? 20);

        return response()->json([
            'status' => 'success',
            'data' => $logs->items(),
            'meta' => [
                'current_page' => $logs->currentPage(),
                'per_page' => $logs->perPage(),
                'total' => $logs->total(),
                'last_page' => $logs->lastPage(),
            ],
        ]);
    }
}
