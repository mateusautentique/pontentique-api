<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class InsertUserRequest extends FormRequest
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
            'name' => 'required',
            'email' => 'required|email|unique:users',
            'cpf' => 'required|unique:users',
            'role' => 'sometimes|in:admin,user',
            'password' => 'required|confirmed'
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        $errors = $validator->errors();

        if ($errors->has('password') && str_contains($errors->first('password'), 'confirmação')) {
            throw new HttpResponseException(response(['error' => $errors->first('password')], 494));
        }        

        $fields = ['name', 'email', 'cpf', 'role', 'password'];
        $statusCodes = [490, 491, 492, 493, 494];
    
        foreach ($fields as $index => $field) {
            if ($errors->has($field)) {
                throw new HttpResponseException(response(['error' => $errors->first($field)], $statusCodes[$index]));
            }
        }

        throw new HttpResponseException(response(['error' => $errors], 422));
    }
}
