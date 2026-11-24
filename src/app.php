<?php

function situacaoResponder(int $status, array $corpo): array
{
    return ["status" => $status, "corpo" => $corpo];
}

function situacaoCarregarDados(string $arquivo): array
{
    if (!is_file($arquivo)) {
        return [];
    }

    $dados = json_decode((string) file_get_contents($arquivo), true);
    return is_array($dados) ? $dados : [];
}

function situacaoSalvarDados(string $arquivo, array $dados): void
{
    $diretorio = dirname($arquivo);
    if (!is_dir($diretorio)) {
        mkdir($diretorio, 0777, true);
    }

    file_put_contents(
        $arquivo,
        json_encode(array_values($dados), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
        LOCK_EX
    );
}

function situacaoProcessar(string $metodo, string $url, ?string $jsonEntrada = null, ?string $arquivo = null): array
{
    $metodo = strtoupper($metodo);
    $arquivo ??= getenv("SITUACAO_STORAGE_FILE") ?: __DIR__ . "/../storage/dados.json";

    $dados = situacaoCarregarDados($arquivo);
    $entrada = null;
    if ($jsonEntrada !== null && trim($jsonEntrada) !== "") {
        $entrada = json_decode($jsonEntrada, true);
    }

    $caminho = rtrim(parse_url($url, PHP_URL_PATH) ?: "/", "/");
    $partes = explode("/", trim($caminho, "/"));

    if (($partes[0] ?? "") !== "situacao" || count($partes) > 2) {
        return situacaoResponder(404, ["mensagem" => "Endpoint não encontrado."]);
    }

    $idTexto = $partes[1] ?? null;

    if ($metodo === "POST" && $idTexto === null) {
        if (
            !is_array($entrada)
            || !isset($entrada["nome"], $entrada["unidadeCurricular"], $entrada["aprovado"])
            || !is_string($entrada["nome"])
            || !is_string($entrada["unidadeCurricular"])
            || !is_bool($entrada["aprovado"])
            || trim($entrada["nome"]) === ""
            || trim($entrada["unidadeCurricular"]) === ""
        ) {
            return situacaoResponder(400, ["mensagem" => "Dados inválidos."]);
        }

        $maiorId = 0;
        foreach ($dados as $registro) {
            if (isset($registro["id"]) && is_numeric($registro["id"])) {
                $maiorId = max($maiorId, (int) $registro["id"]);
            }
        }

        $novo = [
            "id" => $maiorId + 1,
            "nome" => $entrada["nome"],
            "unidadeCurricular" => $entrada["unidadeCurricular"],
            "aprovado" => $entrada["aprovado"],
        ];
        $dados[] = $novo;
        situacaoSalvarDados($arquivo, $dados);

        return situacaoResponder(201, $novo);
    }

    if ($idTexto === null || !ctype_digit($idTexto) || (int) $idTexto < 1) {
        return situacaoResponder(400, ["mensagem" => "ID inválido."]);
    }

    $id = (int) $idTexto;
    $posicao = null;
    foreach ($dados as $indice => $registro) {
        if (isset($registro["id"]) && (int) $registro["id"] === $id) {
            $posicao = $indice;
            break;
        }
    }

    if ($posicao === null) {
        return situacaoResponder(404, ["mensagem" => "Estudante não encontrado."]);
    }

    if ($metodo === "GET") {
        return situacaoResponder(200, $dados[$posicao]);
    }

    if ($metodo === "PUT") {
        if (
            !is_array($entrada)
            || count($entrada) !== 1
            || !array_key_exists("aprovado", $entrada)
            || !is_bool($entrada["aprovado"])
        ) {
            return situacaoResponder(400, ["mensagem" => "Informe somente o campo aprovado como true ou false."]);
        }

        $dados[$posicao]["aprovado"] = $entrada["aprovado"];
        situacaoSalvarDados($arquivo, $dados);
        return situacaoResponder(200, $dados[$posicao]);
    }

    if ($metodo === "DELETE") {
        array_splice($dados, $posicao, 1);
        situacaoSalvarDados($arquivo, $dados);
        return situacaoResponder(200, ["mensagem" => "Registro removido com sucesso."]);
    }

    return situacaoResponder(405, ["mensagem" => "Método não permitido."]);
}

function situacaoDespachar(): void
{
    header("Content-Type: application/json; charset=utf-8");

    $metodo = $_SERVER["REQUEST_METHOD"] ?? "GET";
    $url = $_SERVER["REQUEST_URI"] ?? "/";
    $entrada = file_get_contents("php://input");
    $resposta = situacaoProcessar($metodo, $url, $entrada ?: null);

    http_response_code($resposta["status"]);
    echo json_encode($resposta["corpo"], JSON_UNESCAPED_UNICODE);
}