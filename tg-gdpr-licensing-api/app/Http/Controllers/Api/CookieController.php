<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Site;
use App\Models\SiteCookie;
use App\Models\CookieDefinition;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class CookieController extends Controller
{
    /**
     * Get cookies for a site (for banner display)
     */
    public function getSiteCookies(Request $request): JsonResponse
    {
        $site = $this->validateSiteToken($request);
        
        if (!$site) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid site token',
            ], 401);
        }
        
        $cookies = $site->cookies()
            ->where('is_active', true)
            ->get()
            ->groupBy('category');
        
        $result = [];
        foreach (['necessary', 'functional', 'analytics', 'marketing'] as $category) {
            $result[$category] = $cookies->get($category, collect())->map(function ($cookie) {
                return [
                    'name' => $cookie->cookie_name,
                    'provider' => $cookie->provider,
                    'description' => $cookie->description,
                    'duration' => $cookie->duration,
                ];
            })->values();
        }
        
        return response()->json([
            'success' => true,
            'data' => $result,
        ]);
    }

    /**
     * Look up cookie definitions from global database
     */
    public function lookupCookie(Request $request): JsonResponse
    {
        $request->validate([
            'cookie_name' => 'required|string|max:255',
        ]);
        
        $cookieName = $request->cookie_name;
        
        // Try exact match first
        $definition = CookieDefinition::where('cookie_name', $cookieName)->first();
        
        // Try pattern matching
        if (!$definition) {
            $definition = CookieDefinition::where('is_regex', true)
                ->get()
                ->first(function ($def) use ($cookieName) {
                    return preg_match('/' . $def->cookie_pattern . '/', $cookieName);
                });
        }
        
        // Try prefix matching (e.g., _ga_ matches _ga)
        if (!$definition) {
            $definition = CookieDefinition::where('cookie_name', 'like', substr($cookieName, 0, 3) . '%')
                ->first();
        }
        
        if (!$definition) {
            return response()->json([
                'success' => false,
                'message' => 'Cookie not found in database',
            ], 404);
        }
        
        return response()->json([
            'success' => true,
            'data' => [
                'cookie_name' => $definition->cookie_name,
                'category' => $definition->category,
                'provider' => $definition->provider,
                'description' => $definition->description,
                'duration' => $definition->duration,
                'platform' => $definition->platform,
                'verified' => $definition->verified,
            ],
        ]);
    }

    /**
     * Bulk lookup cookies
     */
    public function bulkLookup(Request $request): JsonResponse
    {
        $request->validate([
            'cookies' => 'required|array|max:100',
            'cookies.*' => 'string|max:255',
        ]);
        
        $results = [];
        
        foreach ($request->cookies as $cookieName) {
            $definition = CookieDefinition::where('cookie_name', $cookieName)->first();
            
            if ($definition) {
                $results[$cookieName] = [
                    'found' => true,
                    'category' => $definition->category,
                    'provider' => $definition->provider,
                    'description' => $definition->description,
                    'duration' => $definition->duration,
                ];
            } else {
                $results[$cookieName] = [
                    'found' => false,
                    'category' => 'unknown',
                ];
            }
        }
        
        return response()->json([
            'success' => true,
            'data' => $results,
        ]);
    }

    /**
     * Submit scanned cookies from the client integration
     */
    public function submitScan(Request $request): JsonResponse
    {
        $site = $this->validateSiteToken($request);
        
        if (!$site) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid site token',
            ], 401);
        }
        
        $validated = $request->validate([
            'cookies' => 'required|array',
            'cookies.*.name' => 'required|string|max:255',
            'cookies.*.domain' => 'nullable|string|max:255',
            'cookies.*.path' => 'nullable|string|max:255',
            'cookies.*.secure' => 'boolean',
            'cookies.*.http_only' => 'boolean',
            'cookies.*.same_site' => 'nullable|string',
            'cookies.*.expiry' => 'nullable|integer',
        ]);
        
        $added = 0;
        $updated = 0;
        
        foreach ($validated['cookies'] as $cookieData) {
            // Look up in global database
            $definition = CookieDefinition::where('cookie_name', $cookieData['name'])->first();
            
            $siteCookie = SiteCookie::updateOrCreate(
                [
                    'site_id' => $site->id,
                    'cookie_name' => $cookieData['name'],
                ],
                [
                    'cookie_definition_id' => $definition?->id,
                    'category' => $definition?->category ?? 'marketing', // Default to most restrictive
                    'provider' => $definition?->provider ?? 'Unknown',
                    'description' => $definition?->description,
                    'duration' => $this->formatDuration($cookieData['expiry'] ?? null),
                    'source' => 'scan',
                    'last_detected_at' => now(),
                ]
            );
            
            if ($siteCookie->wasRecentlyCreated) {
                $added++;
                
                // Increment usage count on global definition
                if ($definition) {
                    $definition->incrementUsage();
                }
            } else {
                $updated++;
            }
        }
        
        // Update site scan info
        $site->update([
            'last_scan_at' => now(),
            'cookies_detected' => $site->cookies()->count(),
        ]);
        
        return response()->json([
            'success' => true,
            'message' => "Scan processed: {$added} new, {$updated} updated",
            'added' => $added,
            'updated' => $updated,
        ]);
    }

    /**
     * Add or update a site cookie
     */
    public function updateSiteCookie(Request $request): JsonResponse
    {
        $site = $this->validateSiteToken($request);
        
        if (!$site) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid site token',
            ], 401);
        }
        
        $validated = $request->validate([
            'cookie_name' => 'required|string|max:255',
            'category' => 'required|in:necessary,functional,analytics,marketing',
            'provider' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:1000',
            'duration' => 'nullable|string|max:100',
            'script_pattern' => 'nullable|string|max:500',
            'is_active' => 'boolean',
        ]);
        
        $cookie = SiteCookie::updateOrCreate(
            [
                'site_id' => $site->id,
                'cookie_name' => $validated['cookie_name'],
            ],
            array_merge($validated, [
                'is_custom' => true,
            ])
        );
        
        return response()->json([
            'success' => true,
            'data' => $cookie,
        ]);
    }

    /**
     * Format expiry seconds to human readable duration
     */
    private function formatDuration(?int $seconds): string
    {
        if ($seconds === null || $seconds <= 0) {
            return 'Session';
        }
        
        $days = $seconds / 86400;
        
        if ($days >= 365) {
            $years = round($days / 365, 1);
            return $years . ' year' . ($years != 1 ? 's' : '');
        }
        
        if ($days >= 30) {
            $months = round($days / 30);
            return $months . ' month' . ($months != 1 ? 's' : '');
        }
        
        if ($days >= 1) {
            return round($days) . ' day' . ($days != 1 ? 's' : '');
        }
        
        $hours = $seconds / 3600;
        if ($hours >= 1) {
            return round($hours) . ' hour' . ($hours != 1 ? 's' : '');
        }
        
        return round($seconds / 60) . ' minutes';
    }

    /**
     * Validate site token
     */
    private function validateSiteToken(Request $request): ?Site
    {
        $token = $request->header('X-Site-Token') ?? $request->input('site_token');
        
        if (!$token) {
            return null;
        }
        
        return Site::where('site_token', $token)->first();
    }
}
