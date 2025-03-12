<?php

namespace creativework\FilamentExact\Services;

use creativework\FilamentExact\Models\ExactToken;
use Picqer\Financials\Exact\Connection;

class ExactService
{
    protected Connection $connection;

    protected ExactToken $token;

    public function __construct()
    {
        $this->connection = new Connection;

        // Setup connection with config values
        $this->connection->setRedirectUrl(config('filament-exact.exact.redirect_uri'));
        $this->connection->setExactClientId(config('filament-exact.exact.client_id'));
        $this->connection->setExactClientSecret(config('filament-exact.exact.client_secret'));
        $this->connection->setWaitOnMinutelyRateLimitHit(true);

        if (config('filament-exact.exact.division')) {
            $this->connection->setDivision(config('filament-exact.exact.division'));
        }

        $this->token = ExactToken::firstOrNew([]);
        if (! empty($this->token->access_token)) {
            $this->connection->setAccessToken(unserialize($this->token->access_token));
        }

        if (! empty($this->token->refresh_token)) {
            $this->connection->setRefreshToken($this->token->refresh_token);
        }

        if (! empty($this->token->expires_in)) {
            $this->connection->setTokenExpires($this->token->expires_in);
        }
    }

    public function getAuthUrl()
    {
        return $this->connection->getAuthUrl();
    }

    public function authorize($code)
    {
        $this->updateToken('authorization_code', $code);

        try {
            $this->connection->setAuthorizationCode($code);
            $this->connection->connect();
        } catch (\Exception $e) {
            throw new \Exception('Could not authorize Exact: ' . $e->getMessage());
        }

        $this->updateToken('client_id', config('filament-exact.exact.client_id'));
        $this->updateToken('access_token', serialize($this->connection->getAccessToken()));
        $this->updateToken('refresh_token', $this->connection->getRefreshToken());
        $this->updateToken('expires_in', $this->connection->getTokenExpires());
    }

    public function getConnection(): Connection
    {
        return $this->connection;
    }

    public function setDivision($division)
    {
        $this->connection->setDivision($division);
    }

    protected function updateToken(string $key, string $code): void
    {
        $this->token->{$key} = $code;
        $this->token->save();
    }
}
