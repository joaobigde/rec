<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../src/app.php';

final class HospitalTest extends TestCase
{
    private string $arquivo;

    protected function setUp(): void
    {
        $this->arquivo = sys_get_temp_dir() . '/situacao_api_' . uniqid('', true) . '.json';
    }

    protected function tearDown(): void
    {
        if (is_file($this->arquivo)) {
            unlink($this->arquivo);
        }
    }

    public function testCadastraConsultaAtualizaEExcluiSituacao(): void
    {
        $criado = situacaoProcessar(
            'POST',
            '/situacao',
            '{"nome":"Maria da Silva","unidadeCurricular":"Desenvolvimento Web","aprovado":true}',
            $this->arquivo
        );

        self::assertSame(201, $criado['status']);
        self::assertSame(1, $criado['corpo']['id']);

        $consultado = situacaoProcessar('GET', '/situacao/1', null, $this->arquivo);
        self::assertSame(200, $consultado['status']);
        self::assertTrue($consultado['corpo']['aprovado']);

        $atualizado = situacaoProcessar('PUT', '/situacao/1', '{"aprovado":false}', $this->arquivo);
        self::assertSame(200, $atualizado['status']);
        self::assertFalse($atualizado['corpo']['aprovado']);

        $excluido = situacaoProcessar('DELETE', '/situacao/1', null, $this->arquivo);
        self::assertSame(200, $excluido['status']);
        self::assertSame('Registro removido com sucesso.', $excluido['corpo']['mensagem']);

        $aposExclusao = situacaoProcessar('GET', '/situacao/1', null, $this->arquivo);
        self::assertSame(404, $aposExclusao['status']);
    }

    public function testRejeitaCadastroComDadosInvalidos(): void
    {
        $resposta = situacaoProcessar(
            'POST',
            '/situacao',
            '{"nome":"","unidadeCurricular":"Web","aprovado":true}',
            $this->arquivo
        );

        self::assertSame(400, $resposta['status']);
    }

    public function testPutAceitaSomenteCampoAprovado(): void
    {
        situacaoProcessar(
            'POST',
            '/situacao',
            '{"nome":"Joao","unidadeCurricular":"PHP","aprovado":true}',
            $this->arquivo
        );

        $resposta = situacaoProcessar(
            'PUT',
            '/situacao/1',
            '{"nome":"Outro","aprovado":false}',
            $this->arquivo
        );
        self::assertSame(400, $resposta['status']);
    }

    public function testRetorna404ParaRegistroInexistente(): void
    {
        $resposta = situacaoProcessar('GET', '/situacao/99', null, $this->arquivo);
        self::assertSame(404, $resposta['status']);
        self::assertSame('Estudante não encontrado.', $resposta['corpo']['mensagem']);
    }
}