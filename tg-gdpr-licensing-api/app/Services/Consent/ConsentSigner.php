<?php

namespace App\Services\Consent;

use App\Models\ConsentRecord;
use DateTimeInterface;
use Illuminate\Support\Facades\Config;

/**
 * Tamper-evident signing of consent records.
 *
 * Each record gets an HMAC-SHA256 signature over its canonical payload
 * (consent_id, site_id, visitor_hash, consent_categories, gcm_state,
 *  tcf_string, consent_method, policy_version, signed_at).
 *
 * Why APP_KEY? It's the secret Laravel already manages and rotates per
 * environment. A regulator asking "how do you verify this consent isn't
 * forged?" gets a satisfying answer: the row's signature reproduces only
 * if (a) every field is intact AND (b) the signing key is intact.
 *
 * Rotation: today, rotating APP_KEY invalidates every existing signature
 * since signed_at doesn't carry a key id. This is a known-and-documented
 * post-launch improvement; for MVP, key rotation is rare and we prefer
 * simplicity over premature key-id machinery.
 */
final class ConsentSigner
{
    /**
     * Sign a record, returning the values to persist on it.
     *
     * @return array{signature: string, signed_at: \DateTimeInterface}
     */
    public static function sign(ConsentRecord $record): array
    {
        $signedAt  = now();
        $signature = hash_hmac('sha256', self::canonicalize($record, $signedAt), self::key());

        return ['signature' => $signature, 'signed_at' => $signedAt];
    }

    /**
     * Re-derive the signature for an already-persisted record and compare it
     * to the stored value (constant time). Returns false if either signature
     * or signed_at is missing — those records pre-date this column.
     */
    public static function verify(ConsentRecord $record): bool
    {
        if (empty($record->signature) || empty($record->signed_at)) {
            return false;
        }

        $expected = hash_hmac('sha256', self::canonicalize($record, $record->signed_at), self::key());

        return hash_equals($expected, $record->signature);
    }

    /**
     * Canonical, deterministic JSON of the fields that matter for legal proof.
     * Keys are sorted recursively so {"a":1,"b":2} and {"b":2,"a":1} hash equal.
     */
    private static function canonicalize(ConsentRecord $r, DateTimeInterface|string $signedAt): string
    {
        $when = $signedAt instanceof DateTimeInterface
            ? $signedAt->format(DateTimeInterface::ATOM)
            : (string) $signedAt;

        $payload = [
            'consent_id'         => (string) $r->consent_id,
            'site_id'            => (int) $r->site_id,
            'visitor_hash'       => (string) $r->visitor_hash,
            'consent_categories' => self::sortRecursive((array) ($r->consent_categories ?? [])),
            'consent_method'     => (string) ($r->consent_method ?? ''),
            'tcf_string'         => (string) ($r->tcf_string ?? ''),
            'gcm_state'          => self::sortRecursive((array) ($r->gcm_state ?? [])),
            'policy_version'     => (int) ($r->policy_version ?? 1),
            'signed_at'          => $when,
        ];

        return json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    private static function sortRecursive(array $a): array
    {
        ksort($a);
        foreach ($a as $k => $v) {
            if (is_array($v)) {
                $a[$k] = self::sortRecursive($v);
            }
        }
        return $a;
    }

    /** Resolve raw signing key from APP_KEY (handles base64: prefix). */
    private static function key(): string
    {
        $raw = (string) Config::get('app.key');
        if (str_starts_with($raw, 'base64:')) {
            $raw = base64_decode(substr($raw, 7));
        }
        if (empty($raw)) {
            throw new \RuntimeException('APP_KEY is not set; consent signing requires it.');
        }
        return $raw;
    }
}
