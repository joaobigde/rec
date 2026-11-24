# API de Situacoes

API simples em PHP para cadastrar, consultar, atualizar e remover situacoes de estudantes.

Os dados sao armazenados em um arquivo JSON. Por padrao, o arquivo utilizado e `storage/dados.json`.

## Requisitos

- PHP 8.2 ou superior
- Composer

## Instalacao

```bash
composer install
```

## Executando a API

Execute o servidor embutido do PHP a partir da raiz do projeto:

```bash
php -S localhost:8000 -t public
```

A API ficara disponivel em `http://localhost:8000`.

Opcionalmente, use a variavel de ambiente `SITUACAO_STORAGE_FILE` para informar outro arquivo JSON:

```bash
SITUACAO_STORAGE_FILE=/tmp/situacao.json php -S localhost:8000 -t public
```

## Executando os testes

```bash
vendor/bin/phpunit test
```

Os testes criam arquivos temporarios e os removem ao final.

## Endpoints

Todas as respostas usam JSON.

### Criar uma situacao

```http
POST /situacao
Content-Type: application/json
```

Corpo da requisicao:

```json
{
  "nome": "Maria da Silva",
  "unidadeCurricular": "Desenvolvimento Web",
  "aprovado": true
}
```

Regras:

- `nome` deve ser uma string nao vazia.
- `unidadeCurricular` deve ser uma string nao vazia.
- `aprovado` deve ser um booleano.

Resposta de sucesso:

```http
201 Created
```

```json
{
  "id": 1,
  "nome": "Maria da Silva",
  "unidadeCurricular": "Desenvolvimento Web",
  "aprovado": true
}
```

Resposta para dados invalidos:

```http
400 Bad Request
```

```json
{
  "mensagem": "Dados inválidos."
}
```

### Consultar uma situacao

```http
GET /situacao/{id}
```

Exemplo:

```bash
curl http://localhost:8000/situacao/1
```

Resposta de sucesso:

```http
200 OK
```

```json
{
  "id": 1,
  "nome": "Maria da Silva",
  "unidadeCurricular": "Desenvolvimento Web",
  "aprovado": true
}
```

Resposta quando o registro nao existe:

```http
404 Not Found
```

```json
{
  "mensagem": "Estudante não encontrado."
}
```

### Atualizar a situacao de aprovacao

```http
PUT /situacao/{id}
Content-Type: application/json
```

O corpo deve conter somente o campo `aprovado`:

```json
{
  "aprovado": false
}
```

Exemplo:

```bash
curl -X PUT http://localhost:8000/situacao/1 \
  -H "Content-Type: application/json" \
  -d '{"aprovado":false}'
```

Resposta de sucesso:

```http
200 OK
```

```json
{
  "id": 1,
  "nome": "Maria da Silva",
  "unidadeCurricular": "Desenvolvimento Web",
  "aprovado": false
}
```

Resposta quando o corpo esta invalido:

```http
400 Bad Request
```

```json
{
  "mensagem": "Informe somente o campo aprovado como true ou false."
}
```

### Remover uma situacao

```http
DELETE /situacao/{id}
```

Exemplo:

```bash
curl -X DELETE http://localhost:8000/situacao/1
```

Resposta de sucesso:

```http
200 OK
```

```json
{
  "mensagem": "Registro removido com sucesso."
}
```

## Respostas de erro comuns

| Status | Causa |
|---|---|
| `400 Bad Request` | Dados de cadastro invalidos, campo de atualizacao invalido ou ID invalido |
| `404 Not Found` | Endpoint ou registro inexistente |
| `405 Method Not Allowed` | Metodo HTTP nao suportado |

Para endpoints ou caminhos nao existentes, a resposta e:

```json
{
  "mensagem": "Endpoint não encontrado."
}
```
