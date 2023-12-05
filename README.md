# Guia para utilizar o Pontentique API

## Requisições

- **Listar todos os usuários**

`GET` para http://localhost/api/users

- Reposta:

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

- **Listar um usuário específico**

`GET` para http://localhost/api/users/{id}

- Resposta:

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

- Criar um usuário

`POST` para http://localhost/api/users

A requisição deve conter um JSON com os seguintes campos:

```json
{
    "name": "Nome do usuário",
    "email": "Email do usuário",
    "password": "Senha do usuário",
    "cpf": "CPF do usuário"
}
```

- Resposta:

A resposta deve conter um JSON com informações do usuário criado:

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

- Atualizar um usuário

`PUT` para http://localhost/api/users/{id}

A requisição deve conter um JSON com os campos a serem alterados:

```json
{
    "name": "Nome do usuário",
    "email": "Email do usuário",
    "password": "Senha do usuário",
    "cpf": "CPF do usuário"
}
```

- Resposta:

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

- Deletar um usuário

`DELETE` para http://localhost/api/users/{id}

- Resposta:

A resposta deve conter um JSON com uma mensagem indicando que o usuário foi deletado com sucesso:

```json
{
    "message": "Usuário deletado com sucesso!"
}
```
