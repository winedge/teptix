<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class FirebaseService
{
    private $serverKey;
    private $projectId;

    public function __construct()
    {
        $this->serverKey = config('firebase.server_key');
        $this->projectId = config('firebase.project_id');
    }

    /**
     * Send FCM notification to specific tokens
     *
     * @param array $tokens
     * @param array $data
     * @return array
     */
    public function sendNotificationToTokens(array $tokens, array $data)
    {
        if (empty($tokens)) {
            return ['success' => 0, 'failure' => 0, 'errors' => []];
        }

        $successful = 0;
        $failed = 0;
        $errors = [];

        // Split tokens into chunks of 1000 (FCM limit)
        $chunks = array_chunk($tokens, 1000);

        foreach ($chunks as $tokenChunk) {
            $result = $this->sendToChunk($tokenChunk, $data);
            $successful += $result['success'];
            $failed += $result['failure'];
            $errors = array_merge($errors, $result['errors']);
        }

        return [
            'success' => $successful,
            'failure' => $failed,
            'errors' => $errors
        ];
    }

    /**
     * Send FCM notification to a chunk of tokens
     *
     * @param array $tokens
     * @param array $data
     * @return array
     */
    private function sendToChunk(array $tokens, array $data)
    {
        try {
            // Always use Firebase V1 API (Modern API)
            \Log::info('FCM: Using Firebase V1 API with service account');
            return $this->sendWithWebPushAPI($tokens, $data);

        } catch (Exception $e) {
            Log::error('FCM Exception: ' . $e->getMessage());
            return [
                'success' => 0,
                'failure' => count($tokens),
                'errors' => [$e->getMessage()]
            ];
        }
    }

    /**
     * Send notification using Web Push API (for Web Push keys like BGbZ...)
     *
     * @param array $tokens
     * @param array $data
     * @return array
     */
    private function sendWithWebPushAPI(array $tokens, array $data)
    {
        try {
            // Get access token for Firebase V1 API
            $accessToken = $this->getAccessToken();
            if (!$accessToken) {
                \Log::error('FCM: Failed to get access token. Check Firebase credentials.');
                return [
                    'success' => 0,
                    'failure' => count($tokens),
                    'errors' => ['Failed to get Firebase access token. Check credentials in config/firebase.php']
                ];
            }

            \Log::info('FCM: Access token obtained successfully');
            \Log::info('FCM: Project ID: ' . $this->projectId);
            \Log::info('FCM: Sending to ' . count($tokens) . ' token(s)');

            $successful = 0;
            $failed = 0;
            $errors = [];

            // Send to each token individually (V1 API requirement)
            foreach ($tokens as $token) {
                $payload = [
                    'message' => [
                        'token' => $token,
                        'notification' => [
                            'title' => $data['title'],
                            'body' => strip_tags($data['description']),
                        ],
                        'data' => [
                            'title' => $data['title'],
                            'description' => $data['description'],
                            'event_id' => (string)($data['event_id'] ?? ''),
                            'image' => $data['image'] ?? '',
                            'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                            'type' => 'notification'
                        ],
                        'android' => [
                            'notification' => [
                                'sound' => 'default',
                                'click_action' => 'FLUTTER_NOTIFICATION_CLICK'
                            ]
                        ],
                        'apns' => [
                            'payload' => [
                                'aps' => [
                                    'alert' => [
                                        'title' => $data['title'],
                                        'body' => strip_tags($data['description'])
                                    ],
                                    'sound' => 'default',
                                    'badge' => 1
                                ]
                            ]
                        ]
                    ]
                ];

                $url = "https://fcm.googleapis.com/v1/projects/{$this->projectId}/messages:send";
                \Log::info('FCM: Posting to URL: ' . $url);

                $response = Http::timeout(30)->withHeaders([
                    'Authorization' => 'Bearer ' . $accessToken,
                    'Content-Type' => 'application/json',
                ])->post($url, $payload);

                if ($response->successful()) {
                    $successful++;
                    \Log::info('FCM V1 Success for token: ' . substr($token, 0, 20) . '...');
                } else {
                    $failed++;
                    $errorMsg = 'Token ' . substr($token, 0, 20) . '...: HTTP ' . $response->status() . ' - ' . $response->body();
                    $errors[] = $errorMsg;
                    \Log::error('FCM V1 Error: ' . $errorMsg);
                }
            }

            return [
                'success' => $successful,
                'failure' => $failed,
                'errors' => $errors,
                'api_used' => 'Firebase V1 API (Web Push)'
            ];

        } catch (Exception $e) {
            \Log::error('FCM Web Push API Exception: ' . $e->getMessage());
            return [
                'success' => 0,
                'failure' => count($tokens),
                'errors' => ['Web Push API Error: ' . $e->getMessage()]
            ];
        }
    }

    /**
     * Send notification using Legacy FCM API (for server keys like AAAA...)
     *
     * @param array $tokens
     * @param array $data
     * @return array
     */
    private function sendWithLegacyAPI(array $tokens, array $data)
    {
        try {
            $payload = [
                'registration_ids' => $tokens,
                'notification' => [
                    'title' => $data['title'],
                    'body' => strip_tags($data['description']),
                    'sound' => 'default',
                    'badge' => 1,
                ],
                'data' => [
                    'title' => $data['title'],
                    'description' => $data['description'],
                    'event_id' => $data['event_id'] ?? null,
                    'image' => $data['image'] ?? null,
                    'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                    'type' => 'notification'
                ]
            ];

            $response = Http::timeout(30)->withHeaders([
                'Authorization' => 'key=' . $this->serverKey,
                'Content-Type' => 'application/json',
            ])->post('https://fcm.googleapis.com/fcm/send', $payload);

            if ($response->successful()) {
                $result = $response->json();
                return [
                    'success' => $result['success'] ?? 0,
                    'failure' => $result['failure'] ?? 0,
                    'errors' => $this->extractErrors($result, $tokens),
                    'api_used' => 'Legacy FCM API'
                ];
            } else {
                return [
                    'success' => 0,
                    'failure' => count($tokens),
                    'errors' => ['HTTP Error: ' . $response->status() . ' - ' . $response->body()],
                    'api_used' => 'Legacy FCM API'
                ];
            }

        } catch (Exception $e) {
            return [
                'success' => 0,
                'failure' => count($tokens),
                'errors' => ['Legacy API Error: ' . $e->getMessage()]
            ];
        }
    }

    /**
     * Get Firebase access token for V1 API
     *
     * @return string|null
     */
    private function getAccessToken()
    {
        try {
            $credentials = config('firebase.credentials');

            \Log::info('FCM: Attempting to get access token...');
            \Log::info('FCM: Client email: ' . ($credentials['client_email'] ?? 'NOT SET'));

            if (empty($credentials['private_key']) || empty($credentials['client_email'])) {
                \Log::error('FCM: Firebase credentials missing in config');
                \Log::error('FCM: private_key exists: ' . (empty($credentials['private_key']) ? 'NO' : 'YES'));
                \Log::error('FCM: client_email exists: ' . (empty($credentials['client_email']) ? 'NO' : 'YES'));
                return null;
            }

            // Create JWT for Google OAuth
            $header = json_encode(['typ' => 'JWT', 'alg' => 'RS256']);
            $now = time();
            $payload = json_encode([
                'iss' => $credentials['client_email'],
                'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
                'aud' => 'https://oauth2.googleapis.com/token',
                'iat' => $now,
                'exp' => $now + 3600
            ]);

            $base64Header = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));
            $base64Payload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($payload));

            $signature = '';
            $signResult = openssl_sign(
                $base64Header . '.' . $base64Payload,
                $signature,
                $credentials['private_key'],
                OPENSSL_ALGO_SHA256
            );

            if (!$signResult) {
                \Log::error('FCM: Failed to sign JWT. OpenSSL error: ' . openssl_error_string());
                return null;
            }

            $base64Signature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));

            $jwt = $base64Header . '.' . $base64Payload . '.' . $base64Signature;

            \Log::info('FCM: JWT created, requesting access token from Google OAuth...');

            // Exchange JWT for access token
            $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $jwt
            ]);

            if ($response->successful()) {
                $result = $response->json();
                \Log::info('FCM: Access token obtained successfully');
                return $result['access_token'] ?? null;
            }

            \Log::error('FCM: Failed to get access token. HTTP ' . $response->status());
            \Log::error('FCM: Response: ' . $response->body());
            return null;

        } catch (Exception $e) {
            \Log::error('FCM: Access token error: ' . $e->getMessage());
            \Log::error('FCM: Stack trace: ' . $e->getTraceAsString());
            return null;
        }
    }

    /**
     * Extract error details from FCM response
     *
     * @param array $result
     * @param array $tokens
     * @return array
     */
    private function extractErrors(array $result, array $tokens)
    {
        $errors = [];

        if (isset($result['results'])) {
            foreach ($result['results'] as $index => $res) {
                if (isset($res['error'])) {
                    $token = $tokens[$index] ?? 'unknown';
                    $errors[] = "Token {$token}: {$res['error']}";
                }
            }
        }

        return $errors;
    }

    /**
     * Get detailed error information from FCM response
     *
     * @param array $result
     * @param array $tokens
     * @return array
     */
    private function getDetailedErrors(array $result, array $tokens)
    {
        $detailedErrors = [];

        if (isset($result['results'])) {
            foreach ($result['results'] as $index => $res) {
                $token = $tokens[$index] ?? 'unknown';
                $tokenPreview = substr($token, 0, 20) . '...' . substr($token, -10);

                if (isset($res['error'])) {
                    $detailedErrors[] = [
                        'token_preview' => $tokenPreview,
                        'error' => $res['error'],
                        'status' => 'failed',
                        'suggestion' => $this->getErrorSuggestion($res['error'])
                    ];
                } else {
                    $detailedErrors[] = [
                        'token_preview' => $tokenPreview,
                        'error' => null,
                        'status' => 'success',
                        'message_id' => $res['message_id'] ?? null
                    ];
                }
            }
        }

        return $detailedErrors;
    }

    /**
     * Get suggestion for FCM error
     *
     * @param string $error
     * @return string
     */
    private function getErrorSuggestion(string $error)
    {
        $suggestions = [
            'InvalidRegistration' => 'FCM token is invalid. User needs to re-register for notifications.',
            'NotRegistered' => 'FCM token is no longer valid. App was uninstalled or token expired.',
            'InvalidPackageName' => 'Package name in FCM token doesn\'t match Firebase project.',
            'MismatchSenderId' => 'FCM token was registered with different sender ID.',
            'MessageTooBig' => 'Notification payload is too large (max 4KB).',
            'InvalidDataKey' => 'Invalid key used in notification data.',
            'InvalidTtl' => 'Invalid time-to-live value.',
            'Unavailable' => 'FCM service temporarily unavailable. Retry later.',
            'InternalServerError' => 'Firebase server error. Retry later.'
        ];

        return $suggestions[$error] ?? 'Unknown FCM error. Check Firebase documentation.';
    }

    /**
     * Send notification to all app users
     *
     * @param array $data
     * @return array
     */
    public function sendToAllAppUsers(array $data)
    {
        $tokens = \App\Models\AppUser::whereNotNull('fcm_token')
            ->where('fcm_token', '!=', '')
            ->pluck('fcm_token')
            ->unique()
            ->values()
            ->toArray();

        return $this->sendNotificationToTokens($tokens, $data);
    }

    /**
     * Send notification to specific user IDs
     *
     * @param array $userIds
     * @param array $data
     * @param string $userType ('app_user' or 'guest_user')
     * @return array
     */
    public function sendToUserIds(array $userIds, array $data, string $userType = 'app_user')
    {
        if ($userType === 'app_user') {
            $tokens = \App\Models\AppUser::whereIn('id', $userIds)
                ->whereNotNull('fcm_token')
                ->where('fcm_token', '!=', '')
                ->pluck('fcm_token')
                ->unique()
                ->values()
                ->toArray();
        } else {
            $tokens = \App\Models\GuestUser::whereIn('id', $userIds)
                ->whereNotNull('fcm_token')
                ->where('fcm_token', '!=', '')
                ->pluck('fcm_token')
                ->unique()
                ->values()
                ->toArray();
        }

        return $this->sendNotificationToTokens($tokens, $data);
    }

    /**
     * Validate FCM token format
     *
     * @param string $token
     * @return bool
     */
    public function isValidToken(string $token): bool
    {
        return !empty($token) && strlen($token) > 50;
    }

    /**
     * Remove invalid tokens from database
     *
     * @param array $invalidTokens
     * @return void
     */
    public function removeInvalidTokens(array $invalidTokens)
    {
        if (empty($invalidTokens)) {
            return;
        }

        try {
            \App\Models\AppUser::whereIn('fcm_token', $invalidTokens)
                ->update(['fcm_token' => null]);

            \App\Models\GuestUser::whereIn('fcm_token', $invalidTokens)
                ->update(['fcm_token' => null]);

            Log::info('Removed invalid FCM tokens: ' . count($invalidTokens));
        } catch (Exception $e) {
            Log::error('Error removing invalid FCM tokens: ' . $e->getMessage());
        }
    }
}
