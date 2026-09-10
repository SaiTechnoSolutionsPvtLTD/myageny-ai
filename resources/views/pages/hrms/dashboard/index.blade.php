@extends('layouts.app')

@section('title', 'HRMS Dashboard')

@push('styles')
<style>
.hrms-dashboard{
    min-height:100%;
    padding:28px;
    background:
        radial-gradient(circle at top left, rgba(254,95,4,.10), transparent 24%),
        linear-gradient(180deg,#fff7f1 0%,#f8f5f1 42%,#f4f5f7 100%);
}
.hrms-shell{max-width:1440px;margin:0 auto;display:flex;flex-direction:column;gap:22px}
.hrms-birthday-banner{
    position:relative;display:flex;align-items:center;justify-content:space-between;gap:18px;
    padding:22px 24px;border-radius:24px;border:1px solid #ffd5b8;
    background:linear-gradient(135deg,#fff3e8 0%,#fff9f5 55%,#ffffff 100%);
    box-shadow:0 18px 36px rgba(254,95,4,.10);
    overflow:hidden;
    isolation:isolate;
}
.hrms-birthday-banner::before{
    content:"";position:absolute;inset:-30% auto auto -10%;width:240px;height:240px;border-radius:50%;
    background:radial-gradient(circle, rgba(255,190,141,.45) 0%, rgba(255,190,141,0) 70%);
    animation:hrmsBirthdayGlow 6s ease-in-out infinite;
    pointer-events:none;z-index:0;
}
.hrms-birthday-banner::after{
    content:"";position:absolute;inset:0;
    background:linear-gradient(120deg, transparent 0%, rgba(255,255,255,.18) 36%, rgba(255,255,255,.62) 49%, rgba(255,255,255,.12) 62%, transparent 100%);
    transform:translateX(-130%);
    animation:hrmsBirthdayShimmer 4.8s ease-in-out infinite;
    pointer-events:none;z-index:0;
}
.hrms-birthday-copy{position:relative;z-index:1;display:flex;flex-direction:column;gap:8px}
.hrms-birthday-kicker{
    display:inline-flex;align-items:center;width:max-content;padding:6px 10px;border-radius:999px;
    background:#ffffff;color:#d35400;font-size:11px;font-weight:800;letter-spacing:.08em;text-transform:uppercase;
    border:1px solid #ffd9bf;
    animation:hrmsBirthdayPop .7s ease-out both;
}
.hrms-birthday-title{font-size:28px;line-height:1.1;font-weight:900;color:#121212;animation:hrmsBirthdayRise .8s ease-out .08s both}
.hrms-birthday-text{font-size:14px;line-height:1.7;color:#7b5e4b;max-width:760px;animation:hrmsBirthdayRise .8s ease-out .16s both}
.hrms-birthday-badges{display:flex;align-items:center;gap:10px;flex-wrap:wrap}
.hrms-birthday-pill{
    display:inline-flex;align-items:center;padding:8px 12px;border-radius:999px;
    background:#fff;color:#c25513;font-size:12px;font-weight:700;border:1px solid #ffe0cc;
    animation:hrmsBirthdayRise .8s ease-out both;
}
.hrms-birthday-pill:nth-child(1){animation-delay:.24s}
.hrms-birthday-pill:nth-child(2){animation-delay:.32s}
.hrms-birthday-sparkles{
    position:absolute;inset:0;pointer-events:none;z-index:0;
}
.hrms-birthday-sparkles span{
    position:absolute;display:block;border-radius:999px;opacity:.85;
    animation:hrmsSparkleFloat linear infinite;
}
.hrms-birthday-sparkles span:nth-child(1){
    top:20px;right:96px;width:10px;height:10px;background:#ffd166;animation-duration:5.4s;
}
.hrms-birthday-sparkles span:nth-child(2){
    top:64px;right:34px;width:7px;height:7px;background:#ff9f68;animation-duration:4.8s;animation-delay:-1.1s;
}
.hrms-birthday-sparkles span:nth-child(3){
    bottom:22px;right:136px;width:12px;height:12px;background:#ffe8b8;animation-duration:6.1s;animation-delay:-2.3s;
}
.hrms-birthday-sparkles span:nth-child(4){
    bottom:42px;left:42%;width:8px;height:8px;background:#ffc08f;animation-duration:5.1s;animation-delay:-1.7s;
}
.hrms-birthday-crackers{
    position:absolute;top:18px;right:18px;display:flex;gap:18px;pointer-events:none;z-index:0;
}
.hrms-birthday-cracker{
    position:relative;width:44px;height:44px;opacity:.9;animation:hrmsCrackerPop 2.6s ease-in-out infinite;
}
.hrms-birthday-cracker:nth-child(2){animation-delay:.9s}
.hrms-birthday-cracker::before,
.hrms-birthday-cracker::after{
    content:"";position:absolute;left:50%;top:50%;width:4px;height:4px;border-radius:999px;background:#ff8f42;
    transform:translate(-50%,-50%) scale(.4);
}
.hrms-birthday-cracker::before{
    box-shadow:
        0 -18px 0 2px #ffd166,
        0 18px 0 2px #ff8f42,
        18px 0 0 2px #ffb703,
        -18px 0 0 2px #ff6b35,
        12px 12px 0 2px #ffe29a,
        -12px 12px 0 2px #ff9f68,
        12px -12px 0 2px #ffd166,
        -12px -12px 0 2px #ffb703;
}
.hrms-birthday-cracker::after{
    box-shadow:
        0 -24px 0 1px rgba(255,209,102,.85),
        0 24px 0 1px rgba(255,143,66,.85),
        24px 0 0 1px rgba(255,183,3,.85),
        -24px 0 0 1px rgba(255,107,53,.85),
        16px 16px 0 1px rgba(255,226,154,.85),
        -16px 16px 0 1px rgba(255,159,104,.85),
        16px -16px 0 1px rgba(255,209,102,.85),
        -16px -16px 0 1px rgba(255,183,3,.85);
    animation:hrmsCrackerSpark 2.6s ease-in-out infinite;
}
.hrms-birthday-burst{
    position:relative;z-index:1;min-width:84px;height:84px;border-radius:26px;display:flex;align-items:center;justify-content:center;
    background:linear-gradient(135deg,#fe5f04,#ff9b5e);color:#fff;font-size:38px;
    box-shadow:0 14px 28px rgba(254,95,4,.22);
    animation:hrmsBirthdayFloat 3.2s ease-in-out infinite;
}
.hrms-birthday-scene{
    position:relative;z-index:1;display:flex;align-items:flex-end;gap:16px;flex-shrink:0;
}
.hrms-birthday-cake{
    position:relative;width:112px;height:118px;flex-shrink:0;
    animation:hrmsCakeBounce 3.4s ease-in-out infinite;
}
.hrms-birthday-cake-top{
    position:absolute;left:16px;right:16px;bottom:54px;height:28px;border-radius:16px 16px 12px 12px;
    background:linear-gradient(180deg,#fff8ef 0%,#ffe2c6 100%);
    border:1px solid #ffd4ae;
}
.hrms-birthday-cake-top::after{
    content:"";position:absolute;left:8px;right:8px;bottom:-8px;height:14px;border-radius:999px;
    background:radial-gradient(circle at 10px 4px, #ff9f68 0 7px, transparent 8px) repeat-x;
    background-size:24px 14px;
}
.hrms-birthday-cake-base{
    position:absolute;left:8px;right:8px;bottom:16px;height:46px;border-radius:16px;
    background:linear-gradient(180deg,#ffb37a 0%,#fe7f45 100%);
    border:1px solid #f88f57;box-shadow:0 12px 24px rgba(254,95,4,.14);
}
.hrms-birthday-cake-base::before{
    content:"";position:absolute;left:16px;right:16px;top:10px;height:8px;border-radius:999px;background:rgba(255,255,255,.35);
}
.hrms-birthday-candle{
    position:absolute;bottom:82px;width:10px;height:28px;border-radius:999px;
    background:repeating-linear-gradient(180deg,#ffffff 0 6px,#ff8f42 6px 12px);
    border:1px solid #ffcba2;
}
.hrms-birthday-candle:nth-child(3){left:30px}
.hrms-birthday-candle:nth-child(4){left:51px}
.hrms-birthday-candle:nth-child(5){left:72px}
.hrms-birthday-flame{
    position:absolute;top:-12px;left:50%;width:12px;height:16px;transform:translateX(-50%);
    background:radial-gradient(circle at 50% 70%, #ffd166 0 35%, #ff8f42 50%, #ff6b35 100%);
    border-radius:60% 60% 60% 60% / 75% 75% 45% 45%;
    box-shadow:0 0 14px rgba(255,193,7,.45);
    animation:hrmsFlameFlicker 1.1s ease-in-out infinite;
}
.hrms-birthday-character{
    position:relative;width:126px;padding:44px 14px 14px;border-radius:26px 26px 22px 22px;
    background:linear-gradient(180deg,#fff 0%,#fff4ea 100%);
    border:1px solid #f4dcca;box-shadow:0 16px 26px rgba(18,18,18,.06);
    animation:hrmsCharacterWave 3.6s ease-in-out infinite;
}
.hrms-birthday-character-head{
    position:absolute;left:50%;top:-22px;transform:translateX(-50%);
    width:56px;height:56px;border-radius:50%;
    background:linear-gradient(180deg,#ffdcbf 0%,#ffc9a0 100%);
    border:1px solid #efb88b;
}
.hrms-birthday-character-head::before,
.hrms-birthday-character-head::after{
    content:"";position:absolute;top:22px;width:6px;height:6px;border-radius:50%;background:#53311d;
}
.hrms-birthday-character-head::before{left:16px}
.hrms-birthday-character-head::after{right:16px}
.hrms-birthday-character-head span{
    position:absolute;left:50%;bottom:12px;width:18px;height:9px;transform:translateX(-50%);
    border-bottom:3px solid #d66a4d;border-radius:0 0 18px 18px;
}
.hrms-birthday-character-body{
    height:72px;border-radius:18px;background:linear-gradient(180deg,#ff9f68 0%,#fe7b3b 100%);
    position:relative;
}
.hrms-birthday-character-body::before,
.hrms-birthday-character-body::after{
    content:"";position:absolute;top:18px;width:18px;height:8px;border-radius:999px;background:#ffb78e;
}
.hrms-birthday-character-body::before{left:-8px;transform-origin:right center;transform:rotate(24deg)}
.hrms-birthday-character-body::after{right:-10px;transform-origin:left center;transform:rotate(-32deg);animation:hrmsArmWave 1.2s ease-in-out infinite}
.hrms-birthday-character-text{
    margin-top:10px;text-align:center;font-size:12px;font-weight:800;line-height:1.45;color:#9f4b1c;
}
.hrms-couple-scene{
    position:relative;display:flex;align-items:flex-end;justify-content:center;gap:10px;
    min-width:148px;padding:8px 8px 0;
}
.hrms-couple-person{
    position:relative;width:54px;padding-top:34px;
}
.hrms-couple-head{
    position:absolute;left:50%;top:0;transform:translateX(-50%);
    width:34px;height:34px;border-radius:50%;
    background:linear-gradient(180deg,#ffdcbf 0%,#ffc9a0 100%);
    border:1px solid #efb88b;
}
.hrms-couple-head::before,
.hrms-couple-head::after{
    content:"";position:absolute;top:13px;width:4px;height:4px;border-radius:50%;background:#53311d;
}
.hrms-couple-head::before{left:10px}
.hrms-couple-head::after{right:10px}
.hrms-couple-head span{
    position:absolute;left:50%;bottom:7px;width:12px;height:6px;transform:translateX(-50%);
    border-bottom:2px solid #d66a4d;border-radius:0 0 12px 12px;
}
.hrms-couple-body{
    height:52px;border-radius:16px 16px 12px 12px;background:linear-gradient(180deg,#ff9f68 0%,#fe7b3b 100%);
    box-shadow:0 12px 24px rgba(18,18,18,.08);
}
.hrms-couple-person.alt .hrms-couple-body{
    background:linear-gradient(180deg,#f472b6 0%,#ec4899 100%);
}
.hrms-couple-heart{
    position:absolute;left:50%;top:14px;transform:translateX(-50%);
    color:#ec4899;font-size:16px;font-weight:900;animation:hrmsBirthdayFloat 2.8s ease-in-out infinite;
}
.hrms-couple-text{
    margin-top:10px;text-align:center;font-size:12px;font-weight:800;line-height:1.45;color:#be185d;
}
.hrms-celebration-section{display:flex;flex-direction:column;gap:10px}
.hrms-celebration-chip{
    display:inline-flex;align-items:center;gap:8px;width:max-content;padding:6px 10px;border-radius:999px;
    font-size:11px;font-weight:800;letter-spacing:.08em;text-transform:uppercase;background:#fff3eb;color:#c25513;border:1px solid #f6d9c6;
}
.hrms-celebration-empty{padding:18px;border-radius:16px;background:#faf7f4;border:1px dashed #e7ddd5;color:#9ca3af;text-align:center}
.hrms-hero{
    display:grid;grid-template-columns:minmax(0,1.4fr) minmax(320px,.8fr);gap:20px;
}
.hrms-card{
    background:rgba(255,255,255,.94);
    border:1px solid #e9e1da;
    border-radius:24px;
    box-shadow:0 20px 44px rgba(18,18,18,.05);
}
.hrms-hero-card{
    padding:28px 30px;
    background:
        radial-gradient(circle at top right, rgba(255,199,164,.55), transparent 28%),
        linear-gradient(135deg,#fffaf7 0%,#ffffff 58%,#fff5ee 100%);
}
.hrms-eyebrow{
    display:inline-flex;align-items:center;padding:6px 10px;border-radius:999px;
    background:#fff1e8;color:#c25513;font-size:11px;font-weight:800;letter-spacing:.08em;text-transform:uppercase;
}
.hrms-title{margin:14px 0 8px;font-size:34px;line-height:1.08;font-weight:800;color:#121212}
.hrms-subtitle{max-width:700px;font-size:14px;line-height:1.7;color:#737373;margin:0}
.hrms-hero-actions{display:flex;gap:12px;flex-wrap:wrap;margin-top:22px}
.hrms-btn{
    display:inline-flex;align-items:center;justify-content:center;gap:8px;
    padding:11px 18px;border-radius:14px;border:1px solid transparent;text-decoration:none;
    font-size:13px;font-weight:700;transition:all .18s ease;cursor:pointer;
}
.hrms-btn-primary{background:linear-gradient(135deg,#fe5f04,#ff7c30);color:#fff;box-shadow:0 14px 24px rgba(254,95,4,.18)}
.hrms-btn-primary:hover{transform:translateY(-1px)}
.hrms-btn-ghost{background:#fff;color:#121212;border-color:#e5ddd6}
.hrms-btn-ghost:hover{background:#faf7f5}
.hrms-aside{
    padding:24px;
    display:flex;flex-direction:column;gap:16px;
    background:linear-gradient(180deg,#fff8f3 0%,#fff 100%);
}
.hrms-aside-title{font-size:15px;font-weight:800;color:#121212}
.hrms-aside-copy{font-size:13px;line-height:1.6;color:#7a7a7a}
.hrms-mini-list{display:flex;flex-direction:column;gap:10px}
.hrms-mini-item{
    display:flex;justify-content:space-between;align-items:center;gap:10px;
    padding:12px 14px;border-radius:16px;background:#fff;border:1px solid #f0e4da;
}
.hrms-mini-item strong{font-size:13px;color:#121212}
.hrms-mini-item span{font-size:12px;color:#8a8a8a}
.hrms-mini-pill{
    min-width:42px;height:42px;border-radius:14px;display:inline-flex;align-items:center;justify-content:center;
    background:#fff3eb;color:#fe5f04;font-weight:800;font-size:14px;
}
.hrms-stats{
    display:grid;grid-template-columns:repeat(6,minmax(0,1fr));gap:16px;
}
.hrms-stat-card{
    padding:20px 22px;
    position:relative;
    overflow:hidden;
    color:#fff !important;
    text-decoration:none !important;
    border:none !important;
    border-radius:20px !important;
    box-shadow:0 10px 25px -5px rgba(0,0,0,0.1),0 8px 10px -6px rgba(0,0,0,0.05) !important;
    transition:all 0.3s cubic-bezier(0.4,0,0.2,1);
    display:flex;
    flex-direction:column;
    justify-content:space-between;
    min-height:130px;
    cursor:pointer;
}
.hrms-stat-card:hover{
    transform:translateY(-4px);
    box-shadow:0 20px 25px -5px rgba(0,0,0,0.15),0 10px 10px -5px rgba(0,0,0,0.08) !important;
}
.hrms-stat-label{
    font-size:11px;
    font-weight:800;
    letter-spacing:.08em;
    text-transform:uppercase;
    color:rgba(255,255,255,0.9) !important;
}
.hrms-stat-value{
    margin-top:10px;
    font-size:32px;
    font-weight:900;
    color:#fff !important;
    line-height:1;
}
.hrms-stat-meta{
    margin-top:8px;
    font-size:12px;
    color:rgba(255,255,255,0.8) !important;
    font-weight:500;
}
.hrms-stat-icon-wrapper {
    position:absolute;
    top:16px;
    right:16px;
    width:36px;
    height:36px;
    border-radius:10px;
    background:rgba(255,255,255,0.22);
    display:flex;
    align-items:center;
    justify-content:center;
    color:#fff;
    font-size:16px;
    backdrop-filter:blur(4px);
}
.hrms-panels{
    display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:18px;
}
.hrms-panel{padding:24px}
.hrms-panel-head{display:flex;justify-content:space-between;align-items:flex-start;gap:14px;margin-bottom:18px}
.hrms-panel-title{font-size:18px;font-weight:800;color:#121212}
.hrms-panel-sub{font-size:13px;line-height:1.6;color:#7b7b7b;margin-top:6px}
.hrms-link{
    color:#fe5f04;text-decoration:none;font-size:12px;font-weight:800;text-transform:uppercase;letter-spacing:.06em;
}
.hrms-feature-list{display:grid;gap:12px}
.hrms-feature{
    display:flex;gap:12px;align-items:flex-start;padding:14px;border-radius:18px;background:#faf7f4;border:1px solid #f0e9e3;
}
.hrms-feature-icon{
    width:42px;height:42px;border-radius:14px;flex-shrink:0;display:flex;align-items:center;justify-content:center;
    background:#fff;color:#fe5f04;font-size:18px;font-weight:800;border:1px solid #f1ddd0;
}
.hrms-feature strong{display:block;font-size:14px;color:#121212}
.hrms-feature span{display:block;font-size:12px;line-height:1.6;color:#808080;margin-top:4px}
.hrms-quick-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px}
.hrms-quick-card{
    padding:18px;border-radius:18px;text-decoration:none;background:linear-gradient(180deg,#fff 0%,#faf7f4 100%);
    border:1px solid #efe6df;transition:transform .18s ease,border-color .18s ease,box-shadow .18s ease;
}
.hrms-quick-card:hover{transform:translateY(-2px);border-color:#f3c8a8;box-shadow:0 14px 24px rgba(18,18,18,.05)}
.hrms-quick-kicker{font-size:11px;font-weight:800;letter-spacing:.08em;text-transform:uppercase;color:#aa7b5b}
.hrms-quick-title{margin-top:8px;font-size:16px;font-weight:800;color:#121212}
.hrms-quick-copy{margin-top:6px;font-size:12px;line-height:1.6;color:#7d7d7d}

/* Department Stats */
.hrms-dept-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:12px}
.hrms-dept-card{padding:16px;border-radius:14px;background:#fff;border:1px solid #f0e9e3}
.hrms-dept-name{font-size:14px;font-weight:700;color:#121212}
.hrms-dept-count{font-size:24px;font-weight:800;color:#fe5f04;margin-top:4px}

/* Horizontal Department Cards */
.hrms-dept-horizontal-grid{display:grid;grid-template-columns:1fr;gap:12px}
.hrms-dept-horizontal-card{
    display:grid;grid-template-columns:1fr auto auto auto;gap:20px;align-items:center;
    padding:18px 20px;border-radius:16px;background:linear-gradient(135deg,#fff 0%,#faf7f4 100%);
    border:1px solid #f0e9e3;transition:all .18s ease;
}
.hrms-dept-horizontal-card:hover{border-color:#f3c8a8;box-shadow:0 8px 16px rgba(18,18,18,.05)}
.hrms-dept-horizontal-info{display:flex;flex-direction:column;gap:4px}
.hrms-dept-horizontal-name{font-size:16px;font-weight:800;color:#121212}
.hrms-dept-horizontal-desc{font-size:12px;color:#8a8a8a}
.hrms-dept-horizontal-stat{display:flex;flex-direction:column;align-items:center;gap:4px;padding:12px 16px;border-radius:12px;background:#fff;border:1px solid #f0e9e3}
.hrms-dept-horizontal-label{font-size:11px;font-weight:800;letter-spacing:.08em;text-transform:uppercase;color:#989898}
.hrms-dept-horizontal-value{font-size:20px;font-weight:800;color:#fe5f04;line-height:1}

/* Birthday Cards */
.hrms-birthday-grid{display:grid;gap:12px}
.hrms-birthday-card{
    display:flex;gap:12px;align-items:center;padding:14px;border-radius:16px;background:#fff;border:1px solid #f0e9e3;
    transition:transform .22s ease, box-shadow .22s ease, border-color .22s ease;
    animation:hrmsBirthdayCardIn .55s ease-out both;
}
.hrms-birthday-card:nth-child(1){animation-delay:.05s}
.hrms-birthday-card:nth-child(2){animation-delay:.12s}
.hrms-birthday-card:nth-child(3){animation-delay:.19s}
.hrms-birthday-card:nth-child(4){animation-delay:.26s}
.hrms-birthday-card:hover{transform:translateY(-2px);border-color:#f3c8a8;box-shadow:0 14px 24px rgba(254,95,4,.08)}
.hrms-birthday-avatar{
    width:48px;height:48px;border-radius:12px;background:#fe5f04;display:flex;align-items:center;justify-content:center;color:#fff;font-size:18px;font-weight:800;
    box-shadow:0 10px 18px rgba(254,95,4,.18);animation:hrmsAvatarPulse 2.8s ease-in-out infinite;
}
.hrms-birthday-info{flex:1}
.hrms-birthday-name{font-size:14px;font-weight:700;color:#121212}
.hrms-birthday-role{font-size:12px;color:#7d7d7d;margin-top:2px}

/* Holiday Cards */
.hrms-filter-form{display:flex;flex-direction:column;gap:12px}
.hrms-filter-bar{display:flex;gap:10px;flex-wrap:wrap}
.hrms-filter-select,
.hrms-filter-input{
    min-height:42px;padding:10px 12px;border-radius:12px;border:1px solid #e6ddd6;background:#fff;color:#121212;
    font-size:13px;font-family:inherit;
}
.hrms-filter-input{min-width:160px}
.hrms-filter-actions{display:flex;gap:10px;flex-wrap:wrap}
.hrms-holiday-list{display:flex;flex-direction:column;gap:10px}
.hrms-holiday-item{display:flex;gap:12px;align-items:center;padding:12px;border-radius:14px;background:#fff;border:1px solid #f0e9e3}
.hrms-holiday-date{width:60px;height:60px;border-radius:12px;background:#fe5f04;display:flex;flex-direction:column;align-items:center;justify-content:center;color:#fff;font-size:12px;font-weight:800;text-align:center}
.hrms-holiday-info{flex:1}
.hrms-holiday-name{font-size:14px;font-weight:700;color:#121212}
.hrms-holiday-desc{font-size:12px;color:#7d7d7d;margin-top:2px}

/* Charts */
.hrms-chart-container{height:300px;position:relative}
.hrms-chart-legend{display:flex;gap:20px;justify-content:center;margin-top:16px}
.hrms-chart-legend-item{display:flex;align-items:center;gap:6px;font-size:12px;color:#7d7d7d}
.hrms-chart-dot{width:8px;height:8px;border-radius:50%}

/* Announcements */
.hrms-announcement-list{display:flex;flex-direction:column;gap:12px}
.hrms-announcement-item{padding:16px;border-radius:16px;border:1px solid #f0e9e3}
.hrms-announcement-priority-high{background:linear-gradient(135deg,#fef2f2,#fee2e2);border-color:#fecaca}
.hrms-announcement-priority-medium{background:linear-gradient(135deg,#fefce8,#fde68a);border-color:#fde68a}
.hrms-announcement-title{font-size:14px;font-weight:700;color:#121212;margin-bottom:4px}
.hrms-announcement-message{font-size:13px;color:#7d7d7d;margin-bottom:8px}
.hrms-announcement-date{font-size:11px;color:#9ca3af}
/* Scrollable Card Lists & Custom Scrollbars */
.hrms-panel-scrollable {
    display: flex;
    flex-direction: column;
    height: 480px;
}
.hrms-panel-scrollable .hrms-panel-head {
    flex-shrink: 0;
}
.hrms-panel-scrollable .hrms-feature-list {
    flex: 1;
    overflow-y: auto;
    padding-right: 6px;
    align-content: flex-start;
}
.hrms-feature-list::-webkit-scrollbar {
    width: 5px;
}
.hrms-feature-list::-webkit-scrollbar-track {
    background: #fbf8f5;
    border-radius: 8px;
}
.hrms-feature-list::-webkit-scrollbar-thumb {
    background: #e2d7cf;
    border-radius: 8px;
}
.hrms-feature-list::-webkit-scrollbar-thumb:hover {
    background: #fe5f04;
}
.hrms-alert{padding:16px 18px;border-radius:18px;border:1px solid;font-size:13px;font-weight:700}
.hrms-alert-success{background:#f0fdf4;border-color:#bbf7d0;color:#166534}
.hrms-alert-error{background:#fef2f2;border-color:#fecaca;color:#991b1b}
.hrms-exit-stack{display:flex;flex-direction:column;gap:14px}
.hrms-exit-card{padding:18px;border-radius:18px;border:1px solid #efe6df;background:linear-gradient(180deg,#fff 0%,#faf7f4 100%)}
.hrms-exit-card-muted{background:linear-gradient(180deg,#fafafa 0%,#f5f5f5 100%);border-color:#e5e7eb}
.hrms-exit-label{display:inline-flex;align-items:center;padding:6px 10px;border-radius:999px;font-size:11px;font-weight:800;letter-spacing:.08em;text-transform:uppercase}
.hrms-exit-label-pending{background:#fff7ed;color:#c2410c}
.hrms-exit-label-approved{background:#ecfdf5;color:#047857}
.hrms-exit-label-rejected{background:#fef2f2;color:#b91c1c}
.hrms-exit-label-revoke{background:#eff6ff;color:#1d4ed8}
.hrms-exit-title{margin-top:12px;font-size:16px;font-weight:800;color:#121212}
.hrms-exit-copy{margin-top:8px;font-size:13px;line-height:1.7;color:#6b7280}
.hrms-exit-meta{margin-top:10px;font-size:12px;color:#8a8a8a}
.hrms-form-stack{display:flex;flex-direction:column;gap:12px;margin-top:14px}
.hrms-textarea{width:100%;min-height:110px;border:1px solid #e5ddd6;border-radius:16px;padding:14px;background:#fff;color:#121212;font-size:14px;resize:vertical}
.hrms-form-actions{display:flex;gap:10px;flex-wrap:wrap;margin-top:8px}
.hrms-exit-list{display:flex;flex-direction:column;gap:12px}
.hrms-exit-item{padding:16px;border-radius:16px;background:#faf7f4;border:1px solid #f0e9e3}
.hrms-exit-row{display:flex;justify-content:space-between;gap:12px;align-items:flex-start}
.hrms-exit-name{font-size:14px;font-weight:800;color:#121212}
.hrms-exit-sub{margin-top:4px;font-size:12px;color:#8a8a8a}
.hrms-exit-reason{margin-top:10px;font-size:13px;line-height:1.7;color:#4b5563}

@keyframes hrmsBirthdayGlow{
    0%,100%{transform:translate3d(0,0,0) scale(1);opacity:.8}
    50%{transform:translate3d(18px,10px,0) scale(1.12);opacity:1}
}
@keyframes hrmsBirthdayShimmer{
    0%,20%{transform:translateX(-130%)}
    55%,100%{transform:translateX(130%)}
}
@keyframes hrmsBirthdayFloat{
    0%,100%{transform:translateY(0) rotate(0deg)}
    50%{transform:translateY(-8px) rotate(4deg)}
}
@keyframes hrmsBirthdayRise{
    from{opacity:0;transform:translateY(10px)}
    to{opacity:1;transform:translateY(0)}
}
@keyframes hrmsBirthdayPop{
    0%{opacity:0;transform:scale(.92)}
    100%{opacity:1;transform:scale(1)}
}
@keyframes hrmsSparkleFloat{
    0%{transform:translate3d(0,0,0) scale(.9);opacity:.3}
    25%{opacity:1}
    50%{transform:translate3d(-6px,-12px,0) scale(1.1);opacity:.8}
    100%{transform:translate3d(4px,-20px,0) scale(.85);opacity:.15}
}
@keyframes hrmsBirthdayCardIn{
    from{opacity:0;transform:translateY(12px)}
    to{opacity:1;transform:translateY(0)}
}
@keyframes hrmsAvatarPulse{
    0%,100%{transform:scale(1)}
    50%{transform:scale(1.06)}
}
@keyframes hrmsCrackerPop{
    0%,100%{transform:scale(.82);opacity:.45}
    35%{transform:scale(1.12);opacity:1}
    55%{transform:scale(.96);opacity:.85}
}
@keyframes hrmsCrackerSpark{
    0%,100%{transform:translate(-50%,-50%) scale(.3);opacity:.2}
    35%{transform:translate(-50%,-50%) scale(1);opacity:1}
    60%{transform:translate(-50%,-50%) scale(.75);opacity:.5}
}
@keyframes hrmsCakeBounce{
    0%,100%{transform:translateY(0)}
    50%{transform:translateY(-6px)}
}
@keyframes hrmsFlameFlicker{
    0%,100%{transform:translateX(-50%) scale(1) rotate(-3deg)}
    50%{transform:translateX(-50%) scale(1.08,.94) rotate(4deg)}
}
@keyframes hrmsCharacterWave{
    0%,100%{transform:translateY(0)}
    50%{transform:translateY(-4px)}
}
@keyframes hrmsArmWave{
    0%,100%{transform:rotate(-32deg)}
    50%{transform:rotate(-54deg)}
}

@media (prefers-reduced-motion: reduce){
    .hrms-birthday-banner::before,
    .hrms-birthday-banner::after,
    .hrms-birthday-kicker,
    .hrms-birthday-title,
    .hrms-birthday-text,
    .hrms-birthday-pill,
    .hrms-birthday-sparkles span,
    .hrms-birthday-cracker,
    .hrms-birthday-cracker::after,
    .hrms-birthday-burst,
    .hrms-birthday-cake,
    .hrms-birthday-flame,
    .hrms-birthday-character,
    .hrms-birthday-character-body::after,
    .hrms-birthday-card,
    .hrms-birthday-avatar{
        animation:none !important;
        transform:none !important;
    }
}

@media (max-width: 1080px){
    .hrms-hero,.hrms-panels{grid-template-columns:1fr}
    .hrms-stats{grid-template-columns:repeat(3,minmax(0,1fr))}
    .hrms-dept-horizontal-card{grid-template-columns:1fr auto}
}
@media (max-width: 720px){
    .hrms-dashboard{padding:18px}
    .hrms-title{font-size:28px}
    .hrms-stats,.hrms-quick-grid{grid-template-columns:1fr}
    .hrms-hero-card,.hrms-aside,.hrms-panel,.hrms-stat-card{padding:20px}
    .hrms-dept-horizontal-card{grid-template-columns:1fr;gap:12px}
    .hrms-birthday-banner{flex-direction:column;align-items:flex-start}
    .hrms-birthday-scene{width:100%;justify-content:space-between}
    .hrms-birthday-character{width:110px}
    .hrms-birthday-cake{width:96px;height:108px}
}
</style>
@endpush

@section('content')
@php($selfServiceMode = $stats['self_service_mode'] ?? false)
@include('layouts.header')
<div class="hrms-dashboard">
    <div class="hrms-shell">
        @if(session('success'))
        <div class="hrms-alert hrms-alert-success">{{ session('success') }}</div>
        @endif

        @if(session('error'))
        <div class="hrms-alert hrms-alert-error">{{ session('error') }}</div>
        @endif

        @if($stats['is_birthday_today'] ?? false)
        <section class="hrms-birthday-banner">
            <div class="hrms-birthday-sparkles" aria-hidden="true">
                <span></span>
                <span></span>
                <span></span>
                <span></span>
            </div>
            <div class="hrms-birthday-crackers" aria-hidden="true">
                <div class="hrms-birthday-cracker"></div>
                <div class="hrms-birthday-cracker"></div>
            </div>
            <div class="hrms-birthday-copy">
                <span class="hrms-birthday-kicker">Birthday Wishes</span>
                <div class="hrms-birthday-title">Happy Birthday {{ $stats['birthday_person_name'] ?? auth()->user()->name }}!</div>
                <div class="hrms-birthday-text">{{ $stats['birthday_greeting'] }}</div>
                <div class="hrms-birthday-badges">
                    <span class="hrms-birthday-pill">Celebrate the day</span>
                    <span class="hrms-birthday-pill">Have an amazing year ahead</span>
                </div>
            </div>
            <div class="hrms-birthday-scene" aria-hidden="true">
                <div class="hrms-birthday-cake">
                    <div class="hrms-birthday-cake-top"></div>
                    <div class="hrms-birthday-cake-base"></div>
                    <div class="hrms-birthday-candle"><span class="hrms-birthday-flame"></span></div>
                    <div class="hrms-birthday-candle"><span class="hrms-birthday-flame"></span></div>
                    <div class="hrms-birthday-candle"><span class="hrms-birthday-flame"></span></div>
                </div>
                <div class="hrms-birthday-character">
                    <div class="hrms-birthday-character-head"><span></span></div>
                    <div class="hrms-birthday-character-body"></div>
                    <div class="hrms-birthday-character-text">Wishing you joy<br>and cake today!</div>
                </div>
                <div class="hrms-birthday-burst">🎉</div>
            </div>
        </section>
        @endif

        @if($stats['is_anniversary_today'] ?? false)
        <section class="hrms-birthday-banner" style="border-color:#fbcfe8;background:linear-gradient(135deg,#fff1f7 0%,#fff8fb 55%,#ffffff 100%);box-shadow:0 18px 36px rgba(236,72,153,.10);">
            <div class="hrms-birthday-sparkles" aria-hidden="true">
                <span style="background:#f9a8d4;"></span>
                <span style="background:#f472b6;"></span>
                <span style="background:#fbcfe8;"></span>
                <span style="background:#f9a8d4;"></span>
            </div>
            <div class="hrms-birthday-crackers" aria-hidden="true">
                <div class="hrms-birthday-cracker"></div>
                <div class="hrms-birthday-cracker"></div>
            </div>
            <div class="hrms-birthday-copy">
                <span class="hrms-birthday-kicker" style="color:#be185d;border-color:#fbcfe8;background:#fff;">Anniversary Wishes</span>
                <div class="hrms-birthday-title">Happy Wedding Anniversary {{ $stats['anniversary_person_name'] ?? auth()->user()->name }}!</div>
                <div class="hrms-birthday-text" style="color:#8b4563;">{{ $stats['anniversary_greeting'] }}</div>
                <div class="hrms-birthday-badges">
                    <span class="hrms-birthday-pill" style="color:#be185d;border-color:#fbcfe8;">Celebrate love today</span>
                    <span class="hrms-birthday-pill" style="color:#be185d;border-color:#fbcfe8;">Wishing many more joyful years</span>
                </div>
            </div>
            <div class="hrms-birthday-scene" aria-hidden="true">
                <div class="hrms-birthday-cake">
                    <div class="hrms-birthday-cake-top" style="background:linear-gradient(180deg,#fff5fb 0%,#fbcfe8 100%);border-color:#f9a8d4;"></div>
                    <div class="hrms-birthday-cake-base" style="background:linear-gradient(180deg,#f9a8d4 0%,#ec4899 100%);border-color:#ec4899;"></div>
                    <div class="hrms-birthday-candle"><span class="hrms-birthday-flame"></span></div>
                    <div class="hrms-birthday-candle"><span class="hrms-birthday-flame"></span></div>
                    <div class="hrms-birthday-candle"><span class="hrms-birthday-flame"></span></div>
                </div>
                <div class="hrms-birthday-character" style="background:linear-gradient(180deg,#fff 0%,#fff1f7 100%);border-color:#fbcfe8;width:150px;">
                    <div class="hrms-couple-scene">
                        <div class="hrms-couple-person">
                            <div class="hrms-couple-head"><span></span></div>
                            <div class="hrms-couple-body"></div>
                        </div>
                        <div class="hrms-couple-heart">❤</div>
                        <div class="hrms-couple-person alt">
                            <div class="hrms-couple-head"><span></span></div>
                            <div class="hrms-couple-body"></div>
                        </div>
                    </div>
                    <div class="hrms-couple-text">Wishing you both<br>love and happiness!</div>
                </div>
                <div class="hrms-birthday-burst" style="background:linear-gradient(135deg,#ec4899,#f472b6);">💍</div>
            </div>
        </section>
        @endif

        @if($stats['is_work_anniversary_today'] ?? false)
        <section class="hrms-birthday-banner" style="border-color:#bfdbfe;background:linear-gradient(135deg,#eef6ff 0%,#f8fbff 55%,#ffffff 100%);box-shadow:0 18px 36px rgba(59,130,246,.10);">
            <div class="hrms-birthday-sparkles" aria-hidden="true">
                <span style="background:#93c5fd;"></span>
                <span style="background:#60a5fa;"></span>
                <span style="background:#bfdbfe;"></span>
                <span style="background:#93c5fd;"></span>
            </div>
            <div class="hrms-birthday-crackers" aria-hidden="true">
                <div class="hrms-birthday-cracker"></div>
                <div class="hrms-birthday-cracker"></div>
            </div>
            <div class="hrms-birthday-copy">
                <span class="hrms-birthday-kicker" style="color:#1d4ed8;border-color:#bfdbfe;background:#fff;">Work Anniversary</span>
                <div class="hrms-birthday-title">Happy Work Anniversary {{ $stats['work_anniversary_person_name'] ?? auth()->user()->name }}!</div>
                <div class="hrms-birthday-text" style="color:#486581;">{{ $stats['work_anniversary_greeting'] }}</div>
                <div class="hrms-birthday-badges">
                    <span class="hrms-birthday-pill" style="color:#1d4ed8;border-color:#bfdbfe;">Celebrating your journey</span>
                    <span class="hrms-birthday-pill" style="color:#1d4ed8;border-color:#bfdbfe;">Thank you for growing with us</span>
                </div>
            </div>
            <div class="hrms-birthday-scene" aria-hidden="true">
                <div class="hrms-birthday-cake">
                    <div class="hrms-birthday-cake-top" style="background:linear-gradient(180deg,#f7fbff 0%,#dbeafe 100%);border-color:#93c5fd;"></div>
                    <div class="hrms-birthday-cake-base" style="background:linear-gradient(180deg,#93c5fd 0%,#3b82f6 100%);border-color:#3b82f6;"></div>
                    <div class="hrms-birthday-candle"><span class="hrms-birthday-flame"></span></div>
                    <div class="hrms-birthday-candle"><span class="hrms-birthday-flame"></span></div>
                    <div class="hrms-birthday-candle"><span class="hrms-birthday-flame"></span></div>
                </div>
                <div class="hrms-birthday-character" style="background:linear-gradient(180deg,#fff 0%,#eff6ff 100%);border-color:#bfdbfe;">
                    <div class="hrms-birthday-character-head"><span></span></div>
                    <div class="hrms-birthday-character-body" style="background:linear-gradient(180deg,#60a5fa 0%,#2563eb 100%);"></div>
                    <div class="hrms-birthday-character-text" style="color:#1d4ed8;">Cheers to your<br>work milestone!</div>
                </div>
                <div class="hrms-birthday-burst" style="background:linear-gradient(135deg,#3b82f6,#60a5fa);">🏆</div>
            </div>
        </section>
        @endif


        @if(! $selfServiceMode)
        @if(($stats['outside_office_pending'] ?? 0) > 0)
        <div style="background: linear-gradient(135deg, #fff7ed 0%, #ffedd5 100%); border: 1px solid #fed7aa; border-radius: 18px; padding: 14px 20px; display: flex; align-items: center; justify-content: space-between; gap: 14px; box-shadow: 0 4px 14px rgba(254, 95, 4, 0.08);">
            <div style="display: flex; align-items: center; gap: 14px;">
                <div style="width: 42px; height: 42px; border-radius: 12px; background: #fe5f04; color: #fff; display: flex; align-items: center; justify-content: center; font-size: 20px;">
                    <i class="bi bi-geo-alt-fill"></i>
                </div>
                <div>
                    <div style="font-size: 14px; font-weight: 800; color: #9a3412;">Outside Office Attendance Requests Awaiting Review</div>
                    <div style="font-size: 12px; color: #c2410c;">You have <strong>{{ $stats['outside_office_pending'] }}</strong> pending request(s) waiting for your approval.</div>
                </div>
            </div>
            <a href="{{ route('hrms.outside-office-requests.index') }}" class="hrms-btn hrms-btn-primary" style="padding: 8px 16px; font-size: 12px; white-space: nowrap;">
                Review Requests <i class="bi bi-arrow-right"></i>
            </a>
        </div>
        @endif

        <!-- Key Metrics -->
        <section class="hrms-stats">
            <a href="{{ route('attendance.index') }}" class="hrms-stat-card" style="background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%);">
                <span class="hrms-stat-icon-wrapper"><i class="bi bi-people-fill"></i></span>
                <div class="hrms-stat-label">{{ $selfServiceMode ? 'Profile' : 'People' }}</div>
                <div class="hrms-stat-value">{{ $stats['employees_total'] }}</div>
                <div class="hrms-stat-meta">{{ $selfServiceMode ? 'Your HRMS profile' : (($stats['employee_count'] ?? 0) . ' Emp / ' . ($stats['interns_total'] ?? 0) . ' Int') }}</div>
            </a>
            <a href="{{ route('attendance.index', ['status' => 'present']) }}" class="hrms-stat-card" style="background: linear-gradient(135deg, #064e3b 0%, #10b981 100%);">
                <span class="hrms-stat-icon-wrapper"><i class="bi bi-person-check-fill"></i></span>
                <div class="hrms-stat-label">Present Today</div>
                <div class="hrms-stat-value">{{ $stats['today_present'] }}</div>
                <div class="hrms-stat-meta">Marked present</div>
            </a>
            <a href="{{ route('attendance.index', ['login_timing' => 'late']) }}" class="hrms-stat-card" style="background: linear-gradient(135deg, #78350f 0%, #f59e0b 100%);">
                <span class="hrms-stat-icon-wrapper"><i class="bi bi-clock-fill"></i></span>
                <div class="hrms-stat-label">Late Today</div>
                <div class="hrms-stat-value">{{ $stats['today_late'] }}</div>
                <div class="hrms-stat-meta">Late arrivals @if(($stats['today_early'] ?? 0) > 0) / Early: {{ $stats['today_early'] }} @endif</div>
            </a>
            <a href="{{ route('attendance.index', ['status' => 'absent']) }}" class="hrms-stat-card" style="background: linear-gradient(135deg, #7f1d1d 0%, #ef4444 100%);">
                <span class="hrms-stat-icon-wrapper"><i class="bi bi-person-x-fill"></i></span>
                <div class="hrms-stat-label">Absent Today</div>
                <div class="hrms-stat-value">{{ $stats['today_absent'] }}</div>
                <div class="hrms-stat-meta">Not present</div>
            </a>
            <a href="{{ $selfServiceMode ? '#' : route('interns.index', ['internship_status' => 'active']) }}" class="hrms-stat-card" style="background: linear-gradient(135deg, #4c1d95 0%, #8b5cf6 100%);">
                <span class="hrms-stat-icon-wrapper"><i class="bi bi-backpack-fill"></i></span>
                <div class="hrms-stat-label">{{ $selfServiceMode ? 'Latest Payslip' : 'Interns' }}</div>
                <div class="hrms-stat-value">{{ $selfServiceMode ? (optional(optional($stats['latest_payroll_item'] ?? null)->payroll)->salary_month?->format('M Y') ?: 'N/A') : $stats['interns_total'] }}</div>
                <div class="hrms-stat-meta">{{ $selfServiceMode ? optional(optional($stats['latest_payroll_item'] ?? null)->payroll)->salary_month?->format('M Y') ?: 'Not available' : 'Intern workforce' }}</div>
            </a>
            <div class="hrms-stat-card" style="background: linear-gradient(135deg, #831843 0%, #db2777 100%); cursor: default;">
                <span class="hrms-stat-icon-wrapper"><i class="bi bi-person-dash-fill"></i></span>
                <div class="hrms-stat-label">{{ $selfServiceMode ? 'Latest Net Salary' : 'Resigned Employees' }}</div>
                <div class="hrms-stat-value">{{ $selfServiceMode ? number_format((float) optional($stats['latest_payroll_item'] ?? null)->net_salary, 2) : $stats['employees_pending'] }}</div>
                <div class="hrms-stat-meta">{{ $selfServiceMode ? 'Rs '.number_format((float) optional($stats['latest_payroll_item'] ?? null)->net_salary, 2) : 'No longer active' }}</div>
            </div>
        </section>
        @endif

        <!-- Main Content Panels -->
        <section class="hrms-panels">
            <!-- Department-wise Employee Count & Salary -->
            <div class="hrms-card hrms-panel hrms-panel-scrollable">
                <div class="hrms-panel-head">
                    <div>
                        <div class="hrms-panel-title">Today's Celebrations</div>
                        <div class="hrms-panel-sub">Birthdays, wedding anniversaries, and work anniversaries in one place.</div>
                    </div>
                </div>
                <div class="hrms-feature-list">
                    <div class="hrms-celebration-section">
                        <span class="hrms-celebration-chip">🎂 Birthdays</span>
                        <div class="hrms-birthday-grid">
                            @forelse($stats['today_birthdays'] as $employee)
                            <div class="hrms-birthday-card" style="border-left: 4px solid #fe5f04; background: linear-gradient(90deg, #fffcf9 0%, #ffffff 100%);">
                                <div class="hrms-birthday-avatar">
                                    {{ strtoupper(substr($employee->name, 0, 1)) }}
                                </div>
                                <div class="hrms-birthday-info">
                                    <div class="hrms-birthday-name">{{ $employee->name }}</div>
                                    <div class="hrms-birthday-role">{{ $employee->role->name ?? 'Employee' }}</div>
                                </div>
                            </div>
                            @empty
                            <div class="hrms-celebration-empty">No birthdays today</div>
                            @endforelse
                        </div>
                    </div>

                    <div class="hrms-celebration-section">
                        <span class="hrms-celebration-chip" style="background:#fff1f7;color:#be185d;border-color:#fbcfe8;">💍 Wedding Anniversaries</span>
                        <div class="hrms-birthday-grid">
                            @forelse($stats['today_anniversaries'] as $employee)
                            <div class="hrms-birthday-card" style="border-left: 4px solid #ec4899; background: linear-gradient(90deg, #fffcfb 0%, #ffffff 100%);">
                                <div class="hrms-birthday-avatar" style="background:#ec4899;box-shadow:0 10px 18px rgba(236,72,153,.18);">
                                    {{ strtoupper(substr($employee->name, 0, 1)) }}
                                </div>
                                <div class="hrms-birthday-info">
                                    <div class="hrms-birthday-name">{{ $employee->name }}</div>
                                    <div class="hrms-birthday-role">{{ $employee->role->name ?? 'Employee' }}</div>
                                </div>
                            </div>
                            @empty
                            <div class="hrms-celebration-empty">No wedding anniversaries today</div>
                            @endforelse
                        </div>
                    </div>

                    <div class="hrms-celebration-section">
                        <span class="hrms-celebration-chip" style="background:#eef6ff;color:#1d4ed8;border-color:#bfdbfe;">🏆 Work Anniversaries</span>
                        <div class="hrms-birthday-grid">
                            @forelse($stats['today_work_anniversaries'] as $employee)
                            <div class="hrms-birthday-card" style="border-left: 4px solid #3b82f6; background: linear-gradient(90deg, #fffcfc 0%, #ffffff 100%);">
                                <div class="hrms-birthday-avatar" style="background:#3b82f6;box-shadow:0 10px 18px rgba(59,130,246,.18);">
                                    {{ strtoupper(substr($employee->name, 0, 1)) }}
                                </div>
                                <div class="hrms-birthday-info">
                                    <div class="hrms-birthday-name">{{ $employee->name }}</div>
                                    <div class="hrms-birthday-role">{{ $employee->role->name ?? 'Employee' }}</div>
                                </div>
                            </div>
                            @empty
                            <div class="hrms-celebration-empty">No work anniversaries today</div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>

            <!-- Interview Assigned Section -->
            <div class="hrms-card hrms-panel hrms-panel-scrollable" style="grid-column: span 2;">
                <div class="hrms-panel-head">
                    <div>
                        <div class="hrms-panel-title">🎯 Interview Assigned</div>
                        <div class="hrms-panel-sub">Interviews scheduled and allocated for today's evaluation</div>
                    </div>
                </div>
                <div class="hrms-feature-list">
                    @forelse($stats['assigned_interviews'] ?? [] as $interview)
                    <div class="hrms-feature" style="align-items: flex-start; justify-content: space-between; flex-wrap: wrap; gap: 14px; background: #ffffff; border-radius: 16px; padding: 16px; border: 1px solid #f0e9e3;">
                        <div style="display: flex; gap: 14px; align-items: flex-start; flex: 1; min-width: 250px;">
                            <div class="hrms-feature-icon" style="background: #fff3eb; color: #fe5f04; font-size: 18px; font-weight: 800; border-color: #ffd9bf;">
                                {{ strtoupper(substr($interview->candidate?->name ?: 'C', 0, 1)) }}
                            </div>
                            <div style="flex: 1;">
                                <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                                    <strong style="font-size: 15px; color: #111827;">{{ $interview->candidate?->name ?: 'Candidate' }}</strong>
                                    @if($interview->candidate?->candidate_no)
                                        <span style="font-size: 11px; font-weight: 700; color: #6b7280; background: #f3f4f6; padding: 2px 8px; border-radius: 999px;">{{ $interview->candidate->candidate_no }}</span>
                                    @endif
                                </div>
                                <span style="font-size: 13px; color: #4b5563; margin-top: 2px; display: block;">
                                    <i class="bi bi-briefcase" style="margin-right: 4px; color: #fe5f04;"></i> {{ $interview->candidate?->job_title ?: 'Position not specified' }}
                                </span>
                                <div style="display: flex; align-items: center; gap: 14px; margin-top: 8px; flex-wrap: wrap; font-size: 12px; color: #6b7280;">
                                    <span><i class="bi bi-calendar-event" style="color: #fe5f04;"></i> <strong>Date:</strong> {{ $interview->scheduled_at ? $interview->scheduled_at->format('d M Y, h:i A') : 'N/A' }}</span>
                                    <span><i class="bi bi-person-check" style="color: #fe5f04;"></i> <strong>Interviewer:</strong> {{ $interview->interviewer_name ?: 'Not assigned' }}</span>
                                </div>
                                @if($interview->notes)
                                    <div style="margin-top: 8px; font-size: 12px; color: #6b7280; font-style: italic; background: #faf5f0; padding: 6px 10px; border-radius: 8px; border-left: 3px solid #fe5f04;">
                                        Note: {{ $interview->notes }}
                                    </div>
                                @endif
                            </div>
                        </div>
                        <div style="display: flex; flex-direction: column; align-items: flex-end; gap: 8px; min-width: 130px;">
                            @if($interview->status === 'scheduled')
                                <span style="display: inline-flex; align-items: center; padding: 4px 10px; border-radius: 999px; font-size: 11px; font-weight: 800; background: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe;">
                                    ● Scheduled
                                </span>
                            @elseif($interview->status === 'completed')
                                <span style="display: inline-flex; align-items: center; padding: 4px 10px; border-radius: 999px; font-size: 11px; font-weight: 800; background: #ecfdf5; color: #047857; border: 1px solid #a7f3d0;">
                                    ✓ Completed
                                </span>
                            @else
                                <span style="display: inline-flex; align-items: center; padding: 4px 10px; border-radius: 999px; font-size: 11px; font-weight: 800; background: #fef2f2; color: #b91c1c; border: 1px solid #fecaca;">
                                    ✕ {{ $interview->status_label }}
                                </span>
                            @endif

                            @if($interview->recruitment_candidate_id)
                                <a href="{{ route('recruitment.show', $interview->recruitment_candidate_id) }}" class="hrms-btn hrms-btn-primary" style="padding: 6px 12px; font-size: 12px; text-decoration: none;">
                                    View Details <i class="bi bi-arrow-right"></i>
                                </a>
                            @endif
                        </div>
                    </div>
                    @empty
                    <div class="hrms-feature" style="align-items: center; justify-content: center; text-align: center; padding: 24px; background: #faf7f4; border-radius: 16px;">
                        <div style="display: flex; flex-direction: column; align-items: center; gap: 8px;">
                            <div class="hrms-feature-icon" style="background: #fff3eb; color: #fe5f04;">📋</div>
                            <strong style="color: #374151;">No Interviews Assigned For Today</strong>
                            <span style="color: #9ca3af; font-size: 12px;">When candidates are scheduled for an interview with you today, they will appear here.</span>
                        </div>
                    </div>
                    @endforelse
                </div>
            </div>


            <!-- Announcements -->
            <div class="hrms-card hrms-panel" style="grid-column: span 2;">
                <div class="hrms-panel-head">
                    <div>
                        <div class="hrms-panel-title">📢 Announcements</div>
                        <div class="hrms-panel-sub">Important updates and notices</div>
                    </div>
                    <div style="display:flex;gap:12px;align-items:center;">
                        @if($stats['can_manage_announcements'] ?? false)
                        <a href="{{ route('hrms-announcements.create') }}" class="hrms-link">+ Create</a>
                        @endif
                        <a href="{{ route('hrms-announcements.index') }}" class="hrms-link">View All</a>
                    </div>
                </div>
                <div class="hrms-announcement-list">
                    @forelse($stats['announcements'] as $announcement)
                    <div class="hrms-announcement-item hrms-announcement-priority-{{ $announcement['priority'] }}">
                        <div style="display:flex;justify-content:space-between;align-items:center;gap:10px;margin-bottom:4px;">
                            <div class="hrms-announcement-title" style="margin-bottom:0;">{{ $announcement['title'] }}</div>
                            @if(!empty($announcement['branches']))
                                <span style="font-size:11px;font-weight:700;padding:2px 8px;border-radius:6px;background:rgba(255,255,255,0.7);color:#374151;">🏢 {{ $announcement['branches'] }}</span>
                            @endif
                        </div>
                        <div class="hrms-announcement-message">{{ $announcement['message'] }}</div>
                        <div class="hrms-announcement-date">{{ \Carbon\Carbon::parse($announcement['date'])->format('M j, Y') }}</div>
                    </div>
                    @empty
                    <div class="hrms-announcement-item" style="background:#faf7f4;">
                        <div class="hrms-announcement-message" style="margin-bottom:0;">No announcements available right now.</div>
                    </div>
                    @endforelse
                </div>
            </div>

            <div class="hrms-card hrms-panel">
                <div class="hrms-panel-head">
                    <div>
                        <div class="hrms-panel-title">Today's Leaves</div>
                        <div class="hrms-panel-sub">{{ collect($stats['today_leave_approvals'] ?? [])->count() }} employee(s) marked on leave today</div>
                    </div>
                </div>
                <div class="hrms-feature-list">
                    @forelse($stats['today_leave_approvals'] as $leaveEntry)
                    <div class="hrms-feature" style="align-items:flex-start;">
                        <div class="hrms-feature-icon" style="background:#ecfdf3;color:#047857;">
                            {{ strtoupper(substr($leaveEntry['employee_name'] ?: 'L', 0, 1)) }}
                        </div>
                        <div style="flex:1;">
                            <strong>{{ $leaveEntry['employee_name'] ?: 'Employee' }}</strong>
                            <span>{{ $leaveEntry['department_name'] ?: 'No department mapped' }}</span>
                            <span>{{ $leaveEntry['role_name'] ?: 'No role mapped' }}</span>
                            <span>{{ $leaveEntry['leave_label'] ?: 'Leave' }}</span>
                        </div>
                    </div>
                    @empty
                    <div class="hrms-feature" style="align-items:flex-start;">
                        <div class="hrms-feature-icon" style="background:#fff7ed;color:#ea580c;">:)</div>
                        <div>
                            <strong>No one is on leave today</strong>
                            <span>Looks like the whole team is in action today.</span>
                        </div>
                    </div>
                    @endforelse
                </div>
            </div>

            <div class="hrms-card hrms-panel">
                <div class="hrms-panel-head">
                    <div>
                        <div class="hrms-panel-title">Today's Permission Approvals</div>
                        <div class="hrms-panel-sub">{{ collect($stats['today_permission_approvals'] ?? [])->count() }} employee(s) with approved permission today</div>
                    </div>
                </div>
                <div class="hrms-feature-list">
                    @forelse($stats['today_permission_approvals'] as $permissionRequest)
                    <div class="hrms-feature" style="align-items:flex-start;">
                        <div class="hrms-feature-icon" style="background:#eff6ff;color:#1d4ed8;">
                            {{ strtoupper(substr($permissionRequest->employee?->name ?: 'P', 0, 1)) }}
                        </div>
                        <div style="flex:1;">
                            <strong>{{ $permissionRequest->employee?->name ?: 'Employee' }}</strong>
                            <span>{{ $permissionRequest->employee?->department?->name ?: 'No department mapped' }}</span>
                            <span>{{ $permissionRequest->employee?->role?->name ?: 'No role mapped' }}</span>
                        </div>
                    </div>
                    @empty
                    <div class="hrms-feature" style="align-items:flex-start;">
                        <div class="hrms-feature-icon" style="background:#eef2ff;color:#4338ca;">^_^</div>
                        <div>
                            <strong>No one has permission today</strong>
                            <span>All clear for the day, no permission outings lined up.</span>
                        </div>
                    </div>
                    @endforelse
                </div>
            </div>

            @if(! $selfServiceMode)
            <!-- Today's Attendance Chart -->
            <div class="hrms-card hrms-panel">
                <div class="hrms-panel-head">
                    <div>
                        <div class="hrms-panel-title">📊 Today's Attendance</div>
                        <div class="hrms-panel-sub">Visual breakdown of attendance status</div>
                    </div>
                </div>
                <div class="hrms-chart-container">
                    <canvas id="attendanceChart"></canvas>
                </div>
                <div class="hrms-chart-legend">
                    <div class="hrms-chart-legend-item">
                        <div class="hrms-chart-dot" style="background:#10b981"></div>
                        <span>Present ({{ $stats['today_present'] }})</span>
                    </div>
                    <div class="hrms-chart-legend-item">
                        <div class="hrms-chart-dot" style="background:#f59e0b"></div>
                        <span>Late ({{ $stats['today_late'] }})</span>
                    </div>
                    <div class="hrms-chart-legend-item">
                        <div class="hrms-chart-dot" style="background:#ef4444"></div>
                        <span>Absent ({{ $stats['today_absent'] }})</span>
                    </div>
                </div>
            </div>
            @endif

            <!-- Monthly Leave Chart -->
            <div class="hrms-card hrms-panel">
                <div class="hrms-panel-head">
                    <div>
                        <div class="hrms-panel-title">📈 Monthly Leave Trends</div>
                        <div class="hrms-panel-sub">{{ $selfServiceMode ? 'Your leave pattern over the past 6 months' : 'Leave requests over the past 6 months' }}</div>
                    </div>
                </div>
                <div class="hrms-chart-container">
                    <canvas id="leaveChart"></canvas>
                </div>
            </div>

            @if(! $selfServiceMode)
            <!-- Payroll Information -->
            <div class="hrms-card hrms-panel">
                <div class="hrms-panel-head">
                    <div>
                        <div class="hrms-panel-title">💰 Payroll Overview</div>
                        <div class="hrms-panel-sub">{{ $selfServiceMode ? 'Your latest salary summary' : 'Monthly salary processing schedule' }}</div>
                    </div>
                </div>
                <div class="hrms-feature-list">
                    @if($selfServiceMode)
                    <div class="hrms-feature">
                        <div class="hrms-feature-icon">Rs</div>
                        <div>
                            <strong>{{ $stats['employee_name'] ?: 'Employee' }}</strong>
                            <span>Latest net salary: Rs {{ number_format((float) optional($stats['latest_payroll_item'] ?? null)->net_salary, 2) }}</span>
                        </div>
                    </div>
                    <div class="hrms-feature">
                        <div class="hrms-feature-icon">PDF</div>
                        <div>
                            <strong>Latest Payslip Month</strong>
                            <span>{{ optional(optional($stats['latest_payroll_item'] ?? null)->payroll)->salary_month?->format('F Y') ?: 'Payslip not generated yet' }}</span>
                        </div>
                    </div>
                    @else
                    <div class="hrms-feature">
                        <div class="hrms-feature-icon">1️⃣</div>
                        <div>
                            <strong>1st of Month</strong>
                            <span>{{ $stats['salary_day_1_employees'] }} employees receive salary</span>
                        </div>
                    </div>
                    <div class="hrms-feature">
                        <div class="hrms-feature-icon">🔟</div>
                        <div>
                            <strong>10th of Month</strong>
                            <span>{{ $stats['salary_day_10_employees'] }} employees receive salary</span>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
            @endif

            @if(! $selfServiceMode && (($stats['can_raise_exit'] ?? false) || ($stats['exit_request'] ?? null)))
            <div class="hrms-card hrms-panel">
                <div class="hrms-panel-head">
                    <div>
                        <div class="hrms-panel-title">Exit Request</div>
                        <div class="hrms-panel-sub">Raise an exit request, track approval, and request revoke when needed.</div>
                    </div>
                </div>
                <div class="hrms-exit-stack">
                    @php($exitRequest = $stats['exit_request'] ?? null)

                    @if($exitRequest)
                        @if($exitRequest->revoke_status === \App\Models\EmployeeExitRequest::REVOKE_STATUS_PENDING)
                        <div class="hrms-exit-card hrms-exit-card-muted">
                            <span class="hrms-exit-label hrms-exit-label-revoke">Revoke Pending</span>
                            <div class="hrms-exit-title">Your revoke request is waiting for approval.</div>
                            <div class="hrms-exit-copy">{{ $exitRequest->revoke_reason }}</div>
                            <div class="hrms-exit-meta">Requested on {{ optional($exitRequest->revoke_requested_at)->format('d M Y h:i A') ?: 'N/A' }}</div>
                        </div>
                        @elseif($exitRequest->exit_status === \App\Models\EmployeeExitRequest::EXIT_STATUS_PENDING)
                        <div class="hrms-exit-card hrms-exit-card-muted">
                            <span class="hrms-exit-label hrms-exit-label-pending">Exit Pending</span>
                            <div class="hrms-exit-title">Your exit request has been submitted.</div>
                            <div class="hrms-exit-copy">{{ $exitRequest->exit_reason }}</div>
                            <div class="hrms-exit-meta">Requested on {{ optional($exitRequest->exit_requested_at)->format('d M Y h:i A') ?: 'N/A' }}</div>
                        </div>
                        @elseif($exitRequest->exit_status === \App\Models\EmployeeExitRequest::EXIT_STATUS_APPROVED)
                        <div class="hrms-exit-card">
                            <span class="hrms-exit-label hrms-exit-label-approved">Exit Approved</span>
                            <div class="hrms-exit-title">Your exit request has been approved.</div>
                            <div class="hrms-exit-copy">{{ $exitRequest->exit_reason }}</div>
                            <div class="hrms-exit-meta">Approved on {{ optional($exitRequest->exit_actioned_at)->format('d M Y h:i A') ?: 'N/A' }}</div>
                        </div>

                        @if($exitRequest->revoke_status === \App\Models\EmployeeExitRequest::REVOKE_STATUS_REJECTED)
                        <div class="hrms-exit-card hrms-exit-card-muted">
                            <span class="hrms-exit-label hrms-exit-label-rejected">Revoke Rejected</span>
                            <div class="hrms-exit-copy">Your previous revoke request was rejected. You can raise a new revoke request below.</div>
                        </div>
                        @endif

                        @if($exitRequest->revoke_status !== \App\Models\EmployeeExitRequest::REVOKE_STATUS_PENDING)
                        <form method="POST" action="{{ route('employee-exit-requests.revoke', $exitRequest) }}" class="hrms-form-stack" onsubmit="return confirm('Are you sure you want to submit a revoke request?');">
                            @csrf
                            <textarea name="revoke_reason" class="hrms-textarea" placeholder="Enter revoke reason..." required>{{ old('revoke_reason') }}</textarea>
                            <div class="hrms-form-actions">
                                <button type="submit" class="hrms-btn hrms-btn-ghost">Revoke</button>
                            </div>
                        </form>
                        @endif
                        @elseif($exitRequest->exit_status === \App\Models\EmployeeExitRequest::EXIT_STATUS_REJECTED)
                        <div class="hrms-exit-card hrms-exit-card-muted">
                            <span class="hrms-exit-label hrms-exit-label-rejected">Exit Rejected</span>
                            <div class="hrms-exit-title">Your previous exit request was not approved.</div>
                            <div class="hrms-exit-copy">{{ $exitRequest->exit_reason }}</div>
                        </div>
                        @endif
                    @endif

                    @if($stats['can_raise_exit'] ?? false)
                    <form method="POST" action="{{ route('employee-exit-requests.store') }}" class="hrms-form-stack" onsubmit="return confirm('Are you sure you want to raise an exit request?');">
                        @csrf
                        <textarea name="exit_reason" class="hrms-textarea" placeholder="Enter relieve reason..." required>{{ old('exit_reason') }}</textarea>
                        <div class="hrms-form-actions">
                            <button type="submit" class="hrms-btn hrms-btn-primary">Raise Exit</button>
                        </div>
                    </form>
                    @endif
                </div>
            </div>
            @endif

            <div class="hrms-card hrms-panel">
                <div class="hrms-panel-head">
                    <div>
                        <div class="hrms-panel-title">Exit Approval Queue</div>
                        <div class="hrms-panel-sub">{{ ($stats['can_manage_exit_requests'] ?? false) ? 'Approve employee exit and revoke requests directly from the dashboard.' : 'Track whether any exit approval is waiting today.' }}</div>
                    </div>
                </div>
                @if(collect($stats['exit_approval_queue'] ?? [])->isNotEmpty())
                <div class="hrms-exit-list">
                    @foreach($stats['exit_approval_queue'] as $requestItem)
                    <div class="hrms-exit-item">
                        <div class="hrms-exit-row">
                            <div>
                                <div class="hrms-exit-name">{{ $requestItem->employee?->name ?: $requestItem->user?->name ?: 'Employee' }}</div>
                                <div class="hrms-exit-sub">{{ $requestItem->employee?->employee_id ?: 'N/A' }}</div>
                            </div>
                            @if($requestItem->revoke_status === \App\Models\EmployeeExitRequest::REVOKE_STATUS_PENDING)
                            <span class="hrms-exit-label hrms-exit-label-revoke">Revoke Pending</span>
                            @else
                            <span class="hrms-exit-label hrms-exit-label-pending">Exit Pending</span>
                            @endif
                        </div>
                        <div class="hrms-exit-reason">
                            {{ $requestItem->revoke_status === \App\Models\EmployeeExitRequest::REVOKE_STATUS_PENDING ? ($requestItem->revoke_reason ?: 'No revoke reason provided.') : ($requestItem->exit_reason ?: 'No exit reason provided.') }}
                        </div>
                        @if($stats['can_manage_exit_requests'] ?? false)
                        <div class="hrms-form-actions">
                            @if($requestItem->revoke_status === \App\Models\EmployeeExitRequest::REVOKE_STATUS_PENDING)
                            <form method="POST" action="{{ route('employee-exit-requests.approve-revoke', $requestItem) }}" onsubmit="return confirm('Approve this revoke request?');">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="hrms-btn hrms-btn-primary">Approve Revoke</button>
                            </form>
                            <form method="POST" action="{{ route('employee-exit-requests.reject-revoke', $requestItem) }}" onsubmit="return confirm('Reject this revoke request?');">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="hrms-btn hrms-btn-ghost">Reject Revoke</button>
                            </form>
                            @else
                            <form method="POST" action="{{ route('employee-exit-requests.approve', $requestItem) }}" onsubmit="return confirm('Approve this exit request?');">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="hrms-btn hrms-btn-primary">Approve Exit</button>
                            </form>
                            <form method="POST" action="{{ route('employee-exit-requests.reject', $requestItem) }}" onsubmit="return confirm('Reject this exit request?');">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="hrms-btn hrms-btn-ghost">Reject Exit</button>
                            </form>
                            @endif
                        </div>
                        @endif
                    </div>
                    @endforeach
                </div>
                @else
                <div style="text-align:center;padding:20px;color:#9ca3af;">
                    No exit approvals waiting today
                </div>
                @endif
            </div>

            <!-- Upcoming Holidays -->
            <div class="hrms-card hrms-panel">
                <div class="hrms-panel-head">
                    <div>
                        <div class="hrms-panel-title">📅 Upcoming Holidays</div>
                        <div class="hrms-panel-sub">{{ $stats['holiday_filter']['label'] ?? 'Next 7 days schedule' }}</div>
                    </div>
                </div>
                <form method="GET" action="{{ route('hrms.dashboard') }}" class="hrms-filter-form" style="margin-bottom:16px;">
                    <div class="hrms-filter-bar">
                        <select name="holiday_filter" class="hrms-filter-select" onchange="this.form.submit()">
                            <option value="week" @selected(($stats['holiday_filter']['type'] ?? 'week') === 'week')>This Week</option>
                            <option value="month" @selected(($stats['holiday_filter']['type'] ?? '') === 'month')>This Month</option>
                            <option value="custom" @selected(($stats['holiday_filter']['type'] ?? '') === 'custom')>Custom Dates</option>
                        </select>
                        @if(($stats['holiday_filter']['type'] ?? 'week') === 'custom')
                        <input type="date" name="holiday_start_date" class="hrms-filter-input" value="{{ $stats['holiday_filter']['start_input'] ?? '' }}">
                        <input type="date" name="holiday_end_date" class="hrms-filter-input" value="{{ $stats['holiday_filter']['end_input'] ?? '' }}">
                        <div class="hrms-filter-actions">
                            <button type="submit" class="hrms-btn hrms-btn-primary">Apply</button>
                            <a href="{{ route('hrms.dashboard', ['holiday_filter' => 'week']) }}" class="hrms-btn hrms-btn-ghost">Reset</a>
                        </div>
                        @endif
                    </div>
                </form>
                <div class="hrms-holiday-list">
                    @forelse($stats['upcoming_holidays'] as $holiday)
                    <div class="hrms-holiday-item">
                        <div class="hrms-holiday-date">
                            <div>{{ $holiday->holiday_date->format('M') }}</div>
                            <div>{{ $holiday->holiday_date->format('d') }}</div>
                        </div>
                        <div class="hrms-holiday-info">
                            <div class="hrms-holiday-name">{{ $holiday->reason }}</div>
                            <div class="hrms-holiday-desc">{{ $holiday->holiday_date->format('l, F j, Y') }}</div>
                        </div>
                    </div>
                    @empty
                    <div style="text-align:center;padding:20px;color:#9ca3af;">
                        No holidays found for the selected range
                    </div>
                    @endforelse
                </div>
            </div>

            @if(! $selfServiceMode)
            <!-- Quick Actions -->
            <div class="hrms-card hrms-panel">
                <div class="hrms-panel-head">
                    <div>
                        <div class="hrms-panel-title">⚡ Quick Actions</div>
                        <div class="hrms-panel-sub">{{ $selfServiceMode ? 'Your self-service shortcuts' : 'Frequently used HR operations' }}</div>
                    </div>
                </div>
                <div class="hrms-quick-grid">
                    @if($selfServiceMode)
                    <a href="{{ route('attendance.index') }}" class="hrms-quick-card">
                        <div class="hrms-quick-kicker">Attendance</div>
                        <div class="hrms-quick-title">My Attendance</div>
                        <div class="hrms-quick-copy">Review your daily check-in history</div>
                    </a>
                    <a href="{{ route('payroll.index') }}" class="hrms-quick-card">
                        <div class="hrms-quick-kicker">Salary</div>
                        <div class="hrms-quick-title">My Payslips</div>
                        <div class="hrms-quick-copy">Open salary details and download PDFs</div>
                    </a>
                    @else
                    <a href="{{ route('employee-onboarding.index') }}" class="hrms-quick-card">
                        <div class="hrms-quick-kicker">Employee</div>
                        <div class="hrms-quick-title">Employee Management</div>
                        <div class="hrms-quick-copy">View and manage all employee records</div>
                    </a>
                    <a href="{{ route('attendance.index') }}" class="hrms-quick-card">
                        <div class="hrms-quick-kicker">Attendance</div>
                        <div class="hrms-quick-title">Daily Attendance</div>
                        <div class="hrms-quick-copy">Monitor employee attendance records</div>
                    </a>
                    <a href="{{ route('house-keeping.index') }}" class="hrms-quick-card">
                        <div class="hrms-quick-kicker">Cleaning</div>
                        <div class="hrms-quick-title">House Keeping</div>
                        <div class="hrms-quick-copy">Manage cleaning categories, works, and monthly sheet view</div>
                    </a>
                    <a href="{{ route('interns.index') }}" class="hrms-quick-card">
                        <div class="hrms-quick-kicker">Intern</div>
                        <div class="hrms-quick-title">Intern Management</div>
                        <div class="hrms-quick-copy">Track intern joining forms and progress</div>
                    </a>
                    <a href="{{ route('settings.departments.index') }}" class="hrms-quick-card">
                        <div class="hrms-quick-kicker">Department</div>
                        <div class="hrms-quick-title">Department Setup</div>
                        <div class="hrms-quick-copy">Manage organizational departments</div>
                    </a>
                    @endif
                </div>
            </div>
            @endif
        </section>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Today's Attendance Chart
const attendanceCanvas = document.getElementById('attendanceChart');
if (attendanceCanvas) {
    const attendanceCtx = attendanceCanvas.getContext('2d');
    new Chart(attendanceCtx, {
        type: 'doughnut',
        data: {
            labels: ['Present', 'Late', 'Absent'],
            datasets: [{
                data: [{{ $stats['today_present'] }}, {{ $stats['today_late'] }}, {{ $stats['today_absent'] }}],
                backgroundColor: ['#10b981', '#f59e0b', '#ef4444'],
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                }
            }
        }
    });
}

// Monthly Leave Chart
const leaveCtx = document.getElementById('leaveChart').getContext('2d');
const leaveData = @json($stats['monthly_leave_data']);
new Chart(leaveCtx, {
    type: 'line',
    data: {
        labels: leaveData.map(item => item.month),
        datasets: [{
            label: 'Leave Requests',
            data: leaveData.map(item => item.leaves),
            borderColor: '#fe5f04',
            backgroundColor: 'rgba(254, 95, 4, 0.1)',
            tension: 0.4,
            fill: true
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: false
            }
        },
        scales: {
            y: {
                beginAtZero: true
            }
        }
    }
});
</script>
@endpush
@endsection
