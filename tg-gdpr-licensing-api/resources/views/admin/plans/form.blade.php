@extends('layouts.admin')

@section('title', $plan->exists ? 'Edit Plan' : 'New Plan')
@section('page-title', $plan->exists ? 'Edit Plan — ' . $plan->name : 'New Plan')

@section('content')
@php
    $action = $plan->exists ? route('admin.plans.update', $plan) : route('admin.plans.store');
    $features = old('features', $plan->features ?? []);
@endphp

<div class="max-w-4xl">

    @if ($errors->any())
        <div class="mb-6 rounded-md bg-red-50 border border-red-200 p-4 text-red-800 text-sm">
            <ul class="list-disc pl-5">
                @foreach ($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ $action }}" class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 space-y-6">
        @csrf
        @if ($plan->exists) @method('PUT') @endif

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Slug</label>
                <input type="text" name="slug" value="{{ old('slug', $plan->slug) }}"
                       pattern="[a-z0-9-]+" required maxlength="64"
                       class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm font-mono">
                <p class="mt-1 text-xs text-gray-500">Lowercase letters, digits and dashes. Used in URLs and provider metadata.</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Name</label>
                <input type="text" name="name" value="{{ old('name', $plan->name) }}" required maxlength="120"
                       class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
            </div>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
            <input type="text" name="description" value="{{ old('description', $plan->description) }}" maxlength="255"
                   class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Max sites</label>
                <input type="number" name="max_sites" value="{{ old('max_sites', $plan->max_sites ?? 1) }}" required min="1" max="1000"
                       class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Display price</label>
                <input type="text" name="display_price" value="{{ old('display_price', $plan->display_price) }}" maxlength="32" placeholder="$99"
                       class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Display period</label>
                <input type="text" name="display_period" value="{{ old('display_period', $plan->display_period ?? '/year') }}" maxlength="32"
                       class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
            </div>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Features (one per line)</label>
            <textarea name="features_raw" rows="5"
                      class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm font-mono">{{ old('features_raw', implode("\n", (array) $features)) }}</textarea>
            <p class="mt-1 text-xs text-gray-500">Each non-empty line becomes a checkmark on the pricing card.</p>
        </div>

        <fieldset class="border border-gray-200 rounded-md p-4">
            <legend class="px-2 text-sm font-semibold text-gray-700">Stripe price IDs</legend>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-2">
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Test (pk_test_…)</label>
                    <input type="text" name="stripe_price_id_test" value="{{ old('stripe_price_id_test', $plan->stripe_price_id_test) }}" placeholder="price_…"
                           class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm font-mono">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Live</label>
                    <input type="text" name="stripe_price_id_live" value="{{ old('stripe_price_id_live', $plan->stripe_price_id_live) }}" placeholder="price_…"
                           class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm font-mono">
                </div>
            </div>
        </fieldset>

        <fieldset class="border border-gray-200 rounded-md p-4">
            <legend class="px-2 text-sm font-semibold text-gray-700">Frisbii plan IDs</legend>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-2">
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Test</label>
                    <input type="text" name="frisbii_plan_id_test" value="{{ old('frisbii_plan_id_test', $plan->frisbii_plan_id_test) }}"
                           class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm font-mono">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Live</label>
                    <input type="text" name="frisbii_plan_id_live" value="{{ old('frisbii_plan_id_live', $plan->frisbii_plan_id_live) }}"
                           class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm font-mono">
                </div>
            </div>
        </fieldset>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <label class="inline-flex items-center text-sm">
                <input type="hidden" name="is_active" value="0">
                <input type="checkbox" name="is_active" value="1" {{ old('is_active', $plan->is_active ?? true) ? 'checked' : '' }}
                       class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                <span class="ml-2 text-gray-700">Active (show on pricing page)</span>
            </label>
            <label class="inline-flex items-center text-sm">
                <input type="hidden" name="is_popular" value="0">
                <input type="checkbox" name="is_popular" value="1" {{ old('is_popular', $plan->is_popular) ? 'checked' : '' }}
                       class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                <span class="ml-2 text-gray-700">Mark as popular</span>
            </label>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Sort order</label>
                <input type="number" name="sort_order" value="{{ old('sort_order', $plan->sort_order ?? 0) }}" min="0" max="9999"
                       class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
            </div>
        </div>

        <div class="flex items-center gap-2 pt-2 border-t border-gray-100">
            <button type="submit" class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-md">
                {{ $plan->exists ? 'Save changes' : 'Create plan' }}
            </button>
            <a href="{{ route('admin.plans.index') }}" class="text-sm text-gray-600 hover:text-gray-900">Cancel</a>
        </div>
    </form>
</div>
@endsection
