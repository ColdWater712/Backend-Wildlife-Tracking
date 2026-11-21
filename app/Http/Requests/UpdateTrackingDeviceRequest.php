<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdateTrackingDeviceRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        $id = $this->route('id');
        return [
            'animal_id' => ['nullable', 'integer', 'exists:animals,animal_id', Rule::unique('tracking_devices', 'animal_id')->ignore($id, 'device_id')],
            'model' => 'sometimes|string|max:100',
            'installation_date' => 'sometimes|date|before_or_equal:today',
            'battery_life_months' => 'sometimes|integer|min:1|max:60',
            'battery_level' => 'sometimes|integer|min:0|max:100',
            'signal_strength' => 'sometimes|integer|min:0|max:5',
            'device_condition' => 'sometimes|in:normal,damaged,lost',
            'is_active' => 'sometimes|boolean',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => $validator->errors()->first(),
            'data' => null,
            'errors' => $validator->errors(),
        ], 422));
    }
}