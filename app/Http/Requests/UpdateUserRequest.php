<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
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
        return [
            'user_id' => 'required|exists:users,id',
            'name' => 'sometimes',
            'email' => ['sometimes', 'email', Rule::unique('users')->ignore($this->user_id)],
            'cpf' => ['sometimes', Rule::unique('users')->ignore($this->user_id), 'digits:11'],
            'pis' => ['sometimes', Rule::unique('users')->ignore($this->user_id), 'digits:11'],
            'role' => 'sometimes|in:admin,user',
            'password' => 'sometimes|confirmed',
            'work_journey_hours' => 'sometimes|numeric|min:0|max:24'
        ];
    }
}
