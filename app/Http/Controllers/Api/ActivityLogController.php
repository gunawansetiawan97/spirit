<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ActivityLogController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'loggable_type' => 'required|string',
            'loggable_id' => 'required|integer',
        ]);

        // Convert short name (e.g. 'account_group') to full model class (e.g. 'App\Models\AccountGroup')
        $loggableType = 'App\\Models\\' . Str::studly($request->loggable_type);

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
