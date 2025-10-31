<?php

namespace App;

use Exception;

class Router
{
    private LinkService $linkService;

    public function __construct()
    {
        // Instancia o serviço de links, que já cuida da conexão com o BD
        $this->linkService = new LinkService();
    }

    /**
     * Rotina principal do Router.
     * Responsável por verificar o método HTTP e a URL limpa e
     * chamar as funções responsáveis pelo tratamento da requisição.
     *
     * @throws Exception Se o método HTTP ou a URL limpa forem inconsistentes
     * @return void
     */
    public function run(): void
    {
        // Obtém o método HTTP (GET, POST, etc.)
        $method = $_SERVER['REQUEST_METHOD'];

        // Obtém a URL limpa (ex: /create ou /aBcD1)
        // Isso depende da configuração do .htaccess funcionar corretamente
        $uri = trim(parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?? '', '/');

        // --- Roteamento Principal ---

        if ($method === 'POST' && $uri === 'create') {
            $this->handlePostCreate();
        } elseif ($method === 'GET' && !empty($uri)) {
            // Qualquer GET que não tenha URI vazia é tratado como hash
            $this->handleGetRedirect($uri);
        } else {
            // Se não for POST /create ou GET /hash
            $this->sendNotFound();
        }
    }

    /**
     * Responsável por criar um novo link curto a partir de uma URL longa.
     * Recebe o conteúdo JSON do corpo da requisição (corpo do POST) e
     * chama a lógica de negócio para criar o link.
     * Retorna a URL encurtada com Status 201 (Created) ou
     * em caso de erro, um erro com Status 500 (Internal Server Error) e
     * detalhes do erro.
     * @return void
     */
    private function handlePostCreate(): void
    {
        header('Content-Type: application/json');

        // Recebe o conteúdo JSON do corpo da requisição (corpo do POST)
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);

        if (empty($data['long_url'])) {
            $this->sendResponse(400, ['error' => 'O campo long_url é obrigatório.']);
            return;
        }

        $longUrl = $data['long_url'];

        try {
            // Chama a lógica de negócio
            $shortCode = $this->linkService->createLink($longUrl);

            // Retorna a URL encurtada com Status 201 (Created)
            $shortUrl = $this->getBaseUrl() . '/' . $shortCode;
            $this->sendResponse(201, [
                'short_code' => $shortCode,
                'short_url' => $shortUrl
            ]);

        } catch (Exception $e) {
            // Em caso de erro de validação (ex: URL inválida) ou banco de dados
            $this->sendResponse(500, ['error' => 'Falha ao criar o link.', 'details' => $e->getMessage()]);
        }
    }

    /**
     * Trata requisições GET com hash curto.
     * Procura pelo link curto no banco de dados, incrementa o contador de cliques
     * e redireciona para a URL longa.
     *
     * Em caso de erro interno (banco de dados falhou, etc.) ou
     * se o hash não for encontrado ou expirou, retorna um erro com Status 500
     * ou 404, respectivamente.
     *
     * @param string $shortCode O código curto para buscar.
     * @throws Exception Se ocorrer um erro interno.
     * @return void
     */
    private function handleGetRedirect(string $shortCode): void
    {
        try {
            // Chama a lógica de busca e incremento
            $longUrl = $this->linkService->getAndIncrementClicks($shortCode);

            if ($longUrl) {
                // Redirecionamento 301 ou 302
                // 301 (Permanente): Melhor para SEO, mas cacheia.
                // 302 (Encontrado/Temporário): Melhor para estatísticas/contador.
                header('Location: ' . $longUrl, true, 302);
                exit; // Termina a execução após o redirecionamento
            }

            // Se o hash não foi encontrado ou expirou
            $this->sendNotFound();

        } catch (Exception $e) {
            // Erro interno (banco de dados falhou, etc.)
            $this->sendResponse(500, ['error' => 'Erro interno ao processar o link.']);
        }
    }

    /**
     * Retorna a base URL do servidor, incluindo o protocolo (http:// ou https://)
     * e o nome do host.
     *
     * @return string A base URL do servidor.
     */
    private function getBaseUrl(): string
    {
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
        $host = $_SERVER['HTTP_HOST'];
        return $protocol . $host;
    }

    /**
     * Envia uma resposta HTTP com o status e dados em formato JSON.
     *
     * @param int $status Código de status HTTP.
     * @param array $data Dados a serem enviados na resposta.
     */
    private function sendResponse(int $status, array $data): void
    {
        http_response_code($status);
        echo json_encode($data);
    }

    /**
     * Envia uma resposta HTTP com o status 404 (Not Found) e dados em formato JSON com
     * uma mensagem de erro indicando que o recurso não foi encontrado.
     *
     * @return void
     */
    private function sendNotFound(): void
    {
        $this->sendResponse(404, ['error' => 'Recurso não encontrado.']);
    }
}