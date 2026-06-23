<?php

namespace App\Services\Zoom;

use App\Models\User;
use App\Models\ZoomAccount;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class ZoomMeetingGenerationService
{
    private const TOKEN_CACHE_PREFIX = 'zoom_access_token_';

    /**
     * Find zoom account where email matches the CRM user email.
     */
    public function findAccountForUser(User $user): ?ZoomAccount
    {
        if (empty($user->email)) {
            return null;
        }

        return ZoomAccount::query()
            ->whereRaw('LOWER(email) = ?', [strtolower(trim($user->email))])
            ->first();
    }

    /**
     * Generate a Zoom meeting for a CRM user (resolves zoom_accounts by user email).
     *
     * @param array{
     *     topic: string,
     *     start_time?: string|null,
     *     duration?: int,
     *     timezone?: string|null,
     *     agenda?: string|null,
     *     settings?: array<string, mixed>|null
     * } $meetingDetails
     *
     * @return array{
     *     join_url: string,
     *     start_url: string|null,
     *     id: int|string,
     *     password: string|null,
     *     zoom_account_id: int,
     *     zoom_email: string
     * }
     */
    public function generateForUser(User $user, array $meetingDetails): array
    {
        $zoomAccount = $this->findAccountForUser($user);

        if (!$zoomAccount) {
            throw new RuntimeException(
                'No Zoom account found. Add a zoom_accounts record where email matches the user: '
                . ($user->email ?? 'unknown email')
            );
        }

        return $this->generateForAccount($zoomAccount, $meetingDetails);
    }

    /**
     * Generate a Zoom meeting using a specific zoom_accounts record.
     *
     * @param array{
     *     topic: string,
     *     start_time?: string|null,
     *     duration?: int,
     *     timezone?: string|null,
     *     agenda?: string|null,
     *     settings?: array<string, mixed>|null
     * } $meetingDetails
     *
     * @return array{
     *     join_url: string,
     *     start_url: string|null,
     *     id: int|string,
     *     password: string|null,
     *     zoom_account_id: int,
     *     zoom_email: string
     * }
     */
    public function generateForAccount(ZoomAccount $zoomAccount, array $meetingDetails): array
    {
        $accessToken = $this->getAccessToken($zoomAccount);
        $zoomEmail = $this->getZoomAccountEmail($zoomAccount);
        $payload = $this->buildMeetingPayload($meetingDetails);
        $attempts = [];

        foreach ($this->meetingEndpointCandidates($zoomAccount, $accessToken) as $endpoint) {
            $response = Http::withToken($accessToken)
                ->acceptJson()
                ->post(rtrim(config('zoom.api_base_url'), '/') . '/' . $endpoint, $payload);

            if ($response->successful()) {
                Log::info('Zoom meeting generated', [
                    'endpoint' => $endpoint,
                    'zoom_email' => $zoomEmail,
                    'zoom_account_id' => $zoomAccount->id,
                    'topic' => $meetingDetails['topic'],
                ]);

                return array_merge($this->formatMeetingResponse($response->json()), [
                    'zoom_account_id' => $zoomAccount->id,
                    'zoom_email' => $zoomEmail,
                ]);
            }

            $message = $response->json('message') ?? $response->body();
            $attempts[] = $endpoint . ' => ' . $message;

            Log::warning('Zoom meeting generation attempt failed', [
                'endpoint' => $endpoint,
                'zoom_email' => $zoomEmail,
                'status' => $response->status(),
                'body' => $response->json(),
                'zoom_account_id' => $zoomAccount->id,
            ]);
        }

        throw new RuntimeException(
            'Failed to generate Zoom meeting for zoom_accounts.email (' . $zoomEmail . '). '
            . implode(' | ', $attempts)
        );
    }

    /**
     * @param array{
     *     topic: string,
     *     start_time?: string|null,
     *     duration?: int,
     *     timezone?: string|null,
     *     agenda?: string|null,
     *     settings?: array<string, mixed>|null
     * } $meetingDetails
     *
     * @return array<string, mixed>
     */
    private function buildMeetingPayload(array $meetingDetails): array
    {
        if (empty($meetingDetails['topic'])) {
            throw new RuntimeException('Zoom meeting topic is required.');
        }

        $timezone = $meetingDetails['timezone'] ?? config('zoom.default_timezone');
        $startTime = $meetingDetails['start_time'] ?? now()->timezone($timezone)->addHour()->format('Y-m-d\TH:i:s');

        return [
            'topic' => $meetingDetails['topic'],
            'type' => 2,
            'start_time' => $startTime,
            'duration' => $meetingDetails['duration'] ?? 30,
            'timezone' => $timezone,
            'agenda' => $meetingDetails['agenda'] ?? null,
            'settings' => array_merge([
                'host_video' => true,
                'participant_video' => true,
                'join_before_host' => false,
                'waiting_room' => true,
                'approval_type' => 2,
            ], $meetingDetails['settings'] ?? []),
        ];
    }

    /**
     * @return list<string>
     */
    private function meetingEndpointCandidates(ZoomAccount $zoomAccount, string $accessToken): array
    {
        $zoomEmail = $this->getZoomAccountEmail($zoomAccount);
        $endpoints = [];

        $resolvedUserId = $this->findZoomUserIdFromDirectory($accessToken, $zoomEmail);
        if ($resolvedUserId !== null) {
            $endpoints[] = 'users/' . rawurlencode($resolvedUserId) . '/meetings';
        }

        $endpoints[] = 'users/' . rawurlencode($zoomEmail) . '/meetings';
        $endpoints[] = 'users/me/meetings';

        return array_values(array_unique($endpoints));
    }

    private function getZoomAccountEmail(ZoomAccount $zoomAccount): string
    {
        $email = trim((string) $zoomAccount->email);

        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException(
                'zoom_accounts.email must contain a valid Zoom user email.'
            );
        }

        return $email;
    }

    private function findZoomUserIdFromDirectory(string $accessToken, string $zoomEmail): ?string
    {
        $searchEmail = strtolower($zoomEmail);
        $nextPageToken = null;

        do {
            $query = [
                'page_size' => 300,
                'status' => 'active',
            ];

            if ($nextPageToken) {
                $query['next_page_token'] = $nextPageToken;
            }

            $response = Http::withToken($accessToken)
                ->acceptJson()
                ->get(rtrim(config('zoom.api_base_url'), '/') . '/users', $query);

            if (!$response->successful()) {
                return null;
            }

            foreach ($response->json('users') ?? [] as $user) {
                $email = strtolower((string) ($user['email'] ?? ''));

                if ($email === $searchEmail) {
                    return (string) ($user['id'] ?? '') ?: null;
                }
            }

            $nextPageToken = $response->json('next_page_token') ?: null;
        } while ($nextPageToken);

        return null;
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @return array{join_url: string, start_url: string|null, id: int|string, password: string|null}
     */
    private function formatMeetingResponse(array $payload): array
    {
        return [
            'join_url' => $payload['join_url'],
            'start_url' => $payload['start_url'] ?? null,
            'id' => $payload['id'],
            'password' => $payload['password'] ?? null,
        ];
    }

    private function getAccessToken(ZoomAccount $zoomAccount): string
    {
        $cacheKey = self::TOKEN_CACHE_PREFIX . $zoomAccount->id;
        $cachedToken = Cache::get($cacheKey);

        if (is_string($cachedToken) && $cachedToken !== '') {
            return $cachedToken;
        }

        $clientId = trim((string) $zoomAccount->client_id);
        $clientSecret = trim((string) $zoomAccount->client_secret);
        $accountId = trim((string) $zoomAccount->account_id);

        if ($clientId === '' || $clientSecret === '' || $accountId === '') {
            throw new RuntimeException(
                'Zoom account credentials are incomplete. client_id, client_secret, and account_id are required.'
            );
        }

        $response = Http::asForm()
            ->withBasicAuth($clientId, $clientSecret)
            ->post(config('zoom.oauth_url'), [
                'grant_type' => 'account_credentials',
                'account_id' => $accountId,
            ]);

        if (!$response->successful()) {
            $body = $response->json();
            $zoomMessage = $body['reason']
                ?? $body['error_description']
                ?? $body['message']
                ?? $body['error']
                ?? $response->body();

            Log::error('Zoom OAuth token request failed', [
                'status' => $response->status(),
                'body' => $body,
                'zoom_account_id' => $zoomAccount->id,
                'account_name' => $zoomAccount->account_name,
            ]);

            throw new RuntimeException('Failed to authenticate with Zoom API: ' . $zoomMessage);
        }

        $accessToken = $response->json('access_token');
        $expiresIn = (int) $response->json('expires_in', 3600);

        if (!is_string($accessToken) || $accessToken === '') {
            throw new RuntimeException('Zoom API did not return an access token.');
        }

        Cache::put($cacheKey, $accessToken, max(60, $expiresIn - 60));

        return $accessToken;
    }
}
