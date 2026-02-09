<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\NumberSequence;
use App\Models\NumberSequenceCounter;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class NumberSequenceService
{
    /**
     * Generate the next number (atomic, thread-safe).
     */
    public static function generate(string $sequenceCode, ?int $branchId = null, ?Carbon $date = null): string
    {
        $date = $date ?? now();
        $sequence = NumberSequence::where('code', $sequenceCode)->firstOrFail();
        $branchCode = self::resolveBranchCode($sequence, $branchId);
        $scopeKey = self::buildScopeKey($sequence, $branchCode, $date);

        $nextNumber = DB::transaction(function () use ($sequence, $scopeKey) {
            $counter = NumberSequenceCounter::lockForUpdate()
                ->firstOrCreate(
                    [
                        'number_sequence_id' => $sequence->id,
                        'scope_key' => $scopeKey,
                    ],
                    ['last_number' => 0]
                );

            $counter->increment('last_number');

            return $counter->fresh()->last_number;
        });

        return self::formatNumber($sequence, $nextNumber, $branchCode, $date);
    }

    /**
     * Preview the next number without consuming it.
     */
    public static function peek(string $sequenceCode, ?int $branchId = null, ?Carbon $date = null): string
    {
        $date = $date ?? now();
        $sequence = NumberSequence::where('code', $sequenceCode)->firstOrFail();
        $branchCode = self::resolveBranchCode($sequence, $branchId);
        $scopeKey = self::buildScopeKey($sequence, $branchCode, $date);

        $counter = NumberSequenceCounter::where('number_sequence_id', $sequence->id)
            ->where('scope_key', $scopeKey)
            ->first();

        $nextNumber = ($counter?->last_number ?? 0) + 1;

        return self::formatNumber($sequence, $nextNumber, $branchCode, $date);
    }

    /**
     * Preview a format pattern with sample data (for configuration UI).
     */
    public static function previewFormat(
        string $prefix,
        string $separator,
        string $format,
        int $sequenceLength,
        string $scopeType = 'global',
        ?string $branchCode = null
    ): string {
        $date = now();
        $sampleNumber = 1;

        $seq = str_pad($sampleNumber, $sequenceLength, '0', STR_PAD_LEFT);

        $replacements = [
            '{prefix}' => $prefix,
            '{sep}' => $separator,
            '{branch}' => ($scopeType === 'branch' && $branchCode) ? $branchCode : '',
            '{YYYY}' => $date->format('Y'),
            '{YY}' => $date->format('y'),
            '{MM}' => $date->format('m'),
            '{DD}' => $date->format('d'),
            '{seq}' => $seq,
        ];

        return str_replace(array_keys($replacements), array_values($replacements), $format);
    }

    private static function resolveBranchCode(NumberSequence $sequence, ?int $branchId): ?string
    {
        if ($sequence->scope_type !== 'branch') {
            return null;
        }

        $branchId = $branchId ?? auth()->user()?->branch_id;

        return Branch::find($branchId)?->code;
    }

    private static function buildScopeKey(NumberSequence $sequence, ?string $branchCode, Carbon $date): string
    {
        $parts = [];

        if ($branchCode) {
            $parts[] = $branchCode;
        }

        switch ($sequence->reset_type) {
            case 'yearly':
                $parts[] = $date->format('Y');
                break;
            case 'monthly':
                $parts[] = $date->format('Y-m');
                break;
            case 'daily':
                $parts[] = $date->format('Y-m-d');
                break;
        }

        return implode('-', $parts) ?: 'global';
    }

    private static function formatNumber(NumberSequence $sequence, int $number, ?string $branchCode, Carbon $date): string
    {
        $seq = str_pad($number, $sequence->sequence_length, '0', STR_PAD_LEFT);

        $replacements = [
            '{prefix}' => $sequence->prefix,
            '{sep}' => $sequence->separator,
            '{branch}' => $branchCode ?? '',
            '{YYYY}' => $date->format('Y'),
            '{YY}' => $date->format('y'),
            '{MM}' => $date->format('m'),
            '{DD}' => $date->format('d'),
            '{seq}' => $seq,
        ];

        return str_replace(array_keys($replacements), array_values($replacements), $sequence->format);
    }
}
