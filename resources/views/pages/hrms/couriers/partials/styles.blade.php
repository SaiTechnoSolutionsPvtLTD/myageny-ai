<style>
.cr-tabs {
    display: flex;
    align-items: center;
    gap: 10px;
    background: #ffffff;
    padding: 10px 14px;
    border-radius: 16px;
    border: 1px solid #e1dee3;
    overflow-x: auto;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.03);
}
.cr-tab {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 18px;
    border-radius: 12px;
    border: 1px solid transparent;
    background: transparent;
    color: #475569;
    font-size: 13px;
    font-weight: 700;
    text-decoration: none;
    white-space: nowrap;
    transition: all .18s ease;
}
.cr-tab:hover {
    background: #fff7ed;
    color: #fe5f04;
}
.cr-tab.active {
    background: linear-gradient(135deg, #fe5f04, #ff7c30);
    color: #ffffff;
    box-shadow: 0 4px 14px rgba(254, 95, 4, 0.22);
}
.cr-stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 14px;
}
.cr-stat-card {
    background: #fff;
    border: 1px solid #e1dee3;
    border-radius: 16px;
    padding: 16px 20px;
    display: flex;
    flex-direction: column;
    gap: 6px;
}
.cr-stat-label {
    font-size: 11px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: .06em;
    color: #8a8a8a;
}
.cr-stat-value {
    font-size: 26px;
    font-weight: 800;
    color: #121212;
    line-height: 1.1;
}
.cr-stat-sub {
    font-size: 12px;
    color: #7c7c7c;
}
.cr-shell {
    display: flex;
    flex-direction: column;
    gap: 18px;
}
.cr-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 16px;
}
.cr-full {
    grid-column: 1 / -1;
}
.cr-field-card {
    border: 1px solid #e7e5ea;
    border-radius: 16px;
    background: #fafafa;
    padding: 18px;
    transition: border-color .18s ease, box-shadow .18s ease;
}
.cr-field-card:hover {
    border-color: #fed7aa;
}
.cr-field-card-head {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 12px;
    margin-bottom: 14px;
    padding-bottom: 10px;
    border-bottom: 1px solid #ece9ef;
}
.cr-field-title-wrap {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
}
.cr-field-title {
    font-size: 14px;
    font-weight: 800;
    color: #121212;
}
.cr-type-pill {
    display: inline-flex;
    align-items: center;
    padding: 3px 9px;
    border-radius: 999px;
    background: #fff4eb;
    color: #c55d1c;
    font-size: 11px;
    font-weight: 800;
    text-transform: uppercase;
}
.cr-field-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 14px;
}
.cr-check-row {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    font-size: 13px;
    font-weight: 600;
    color: #334155;
    cursor: pointer;
    user-select: none;
}
.cr-check-row input {
    width: 16px;
    height: 16px;
    accent-color: #fe5f04;
}
.cr-help {
    font-size: 12px;
    color: #8a8a8a;
}
.cr-detail-pills {
    display: flex;
    flex-direction: column;
    gap: 5px;
    max-width: 480px;
}
.cr-detail-row {
    display: flex;
    align-items: baseline;
    gap: 6px;
    font-size: 12.5px;
    line-height: 1.45;
}
.cr-detail-key {
    font-weight: 700;
    color: #64748b;
    flex-shrink: 0;
}
.cr-detail-val {
    color: #0f172a;
    word-break: break-word;
}
.cr-file-pill {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 4px 10px;
    border-radius: 8px;
    background: #eff6ff;
    color: #1d4ed8;
    border: 1px solid #bfdbfe;
    font-size: 12px;
    font-weight: 700;
    text-decoration: none;
}
.cr-file-pill:hover {
    background: #dbeafe;
}
.cr-dynamic-box {
    margin-top: 20px;
    padding: 22px;
    background: #ffffff;
    border: 1.5px solid #fe5f04;
    border-radius: 16px;
    box-shadow: 0 4px 14px rgba(254, 95, 4, 0.08);
}
.cr-dynamic-head {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 12px;
    margin-bottom: 18px;
    padding-bottom: 12px;
    border-bottom: 1.5px dashed #fed7aa;
    flex-wrap: wrap;
}
.cr-count-badge {
    background: #fff7ed;
    color: #ea580c;
    border: 1px solid #fed7aa;
    padding: 4px 12px;
    border-radius: 999px;
    font-size: 12px;
    font-weight: 800;
}
.cr-notice {
    margin-top: 18px;
    padding: 18px 20px;
    border-radius: 14px;
    border: 1.5px dashed #cbd5e1;
    background: #f8fafc;
    color: #475569;
    font-size: 13px;
    display: flex;
    align-items: center;
    gap: 10px;
}
.cr-option-list {
    display: flex;
    flex-direction: column;
    gap: 8px;
    padding-top: 4px;
}
.cr-file-preview-box {
    margin-top: 8px;
    padding: 10px 12px;
    border-radius: 10px;
    border: 1px solid #e2e8f0;
    background: #f8fafc;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    flex-wrap: wrap;
}
.cr-img-thumb {
    margin-top: 10px;
    max-width: 240px;
    max-height: 160px;
    border-radius: 10px;
    border: 1px solid #e2e8f0;
    object-fit: cover;
    display: block;
}
@media (max-width: 768px) {
    .cr-grid,
    .cr-field-grid {
        grid-template-columns: 1fr;
    }
}
</style>
