<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validasi input KPI Actual (realisasi) — dipakai storeActual().
 * Otorisasi ditangani terpusat di controller.
 */
class StoreKpiActualRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'kpi_target_id' => 'required|exists:kpi_targets,id',
            'staff_id'      => 'required|exists:users,id',
            'month'         => 'required|integer|min:1|max:12',
            'year'          => 'required|integer|min:2024|max:2030',
            'actual_value'  => 'required|numeric|min:0',
            'notes'         => 'nullable|string|max:1000',
        ];
    }
}
