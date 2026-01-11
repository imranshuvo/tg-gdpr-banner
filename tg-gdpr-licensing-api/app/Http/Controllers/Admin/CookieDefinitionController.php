<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CookieDefinition;
use Illuminate\Http\Request;

class CookieDefinitionController extends Controller
{
    /**
     * Display global cookie database
     */
    public function index(Request $request)
    {
        $query = CookieDefinition::query();
        
        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }
        
        if ($request->filled('source')) {
            $query->where('source', $request->source);
        }
        
        if ($request->filled('verified')) {
            $query->where('verified', $request->boolean('verified'));
        }
        
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('cookie_name', 'like', "%{$search}%")
                  ->orWhere('provider', 'like', "%{$search}%")
                  ->orWhere('platform', 'like', "%{$search}%");
            });
        }
        
        $sortField = $request->get('sort', 'usage_count');
        $sortDir = $request->get('dir', 'desc');
        $query->orderBy($sortField, $sortDir);
        
        $cookies = $query->paginate(50)->withQueryString();
        
        return view('admin.cookies.index', compact('cookies'));
    }

    /**
     * Show create form
     */
    public function create()
    {
        return view('admin.cookies.create');
    }

    /**
     * Store new cookie definition
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'cookie_name' => 'required|string|max:255',
            'cookie_pattern' => 'nullable|string|max:255',
            'is_regex' => 'boolean',
            'category' => 'required|in:necessary,functional,analytics,marketing',
            'provider' => 'required|string|max:255',
            'provider_url' => 'nullable|url|max:500',
            'description' => 'required|string|max:1000',
            'description_translations' => 'nullable|array',
            'duration' => 'required|string|max:100',
            'duration_seconds' => 'nullable|integer|min:0',
            'platform' => 'nullable|string|max:100',
            'source' => 'required|in:open_database,scanned,manual,ai_categorized',
        ]);
        
        $validated['verified'] = true;
        $validated['verified_by'] = auth()->id();
        
        $cookie = CookieDefinition::create($validated);
        
        return redirect()
            ->route('admin.cookie-definitions.show', $cookie)
            ->with('success', 'Cookie definition created.');
    }

    /**
     * Show cookie definition
     */
    public function show(CookieDefinition $cookieDefinition)
    {
        $cookieDefinition->load('siteCookies.site');
        
        return view('admin.cookies.show', ['cookie' => $cookieDefinition]);
    }

    /**
     * Edit form
     */
    public function edit(CookieDefinition $cookieDefinition)
    {
        return view('admin.cookies.edit', ['cookie' => $cookieDefinition]);
    }

    /**
     * Update cookie definition
     */
    public function update(Request $request, CookieDefinition $cookieDefinition)
    {
        $validated = $request->validate([
            'cookie_name' => 'required|string|max:255',
            'cookie_pattern' => 'nullable|string|max:255',
            'is_regex' => 'boolean',
            'category' => 'required|in:necessary,functional,analytics,marketing',
            'provider' => 'required|string|max:255',
            'provider_url' => 'nullable|url|max:500',
            'description' => 'required|string|max:1000',
            'description_translations' => 'nullable|array',
            'duration' => 'required|string|max:100',
            'duration_seconds' => 'nullable|integer|min:0',
            'platform' => 'nullable|string|max:100',
        ]);
        
        $cookieDefinition->update($validated);
        
        return redirect()
            ->route('admin.cookie-definitions.show', $cookieDefinition)
            ->with('success', 'Cookie definition updated.');
    }

    /**
     * Delete cookie definition
     */
    public function destroy(CookieDefinition $cookieDefinition)
    {
        $cookieDefinition->delete();
        
        return redirect()
            ->route('admin.cookie-definitions.index')
            ->with('success', 'Cookie definition deleted.');
    }

    /**
     * Verify a cookie definition
     */
    public function verify(CookieDefinition $cookieDefinition)
    {
        $cookieDefinition->update([
            'verified' => true,
            'verified_by' => auth()->id(),
        ]);
        
        return back()->with('success', 'Cookie verified.');
    }

    /**
     * Bulk import cookies
     */
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt|max:10240',
        ]);
        
        $file = $request->file('file');
        $handle = fopen($file->getPathname(), 'r');
        
        // Skip header row
        $header = fgetcsv($handle);
        
        $imported = 0;
        $skipped = 0;
        
        while (($row = fgetcsv($handle)) !== false) {
            if (count($row) < 5) {
                $skipped++;
                continue;
            }
            
            try {
                CookieDefinition::updateOrCreate(
                    ['cookie_name' => $row[0], 'provider' => $row[2]],
                    [
                        'category' => $row[1],
                        'description' => $row[3] ?? '',
                        'duration' => $row[4] ?? 'Session',
                        'platform' => $row[5] ?? null,
                        'source' => 'open_database',
                    ]
                );
                $imported++;
            } catch (\Exception $e) {
                $skipped++;
            }
        }
        
        fclose($handle);
        
        return back()->with('success', "Imported {$imported} cookies. Skipped {$skipped}.");
    }
}
