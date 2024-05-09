<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Validator;

class CreateTicketRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $type = $this->input('type');
        if (!in_array($type, ['create', 'delete', 'update'])) {
            throw new \InvalidArgumentException('Tipo de ticket inválido');
        }

        $rules = [
            'type' => 'required',
            'justification' => 'required',
        ];

        if ($type === 'create') {
            $rules['clock_event_id'] = 'nullable';
            $rules['requested_data'] = [
                'required',
                'array',
                $this->getRequestedDataValidator(),
            ];
        }

        if ($type === 'update') {
            $rules['clock_event_id'] = 'required|exists:clock_events,id';
            $rules['requested_data'] = [
                'required',
                'array',
                $this->getRequestedDataValidator(),
            ];
        }

        if ($type === 'delete') {
            $rules['clock_event_id'] = 'required|exists:clock_events,id';
            $rules['requested_data'] = 'nullable';
        }

        return $rules;
    }

    private function getRequestedDataValidator()
    {
        return function ($attribute, $value, $fail) {
            $validator = Validator::make($value, [
                'timestamp' => ['required'],
                'justification' => ['required'],
                'day_off' => ['required', 'boolean'],
                'doctor' => ['required', 'boolean'],
            ]);

            if ($validator->fails()) {
                $fail('Dados de atualização inválidos.');
            }
        };
    }
}
