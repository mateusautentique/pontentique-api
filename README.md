# Guia para utilizar o Pontentique API

Abaixo, estarão listados alguns exemplos de requisições. Todos elas (exceto registro e login) requerem um token de autenticação válido, caso contrário, elas retornam um erro **401** (*Unauthorized*).

Com exceção de login e do registro, todas as requisições devem possuir os seguintes atributos:

- Header
```json
"Accept": "application/json"
```

 - Authorization
```json
"token": "ayJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9.eyJhdWQiOiI5YWM3MzBlMC03MDFiLTQ0YTYtYmU2Yi1mYjZhMWQ2NzBiYjgiLCJqdGkiOiIwZTI0YmZhNTgwOGU3MTUyYmExY2FkNDM1MTdlNzA5MjVmNzY2ZDdlM2RlZjBkM2ZkMmExZTg1MTUwMzFjMmIwMGUyNjM3MTVhMjMyY2E5NyIsImlhdCI6MTcwMTg3MTgyMy4yNTYzMDcsIm5iZiI6MTcwMTg3MTgyMy4yNTYzMDksImV4cCI6MTczMzQ5NDIyMy4yNTExOTIsInN1YiI6IjEwIiwic2NvcGVzIjpbXX0.NV9DC-MjNV73cb1kOsvbHLxRKtiHrobHoIO34cnoU8yASb7yc_VP9cVtdn2gLbDZce92_Te11Jp_kX_MjkTLTktGMFJHouzZ_Sh_EO8WAwQI4OfXchOP0RtSYZSIAqxeiAW1Bo38POBn-9PgrxRZLoESsqjTZpqlelAcwY61pUFwvgLLxpsqvyQzIIumyKYFpXGFdmiTq5E0LAuZfKpvM-GR_nv0yyr-Niw_6cSNVeznGLY-ZxRqZdQkWG6rdsfdnRzwBwCgMMa_IjzFr8d_22m64x4an1l0vseTAdMEJTk2k3ekLqyO2jN2t9ClIP935RJGIDu26qibUXJFaMdQ0h-UDh0FL1CDt-H889hGilpo8Azjpqr3CplQaFPNpPeEYt1E74u-ZuZitXGsSsFRG8XQDXNVyxz-PC1GI5u8ws4YzC00jwe7n8Ql9C4q6D0wF-b3wLwsl6rLv3D8BOsIEOLzg9TytiBLzvgClM-HHFRZkvyHtdwZQd7QZWydk18puqJq5CQ3wmv__gDdeI2_ykD0TYxUKzZl9L8EUb9O0NZ8-cVqXQhFi4etB5d87BpZO-epX9CR9wHeJOWR6GRpGvMuDv4TPCmqR-JckE5lUfN6HoTDZM98NCGJX7v7FEHyV2GRJpGajzT74zXaMyQT_d8sxqyW8BIrukRuoNuSGxA"
```

> Obviamente, esse é um token de exemplo e consequentemente não é válido :D

------------------------------------------------------

## Register

Para registrar um usuário, deve-se enviar uma requisição do tipo `POST` para http://localhost/api/register

Os campos devem ser os seguintes:

- Header
```json
"Accept": "application/json"
```

- Body
```json
{
    "name": "Jair Teste da silva",
    "email": "jairtestetesteteste@tuamaeaquelaursa.com",
    "password": "umasenhabemsegura",
    "password_confirmation": "umasenhabemsegura",
    "cpf": "12345678900"
}
```
> Os campos são bem autoexplicativos

- Response

A resposta deve ser um JSON contendo um atributo `Token` com um token de autenticação para a sessão do usuário

```json
"token": "ayJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9.eyJhdWQiOiI5YWM3MzBlMC03MDFiLTQ0YTYtYmU2Yi1mYjZhMWQ2NzBiYjgiLCJqdGkiOiIwZTI0YmZhNTgwOGU3MTUyYmExY2FkNDM1MTdlNzA5MjVmNzY2ZDdlM2RlZjBkM2ZkMmExZTg1MTUwMzFjMmIwMGUyNjM3MTVhMjMyY2E5NyIsImlhdCI6MTcwMTg3MTgyMy4yNTYzMDcsIm5iZiI6MTcwMTg3MTgyMy4yNTYzMDksImV4cCI6MTczMzQ5NDIyMy4yNTExOTIsInN1YiI6IjEwIiwic2NvcGVzIjpbXX0.NV9DC-MjNV73cb1kOsvbHLxRKtiHrobHoIO34cnoU8yASb7yc_VP9cVtdn2gLbDZce92_Te11Jp_kX_MjkTLTktGMFJHouzZ_Sh_EO8WAwQI4OfXchOP0RtSYZSIAqxeiAW1Bo38POBn-9PgrxRZLoESsqjTZpqlelAcwY61pUFwvgLLxpsqvyQzIIumyKYFpXGFdmiTq5E0LAuZfKpvM-GR_nv0yyr-Niw_6cSNVeznGLY-ZxRqZdQkWG6rdsfdnRzwBwCgMMa_IjzFr8d_22m64x4an1l0vseTAdMEJTk2k3ekLqyO2jN2t9ClIP935RJGIDu26qibUXJFaMdQ0h-UDh0FL1CDt-H889hGilpo8Azjpqr3CplQaFPNpPeEYt1E74u-ZuZitXGsSsFRG8XQDXNVyxz-PC1GI5u8ws4YzC00jwe7n8Ql9C4q6D0wF-b3wLwsl6rLv3D8BOsIEOLzg9TytiBLzvgClM-HHFRZkvyHtdwZQd7QZWydk18puqJq5CQ3wmv__gDdeI2_ykD0TYxUKzZl9L8EUb9O0NZ8-cVqXQhFi4etB5d87BpZO-epX9CR9wHeJOWR6GRpGvMuDv4TPCmqR-JckE5lUfN6HoTDZM98NCGJX7v7FEHyV2GRJpGajzT74zXaMyQT_d8sxqyW8BIrukRuoNuSGxA"
```

## Login

Para registrar um login de um usuário, deve-se enviar uma requisição do tipo `POST` para http://localhost/api/login.

Os campos devem ser os seguintes:

- Header
```json
"Accept": "application/json"
```

- Body
```json
{
    "cpf": "12345678900"
    "password": "umasenhabemsegura",
}
```
> Os campos são bem autoexplicativos

- Response

A resposta deve ser um JSON contendo um atributo `Token` com um token de autenticação para a sessão do usuário

```json
"token": "ayJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9.eyJhdWQiOiI5YWM3MzBlMC03MDFiLTQ0YTYtYmU2Yi1mYjZhMWQ2NzBiYjgiLCJqdGkiOiIwZTI0YmZhNTgwOGU3MTUyYmExY2FkNDM1MTdlNzA5MjVmNzY2ZDdlM2RlZjBkM2ZkMmExZTg1MTUwMzFjMmIwMGUyNjM3MTVhMjMyY2E5NyIsImlhdCI6MTcwMTg3MTgyMy4yNTYzMDcsIm5iZiI6MTcwMTg3MTgyMy4yNTYzMDksImV4cCI6MTczMzQ5NDIyMy4yNTExOTIsInN1YiI6IjEwIiwic2NvcGVzIjpbXX0.NV9DC-MjNV73cb1kOsvbHLxRKtiHrobHoIO34cnoU8yASb7yc_VP9cVtdn2gLbDZce92_Te11Jp_kX_MjkTLTktGMFJHouzZ_Sh_EO8WAwQI4OfXchOP0RtSYZSIAqxeiAW1Bo38POBn-9PgrxRZLoESsqjTZpqlelAcwY61pUFwvgLLxpsqvyQzIIumyKYFpXGFdmiTq5E0LAuZfKpvM-GR_nv0yyr-Niw_6cSNVeznGLY-ZxRqZdQkWG6rdsfdnRzwBwCgMMa_IjzFr8d_22m64x4an1l0vseTAdMEJTk2k3ekLqyO2jN2t9ClIP935RJGIDu26qibUXJFaMdQ0h-UDh0FL1CDt-H889hGilpo8Azjpqr3CplQaFPNpPeEYt1E74u-ZuZitXGsSsFRG8XQDXNVyxz-PC1GI5u8ws4YzC00jwe7n8Ql9C4q6D0wF-b3wLwsl6rLv3D8BOsIEOLzg9TytiBLzvgClM-HHFRZkvyHtdwZQd7QZWydk18puqJq5CQ3wmv__gDdeI2_ykD0TYxUKzZl9L8EUb9O0NZ8-cVqXQhFi4etB5d87BpZO-epX9CR9wHeJOWR6GRpGvMuDv4TPCmqR-JckE5lUfN6HoTDZM98NCGJX7v7FEHyV2GRJpGajzT74zXaMyQT_d8sxqyW8BIrukRuoNuSGxA"
```

------------------------------------------------------

# Requisições

> A partir desse ponto, todas as requisições necessitam de um token válido no atributo de Authorization, semelhante ao citado no início dessa documentação.

## Listar todos os usuários

`GET` para http://localhost/api/users

- Response:

A resposta deve conter um JSON com informações de todos os usuários cadastrados no banco de dados:
```json
[
    {
        "id": 1,
        "name": "Nome do usuário",
        "email": "Email do usuário",
        "cpf": "CPF do usuário",
        "created_at": "Data de criação do usuário",
        "updated_at": "Data de atualização do usuário"
    },
    {
        "id": 2,
        "name": "Nome do usuário",
        "email": "Email do usuário",
        "cpf": "CPF do usuário",
        "created_at": "Data de criação do usuário",
        "updated_at": "Data de atualização do usuário"
    }
    ...
]
```

## Listar um usuário específico

`GET` para http://localhost/api/users/{id}

- Response:

A resposta deve conter um JSON com informações do usuário com o id especificado:

```json
{
    "id": 1,
    "name": "Nome do usuário",
    "email": "Email do usuário",
    "cpf": "CPF do usuário",
    "created_at": "Data de criação do usuário",
    "updated_at": "Data de atualização do usuário"
}
```

## Atualizar um usuário

`PUT` para http://localhost/api/users/{id}

A requisição deve conter um JSON com os campos a serem alterados:

- Body

```json
{
    "name": "Nome do usuário",
    "email": "Email do usuário",
    "password": "Senha do usuário",
    "cpf": "CPF do usuário"
}
```

- Response:

A resposta deve conter um JSON com informações do usuário atualizado:

```json
{
    "id": 1,
    "name": "Nome do usuário",
    "email": "Email do usuário",
    "cpf": "CPF do usuário",
    "created_at": "Data de criação do usuário",
    "updated_at": "Data de atualização do usuário"
}
```

## Deletar um usuário

`DELETE` para http://localhost/api/users/{id}

- Resposta:

A resposta deve conter um JSON com uma mensagem indicando que o usuário foi deletado com sucesso:

```json
{
    "message": "Usuário deletado com sucesso!"
}
```
