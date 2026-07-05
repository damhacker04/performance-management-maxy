<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Validasi KPI target departemen — dipakai store() & update().
 * update() menambah `is_active` (boolean); no-op saat store().
 *
 * Otorisasi (isExecutive / is_management) sengaja tidak di sini — ditangani
 * terpusat di controller agar konsisten dengan method KPI lain.
 */
class KpiTargetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'department'   => 'required|string|in:' . implode(',', array_keys(User::DEPARTMENTS)),
            'aggregation'  => 'required|in:' . implode(',', array_keys(\App\Models\KpiTarget::AGGREGATIONS)),
            'kpi_name'     => 'required|string|max:255',
            // Milestone tak butuh target/satuan angka (auto 100 / '%' di controller).
            'target_value' => 'required_unless:aggregation,milestone|nullable|numeric|min:0',
            'unit'         => 'required_unless:aggregation,milestone|nullable|string|max:100',
            'month'        => 'required|integer|min:1|max:12',
            'year'         => 'required|integer|min:2024|max:2030',
            'notes'        => 'nullable|string|max:1000',
            'is_active'    => 'boolean',
        ];
    }
}
