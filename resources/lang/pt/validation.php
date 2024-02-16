<?php

return [
    'required' => 'O campo :attribute é obrigatório.',
    'string' => 'O campo :attribute deve ser uma string.',
    'max' => [
        'string' => 'O campo :attribute não pode ter mais de :max caracteres.',
    ],
    'size' => [
        'string' => 'O campo :attribute deve ter :size caracteres.',
    ],
    'unique' => 'O :attribute já está em uso.',
    'email' => 'O campo :attribute deve ser um endereço de email válido.',
    'min' => [
        'string' => 'O campo :attribute deve ter pelo menos :min caracteres.',
    ],
    'confirmed' => 'A confirmação de :attribute não corresponde.',
    'attributes' => [
        'password' => 'senha',
        'name' => 'nome',
    ],
    'custom' => [
        'clock_event_id' => [
            'exists' => 'O idenitficador do registro é inválido.',
        ],
    ],
];