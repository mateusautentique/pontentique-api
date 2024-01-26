# Guia para utilizar o Pontentique API

Este é um guia para utilizar a API do Pontentique, um sistema de controle de ponto eletrônico de uso interno.

Até o momento, a API possui as seguintes funcionalidades:

- Armazenamento e autenticação de usuários
- Registro de batidas de ponto
- Relatórios sobre batidas de ponto
- Cálculo de horas trabalhadas e valor a receber
- Inserção, atualização e remoção de batidas de ponto manualmente
- Sistema de tickets, que podem ser aceitos ou não por administradores

Abaixo, estarão listados alguns exemplos de requisições. Todos elas (exceto registro e login) requerem um token de autenticação válido, caso contrário, elas retornam um erro **401** (*Unauthorized*).

Todas as requisições (com excessão as de autenticação) são divididas em *user* e *admin*, indicando o nível de acesso indicado para elas. As requisições de *user* são aquelas que podem ser feitas por qualquer usuário, enquanto as de *admin* são aquelas que só podem ser feitas por administradores. Isso pode ser visto na URL das requisições, que contém o prefixo `/user/` ou `/admin/`.

- Exemplo

 - Authorization
```json
"token": "ayJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9.eyJhdWQiOiI5YWM3MzBlMC03MDFiLTQ0YTYtYmU2Yi1mYjZhMWQ2NzBiYjgiLCJqdGkiOiIwZTI0YmZhNTgwOGU3MTUyYmExY2FkNDM1MTdlNzA5MjVmNzY2ZDdlM2RlZjBkM2ZkMmExZTg1MTUwMzFjMmIwMGUyNjM3MTVhMjMyY2E5NyIsImlhdCI6MTcwMTg3MTgyMy4yNTYzMDcsIm5iZiI6MTcwMTg3MTgyMy4yNTYzMDksImV4cCI6MTczMzQ5NDIyMy4yNTExOTIsInN1YiI6IjEwIiwic2NvcGVzIjpbXX0.NV9DC-MjNV73cb1kOsvbHLxRKtiHrobHoIO34cnoU8yASb7yc_VP9cVtdn2gLbDZce92_Te11Jp_kX_MjkTLTktGMFJHouzZ_Sh_EO8WAwQI4OfXchOP0RtSYZSIAqxeiAW1Bo38POBn-9PgrxRZLoESsqjTZpqlelAcwY61pUFwvgLLxpsqvyQzIIumyKYFpXGFdmiTq5E0LAuZfKpvM-GR_nv0yyr-Niw_6cSNVeznGLY-ZxRqZdQkWG6rdsfdnRzwBwCgMMa_IjzFr8d_22m64x4an1l0vseTAdMEJTk2k3ekLqyO2jN2t9ClIP935RJGIDu26qibUXJFaMdQ0h-UDh0FL1CDt-H889hGilpo8Azjpqr3CplQaFPNpPeEYt1E74u-ZuZitXGsSsFRG8XQDXNVyxz-PC1GI5u8ws4YzC00jwe7n8Ql9C4q6D0wF-b3wLwsl6rLv3D8BOsIEOLzg9TytiBLzvgClM-HHFRZkvyHtdwZQd7QZWydk18puqJq5CQ3wmv__gDdeI2_ykD0TYxUKzZl9L8EUb9O0NZ8-cVqXQhFi4etB5d87BpZO-epX9CR9wHeJOWR6GRpGvMuDv4TPCmqR-JckE5lUfN6HoTDZM98NCGJX7v7FEHyV2GRJpGajzT74zXaMyQT_d8sxqyW8BIrukRuoNuSGxA"
```

> Obviamente, esse é um token de exemplo e consequentemente não é válido :D

Todas as requisições devem conter um header `Accept` com o valor `application/json`.

- Header
```json
"Accept": "application/json"
```

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
    "name": "Jair Teste da Silva",
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

> **A partir daqui, todas as requisições necessitam de um token de autenticação**

## Logout

Para registrar um logout de um usuário, deve-se enviar uma requisição do tipo `POST` para http://localhost/api/logout.

- Response

A resposta deve ser um JSON contendo um atributo `message` com uma mensagem de sucesso

```json
"message": "Successfully logged out"
```

## Validar token de sessão

Para validar um token de sessão, deve-se enviar uma requisição do tipo `GET` para http://localhost/api/validateToken.

- Response

A resposta deve ser um JSON contendo um atributo `message` com uma mensagem de *true* ou *false*

```json
"message": "true"
```

## Buscar usuário logado

Para buscar o usuário logado, deve-se enviar uma requisição do tipo `GET` para http://localhost/api/user.

- Response

A resposta deve ser um JSON contendo diversos atributos do usuário logado.

```json
{
    "id": 1,
    "name": "Jair Teste da Silva",
    "cpf": "12345678900",
    "email": "jair@tuamaeaquelaursa.com",
    "role": "user",
    "work_journey_hours": 8,
    "created_at": "2024-01-23T18:34:12.000000Z",
    "updated_at": "2024-01-23T18:34:12.000000Z"
}
```

------------------------------------------------------

# Requisições de usuários

Essas são requisições que podem ser feitas apenas por administradores, e servem para gerenciar os usuários do sistema.

## Listar todos os usuários

`GET` para http://localhost/api/admin/manageUsers/

- Response

A resposta deve conter um JSON com informações de todos os usuários cadastrados no banco de dados:
```json
[
    {
        "id": 1,
        "name": "Jair Teste da Silva",
        "cpf": "12345678900",
        "email": "jair@tuamaeaquelaursa.com",
        "role": "user",
        "work_journey_hours": 8,
        "created_at": "2024-01-23T18:34:12.000000Z",
        "updated_at": "2024-01-23T18:34:12.000000Z"
    },
    ...
]
```

## Listar um usuário específico

`GET` para http://localhost/api/admin/manageUsers/user/

- Body

A requisição deve conter um JSON com o id do usuário a ser buscado:

```json
{
    "user_id": 1
}
```

- Response

A resposta deve conter um JSON com informações do usuário com o id especificado:

```json
{
    "id": 1,
    "name": "Jair Teste da Silva",
    "cpf": "12345678900",
    "email": "jair@tuamaeaquelaursa.com",
    "role": "user",
    "work_journey_hours": 8,
    "created_at": "2024-01-23T18:34:12.000000Z",
    "updated_at": "2024-01-23T18:34:12.000000Z"
}
```

## Atualizar um usuário

`PUT` para http://localhost/api/admin/manageUsers/user/

A requisição deve conter um JSON com os campos a serem alterados, bem como um campo "user_id", que representa o id do usuário a ser alterado:

- Body

```json
{
    "user_id": 1,
    "name": "Nome do usuário",
    "email": "Email do usuário",
    "cpf": "CPF do usuário",
    "role": "user",
    "work_journey_hours": 8
}
```

- Response

A resposta deve conter um JSON com informações do usuário atualizado:

```json
{
    "id": 1,
    "name": "Jair Teste da Silva",
    "cpf": "12345678900",
    "email": "jair@tuamaeaquelaursa.com",
    "role": "user",
    "work_journey_hours": 8,
    "created_at": "2024-01-23T18:34:12.000000Z",
    "updated_at": "2024-01-23T18:34:12.000000Z"
}
```

## Deletar um usuário

`DELETE` para http://localhost/api/admin/manageUsers/user/{id}

- Response

A resposta deve conter um JSON com uma mensagem indicando que o usuário foi deletado com sucesso:

```json
{
    "message": "Usuário deletado com sucesso!"
}
```

## Verificar status do usuário

`POST` para http://localhost/api/admin/manageUsers/user/status

- Body

A requisição deve conter um JSON com o id do usuário a ser buscado:

```json
{
    "user_id": 1
}
```

- Response

A resposta deve conter um JSON com um campo message, com uma cor representando o stauts do usuário naquele momento:

```json
{
    "message": "Green"
}
```

> As cores podem ser "Green" (ativo), "Red" (intervalo) ou "Gray" (falta).

------------------------------------------------------

# Requisições de ponto

As requisições de ponto são dividias em *user* e *admin*. As requisições de usuários indicam que a requisição em questão é feita pelo e para o próprio usuário, já as requisicões de admin indicam que a requisição é feita por um administrador, para qualquer usuário. Requisições de administradores permitem a edição direta dos registros de ponto de usuários.

## Bater o ponto

`POST` para http://localhost/api/user/punchClock

- Body

O body da requisição deve conter um campo "user_id", que representa o id do usuário que irá bater o ponto.

- Response

A resposta contém uma mensagem que indica que o ponto foi marcado com sucesso, bem como o dia e a hora da marcação.

Exemplo:

```json
{
    "message": "Clock event registered successfully at 2023-12-08 19:47:01"
}
```

## Listar todos os pontos de um usuário

`GET` para http://localhost/api/admin/userEntries

- Response

A resposta contém um JSON com todos os pontos do usuário.

Exemplo:

```json
[
    {
        "id": 11,
        "user_id": 1,
        "timestamp": "2024-01-23T15:58:00.000000Z",
        "created_at": "2024-01-23T18:35:57.000000Z",
        "updated_at": "2024-01-23T18:35:57.000000Z",
        "justification": null,
        "day_off": 0,
        "doctor": 0,
        "controlId": 0
    },
    {
        "id": 10,
        "user_id": 1,
        "timestamp": "2024-01-23T15:02:00.000000Z",
        "created_at": "2024-01-23T18:35:57.000000Z",
        "updated_at": "2024-01-23T18:35:57.000000Z",
        "justification": null,
        "day_off": 0,
        "doctor": 0,
        "controlId": 0
    },
    ...
]
```

## Listar as atividades de um usuário em um período específico

`POST` para http://localhost/api/user/userEntries/

Essa requisição é utilizada para listar os pontos de um usuário em um período específico. Ela traz informações mais detalhadas sobre os batimentos de ponto, indicando quantidade de horas trabalhadas, e se a batida foi uma entrada ou uma saída, por exemplo. Além disso, ela também contém o tempo total trabalhado no período, número de horas normais trabalhadas e o banco de horas resultante.

Essa função possui o intuito de ser uma ferramenta geral para a apuração de ponto, e pode ser utilizada para gerar relatórios de ponto, por exemplo.

- Body

O body da requisição deve conter um campo "user_id", que representa o id do usuário, e dois campos adicionais: "start_date" e "end_date" (no formato "YYYY-MM-DD"), que representam o início e o fim do período que se deseja consultar.

Ambos os campos são opcionais, e adiconam um fator dinâmico à função. Caso apenas o campo "start_date" não seja especificado, o período de consulta será do momento em que o usuário selecionado foi criado até a data especificada no campo "end_date". Caso apenas o campo "end_date" não seja especificado, o período de consulta será de todos os dias a partir da data especificada no campo "start_date", até o dia atual. Caso nenhum dos dois campos seja especificado, o período de consulta será de todos os dias desde a criação do usuário especificado.

Exemplo:

```json
{
    "user_id": 1,
    "start_date": "2023-12-08",
    "end_date": "2023-12-11"
}
```

> Lista todos os pontos do usuário com id 1, entre os dias 08/12/2023 e 11/12/2023.

Ou

```json
{
    "user_id": 1,
    "start_date": "2023-01-01"
}
```

> Lista todos os pontos do usuário com id 1, do dia 01/01/2023 até o dia atual.

Ou

```json
{
    "user_id": 1,
    "end_date": "2023-12-11"
}
```

> Lista todos os pontos do usuário com id 1, até o dia 11/12/2023.

Ou

```json
{
    "user_id": 1
}
```

> Lista todos os pontos do usuário com id 1.

- Response

A resposta contém um JSON com diversas informações sobre os pontos do usuário no período especificado.

- Exemplo

```json
{
    "total_hours_worked": "13:51",
    "total_normal_hours_worked": "8:00",
    "total_hour_balance": "5:51",
    "entries": [
        {
            "day": "2023-12-01",
            "user_name": "Jair Teste da Silva",
            "user_id": 1,
            "normal_hours_worked_on_day": "8:00",
            "extra_hours_worked_on_day": "5:51",
            "balance_hours_on_day": "5:51",
            "total_time_worked_in_seconds": 49836,
            "event_count": 4,
            "events": [
                {
                    "id": 155,
                    "timestamp": "2024-01-15 08:40:00",
                    "justification": null,
                    "type": "clock_in",
                    "day_off": 0,
                    "doctor": 0,
                    "controlId": 0
                },
                {
                    "id": 156,
                    "timestamp": "2024-01-15 12:00:00",
                    "justification": null,
                    "type": "clock_out",
                    "day_off": 0,
                    "doctor": 0,
                    "controlId": 0
                },
                {
                    "id": 157,
                    "timestamp": "2024-01-15 13:00:00",
                    "justification": null,
                    "type": "clock_in",
                    "day_off": 0,
                    "doctor": 0,
                    "controlId": 0
                },
                {
                    "id": 158,
                    "timestamp": "2024-01-15 17:45:00",
                    "justification": null,
                    "type": "clock_out",
                    "day_off": 0,
                    "doctor": 0,
                    "controlId": 0
                }
            ]
        }
    ]
}
```

Os campos representam:

- `user_name`: Nome do usuário.
- `user_id`: Id do usuário.
- `total_hours_worked`: Tempo total trabalhado no período.
- `total_normal_hours_worked`: Tempo total trabalhado no período, desconsiderando horas extras.
- `total_hour_balance`: Banco de horas resultante do período.

- `entries`: Lista de dias com pontos registrados no período.

    - `day`: Dia em que os pontos foram registrados.
    - `normal_hours_worked_on_day`: Horas normais trabalhadas no dia.
    - `extra_hours_worked_on_day`: Horas extras trabalhadas no dia.
    - `total_time_worked_in_seconds`: Tempo total trabalhado no dia, em segundos.
    - `event_count`: Número de pontos registrados no dia.
    - `events`: Lista de pontos registrados no dia.

        - `id`: Id do ponto.
        - `timestamp`: Data e hora do ponto.
        - `justification`: Justificativa do ponto.
        - `type`: Tipo do ponto. Pode ser "clock_in" ou "clock_out".
        - `day_off`: Indica se o ponto é um dia de folga. Pode ser 0 ou 1.
        - `doctor`: Indica se o ponto é um dia de atestado. Pode ser 0 ou 1.
        - `controlId`: Indica se o ponto foi importado da máquina de ponto. Pode ser 0 ou 1.

## Inserir um ponto manualmente

`POST` para http://localhost/api/admin/userEntries

- Body

O body da requisição deve conter um campo "user_id", que representa o id do usuário, e dois campos adicionais: "timestamp" (no formato "YYYY-MM-DD HH:MM:SS"), que representa a data e hora do ponto, e "justification", que representa a justificativa do ponto.

> Toda inserção ou alteração de ponto deve obrigatoriamente possuir uma justificativa.

Exemplo:

```json
{
    "user_id": 1,
    "timestamp": "2023-12-08 19:40:02",
    "justification": "i forgor",
    "day_off": 0,
    "doctor": 0
}
```

> Insere um ponto para o usuário com id 1, no dia 08/12/2023 às 19:40:02, com a justificativa "i forgor".

- Response

```json
{
    "message": "Clock entry inserted successfully at 2023-12-08 19:40:02 with id 15"
}
```

## Atualizar um ponto manualmente

`PUT` para http://localhost/api/admin/userEntries/

- Body

O body da requisição deve conter um campo "id", que representa o id do ponto, e dois campos adicionais: "timestamp" (no formato "YYYY-MM-DD HH:MM:SS"), que representa a data e hora do ponto, e "justification", que representa a justificativa do ponto.

> Toda inserção ou alteração de ponto deve obrigatoriamente possuir uma justificativa.

Exemplo:

```json
{
    "id": 15,
    "timestamp": "2023-12-08 19:40:02",
    "justification": "surpreendentemente não é i forgor",
    "day_off": 0,
    "doctor": 0
}
```

> Atualiza o ponto com id 15, para o dia 08/12/2023 às 19:40:02, com a justificativa "i forgor".

- Response

```json
{
    "message": "Clock entry updated successfully to 2023-12-08 19:40:02 with justification: surpreendentemente não é i forgor"
}
```

## Deletar um ponto manualmente

`DELETE` para http://localhost/api/admin/userEntries/

- Body

O body da requisição deve conter um campo "id", que representa o id do ponto.

Exemplo:

```json
{
    "id": 15
}
```

- Response

```json
{
    "message": "Clock entry deleted successfully"
}
```

---

# Requisições de tickets

As requisic'oes de tickets são divididas em *user* e *admin*. As requisições de usuários indicam que a requisição em questão é feita pelo e para o próprio usuário, já as requisicões de admin indicam que a requisição é feita por um administrador, para qualquer usuário. Esse sistema permite que os usuários enviem tickets para os administradores, que podem aceitá-los ou não.

## Listar todos os tickets ativos

`GET` para http://localhost/api/admin/manageTickets/active

- Response

A resposta contém um JSON com todos os tickets ativos.

Exemplo:

```json
[
    {
        "id": 1,
        "created_at": "2024-01-18T18:39:31.000000Z",
        "updated_at": "2024-01-18T18:39:31.000000Z",
        "user_id": 1,
        "clock_event_id": null,
        "type": "create",
        "status": "pending",
        "justification": "ticket test",
        "requested_data": "{\"id\": \"2\", \"doctor\": \"0\", \"day_off\": \"0\", \"user_id\": \"1\", \"timestamp\": \"2024-1-18 10:00:00\", \"justification\": \"ticket test\"}"
    },
    {
        "id": 2,
        "created_at": "2024-01-18T18:39:34.000000Z",
        "updated_at": "2024-01-18T18:39:34.000000Z",
        "user_id": 1,
        "clock_event_id": null,
        "type": "create",
        "status": "pending",
        "justification": "ticket test",
        "requested_data": "{\"id\": \"2\", \"doctor\": \"0\", \"day_off\": \"0\", \"user_id\": \"1\", \"timestamp\": \"2024-1-18 18:00:00\", \"justification\": \"ticket test\"}"
    }
    ...
]
```

## Listar todos os tickets de um usuário

`GET` para http://localhost/api/admin/manageTickets/user

- Body

O body da requisição deve conter um campo "user_id", que representa o id do usuário.

- Response

A resposta contém um JSON com todos os tickets do usuário.

Exemplo:

```json
[
    {
        "id": 1,
        "created_at": "2024-01-18T18:39:31.000000Z",
        "updated_at": "2024-01-18T18:39:31.000000Z",
        "user_id": 1,
        "clock_event_id": null,
        "type": "create",
        "status": "pending",
        "justification": "ticket test",
        "requested_data": "{\"id\": \"2\", \"doctor\": \"0\", \"day_off\": \"0\", \"user_id\": \"1\", \"timestamp\": \"2024-1-18 10:00:00\", \"justification\": \"ticket test\"}"
    }
    ...
]
```

## Criar um ticket

`POST` para http://localhost/api/user/tickets

- Body

O body da requisição deve conter um campo "user_id", que representa o id do usuário, um campo type, que representa o tipo do ticket, e um campo "requested_data", que representa os dados requisitados pelo ticket (mesmos valores de um update ou create, contendo um id, caso for um update, um user_id, um timestamp, uma justificativa, e os campos de day_off e doctor), um campo "justification", que representa a justificativa do ticket e um campo "clock_event_id", que representa o id do ponto que o ticket se refere.

No caso de tickets do tipo "delete", o campo requested_data pode ser nulo, e no caso de um "create", o campo clock_event_id pode ser nulo. Tickets de "update" devem conter todos os campos

```json
{
    "user_id": "1",
    "type": "create",
    "justification": "ticket test",
    "requested_data": {
        "user_id": "1",
        "timestamp": "2024-1-18 18:00:00",
        "justification": "ticket test",
        "day_off": "0",
        "doctor": "0"
    }
}
```

> Cria um ticket do tipo "create", com a justificativa "ticket test", e os dados requisitados sendo os mesmos de um create.

- Response

```json
{
    "message": "Ticket criado com sucesso"
}
```

## Aprovar ou negar um ticket

`PUT` para http://localhost/api/admin/manageTickets/handle

- Body

O body da requisição deve conter um campo "ticket_id", que representa o id do ticket, e um campo "action", que representa a ação a ser tomada com o ticket. O campo "action" pode ser "approve" ou "deny".

```json
{
    "ticket_id": "1",
    "action": "approve"
}
```

> Aprova o ticket com id 1.

- Response

```json
{
    "message": "Ticket aprovado com sucesso"
}
```
> Ou então "Ticket negado com sucesso"

------------------------------------------------------