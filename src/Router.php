<?php

namespace App;

use Exception;

class Router
{
    private LinkService $linkService;
    private string $basePath = '';

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
        // Obtém o caminho base do arquivo
        $scriptName = $_SERVER['SCRIPT_NAME'] ?? ''; 

        // Obtém o diretório base do projeto
        $baseDir = dirname($scriptName); 
        if ($baseDir !== '/') {
            $this->basePath = $baseDir;
        }

        // Obtém a URI completa
        $requestUri = $_SERVER['REQUEST_URI'] ?? ''; 

        // Limpa a URI: remove o subdiretório e limpa as barras
        if (str_starts_with($requestUri, $baseDir) && $baseDir !== '/') {
            // Remove o prefixo do subdiretório
            $uri = substr($requestUri, strlen($baseDir));
        } else {
            $uri = $requestUri;
        }

        // Normaliza a URI
        $uri = trim(parse_url($uri, PHP_URL_PATH) ?? '', '/');

        $method = $_SERVER['REQUEST_METHOD'];

        switch ($method) {
            case 'POST':
                if($uri === 'api/link') {
                    $this->handlePostCreate();
                } else {
                    $this->sendNotFound();
                }
                break;
            
            case 'GET':
                if(!empty($uri)) {
                    if($uri === 'api/status') {
                        $this->handleHealthCheck();
                    } else {
                        $this->handleGetRedirect($uri);
                    }
                } else {
                    $this->handleHomepage(); 
                }
                break;
            default:
                $this->sendNotFound();
                break;
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