<?php

namespace App\Core;

/**
 * Regras do chat (D66), as mesmas para a API e para o painel do dono: o texto da mensagem
 * (até 1000 caracteres, sem links), o limite de envio e os parâmetros da lista de mensagens.
 * Não usa o banco (testes em tests/chat_rules_test.php).
 */
class ChatRules
{
    public const BODY_MAX = 1000;
    /** Mais que isso numa janela de RATE_WINDOW segundos: 429 TOO_MANY_ATTEMPTS. */
    public const RATE_LIMIT = 20;
    public const RATE_WINDOW = 60;
    public const PER_PAGE_DEFAULT = 30;
    public const PER_PAGE_MAX = 50;
    /** Linhas em branco seguidas que sobram depois de limpar o texto. */
    private const MAX_BLANK_LINES = 2;

    /**
     * Domínios que contam como link sem http nem www ("kolae.online", "bit.ly/x", "wa.me/55...").
     * Lista fechada e só em minúsculas, para "R$ 50.00", "19.30" e frases com o espaço esquecido
     * depois do ponto ("Pode ser.De tarde", "Tá bom.Com certeza") não virarem link. Ficam de fora
     * os domínios curtos que são palavras em português (.de, .to, .es, .in, .is, .us, .co...).
     */
    private const TLDS = 'com|net|org|br|io|app|me|ly|gg|online|site|info|biz|xyz|link|store|shop|tv|club|live|top|vip|bet|page|dev|ai|uk|pt|fr|ru|cn|jp|cc|tk|ws|gl|gd';

    /**
     * Texto da mensagem: tira espaços das pontas, junta quebras de linha do Windows e corta
     * linhas em branco demais. Mantém as quebras de linha (é conversa, não título).
     * @return array{0: ?string, 1: ?string} [texto limpo, código do erro: 'required' | 'too_long' | 'link']
     */
    public static function cleanBody($input): array
    {
        if (!is_string($input)) {
            return [null, 'required'];
        }
        $body = str_replace(["\r\n", "\r"], "\n", $input);
        $body = preg_replace('/[^\S\n]+\n/u', "\n", $body);         // espaços no fim das linhas
        $body = preg_replace('/\n{' . (self::MAX_BLANK_LINES + 2) . ',}/', str_repeat("\n", self::MAX_BLANK_LINES + 1), $body);
        $body = trim($body);
        if ($body === '') {
            return [null, 'required'];
        }
        if (mb_strlen($body) > self::BODY_MAX) {
            return [null, 'too_long'];
        }
        if (self::hasLink($body)) {
            return [null, 'link'];
        }
        return [$body, null];
    }

    /** Tem link? http(s)://, www. ou um domínio conhecido (kolae.online, bit.ly/abc, a@b.com). */
    public static function hasLink(string $text): bool
    {
        if (preg_match('~https?://|www\.~i', $text)) {
            return true;
        }
        // palavra.domínio em minúsculas, sem letra logo depois ("site.com" sim, "site.combo" não)
        return (bool)preg_match('~[\p{L}\p{N}-]\.(?:' . self::TLDS . ')(?![\p{L}\p{N}])~u', $text);
    }

    /**
     * Parâmetros do GET /conversations/{id}/messages: after_id OU before_id (inteiros positivos)
     * e per_page de 1 a 50 (padrão 30).
     * @return array{after: ?int, before: ?int, per_page: int, errors: array<string, string>}
     */
    public static function listParams(array $query): array
    {
        $errors = [];
        $read = function (string $key) use ($query, &$errors): ?int {
            if (!array_key_exists($key, $query) || $query[$key] === '' || $query[$key] === null) {
                return null;
            }
            $value = $query[$key];
            if (!is_scalar($value) || !ctype_digit((string)$value) || (int)$value < 1) {
                $errors[$key] = 'Use um número inteiro positivo.';
                return null;
            }
            return (int)$value;
        };
        $after = $read('after_id');
        $before = $read('before_id');
        if ($after !== null && $before !== null) {
            $errors['after_id'] = 'Use after_id ou before_id, não os dois.';
        }

        $perPage = self::PER_PAGE_DEFAULT;
        if (array_key_exists('per_page', $query) && $query['per_page'] !== '') {
            $raw = $query['per_page'];
            if (!is_scalar($raw) || !ctype_digit((string)$raw) || (int)$raw < 1 || (int)$raw > self::PER_PAGE_MAX) {
                $errors['per_page'] = 'Use um número de 1 a ' . self::PER_PAGE_MAX . '.';
            } else {
                $perPage = (int)$raw;
            }
        }
        return ['after' => $after, 'before' => $before, 'per_page' => $perPage, 'errors' => $errors];
    }

    /** Segundos até poder enviar de novo, dado quando foi a mais antiga das mensagens da janela. */
    public static function retryAfter(int $oldestInWindowTs, int $nowTs): int
    {
        return max(1, $oldestInWindowTs + self::RATE_WINDOW - $nowTs);
    }

    /**
     * Nova "última lida" de um lado (POST /conversations/{id}/read e abrir a conversa no painel):
     * só avança, e um id maior que a última mensagem vale como a última. Conversa vazia: não muda.
     */
    public static function nextReadPointer(?int $current, int $requested, ?int $lastMessageId): ?int
    {
        if ($lastMessageId === null) {
            return $current; // conversa vazia: nada para marcar
        }
        $target = min($requested, $lastMessageId); // id maior que a última vale como a última
        return $current !== null && $current >= $target ? $current : $target; // só avança
    }
}
