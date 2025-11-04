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
     * Cria um novo link curto.
     * @param string $longUrl A URL longa.
     * @param string|null $validUntil Data e hora de expiração (opcional, formato YYYY-MM-DD HH:MM:SS).
     * @return string O código curto gerado.
     * @throws \Exception Se a URL for inválida.
     */
    public function createLink(string $longUrl, ?string $validUntil = null): string
    {
        if (!filter_var($longUrl, FILTER_VALIDATE_URL)) {
            throw new Exception("URL fornecida é inválida.");
        }

        $shortCode = $this->generateUniqueCode();

        $sql = "INSERT INTO links (short_code, long_url, created_at, valid_until) VALUES (:code, :url, NOW(), :valid_until)";

        $stmt = $this->db->prepare($sql);

        $stmt->bindParam(':code', $shortCode);
        $stmt->bindParam(':url', $longUrl);
        $stmt->bindValue(':valid_until', $validUntil);

        $stmt->execute();

        return $shortCode;
    }

    /**
     * Busca a URL longa e incrementa o contador de cliques.
     * @param string $shortCode O código curto para buscar.
     * @return string|null A URL longa, ou null se não for encontrada/expirada.
     */
    public function getAndIncrementClicks(string $shortCode): ?string
    {
        $sql = "SELECT long_url, valid_until FROM links WHERE short_code = :code";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':code', $shortCode);
        $stmt->execute();
        $link = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$link) {
            return null;
        }

        if ($link['valid_until'] !== null) {
            $currentTime = new \DateTime('now', new \DateTimeZone('UTC'));
            $expiryTime = new \DateTime($link['valid_until'], new \DateTimeZone('UTC'));

            if ($currentTime >= $expiryTime) {
                return null;
            }
        }

        $this->db->exec("UPDATE links SET clicks = clicks + 1 WHERE short_code = '{$shortCode}'");

        return $link['long_url'];
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
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

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