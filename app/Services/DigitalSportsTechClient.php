<?php

namespace App\Services;

use Carbon\CarbonImmutable;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

class DigitalSportsTechClient
{
    private const CHALLENGE_CACHE_KEY = 'digital_sports_tech.challenge_token';
    private const REQUEST_VERSION = 'v1';
    private const UNCHALLENGED_PATHS = [
        'sgmMarkets/gfm/grouped',
    ];

    public function get(string $path, array $query = [], int $timeout = 15): Response
    {
        return $this->request('get', $path, $query, $timeout);
    }

    public function post(string $path, array $data = [], int $timeout = 15): Response
    {
        return $this->request('post', $path, $data, $timeout);
    }

    private function request(string $method, string $path, array $payload, int $timeout): Response
    {
        if ($this->withoutChallenge($path)) {
            return $this->sendWithoutChallenge($method, $path, $payload, $timeout);
        }

        $response = $this->send($method, $path, $payload, $timeout, $this->challengeToken());

        if (! in_array($response->status(), [400, 401, 403, 419], true)) {
            return $response;
        }

        Cache::forget(self::CHALLENGE_CACHE_KEY);

        return $this->send($method, $path, $payload, $timeout, $this->challengeToken());
    }

    private function sendWithoutChallenge(string $method, string $path, array $payload, int $timeout): Response
    {
        $request = Http::withHeaders($this->baseHeaders())->timeout($timeout);
        $url = $this->url($path);

        return $method === 'post'
            ? $request->post($url, $payload)
            : $request->get($url, $payload);
    }

    private function send(string $method, string $path, array $payload, int $timeout, string $challengeToken): Response
    {
        $request = Http::withHeaders([
            ...$this->baseHeaders(),
            'X-Req-Challenge' => $challengeToken,
            'X-Req-Time' => (string) $this->requestTime(),
            'X-Req-Nonce' => (string) Str::uuid(),
            'X-Req-Version' => self::REQUEST_VERSION,
        ])->timeout($timeout);

        $url = $this->url($path);

        return $method === 'post'
            ? $request->post($url, $payload)
            : $request->get($url, $payload);
    }

    private function challengeToken(): string
    {
        $cachedToken = Cache::get(self::CHALLENGE_CACHE_KEY);

        if (is_string($cachedToken) && $cachedToken !== '') {
            return $cachedToken;
        }

        $response = Http::withHeaders($this->baseHeaders())
            ->timeout((int) config('services.digital_sports_tech.challenge_timeout', 10))
            ->post($this->url('security/challenge'));

        if ($response->failed()) {
            throw new RuntimeException(
                "Failed to get DigitalSportsTech challenge: status {$response->status()}"
            );
        }

        $token = $response->json('token');

        if (! is_string($token) || $token === '') {
            throw new RuntimeException('DigitalSportsTech challenge response did not include a token');
        }

        Cache::put(
            self::CHALLENGE_CACHE_KEY,
            $token,
            $this->challengeTtl($response->json('expiresAt'))
        );

        return $token;
    }

    private function challengeTtl(?string $expiresAt): int
    {
        if (! $expiresAt) {
            return (int) config('services.digital_sports_tech.challenge_ttl', 300);
        }

        try {
            $seconds = CarbonImmutable::parse($expiresAt)->getTimestamp() - now()->getTimestamp();
        } catch (\Throwable) {
            return (int) config('services.digital_sports_tech.challenge_ttl', 300);
        }

        return max(1, min($seconds - 10, (int) config('services.digital_sports_tech.challenge_ttl', 300)));
    }

    private function baseHeaders(): array
    {
        return [
            'Accept' => 'application/json, text/plain, */*',
            'Accept-Language' => config('services.digital_sports_tech.accept_language') ?: 'es-ES,es;q=0.9',
            'User-Agent' => config('services.digital_sports_tech.user_agent')
                ?: 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',
            'Referer' => rtrim((string) (config('services.digital_sports_tech.referer') ?: 'https://bv2-us.digitalsportstech.com/betbuilder'), '?') . '?sb=' . $this->sportsbookAlias(),
        ];
    }

    private function url(string $path): string
    {
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        return rtrim((string) config('services.digital_sports_tech.base_url'), '/') . '/' . ltrim($path, '/');
    }

    private function withoutChallenge(string $path): bool
    {
        foreach (self::UNCHALLENGED_PATHS as $unchallengedPath) {
            if (str_contains($path, $unchallengedPath)) {
                return true;
            }
        }

        return false;
    }

    private function sportsbookAlias(): string
    {
        return '';//(string) config('services.digital_sports_tech.sportsbook_alias', 'juancito');
    }

    private function requestTime(): int
    {
        return (int) floor(microtime(true) * 1000);
    }
}
