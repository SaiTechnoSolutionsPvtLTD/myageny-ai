<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Client\ConnectionException;

/**
 * Thin HTTP client for the standalone face_recognition_myagenci_python Flask
 * service (config('services.face_recognition')). Laravel never computes or
 * stores face embeddings itself — this service only proxies a photo + an
 * identifier to Python and relays back its verdict.
 *
 * Two flows only, matching what main.py actually exposes:
 *   - verify(): 1:1 verification for the self-service Face Attendance
 *     check-in/out flow — compares a live photo against ONE employee's own
 *     registered encoding (POST /verify_face).
 *   - register(): HR/Admin-only face registration/re-registration for the
 *     HRMS Face Registration screen (POST /add_face). The Python endpoint
 *     accumulates multiple samples server-side before it returns "success"
 *     — callers must keep calling this with fresh photos while status is
 *     "pending" until it reports "success".
 *
 * All failures (network, timeout, non-2xx, unexpected shape) are normalized
 * into a single ['ok' => bool, ...] shape so controllers never have to
 * guess whether a raw exception vs. a JSON error body came back — the
 * ticket requires a hard fail-closed block on ANY verification uncertainty,
 * so callers can just check `ok` and `matched` without special-casing
 * transport errors differently from a clean "no match" response.
 */
class FaceRecognitionService
{
    private string $baseUrl;
    private string $apiKey;
    private int $timeout;

    public function __construct()
    {
        $this->baseUrl = rtrim((string) config('services.face_recognition.base_url'), '/');
        $this->apiKey  = (string) config('services.face_recognition.api_key');
        $this->timeout = (int) config('services.face_recognition.timeout', 15);
    }

    /**
     * 1:1 verification. Returns:
     *   ok=true, matched=bool, distance=float|null   on a clean response
     *   ok=false, message=string                     on any failure (network,
     *                                                 timeout, non-2xx, bad shape)
     */
    public function verify(UploadedFile $photo, string $userId): array
    {
        try {
            $response = Http::withHeaders(['X-Api-Key' => $this->apiKey])
                ->timeout($this->timeout)
                ->attach('file', $photo->get(), 'face.jpg')
                ->post("{$this->baseUrl}/verify_face", [
                    'user_id' => $userId,
                ]);
        } catch (ConnectionException $e) {
            Log::warning('[FaceRecognitionService] verify_face connection failed', [
                'user_id' => $userId,
                'error'   => $e->getMessage(),
            ]);

            return ['ok' => false, 'message' => 'Face verification service is unreachable. Please try again.'];
        }

        $body = $response->json();

        if (! $response->successful() || ! is_array($body) || ($body['status'] ?? null) !== 'success') {
            $message = is_array($body) ? ($body['message'] ?? null) : null;

            Log::warning('[FaceRecognitionService] verify_face failed', [
                'user_id' => $userId,
                'status'  => $response->status(),
                'body'    => $body,
            ]);

            return [
                'ok'      => false,
                'message' => $message ?: 'Face verification failed. Please try again.',
            ];
        }

        $data = $body['data'] ?? [];

        return [
            'ok'       => true,
            'matched'  => (bool) ($data['matched'] ?? false),
            'distance' => isset($data['distance']) ? (float) $data['distance'] : null,
        ];
    }

    /**
     * HR/Admin face registration (and re-registration when $force is true).
     * Python accumulates 5 samples per employee before finalizing, so this
     * returns status='pending' with a captured/required count for every
     * call except the last — the caller (FaceRegistrationApiController)
     * loops this across several captured photos in one request, or the
     * Flutter screen calls it once per captured sample.
     *
     * Returns:
     *   ok=true, status='success'|'pending', message, capturedCount, requiredCount
     *   ok=false, message
     */
    public function register(UploadedFile $photo, string $userId, string $name, bool $force = false): array
    {
        try {
            $response = Http::withHeaders(['X-Api-Key' => $this->apiKey])
                ->timeout($this->timeout)
                ->attach('file', $photo->get(), 'face.jpg')
                ->post("{$this->baseUrl}/add_face", [
                    'user_id' => $userId,
                    'name'    => $name,
                    'force'   => $force ? '1' : '0',
                ]);
        } catch (ConnectionException $e) {
            Log::warning('[FaceRecognitionService] add_face connection failed', [
                'user_id' => $userId,
                'error'   => $e->getMessage(),
            ]);

            return ['ok' => false, 'message' => 'Face registration service is unreachable. Please try again.'];
        }

        $body = $response->json();

        if (! is_array($body) || ! in_array($body['status'] ?? null, ['success', 'pending'], true)) {
            $message = is_array($body) ? ($body['message'] ?? null) : null;

            Log::warning('[FaceRecognitionService] add_face failed', [
                'user_id' => $userId,
                'status'  => $response->status(),
                'body'    => $body,
            ]);

            return [
                'ok'      => false,
                'message' => $message ?: 'Face registration failed. Please try again.',
            ];
        }

        $message = (string) ($body['message'] ?? '');
        $captured = null;
        $required = null;

        if ($body['status'] === 'pending' && preg_match('/Captured (\d+)\/(\d+)/', $message, $m)) {
            $captured = (int) $m[1];
            $required = (int) $m[2];
        }

        return [
            'ok'             => true,
            'status'         => $body['status'],
            'message'        => $message,
            'capturedCount'  => $captured,
            'requiredCount'  => $required,
        ];
    }

    /**
     * 1:N face recognition (finds best match across all registered faces).
     * Proxies to Python /upload_face endpoint.
     *
     * Returns:
     *   ok=true, matched=true, userId=string, name=string, distance=float|null
     *   ok=false, message=string
     */
    public function recognize(UploadedFile $photo): array
    {
        try {
            $response = Http::withHeaders(['X-Api-Key' => $this->apiKey])
                ->timeout($this->timeout)
                ->attach('file', $photo->get(), 'face.jpg')
                ->post("{$this->baseUrl}/upload_face");
        } catch (ConnectionException $e) {
            Log::warning('[FaceRecognitionService] upload_face connection failed', [
                'error' => $e->getMessage(),
            ]);

            return ['ok' => false, 'message' => 'Face recognition service is unreachable. Please try again.'];
        }

        $body = $response->json();

        if (! $response->successful() || ! is_array($body) || ($body['status'] ?? null) !== 'success') {
            $message = is_array($body) ? ($body['message'] ?? null) : null;

            return [
                'ok'      => false,
                'message' => $message ?: 'No matching face found.',
            ];
        }

        $data = $body['data'] ?? [];

        return [
            'ok'       => true,
            'matched'  => true,
            'userId'   => (string) ($data['user_id'] ?? ''),
            'name'     => (string) ($data['name'] ?? ''),
            'distance' => isset($data['distance']) ? (float) $data['distance'] : null,
        ];
    }
}
