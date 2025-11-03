<?php

namespace App;

use PDO;
use Exception;

class LinkService
{
    private PDO $db;
    private const SHORT_CODE_LENGTH = 5;


    private const ALPHABET = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';

    public function __construct()
    {
        // Obtém a única instância da conexão PDO via Singleton
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Gera um código curto único para um link.
     *
     * Itera até que um código não utilizado seja encontrado.
     *
     * @return string O código curto único gerado.
     */
    private function generateUniqueCode(): string
    {
        $alphabetLength = strlen(self::ALPHABET);
        $code = '';

        do {
            $code = '';
            for ($i = 0; $i < self::SHORT_CODE_LENGTH; $i++) {
                $code .= self::ALPHABET[random_int(0, $alphabetLength - 1)];
            }

            // Verifica no banco se o short_code já está em uso
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM links WHERE short_code = :code");
            $stmt->execute([':code' => $code]);
            $exists = $stmt->fetchColumn();

        } while ($exists > 0);

        return $code;
    }

    /**
     * Cria e armazena um novo link.
     * @param string $longUrl A URL que será encurtada.
     * @return string O código curto gerado.
     */
    public function createLink(string $longUrl): string
    {
        if (!filter_var($longUrl, FILTER_VALIDATE_URL)) {
            throw new Exception("URL fornecida é inválida.");
        }

        $shortCode = $this->generateUniqueCode();

        $sql = "INSERT INTO links (long_url, short_code, created_at) VALUES (:long_url, :short_code, NOW())";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':long_url' => $longUrl,
            ':short_code' => $shortCode
        ]);

        return $shortCode;
    }

    /**
     * Busca a URL longa e incrementa o contador de cliques.
     * @param string $shortCode O código curto para buscar.
     * @return string|null A URL longa, ou null se não for encontrada/expirada.
     */
    public function getAndIncrementClicks(string $shortCode): ?string
    {
        $shortCode = filter_var($shortCode, FILTER_SANITIZE_SPECIAL_CHARS);

        // Inicia a transação
        $this->db->beginTransaction();

        try {
            // Busca a URL longa
            $stmt = $this->db->prepare("SELECT long_url FROM links WHERE short_code = :short_code");
            $stmt->execute([':short_code' => $shortCode]);
            $longUrl = $stmt->fetchColumn();

            // Se o link for encontrado e válido
            if ($longUrl) {
                // Incrementa o contador de cliques
                $stmt = $this->db->prepare("UPDATE links SET clicks = clicks + 1 WHERE short_code = :short_code");
                $stmt->execute([':short_code' => $shortCode]);

                // Confirma a transação
                $this->db->commit();
                return $longUrl;
            }

            // Se não encontrou, desfaz a transação
            $this->db->rollBack();

        } catch (Exception $e) {
            // Em caso de qualquer erro, desfaz a transação
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw new Exception("Falha ao processar o link: " . $e->getMessage());
        }

        return null;
    }

    /**
     * Busca as estatísticas de um link, dado o código curto.
     * @param string $shortCode O código curto a ser consultado.
     * @return array Retorna um array com 'clicks' e 'long_url', ou null se não for encontrado.
     */
    public function getLinkStats(string $shortCode): ?array
    {
        $sql = "SELECT long_url, clicks, created_at, valid_until FROM links WHERE short_code = :code";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':code', $shortCode);
        $stmt->execute();
        
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);

        // Se o resultado for falso (link não encontrado), retorna null
        if (!$result) {
            return null;
        }

        // Retorna apenas os dados relevantes
        return [
            'long_url' => $result['long_url'],
            'clicks' => (int) $result['clicks'], // Garante que seja um inteiro
            'created_at' => $result['created_at'],
            'valid_until' => $result['valid_until'],
        ];
    }
}