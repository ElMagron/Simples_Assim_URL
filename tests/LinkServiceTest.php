<?php

namespace Tests;

use PHPUnit\Framework\TestCase;
use App\LinkService;
use App\Database;

class LinkServiceTest extends TestCase
{
    private LinkService $linkService;

    /**
     * Executa antes de cada teste.
     * Cria um objeto LinkService e apaga todos os registros da tabela links
     * para que cada teste possa começar com um banco de dados limpo.
     * @return void
     */
    protected function setUp(): void
    {
        $this->linkService = new LinkService();

        $db = Database::getInstance()->getConnection();
        $db->exec("DELETE FROM links");
    }


    /**
     * Testa se a criação de um link e a subsequente redirecionamento
     * para a URL longa original funcionam corretamente.
     *
     * Verifica se a criação de um link com uma URL longa retorna um código curto
     * válido e se a subsequente busca pelo código curto retorna a URL
     * longa original. Além disso, verifica se o contador de cliques foi
     * incrementado corretamente.
     * @return void
     */
    public function testLinkCreationAndRedirectionSuccess(): void
    {
        $longUrl = 'https://www.exemplo.com/teste-phpunit';

        $shortCode = $this->linkService->createLink($longUrl);

        $this->assertIsString($shortCode);
        $this->assertEquals(5, strlen($shortCode));

        $retrievedUrl = $this->linkService->getAndIncrementClicks($shortCode);

        $this->assertEquals($longUrl, $retrievedUrl);

        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT clicks FROM links WHERE short_code = :code");
        $stmt->execute([':code' => $shortCode]);

        $clicks = $stmt->fetchColumn();
        $this->assertEquals(1, (int) $clicks);
    }


    /**
     * Testa se a criação de um link com uma URL inválida lança uma exceção.
     *
     * Verifica se a criação de um link com uma URL inválida lança uma
     * exceção com mensagem de erro descritiva.
     *
     * @return void
     */
    public function testInvalidUrlCreationFailure(): void
    {
        $this->expectException(\Exception::class);

        $this->linkService->createLink("nao-e-uma-url");
    }


    /**
     * Testa se a busca por um link inexistente retorna nulo.
     *
     * Verifica se a busca por um link inexistente retorna nulo.
     * Isso garante que a busca por um link inexistente não
     * lance nenhuma exceção e retorna nulo.
     *
     * @return void
     */
    public function testNonExistentLinkReturnsNull(): void
    {
        $nonExistentCode = "99999";

        $retrievedUrl = $this->linkService->getAndIncrementClicks($nonExistentCode);

        $this->assertNull($retrievedUrl);
    }
}