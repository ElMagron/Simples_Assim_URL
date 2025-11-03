<?php
// tests/LinkServiceTest.php

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

    /**
     * Testa se o método getLinkStats() retorna os dados corretos (URL, Cliques)
     * para um código curto existente e se retorna nulo para códigos inexistentes.
     * @return void
     */
    public function testGetLinkStatsSuccessAndFailure(): void
    {
        // 1. ARRANGE: Prepara o cenário no banco de dados
        $testCode = 'ST4T5';
        $testUrl = 'https://www.exemplo.com/url-para-estatisticas';
        $initialClicks = 15;
        
        // Como o seu LinkService não tem um método para inserir com cliques,
        // vamos usar a conexão direta do setUp() para garantir os dados iniciais.
        $db = Database::getInstance()->getConnection();
        
        // Insere o registro com um número inicial de cliques (15)
        $sql = "INSERT INTO links (short_code, long_url, clicks, created_at) 
                VALUES (?, ?, ?, NOW())";
        $stmt = $db->prepare($sql);
        $stmt->execute([$testCode, $testUrl, $initialClicks]);


        // 2. ACT & ASSERT (Sucesso): Verifica se o método retorna as estatísticas corretas
        
        $stats = $this->linkService->getLinkStats($testCode);

        $this->assertIsArray($stats, "Deve retornar um array de estatísticas.");
        $this->assertEquals($testUrl, $stats['long_url'], "A URL longa deve ser a mesma que foi inserida.");
        $this->assertEquals($initialClicks, $stats['clicks'], "O contador de cliques deve ser o inicial (15).");
        $this->assertArrayHasKey('created_at', $stats, "O array deve conter o campo 'created_at'.");
        // O valid_until pode ser null, mas o campo deve existir na query
        $this->assertArrayHasKey('valid_until', $stats, "O array deve conter o campo 'valid_until'.");


        // 3. ACT & ASSERT (Falha): Verifica se retorna nulo para código inexistente
        $nonExistentStats = $this->linkService->getLinkStats('NAO_EXI5T3');

        $this->assertNull($nonExistentStats, "Deve retornar NULL para um short_code inexistente.");
    }
}