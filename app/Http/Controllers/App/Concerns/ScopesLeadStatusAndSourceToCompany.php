<?php

namespace App\Http\Controllers\App\Concerns;

use App\Models\LeadSource;
use App\Models\LeadStatus;
use App\Models\User;

/**
 * Mobile-only company isolation for the Lead Status / Lead Source lookup
 * tables (`lead_statuses`, `lead_sources`) — see "Lead Status & Source –
 * Company and Branch-wise Data Filtering".
 *
 * Both `LeadStatus` and `LeadSource` already carry a `BelongsToCompany`
 * global scope (see app/Models/Concerns/BelongsToCompany.php), but that
 * scope is silently bypassed by two very common code paths: Laravel's
 * `exists:lead_statuses,id` / `exists:lead_sources,id` validation rules run
 * a raw DB query with no model scope at all, and `Model::find($id)` inside
 * a request whose auth guard/context the global scope doesn't pick up still
 * returns rows across every company. `Lead::sourceOptions()` /
 * `statusOptions()` (app/Models/Lead.php) additionally cache their result
 * in a static property for the lifetime of the PHP process, so a value
 * resolved once can keep being served regardless of which company's
 * request asks for it next. That combination is what let every company's
 * identically-named defaults ("New", "Hot", "Warm", ...) show up together
 * as apparent duplicates in the mobile app, and would just as easily let a
 * crafted `lead_status_id`/`lead_source_id` from one company be saved onto
 * another company's lead.
 *
 * This trait does not touch `Lead.php`, `LeadStatus.php`, `LeadSource.php`,
 * or `BelongsToCompany.php` (all shared with web) — it only gives
 * mobile-only (App\Http\Controllers\App\*) controllers an explicit,
 * non-cached, always-company-scoped alternative to reach for instead.
 *
 * A row with a NULL `company_id` is treated as a shared/global default
 * available to every company — the same convention
 * LeadShowController::updateProductStatus() already uses.
 */
trait ScopesLeadStatusAndSourceToCompany
{
    /** Returns `[id => name]`, ordered by name, scoped to the user's company (+ global/NULL-company rows). */
    protected function companyScopedLeadStatusOptions(?User $user): array
    {
        return $this->companyScopedLeadLookupQuery(LeadStatus::query(), $user)
            ->orderBy('name')
            ->pluck('name', 'id')
            ->toArray();
    }

    /** Returns `[id => name]`, ordered by name, scoped to the user's company (+ global/NULL-company rows). */
    protected function companyScopedLeadSourceOptions(?User $user): array
    {
        return $this->companyScopedLeadLookupQuery(LeadSource::query(), $user)
            ->orderBy('name')
            ->pluck('name', 'id')
            ->toArray();
    }

    /**
     * True if `$id` is safe to accept from the client as a lead_status_id —
     * either omitted, or an id that actually belongs to (or is a global
     * default for) the acting user's company. Call this before persisting
     * a client-submitted lead_status_id (store/update), since the
     * `exists:lead_statuses,id` validation rule alone does not check
     * company ownership.
     */
    protected function isLeadStatusIdAllowedForCompany(?int $id, ?User $user): bool
    {
        if ($id === null) {
            return true;
        }

        return $this->companyScopedLeadLookupQuery(LeadStatus::query(), $user)
            ->whereKey($id)
            ->exists();
    }

    /** Same as {@see isLeadStatusIdAllowedForCompany()}, for lead_source_id. */
    protected function isLeadSourceIdAllowedForCompany(?int $id, ?User $user): bool
    {
        if ($id === null) {
            return true;
        }

        return $this->companyScopedLeadLookupQuery(LeadSource::query(), $user)
            ->whereKey($id)
            ->exists();
    }

    /**
     * A deterministic (alphabetical), company-scoped "default" LeadStatus —
     * used where a status must be auto-picked with no explicit id given
     * (e.g. auto-creating a LeadProduct from an approved price request).
     * Replaces a bare `LeadStatus::first()`, which has no company scope
     * guarantee and no defined ordering.
     */
    protected function companyScopedDefaultLeadStatus(?User $user): ?LeadStatus
    {
        return $this->companyScopedLeadLookupQuery(LeadStatus::query(), $user)
            ->orderBy('name')
            ->first();
    }

    /**
     * @param \Illuminate\Database\Eloquent\Builder<LeadStatus|LeadSource> $query
     * @return \Illuminate\Database\Eloquent\Builder<LeadStatus|LeadSource>
     */
    private function companyScopedLeadLookupQuery(\Illuminate\Database\Eloquent\Builder $query, ?User $user): \Illuminate\Database\Eloquent\Builder
    {
        // withoutGlobalScope('company') — this explicit filter (with its
        // NULL-company_id carve-out) fully replaces BelongsToCompany's
        // global scope for these two lookups; running both would either be
        // redundant or, if the global scope silently no-ops for any
        // reason, would leave this call as the only thing actually
        // enforcing it.
        $query->withoutGlobalScope('company');

        $companyId = $user?->company_id;

        if (! $companyId) {
            // No resolvable company for the acting user — fail safe to
            // "global/unowned rows only" rather than returning every
            // company's data.
            return $query->whereNull('company_id');
        }

        return $query->where(function ($q) use ($companyId) {
            $q->where('company_id', $companyId)->orWhereNull('company_id');
        });
    }
}
