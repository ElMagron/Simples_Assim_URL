<?php

namespace App;

use Exception;

class Router
{
    private LinkService $linkService;
    private string $basePath = '';

    private array $routes = [
        'GET' => [],
        'POST' => [],
        'PUT' => [],
        'DELETE' => [],
    ];

    public function __construct()
    {
        $this->linkService = new LinkService();
    }

    public function get(string $path, string $handler): void
    {
        $this->routes['GET'][$path] = $handler;
    }

    public function post(string $path, string $handler): void
    {
        $this->routes['POST'][$path] = $handler;
    }

    public function put(string $path, string $handler): void
    {
        $this->routes['PUT'][$path] = $handler;
    }

    public function delete(string $path, string $handler): void
    {
        $this->routes['DELETE'][$path] = $handler;
    }


    /**
     * Execute o roteador.
     * Este método recebe a solicitação HTTP atual, determina o 
     * método e o caminho e, em seguida, chama o manipulador relevante.
     * 
     * Se o método for GET e o caminho estiver vazio, ele chama o
     * método handleHomepage.
     * Se o método for GET e o caminho não estiver vazio, ele chama o
     * método handleGetRedirect.
     * 
     * Se nenhuma rota correspondente for encontrada, ele chama o método sendNotFound.
     * @return void
     */
    public function run(): void
    {
        $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
        $baseDir = dirname($scriptName);
        if ($baseDir !== '/') {
            $this->basePath = $baseDir;
        }

        $requestUri = $_SERVER['REQUEST_URI'] ?? '';
        if (str_starts_with($requestUri, $baseDir) && $baseDir !== '/') {
            $uri = substr($requestUri, strlen($baseDir));
        } else {
            $uri = $requestUri;
        }

        $uri = trim(parse_url($uri, PHP_URL_PATH) ?? '', '/');
        $method = $_SERVER['REQUEST_METHOD'];

        $methodRoutes = $this->routes[$method] ?? [];

        foreach ($methodRoutes as $path => $handler) {
            $pattern = "#^" . $path . "$#";
            if (preg_match($pattern, $uri, $matches)) {
                array_shift($matches);
                call_user_func_array([$this, $handler], $matches);
                return;
            }
        }

        if ($method === 'GET' && empty($uri)) {
            $this->handleHomepage();
            return;
        }

        if ($method === 'GET' && !empty($uri)) {
            $this->handleGetRedirect($uri);
            return;
        }

        $this->sendNotFound();
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
        $validUntil = $data['valid_until'] ?? null;

        try {
            // Chama a lógica de negócio
            $shortCode = $this->linkService->createLink($longUrl, $validUntil);

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
     * Retorna a base URL do servidor, incluindo o protocolo (http:// ou https://)
     * e o nome do host.
     *
     * @return string A base URL do servidor.
     */
    private function getBaseUrl(): string
    {
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
        $host = $_SERVER['HTTP_HOST'];
        $fullPath = $protocol . $host . $this->basePath;
        return rtrim($fullPath, '/');
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
     * Envia uma resposta de verificação de saúde da API, incluindo o status do DB.
     * Retorna 200 (OK) se tudo estiver funcionando ou 500 (Erro Interno) se falhar.
     * @return void
     */
    private function handleHealthCheck(): void
    {
        $dbStatus = 'OK';
        $httpStatus = 200;
        $details = [];

        try {
            // Tenta obter uma conexão real com o banco de dados
            $db = Database::getInstance()->getConnection();

            // Tenta executar uma query simples para garantir que o DB está UP
            $db->query('SELECT 1')->fetch();

        } catch (\Exception $e) {
            // Se houver qualquer erro (conexão, credenciais, etc.)
            $dbStatus = 'FAIL';
            $httpStatus = 503; // Service Unavailable é mais preciso para dependências
            $details['database_error'] = 'Falha na conexão ou na query simples: ' . $e->getMessage();
        }

        // Verifica as variáveis de ambiente (basicamente checa se foram carregadas)
        $envStatus = ($_ENV['DB_HOST'] && $_ENV['DB_NAME']) ? 'OK' : 'FAIL';
        if ($envStatus === 'FAIL') {
            $httpStatus = 503;
            $details['environment_error'] = 'Variáveis de ambiente (DB_HOST/DB_NAME) não carregadas.';
        }

        // Define o status geral
        $overallStatus = ($httpStatus === 200) ? 'ACTIVE! 🎉' : 'DEGRADED! 😥';


        // Envia a resposta completa
        $this->sendResponse($httpStatus, [
            'status' => $overallStatus,
            'service' => 'URL Shortener API',
            'dependencies' => [
                'database' => $dbStatus,
                'environment' => $envStatus
            ],
            'details' => $details,
            'timestamp' => time()
        ]);
    }

    /**
     * Carrega e exibe a página principal (o formulário HTML).
     * @return void
     */
    private function handleHomepage(): void
    {
        // Define o tipo de conteúdo como HTML (evitando que o JSON header interfira)
        header('Content-Type: text/html');
        // Carrega e imprime o conteúdo do form.html
        echo file_get_contents('form.html');
        exit;
    }

    /**
     * Trata requisições GET para estatísticas de um código curto.
     * @param string $shortCode O código curto capturado pela Regex.
     * @return void
     */
    private function handleGetStats(string $shortCode): void
    {
        try {
            // Chama a lógica de busca do serviço
            $stats = $this->linkService->getLinkStats($shortCode);

            if ($stats) {
                // Retorna as estatísticas com Status 200 (OK)
                $this->sendResponse(200, [
                    'message' => 'Estatísticas encontradas.',
                    'link_info' => $stats
                ]);
            } else {
                // Se o código não for encontrado, retorna 404
                $this->sendResponse(404, ['error' => 'Link de estatísticas não encontrado.']);
            }

        } catch (Exception $e) {
            // Em caso de erro de banco de dados
            $this->sendResponse(500, [
                'error' => 'Erro interno ao buscar estatísticas.', 
                'details' => $e->getMessage()
            ]);
        }
    }

    /**
     * Envia uma resposta HTTP com o status e dados em formato JSON.
     *
     * @param int $status Código de status HTTP.
     * @param array $data Dados a serem enviados na resposta.
     */
    private function sendResponse(int $status, array $data): void
    {
        header('Content-Type: application/json');
        http_response_code($status);
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
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