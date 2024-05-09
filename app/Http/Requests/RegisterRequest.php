<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class RegisterRequest extends FormRequest
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
            'name' => 'required|string|max:255',
            'cpf' => 'required|string|size:11|unique:users',
            'pis' => 'required|string|size:11|unique:users',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:5|confirmed',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        $errors = $validator->errors();

        if ($errors->has('password') && str_contains($errors->first('password'), 'confirmação')) {
            throw new HttpResponseException(response(['error' => $errors->first('password')], 494));
        }

        $fields = ['name', 'cpf', 'email', 'password', 'pis'];
        $statusCodes = [490, 491, 492, 493, 495];

        foreach ($fields as $index => $field) {
            if ($errors->has($field)) {
                throw new HttpResponseException(response(['error' => $errors->first($field)], $statusCodes[$index]));
            }
        }

        throw new HttpResponseException(response(['error' => $errors], 422));
    }
}
