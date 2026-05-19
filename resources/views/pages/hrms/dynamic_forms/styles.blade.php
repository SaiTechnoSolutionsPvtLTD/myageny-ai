<style>
.df-shell {
    display: flex;
    flex-direction: column;
    gap: 18px;
}
.df-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 16px;
}
.df-full {
    grid-column: 1 / -1;
}
.df-field-card {
    border: 1px solid #f0eef2;
    border-radius: 16px;
    background: #fafafa;
    padding: 18px;
}
.df-field-card-head {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 12px;
    margin-bottom: 14px;
}
.df-field-title {
    font-size: 14px;
    font-weight: 800;
    color: #121212;
}
.df-field-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 14px;
}
.df-help {
    font-size: 12px;
    color: #8a8a8a;
}
.df-chip {
    display: inline-flex;
    align-items: center;
    padding: 6px 10px;
    border-radius: 999px;
    background: #fff4eb;
    color: #c55d1c;
    font-size: 12px;
    font-weight: 700;
}
.df-actions {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}
.df-link-box {
    padding: 14px 16px;
    border-radius: 14px;
    border: 1px solid #f0eef2;
    background: #fafafa;
    font-size: 13px;
    color: #444;
    word-break: break-all;
}
.df-public-wrap {
    min-height: 100vh;
    background: linear-gradient(180deg, #fff7f1 0%, #f7f3ef 40%, #f4f5f7 100%);
    padding: 28px 16px;
}
.df-public-card {
    width: 100%;
    max-width: 880px;
    margin: 0 auto;
    background: rgba(255,255,255,.95);
    border: 1px solid #e1dee3;
    border-radius: 24px;
    box-shadow: 0 18px 45px rgba(18,18,18,.06);
    overflow: hidden;
}
.df-public-head {
    padding: 24px 28px 18px;
    border-bottom: 1px solid #f0eef2;
}
.df-public-title {
    font-size: 26px;
    font-weight: 800;
    color: #121212;
}
.df-public-sub {
    margin-top: 8px;
    color: #7c7c7c;
    line-height: 1.6;
}
.df-public-body {
    padding: 24px 28px 28px;
}
.df-response-table {
    overflow-x: auto;
}
.df-response-table table {
    width: 100%;
    min-width: 900px;
    border-collapse: collapse;
}
.df-response-table th,
.df-response-table td {
    padding: 12px 14px;
    border-bottom: 1px solid #f0eef2;
    vertical-align: top;
    text-align: left;
    font-size: 13px;
}
.df-response-table th {
    background: #fafafa;
    color: #8f8f8f;
    font-size: 11px;
    font-weight: 800;
    letter-spacing: .06em;
    text-transform: uppercase;
}
@media (max-width: 768px) {
    .df-grid,
    .df-field-grid {
        grid-template-columns: 1fr;
    }
    .df-public-head,
    .df-public-body {
        padding: 20px;
    }
}
</style>
