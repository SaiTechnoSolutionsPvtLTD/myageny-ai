<style>
.rec-stats { display:grid; grid-template-columns:repeat(auto-fit, minmax(180px, 1fr)); gap:12px; }
.rec-stat { background:#fff; border:1px solid #e1dee3; border-radius:16px; padding:16px; text-decoration:none; color:#121212; }
.rec-stat.is-active { border-color:#fe5f04; box-shadow:0 10px 24px rgba(254,95,4,.10); }
.rec-stat-label { font-size:11px; font-weight:800; color:#8a8a8a; text-transform:uppercase; letter-spacing:.06em; }
.rec-stat-value { margin-top:8px; font-size:28px; font-weight:800; }
.rec-chip { display:inline-flex; align-items:center; padding:4px 10px; border-radius:999px; font-size:11px; font-weight:800; white-space:nowrap; }
.rec-chip-shortlist { background:#eef2ff; color:#4338ca; }
.rec-chip-follow_up { background:#fffbeb; color:#b45309; }
.rec-chip-rnr { background:#fff1f2; color:#e11d48; }
.rec-chip-applied { background:#eff6ff; color:#2563eb; }
.rec-chip-screening { background:#fff7ed; color:#c2410c; }
.rec-chip-interview_scheduled { background:#faf5ff; color:#7c3aed; }
.rec-chip-selected { background:#f0fdf4; color:#15803d; }
.rec-chip-rejected { background:#fef2f2; color:#b91c1c; }
.rec-grid-3 { display:grid; grid-template-columns:repeat(3, minmax(0, 1fr)); gap:16px; }
.rec-timeline { display:flex; flex-direction:column; gap:12px; }
.rec-timeline-item { padding:14px; border:1px solid #f0eef2; border-radius:14px; background:#fafafa; }
.rec-timeline-head { display:flex; justify-content:space-between; gap:12px; align-items:flex-start; }
.rec-timeline-title { font-size:13px; font-weight:800; color:#121212; }
.rec-timeline-meta { font-size:11px; color:#9e9e9e; margin-top:3px; }
.rec-timeline-note { margin-top:10px; font-size:13px; line-height:1.55; color:#444; white-space:pre-line; }
.rec-profile-code { margin-top:16px; padding:14px 16px; border-radius:16px; background:#fff7ed; border:1px solid #fed7aa; }
.rec-profile-code-label { font-size:10px; font-weight:800; letter-spacing:.12em; text-transform:uppercase; color:#c2410c; }
.rec-profile-code-value { margin-top:7px; font-size:22px; font-weight:800; letter-spacing:.04em; color:#121212; }
.rec-status-actions { display:grid; grid-template-columns:1fr 1fr; gap:8px; margin-top:14px; }
.rec-resume-link { color:#fe5f04; font-weight:800; text-decoration:none; }
.rec-resume-link:hover { text-decoration:underline; }
@media (max-width: 960px) {
    .rec-stats, .rec-grid-3 { grid-template-columns:repeat(2, minmax(0, 1fr)); }
}
@media (max-width: 640px) {
    .rec-stats, .rec-grid-3 { grid-template-columns:1fr; }
}
</style>
