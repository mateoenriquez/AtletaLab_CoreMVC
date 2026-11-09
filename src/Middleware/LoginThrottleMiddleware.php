<?php
declare(strict_types=1);

namespace App\Middleware;

use Cake\Cache\Cache;
use Cake\Http\Response;
use Cake\Http\ServerRequest;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class LoginThrottleMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $params = $request->getAttribute('params', []);
        if ($request->getMethod() === 'POST' && ($params['controller'] ?? '') === 'Users' && ($params['action'] ?? '') === 'login') {
            $ip = $request instanceof ServerRequest ? $request->clientIp() : (string)($request->getServerParams()['REMOTE_ADDR'] ?? 'unknown');
            [$allowed, $retryAfter] = $this->consumeAttempt($ip);
            if (!$allowed) {
                return (new Response())->withStatus(429)->withType('text')->withHeader('Retry-After', (string)$retryAfter)
                    ->withStringBody('Demasiados intentos de acceso. Espera 15 minutos antes de volver a intentarlo.');
            }
            $body = $request->getParsedBody();
            if (!is_array($body) || !is_string($body['email'] ?? null) || !is_string($body['password'] ?? null)
                || strlen($body['email']) > 200 || strlen($body['password']) > 72 || str_contains($body['password'], "\0")) {
                return (new Response())->withStatus(400)->withType('text')->withStringBody('Introduce un correo y una contraseña válidos.');
            }
            $body['email'] = mb_strtolower(trim($body['email']));
            $request = $request->withParsedBody($body);
        }

        return $handler->handle($request);
    }

    private function consumeAttempt(string $ip): array
    {
        // FileEngine no permite incrementos atómicos. El bloqueo serializa lectura y escritura.
        $lock = fopen(CACHE . 'login' . DIRECTORY_SEPARATOR . 'attempts.lock', 'c');
        if ($lock === false || !flock($lock, LOCK_EX)) {
            throw new \RuntimeException('No se pudo bloquear el contador de acceso.');
        }
        try {
            $key = hash('sha256', $ip);
            $now = time();
            $entry = Cache::read($key, 'login');
            if (!is_array($entry) || ($entry['resetAt'] ?? 0) <= $now) {
                $entry = ['attempts' => 0, 'resetAt' => $now + 900];
            }
            $allowed = $entry['attempts'] < 10;
            if ($allowed) {
                $entry['attempts']++;
                if (!Cache::write($key, $entry, 'login')) {
                    throw new \RuntimeException('No se pudo guardar el contador de acceso.');
                }
            }

            return [$allowed, max(1, $entry['resetAt'] - $now)];
        } finally {
            flock($lock, LOCK_UN);
            fclose($lock);
        }
    }
}
