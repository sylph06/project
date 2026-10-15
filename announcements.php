<?php require_once __DIR__ . '/config.php'; require_viewer(); ?>
<!DOCTYPE html>
<html lang="en"<?= is_limited_viewer() ? ' class="is-public"' : '' ?>>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KK Youth Profiling System | Announcements</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="sidebar.css" rel="stylesheet">
    <script>window.ANN_MODE = "<?= is_limited_viewer() ? 'public' : 'official' ?>";</script>
    <style>
/* =========================================================
   ANNOUNCEMENTS PAGE STYLES (announcements.php = official view + public view in ONE file)
   The first part is the original style of the page, unchanged.
   ========================================================= */
:root{
    --kk-primary:var(--bs-primary);
    --kk-primary-dark:var(--bs-primary-text-emphasis);
    --kk-primary-soft:rgba(var(--bs-primary-rgb),.12);
    --black:var(--bs-body-color);
    --dark:var(--bs-primary-text-emphasis);
    --cream:var(--bs-tertiary-bg);
    --line:var(--bs-border-color);
    --muted:var(--bs-secondary-color);
    --white:#ffffff;
}

*{
    box-sizing:border-box;
}

body{
    margin:0;
    background:var(--bs-tertiary-bg);
    color:var(--black);
    font-family:Inter,system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif;
}

a{
    text-decoration:none;
}

.top-strip{
    height:7px;
    background:var(--bs-primary);
}

header{
    background:#fff!important;
    border-bottom:1px solid var(--line)!important;
}

header h1{
    font-weight:900;
}

.btn-success{
    --bs-btn-bg:var(--bs-body-color);
    --bs-btn-border-color:var(--bs-body-color);
    --bs-btn-hover-bg:var(--kk-primary);
    --bs-btn-hover-border-color:var(--kk-primary);
    --bs-btn-hover-color:#fff;
    --bs-btn-active-bg:var(--bs-body-color);
    --bs-btn-active-border-color:var(--bs-body-color);
    --bs-btn-focus-shadow-rgb:255,230,0;
    font-weight:800;
}

.btn-outline-success{
    --bs-btn-color:var(--bs-body-color);
    --bs-btn-border-color:var(--bs-body-color);
    --bs-btn-hover-bg:var(--kk-primary);
    --bs-btn-hover-border-color:var(--kk-primary);
    --bs-btn-hover-color:#fff;
    --bs-btn-active-bg:var(--kk-primary);
    --bs-btn-active-border-color:var(--kk-primary);
    font-weight:700;
}

.avatar{
    width:40px;
    height:40px;
    flex:none;
    display:inline-flex;
    align-items:center;
    justify-content:center;
    border-radius:50%;
    background:var(--bs-primary);
    color:var(--bs-body-color);
    font-weight:900;
    border:2px solid var(--bs-body-color);
}

footer{
    display:none;
}

/* ---------- HERO (same style as Dashboard) ---------- */
.dashboard-hero{
    position:relative;
    overflow:hidden;
    padding:32px;
    border-radius:28px;
    background:var(--bs-primary);
    color:var(--bs-body-color);
    box-shadow:0 16px 35px rgba(31,31,31,.10);
}

.dashboard-hero:before{
    content:"";
    position:absolute;
    width:280px;
    height:280px;
    border-radius:50%;
    right:-80px;
    top:-120px;
    background:var(--bs-primary-text-emphasis);
    opacity:.08;
}

.dashboard-hero:after{
    content:"";
    position:absolute;
    width:170px;
    height:170px;
    border-radius:38px;
    right:145px;
    bottom:-105px;
    background:#fff;
    opacity:.28;
    transform:rotate(28deg);
}

.hero-copy{
    position:relative;
    z-index:2;
    max-width:610px;
}

.hero-copy h2{
    font-size:clamp(1.8rem,3vw,3rem);
    font-weight:950;
    letter-spacing:-1px;
}

.hero-copy p{
    max-width:560px;
    color:var(--bs-primary-text-emphasis);
}

.hero-tag{
    display:inline-flex;
    align-items:center;
    gap:8px;
    padding:8px 13px;
    border-radius:999px;
    background:var(--bs-primary-text-emphasis);
    color:#fff;
    font-size:.78rem;
    font-weight:800;
}

/* ---------- TOOLBAR (search + filters) ---------- */
.toolbar-box{
    background:#fff;
    border:1px solid var(--line);
    border-radius:22px;
    box-shadow:0 8px 20px rgba(20,20,20,.045);
    padding:16px;
}

.toolbar-box .form-control,
.toolbar-box .input-group-text{
    border-color:var(--bs-border-color);
    background:#fff;
}

.toolbar-box .form-control:focus{
    border-color:var(--bs-body-color);
    box-shadow:0 0 0 3px rgba(var(--bs-primary-rgb),.28);
}

.filter-pill{
    border:1px solid var(--bs-body-color);
    background:#fff;
    color:var(--bs-body-color);
    font-weight:700;
    font-size:.85rem;
    padding:.4rem .95rem;
    border-radius:999px;
    transition:.2s ease;
}

.filter-pill:hover{
    background:var(--kk-primary-soft);
}

.filter-pill.active{
    background:var(--bs-primary-text-emphasis);
    color:var(--kk-primary);
}

.filter-pill .count{
    display:inline-block;
    min-width:20px;
    margin-left:4px;
    padding:0 6px;
    border-radius:999px;
    background:var(--bs-primary);
    color:var(--bs-body-color);
    font-size:.72rem;
    font-weight:800;
}

/* ---------- ANNOUNCEMENT CARD ---------- */
.ann-card{
    height:100%;
    display:flex;
    flex-direction:column;
    background:#fff;
    border:1px solid var(--line);
    border-radius:22px;
    overflow:hidden;
    box-shadow:0 8px 20px rgba(20,20,20,.05);
    transition:.2s ease;
}

.ann-card:hover{
    transform:translateY(-5px);
    box-shadow:0 15px 28px rgba(20,20,20,.10);
}

.ann-banner{
    position:relative;
    aspect-ratio:16 / 9;
    background:var(--bs-primary);
    overflow:hidden;
}

.ann-banner img{
    width:100%;
    height:100%;
    object-fit:cover;
    display:block;
}

/* shown when no banner image was uploaded */
.ann-banner-empty{
    width:100%;
    height:100%;
    display:flex;
    align-items:center;
    justify-content:center;
    background:var(--bs-primary);
    color:var(--bs-body-color);
    font-size:3.2rem;
    position:relative;
}

.ann-banner-empty:before{
    content:"";
    position:absolute;
    width:200px;
    height:200px;
    border-radius:50%;
    right:-60px;
    top:-90px;
    background:var(--bs-primary-text-emphasis);
    opacity:.08;
}

.ann-status{
    position:absolute;
    left:12px;
    top:12px;
    display:inline-flex;
    align-items:center;
    gap:6px;
    padding:6px 12px;
    border-radius:999px;
    font-size:.74rem;
    font-weight:800;
    border:2px solid var(--bs-body-color);
}

.ann-status.upcoming{
    background:var(--bs-primary-text-emphasis);
    color:var(--kk-primary);
}

.ann-status.ongoing{
    background:var(--bs-primary);
    color:var(--bs-body-color);
}

.ann-status.ongoing:before{
    content:"";
    width:8px;
    height:8px;
    border-radius:50%;
    background:var(--bs-primary-text-emphasis);
}

.ann-status.completed{
    background:var(--bs-border-color);
    color:var(--bs-secondary-color);
    border-color:var(--bs-border-color);
}

.ann-date-chip{
    position:absolute;
    right:12px;
    top:12px;
    width:56px;
    text-align:center;
    padding:.4rem 0;
    border-radius:15px;
    background:var(--bs-primary);
    color:var(--bs-body-color);
    border:2px solid var(--bs-body-color);
    line-height:1.1;
}

.ann-date-chip .m{
    display:block;
    font-size:.7rem;
    font-weight:800;
}

.ann-date-chip .d{
    display:block;
    font-size:1.25rem;
    font-weight:900;
}

.ann-body{
    flex:1;
    padding:18px 18px 8px;
}

.ann-title{
    font-size:1.08rem;
    font-weight:900;
    line-height:1.3;
    margin:0 0 6px;
}

.ann-desc{
    color:var(--bs-secondary-color);
    font-size:.9rem;
    margin:0 0 14px;
    display:-webkit-box;
    -webkit-box-orient:vertical;
    overflow:hidden;
}

.ann-meta{
    display:flex;
    flex-direction:column;
    gap:8px;
}

.ann-meta-row{
    display:flex;
    align-items:flex-start;
    gap:10px;
    font-size:.84rem;
}

.ann-meta-icon{
    width:28px;
    height:28px;
    flex:none;
    display:inline-flex;
    align-items:center;
    justify-content:center;
    border-radius:9px;
    background:var(--bs-primary);
    color:var(--bs-body-color);
    font-size:.85rem;
}

.ann-meta-row span{
    padding-top:3px;
    word-break:break-word;
}

.ann-footer{
    display:flex;
    align-items:center;
    gap:8px;
    padding:14px 18px 18px;
}

.ann-footer .btn-view{
    flex:1;
}

.icon-btn{
    width:38px;
    height:38px;
    padding:0;
    display:inline-flex;
    align-items:center;
    justify-content:center;
    border-radius:12px;
}

.btn-outline-danger.icon-btn{
    --bs-btn-color:var(--bs-danger);
    --bs-btn-border-color:var(--bs-danger);
    --bs-btn-hover-bg:var(--bs-danger);
    --bs-btn-hover-border-color:var(--bs-danger);
}

/* ---------- EMPTY STATE ---------- */
.empty-state{
    text-align:center;
    background:#fff;
    border:2px dashed var(--line);
    border-radius:22px;
    padding:48px 20px;
    color:var(--muted);
}

.empty-state i{
    font-size:3rem;
    color:var(--bs-border-color);
}

/* ---------- MODALS & FORM ---------- */
.modal-content{
    border:0;
    border-radius:22px;
    overflow:hidden;
}

.modal-header{
    background:var(--bs-primary);
    border-bottom:0;
}

.modal-title{
    font-weight:900;
}

.form-label{
    font-weight:700;
    font-size:.85rem;
}

.form-control,
.form-select{
    border:1px solid var(--bs-border-color);
    border-radius:12px;
}

.form-control:focus,
.form-select:focus{
    border-color:var(--bs-body-color);
    box-shadow:0 0 0 3px rgba(var(--bs-primary-rgb),.28);
}

.banner-preview{
    position:relative;
    aspect-ratio:16 / 9;
    border:2px dashed var(--bs-border-color);
    border-radius:16px;
    background:var(--bs-tertiary-bg);
    overflow:hidden;
    display:flex;
    align-items:center;
    justify-content:center;
    color:var(--muted);
    text-align:center;
    font-size:.85rem;
}

.banner-preview img{
    width:100%;
    height:100%;
    object-fit:cover;
    display:block;
}

.detail-banner{
    aspect-ratio:16 / 9;
    background:var(--bs-primary);
    overflow:hidden;
    position:relative;
}

.detail-banner img{
    width:100%;
    height:100%;
    object-fit:cover;
    display:block;
}

.detail-text{
    white-space:pre-line;
    color:var(--bs-body-color);
}

.save-toast{
    position:fixed;
    right:20px;
    bottom:20px;
    z-index:2000;
}

@media(max-width:575px){
    .dashboard-hero{
        padding:24px;
    }

    .ann-title{
        font-size:1rem;
    }
}
    
/* =========================================================
   ANNOUNCEMENT MODULE ADDITIONS (same colors, fonts, radius)
   ========================================================= */
.ann-status.cancelled{
    background:var(--bs-danger-bg-subtle);
    color:var(--bs-danger);
    border-color:var(--bs-danger);
}
.ann-status.inline{
    position:static;
}
.ann-card.is-cancelled .ann-banner img{
    filter:grayscale(1);
}
.ann-card.is-draft{
    border-style:dashed;
}
.ann-tag{
    position:absolute;
    bottom:12px;
    padding:4px 10px;
    border-radius:999px;
    font-size:.7rem;
    font-weight:800;
    border:2px solid var(--bs-body-color);
    background:#fff;
    color:var(--bs-body-color);
}
.ann-tag.cat{ left:12px; }
.ann-tag.pub{ right:12px; background:var(--bs-primary-text-emphasis); color:var(--kk-primary); }
.ann-tag.pub.draft,
.ann-tag.pub.unpublished{ background:var(--bs-border-color); color:var(--bs-secondary-color); border-color:var(--bs-border-color); }
.ann-tag.pub.scheduled{ background:var(--kk-primary-soft); color:var(--bs-body-color); }
.ann-footer{
    flex-wrap:wrap;
}
.ann-count{
    display:inline-flex;
    align-items:center;
    gap:6px;
    margin-top:12px;
    padding:6px 12px;
    border-radius:12px;
    background:var(--bs-primary-text-emphasis);
    color:var(--kk-primary);
    font-size:.8rem;
    font-weight:800;
    font-variant-numeric:tabular-nums;
}
.ann-count.live{
    background:var(--bs-primary);
    color:var(--bs-body-color);
    border:2px solid var(--bs-body-color);
}
.ann-count.off{
    background:var(--bs-danger-bg-subtle);
    color:var(--bs-danger);
}
.section-head{
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:10px;
    margin-bottom:14px;
}
.section-head h2{
    font-size:1.1rem;
    font-weight:900;
    margin:0;
}
/* upcoming events strip */
.up-card{
    display:flex;
    gap:14px;
    height:100%;
    padding:12px;
    background:#fff;
    border:1px solid var(--line);
    border-radius:18px;
    box-shadow:0 8px 20px rgba(20,20,20,.05);
    cursor:pointer;
    transition:.2s ease;
}
.up-card:hover,
.up-card:focus-visible{
    transform:translateY(-3px);
    box-shadow:0 14px 26px rgba(20,20,20,.10);
    outline:none;
    border-color:var(--bs-body-color);
}
.up-thumb{
    width:112px;
    flex:none;
    align-self:stretch;
    min-height:112px;
    border-radius:14px;
    overflow:hidden;
    background:var(--bs-primary);
}
.up-thumb img,
.up-thumb .ann-banner-empty{
    width:100%;
    height:100%;
    object-fit:cover;
    display:block;
    font-size:2rem;
}
.up-info{
    min-width:0;
    flex:1;
}
.up-title{
    font-weight:900;
    font-size:.98rem;
    line-height:1.25;
    margin:6px 0;
}
.up-line{
    display:flex;
    gap:6px;
    font-size:.8rem;
    color:var(--bs-secondary-color);
    margin-bottom:2px;
    word-break:break-word;
}
.up-line i{
    flex:none;
    margin-top:2px;
}
.up-info .ann-count{
    margin-top:8px;
    padding:4px 10px;
    font-size:.75rem;
}
.mini-badge{
    display:inline-block;
    padding:2px 9px;
    border-radius:999px;
    font-size:.68rem;
    font-weight:800;
    border:1.5px solid var(--bs-body-color);
    background:#fff;
    color:var(--bs-body-color);
}
.mini-badge.upcoming{ background:var(--bs-primary-text-emphasis); color:var(--kk-primary); }
.mini-badge.ongoing{ background:var(--bs-primary); color:var(--bs-body-color); }
/* details window */
.cd-panel{
    background:rgba(var(--bs-primary-rgb),.08);
    border:1px solid var(--bs-border-color);
    border-left:4px solid var(--kk-primary);
    border-radius:14px;
    padding:14px;
}
.ann-countdown{
    display:flex;
    flex-wrap:wrap;
    gap:10px;
}
.cd-box{
    min-width:72px;
    text-align:center;
    padding:10px 8px;
    border-radius:14px;
    background:var(--bs-primary-text-emphasis);
    color:var(--kk-primary);
}
.cd-num{
    display:block;
    font-size:1.6rem;
    font-weight:900;
    line-height:1.1;
    font-variant-numeric:tabular-nums;
}
.cd-lbl{
    display:block;
    font-size:.65rem;
    letter-spacing:1px;
    text-transform:uppercase;
    color:var(--bs-border-color);
}
.poster-box{
    border:1px solid var(--line);
    border-radius:16px;
    background:var(--bs-tertiary-bg);
    padding:10px;
    text-align:center;
}
.poster-box img{
    max-width:100%;
    max-height:520px;
    object-fit:contain;
    border-radius:10px;
    cursor:zoom-in;
}
.banner-preview.poster{
    aspect-ratio:3 / 4;
    max-width:260px;
}
.banner-preview.poster img{
    object-fit:contain;
}
#posterLightbox{
    position:fixed;
    inset:0;
    z-index:2100;
    display:flex;
    align-items:center;
    justify-content:center;
    padding:20px;
    background:rgba(0,0,0,.88);
}
#posterLightbox.d-none{
    display:none!important;
}
#posterLightbox img{
    max-width:96vw;
    max-height:92vh;
    object-fit:contain;
    border-radius:10px;
}
#posterLightbox button{
    position:absolute;
    top:14px;
    right:14px;
}
.pub-brand{
    display:flex;
    align-items:center;
    gap:10px;
}
@media(max-width:575px){
    .up-thumb{ width:84px; min-height:84px; }
    .cd-box{ min-width:62px; }
    .cd-num{ font-size:1.3rem; }
}


/* Add / Edit window: the fields scroll, the header and the buttons stay visible */
#announcementForm{
    display:flex;
    flex-direction:column;
    flex:1 1 auto;
    min-height:0;
    overflow:hidden;
}
#announcementForm .modal-body{
    overflow-y:auto;
}
#announcementForm .modal-footer{
    flex-shrink:0;
    background:#fff;
    border-top:1px solid var(--line);
}
    
/* =========================================================
   STATUS COLOURS  (Bootstrap colours: easy to tell apart + easy to read)
   Upcoming = primary (blue)   Ongoing = success (green)
   Completed = secondary (grey)   Cancelled = danger (red)
   ========================================================= */
.filter-pill{
    --c:var(--bs-dark);
    --soft:var(--bs-dark-bg-subtle);
    --ink:var(--bs-dark);
    display:inline-flex;
    align-items:center;
    gap:.45rem;
    border:2px solid var(--soft);
    background:var(--bs-white);
    color:var(--ink);
    font-weight:700;
    font-size:.9rem;
    padding:.45rem .85rem .45rem 1rem;
    border-radius:999px;
    transition:.2s ease;
}
.filter-pill[data-filter="Upcoming"]{ --c:var(--bs-primary);   --soft:var(--bs-primary-bg-subtle);   --ink:var(--bs-primary-text-emphasis); }
.filter-pill[data-filter="Ongoing"]{  --c:var(--bs-success);   --soft:var(--bs-success-bg-subtle);   --ink:var(--bs-success-text-emphasis); }
.filter-pill[data-filter="Completed"]{--c:var(--bs-secondary); --soft:var(--bs-secondary-bg-subtle); --ink:var(--bs-secondary-text-emphasis); }
.filter-pill[data-filter="Cancelled"]{--c:var(--bs-danger);    --soft:var(--bs-danger-bg-subtle);    --ink:var(--bs-danger-text-emphasis); }

.filter-pill i{
    color:var(--c);
    font-size:1.05em;
}
.filter-pill:hover{
    background:var(--soft);
    border-color:var(--c);
    transform:translateY(-2px);
    box-shadow:0 6px 14px rgba(0,0,0,.12);
}
.filter-pill.active{
    background:var(--c);
    border-color:var(--c);
    color:var(--bs-white);
    box-shadow:0 6px 16px rgba(0,0,0,.2);
}
.filter-pill.active i{
    color:var(--bs-white);
}
.filter-pill .count{
    display:inline-flex;
    align-items:center;
    justify-content:center;
    min-width:26px;
    height:22px;
    margin-left:0;
    padding:0 8px;
    border-radius:999px;
    background:var(--soft);
    color:var(--ink);
    font-size:.78rem;
    font-weight:800;
}
.filter-pill.active .count{
    background:var(--bs-white);
    color:var(--c);
}

/* status badge on the card banner: solid colour + white text */
.ann-status{
    border:2px solid var(--bs-white);
    color:var(--bs-white);
    box-shadow:0 3px 10px rgba(0,0,0,.25);
}
.ann-status.upcoming{  background:var(--bs-primary);   color:var(--bs-white); }
.ann-status.ongoing{   background:var(--bs-success);   color:var(--bs-white); }
.ann-status.completed{ background:var(--bs-secondary); color:var(--bs-white); border-color:var(--bs-white); }
.ann-status.cancelled{ background:var(--bs-danger);    color:var(--bs-white); border-color:var(--bs-white); }
.ann-status.ongoing:before{
    background:var(--bs-white);
    animation:kkPulse 1.4s ease-in-out infinite;
}
@keyframes kkPulse{
    0%,100%{ opacity:1;  transform:scale(1); }
    50%    { opacity:.45; transform:scale(.7); }
}

/* countdown box */
.ann-count{
    background:var(--bs-primary-bg-subtle);
    color:var(--bs-primary-text-emphasis);
    border:1px solid var(--bs-primary-border-subtle);
}
.ann-count.live{
    background:var(--bs-success-bg-subtle);
    color:var(--bs-success-text-emphasis);
    border:1px solid var(--bs-success-border-subtle);
}
.ann-count.off{
    background:var(--bs-danger-bg-subtle);
    color:var(--bs-danger-text-emphasis);
    border:1px solid var(--bs-danger-border-subtle);
}

/* small status labels in lists */
.mini-badge{
    border:1.5px solid var(--bs-border-color);
    background:var(--bs-white);
    color:var(--bs-body-color);
}
.mini-badge.upcoming{  background:var(--bs-primary-bg-subtle);   color:var(--bs-primary-text-emphasis);   border-color:var(--bs-primary-border-subtle); }
.mini-badge.ongoing{   background:var(--bs-success-bg-subtle);   color:var(--bs-success-text-emphasis);   border-color:var(--bs-success-border-subtle); }
.mini-badge.completed{ background:var(--bs-secondary-bg-subtle); color:var(--bs-secondary-text-emphasis); border-color:var(--bs-secondary-border-subtle); }
.mini-badge.cancelled{ background:var(--bs-danger-bg-subtle);    color:var(--bs-danger-text-emphasis);    border-color:var(--bs-danger-border-subtle); }


/* ---------- OFFICIAL vs PUBLIC view (same page) ---------- */
html.is-public .hero-copy p{
    margin-bottom:0!important;
}
</style>
<?php include __DIR__ . '/init.php'; ?>
</head>

<body>

<div class="top-strip"></div>

<script src="sidebar.js?v=3"></script>

<div class="page-content">

    <!-- OFFICIAL HEADER -->
    <header class="bg-white border-bottom sticky-top">
        <div class="d-flex align-items-center gap-2 gap-md-3 px-3 px-lg-4 py-2 py-lg-3">

            <button class="btn btn-outline-success d-lg-none"
                    type="button"
                    data-bs-toggle="offcanvas"
                    data-bs-target="#sidebar"
                    aria-controls="sidebar">
                <i class="bi bi-list fs-5"></i>
            </button>

            <div>
                <h1 class="h5 fw-bold mb-0">Announcements</h1>
                <div class="text-muted small d-none d-sm-block">
                    News, events, and notices from the SK office
                </div>
            </div>

            <div class="ms-auto d-flex align-items-center gap-2 gap-md-3">

                <a href="announcements.php"
                   class="btn btn-outline-success btn-sm position-relative"
                   aria-label="Notifications">
                    <i class="bi bi-bell"></i>
                    <span id="bellCount"
                          class="position-absolute top-0 start-100 translate-middle badge rounded-pill"
                          style="background:var(--bs-primary);color:var(--bs-body-color);">
                        0
                    </span>
                </a>

                <span class="avatar"><?= e(uinit()) ?></span>

                <div class="d-none d-md-block lh-sm">
                    <div class="fw-semibold small"><?= e(uname()) ?></div>
                    <div class="text-muted" style="font-size:.75rem;"><?= e(strtoupper(urole())) ?></div>
                </div>

                <a href="logout.php" class="btn btn-success btn-sm">
                    <i class="bi bi-box-arrow-right me-1"></i>
                    <?= is_public() ? 'Official Login' : 'Logout' ?>
                </a>

            </div>
        </div>
    </header>

    <main class="container-fluid p-3 p-lg-4">

        <!-- SEARCH, FILTER, SORT -->
        <section class="toolbar-box mb-4">
            <div class="row g-3 align-items-center">

                <div class="col-lg-4">
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-search"></i></span>
                        <input type="text" id="searchInput" class="form-control"
                               placeholder="Search title, description, venue, or organizer...">
                    </div>
                </div>

                <div class="col-6 col-lg-2">
                    <select id="categoryFilter" class="form-select" aria-label="Filter by category">
                        <option value="">All categories</option>
                    </select>
                </div>

                <div class="col-6 col-lg-2">
                    <input type="date" id="dateFilter" class="form-control" aria-label="Filter by event date">
                </div>

                <div class="col-6 col-lg-2">
                    <select id="sortSelect" class="form-select" aria-label="Sort announcements">
                        <option value="soonest">Upcoming soonest</option>
                        <option value="newest">Newest</option>
                        <option value="oldest">Oldest</option>
                        <option value="updated">Recently updated</option>
                    </select>
                </div>

                <div class="col-6 col-lg-2">
                    <select id="publishFilter" class="form-select" aria-label="Filter by publishing">
                        <option value="">Any publishing</option>
                        <option>Published</option>
                        <option>Draft</option>
                        <option>Unpublished</option>
                        <option>Scheduled</option>
                    </select>
                </div>

                <div class="col-12 d-flex flex-wrap gap-2 align-items-center justify-content-between">
                    <div class="d-flex flex-wrap gap-2" id="filterPills">
                        <button type="button" class="filter-pill active" data-filter="All"><i class="bi bi-grid-fill"></i>All<span class="count" id="countAll">0</span></button>
                        <button type="button" class="filter-pill" data-filter="Upcoming"><i class="bi bi-clock-history"></i>Upcoming<span class="count" id="countUpcoming">0</span></button>
                        <button type="button" class="filter-pill" data-filter="Ongoing"><i class="bi bi-broadcast"></i>Ongoing<span class="count" id="countOngoing">0</span></button>
                        <button type="button" class="filter-pill" data-filter="Completed"><i class="bi bi-check-circle-fill"></i>Completed<span class="count" id="countCompleted">0</span></button>
                        <button type="button" class="filter-pill" data-filter="Cancelled"><i class="bi bi-x-octagon-fill"></i>Cancelled<span class="count" id="countCancelled">0</span></button>
                    </div>

                    <button type="button" class="btn btn-sm btn-outline-success" id="resetFiltersBtn">
                        <i class="bi bi-arrow-counterclockwise me-1"></i> Reset filters
                    </button>
                </div>

            </div>
        </section>

        <!-- HERO -->
        <section class="dashboard-hero mb-4">
            <div class="hero-copy">
                <span class="hero-tag">
                    <i class="bi bi-megaphone-fill"></i>
                    SK ANNOUNCEMENTS
                </span>

                <h2 class="mt-3 mb-2">Abangan, Kabataan!</h2>

                <p class="mb-3">
                    Check the latest events, programs, and notices from the
                    Sangguniang Kabataan of Barangay Sikat.
                </p>

                <div class="d-flex flex-wrap gap-2 admin-only">
                    <button type="button" class="btn btn-dark rounded-pill px-4" id="openAddBtn">
                        <i class="bi bi-plus-lg me-2"></i>
                        Add Announcement
                    </button>
                </div>
            </div>
        </section>

        <!-- UPCOMING EVENTS -->
        <section class="mb-4" id="upcomingSection">
            <div class="section-head">
                <h2><i class="bi bi-calendar-event me-2"></i>Upcoming Events</h2>
                <button type="button" class="btn btn-sm btn-outline-success d-none" id="viewAllUpcomingBtn">View all upcoming</button>
            </div>

            <div class="row row-cols-1 row-cols-lg-2 row-cols-xxl-3 g-3" id="upcomingGrid"></div>

            <div class="empty-state d-none" id="upcomingEmpty">
                <i class="bi bi-calendar-x"></i>
                <p class="mb-0 mt-2">No upcoming events right now.</p>
            </div>
        </section>

        <div class="section-head" id="allAnnouncementsHead">
            <h2><i class="bi bi-megaphone me-2"></i><span id="resultsTitle">All Programs & Activities</span></h2>
        </div>

        <!-- ANNOUNCEMENT CARDS (filled by JavaScript) -->
        <section class="row row-cols-1 row-cols-md-2 row-cols-xxl-3 g-4" id="announcementGrid"></section>

        <!-- EMPTY STATE -->
        <div class="empty-state d-none" id="emptyState">
            <i class="bi bi-megaphone"></i>
            <h2 class="h5 fw-bold mt-2 mb-1" id="emptyTitle">No announcements yet</h2>
            <p class="mb-3" id="emptyText">Click “Add Announcement” to post the first one.</p>
        </div>

    </main>
</div>

<!-- ================= ADD / EDIT MODAL ================= -->
<div class="modal fade" id="announcementModal" tabindex="-1" aria-labelledby="formModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">

            <div class="modal-header">
                <h2 class="modal-title h5" id="formModalTitle">Add Announcement</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <form id="announcementForm" novalidate>
                <div class="modal-body">

                    <input type="hidden" id="annId">

                    <div class="row g-3">

                        <div class="col-md-8">
                            <label class="form-label" for="annTitle">Announcement Title *</label>
                            <input type="text" id="annTitle" class="form-control" maxlength="90" placeholder="e.g. Inter-Purok Basketball League">
                            <div class="invalid-feedback">Please enter a title.</div>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label" for="annCategory">Category *</label>
                            <select id="annCategory" class="form-select"></select>
                        </div>

                        <div class="col-12">
                            <label class="form-label" for="annDescription">Short Description *</label>
                            <textarea id="annDescription" class="form-control" rows="2" maxlength="200"
                                      placeholder="One or two sentences shown on the card"></textarea>
                            <div class="invalid-feedback">Please enter a short description.</div>
                        </div>

                        <div class="col-12">
                            <label class="form-label" for="annDetails">Full Details</label>
                            <textarea id="annDetails" class="form-control" rows="3"
                                      placeholder="Requirements, program, reminders..."></textarea>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label" for="annDate">Event Date *</label>
                            <input type="date" id="annDate" class="form-control">
                            <div class="invalid-feedback">Please choose the event date.</div>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label" for="annStart">Start Time *</label>
                            <input type="time" id="annStart" class="form-control">
                            <div class="invalid-feedback">Please choose the start time.</div>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label" for="annEnd">End Time</label>
                            <input type="time" id="annEnd" class="form-control">
                            <div class="invalid-feedback" id="annEndError">End time must be after the start time.</div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label" for="annVenue">Venue / Location *</label>
                            <select id="annVenue" class="form-select"></select>
                            <div class="invalid-feedback">Please choose the venue.</div>
                            <div class="d-none mt-2" id="annVenueOtherBox">
                                <input type="text" id="annVenueOther" class="form-control" maxlength="90" placeholder="Type the venue / location">
                                <div class="invalid-feedback">Please type the venue.</div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label" for="annOrganizer">Organizer *</label>
                            <select id="annOrganizer" class="form-select"></select>
                            <div class="invalid-feedback">Please choose the organizer.</div>
                            <div class="d-none mt-2" id="annOrganizerOtherBox">
                                <input type="text" id="annOrganizerOther" class="form-control" maxlength="90" placeholder="Type the organizer">
                                <div class="invalid-feedback">Please type the organizer.</div>
                            </div>
                        </div>

                        <div class="col-12">
                            <label class="form-label" for="annContact">Contact Information</label>
                            <input type="text" id="annContact" class="form-control" maxlength="120" placeholder="Phone, email, or contact person">
                        </div>

                        <!-- IMAGES -->
                        <div class="col-12">
                            <label class="form-label" for="annBanner">Banner Image (optional)</label>
                            <input type="file" id="annBanner" class="form-control" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp">
                            <div class="form-text">JPG, JPEG, PNG or WEBP, up to 5 MB. Wide (16:9) works best. Choose another file to replace it.</div>
                            <div class="text-danger small mt-1 d-none" id="bannerError"></div>
                            <div class="banner-preview mt-2" id="bannerPreview" style="max-width:420px;">
                                <span><i class="bi bi-image fs-3 d-block"></i>No banner selected</span>
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-danger mt-2 d-none" id="removeBannerBtn">
                                <i class="bi bi-x-lg me-1"></i> Remove banner
                            </button>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label" for="annEventStatus">Event Status</label>
                            <select id="annEventStatus" class="form-select">
                                <option value="Automatic">Automatic (Upcoming, Ongoing, or Completed by date and time)</option>
                                <option value="Cancelled">Cancelled</option>
                            </select>
                            <div class="form-text">The status updates by itself. Only choose Cancelled if the event will not happen.</div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label" for="annPublishDate">Publish Date</label>
                            <input type="date" id="annPublishDate" class="form-control">
                            <div class="form-text">Leave empty to publish today. A future date schedules it.</div>
                        </div>

                        <div class="col-12">
                            <label class="form-label" for="annNotes">Additional Notes (optional)</label>
                            <textarea id="annNotes" class="form-control" rows="2" placeholder="Reminders, dress code, what to bring..."></textarea>
                        </div>

                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-success" data-bs-dismiss="modal">Cancel</button>

                    <button type="button" class="btn btn-outline-success" id="saveDraftBtn">
                        <i class="bi bi-file-earmark me-1"></i> <span>Save as Draft</span>
                    </button>

                    <button type="submit" class="btn btn-success" id="saveBtn">
                        <i class="bi bi-check-lg me-1"></i> <span>Publish Announcement</span>
                    </button>
                </div>
            </form>

        </div>
    </div>
</div>

<!-- ================= CONFIRM MODAL (delete / cancel event / unpublish) ================= -->
<div class="modal fade" id="confirmModal" tabindex="-1" aria-labelledby="confirmTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-body p-4 text-center">

                <div class="mb-2" style="font-size:2.5rem;color:var(--bs-danger);" id="confirmIcon">
                    <i class="bi bi-trash3"></i>
                </div>

                <h2 class="h5 fw-bold" id="confirmTitle">Delete Announcement?</h2>
                <p class="text-muted mb-1" id="confirmName"></p>
                <p class="text-muted mb-4" id="confirmText">This action cannot be undone.</p>

                <div class="d-flex gap-2 justify-content-center">
                    <button type="button" class="btn btn-outline-success px-4" data-bs-dismiss="modal" id="confirmBackBtn">Cancel</button>
                    <button type="button" class="btn btn-danger px-4 fw-bold" id="confirmOkBtn">Delete</button>
                </div>

            </div>
        </div>
    </div>
</div>

<!-- SAVED MESSAGE -->
<div class="save-toast">
    <div id="saveToast" class="toast align-items-center text-bg-dark border-0" role="status" aria-live="polite" aria-atomic="true">
        <div class="d-flex">
            <div class="toast-body" id="toastText"></div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
/* =========================================================
   ANNOUNCEMENTS CORE  (used by both the official view and the public view)
   - saves in the MySQL database (app_data table) under "skAnnouncements"
   - the event status is calculated from the date and time (never typed in by hand)
   - the mode is chosen at the top of the page:
     officials get add / edit / delete; public viewers (Continue as Public Viewer) are view only
   ========================================================= */
(function () {
    "use strict";

    var ANN_KEY = "skAnnouncements";
    var MODE = window.ANN_MODE === "official" ? "official" : "public";
    var OFFICIAL = MODE === "official";
    var CATEGORIES = ["Health", "Education", "Economic Empowerment", "Social Inclusion and Equity", "Peace-Building and Security", "Governance", "Active Citizenship", "Environment", "Global Mobility", "Agriculture"];
    // old saved announcements used the previous category names: map them to a Center of Participation
    var LEGACY_CATEGORY = { "General": "Governance", "Community": "Active Citizenship", "Youth": "Active Citizenship",
        "Program": "Education", "Meeting": "Governance", "Event": "Active Citizenship",
        "Emergency": "Peace-Building and Security", "Other": "Governance" };

    var announcements = [];
    var currentFilter = "All";   // All / Upcoming / Ongoing / Completed / Cancelled
    var viewingId = null;
    var lastSig = "";
    var actions = {};            // official page adds edit / delete / ... here

    function $(id) { return document.getElementById(id); }

    /* ---------- small helpers ---------- */
    function escapeHtml(text) {
        return String(text == null ? "" : text)
            .replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;").replace(/'/g, "&#039;");
    }
    function safeUrl(url) { return /^https?:\/\/\S+$/i.test(url || "") ? url : ""; }
    function pad2(n) { return String(n).padStart(2, "0"); }
    function toDate(dateText) {
        var p = String(dateText || "").split("-");
        return new Date(Number(p[0]), Number(p[1]) - 1, Number(p[2]));
    }
    function formatDate(dateText) {
        if (!dateText) return "";
        return toDate(dateText).toLocaleDateString("en-US", { month: "long", day: "numeric", year: "numeric" });
    }
    function formatTime(t) {
        if (!t) return "";
        var parts = t.split(":");
        var hour = Number(parts[0]);
        var ampm = hour >= 12 ? "PM" : "AM";
        hour = hour % 12;
        if (hour === 0) hour = 12;
        return hour + ":" + parts[1] + " " + ampm;
    }
    function todayISO() {
        var d = new Date();
        return d.getFullYear() + "-" + pad2(d.getMonth() + 1) + "-" + pad2(d.getDate());
    }
    function timeRange(a) {
        return formatTime(a.startTime) + (a.endTime ? " \u2013 " + formatTime(a.endTime) : "");
    }
    function toast(text) {
        var el = $("saveToast");
        if (!el) return;
        $("toastText").textContent = text;
        bootstrap.Toast.getOrCreateInstance(el, { delay: 2500 }).show();
    }

    /* ---------- event schedule + automatic status ---------- */
    function eventStart(a) { return new Date(a.date + "T" + (a.startTime || "00:00") + ":00"); }
    // no end time = the event lasts until the end of that day
    function eventEnd(a) { return a.endTime ? new Date(a.date + "T" + a.endTime + ":00") : new Date(a.date + "T23:59:59"); }
    function getStatus(a, now) {
        if (a.cancelled) return "Cancelled";          // a manual cancel is never overwritten
        now = now || new Date();
        if (now < eventStart(a)) return "Upcoming";
        if (now <= eventEnd(a)) return "Ongoing";
        return "Completed";
    }

    /* ---------- publishing ---------- */
    function isPublic(a) {
        return a.publishState === "Published" && (!a.publishDate || a.publishDate <= todayISO());
    }
    function canSee(a) { return OFFICIAL || isPublic(a); }
    function publishLabel(a) {
        if (a.publishState === "Published" && a.publishDate && a.publishDate > todayISO()) return "Scheduled";
        return a.publishState;
    }

    /* ---------- countdown ---------- */
    function splitDiff(ms) {
        var s = Math.max(0, Math.floor(ms / 1000));
        return { d: Math.floor(s / 86400), h: Math.floor((s % 86400) / 3600), m: Math.floor((s % 3600) / 60), s: s % 60 };
    }
    function shortCountdown(ms) {
        var t = splitDiff(ms);
        if (t.d >= 1) return t.d + (t.d === 1 ? " Day" : " Days") + " Remaining";
        if (t.h >= 1) return t.h + (t.h === 1 ? " Hour " : " Hours ") + t.m + " Min Remaining";
        return t.m + " Min " + t.s + " Sec Remaining";
    }
    function updateCountdowns() {
        var now = Date.now();
        document.querySelectorAll(".ann-count[data-start]").forEach(function (el) {
            var diff = Number(el.dataset.start) - now;
            el.querySelector("span").textContent = diff > 0 ? shortCountdown(diff) : "Starting now";
        });
        document.querySelectorAll(".cd-live[data-start]").forEach(function (el) {
            var t = splitDiff(Number(el.dataset.start) - now);
            el.querySelector('[data-u="d"]').textContent = pad2(t.d);
            el.querySelector('[data-u="h"]').textContent = pad2(t.h);
            el.querySelector('[data-u="m"]').textContent = pad2(t.m);
            el.querySelector('[data-u="s"]').textContent = pad2(t.s);
        });
    }

    /* ---------- sample data (official page, first time only) ---------- */
    function makeBanner(background, shapeColor, accent) {
        var svg =
            '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 360">' +
            '<rect width="640" height="360" fill="' + background + '"/>' +
            '<circle cx="560" cy="40" r="150" fill="' + shapeColor + '" opacity=".12"/>' +
            '<rect x="60" y="210" width="200" height="200" rx="44" fill="' + accent + '" opacity=".35" transform="rotate(24 160 310)"/>' +
            '<rect x="48" y="96" width="280" height="22" rx="11" fill="' + shapeColor + '"/>' +
            '<rect x="48" y="134" width="190" height="14" rx="7" fill="' + shapeColor + '" opacity=".45"/>' +
            '<circle cx="470" cy="215" r="46" fill="' + shapeColor + '"/>' +
            '<circle cx="548" cy="215" r="46" fill="' + accent + '" stroke="' + shapeColor + '" stroke-width="6"/>' +
            '</svg>';
        return "data:image/svg+xml;charset=utf-8," + encodeURIComponent(svg);
    }
    function sampleAnnouncements() {
        var now = new Date().toISOString();
        function base(o) {
            o.poster = ""; o.contact = ""; o.regLink = ""; o.notes = "";
            o.cancelled = false; o.publishState = "Published"; o.publishDate = "";
            o.createdAt = now; o.updatedAt = now;
            return o;
        }
        return [
            base({ id: "sample-4", title: "Youth Clean-Up Drive", category: "Environment",
                description: "Join the SK in cleaning up our barangay. Gloves and trash bags are provided.",
                details: "Wear comfortable clothes and bring a water bottle.\nVolunteers receive a certificate of participation.",
                date: "2026-10-20", startTime: "08:00", endTime: "12:00", venue: "Barangay Sikat Covered Court",
                organizer: "SK Committee on Environment", contact: "SK Office \u2022 0917 000 0000",
                banner: makeBanner("#9ec5fe", "#212529", "#ffffff") }),
            base({ id: "sample-1", title: "KK Youth Profiling Week", category: "Governance",
                description: "All residents aged 15\u201330 are encouraged to complete their KK profiling.",
                details: "Bring a valid ID or school ID.\nProfiling forms are available at the SK Office.\nWalk-ins are welcome.",
                date: "2026-10-15", startTime: "09:00", endTime: "16:00", venue: "Barangay Hall, Barangay Sikat",
                organizer: "SK Barangay Sikat", banner: makeBanner("rgba(13,110,253,.12)", "#212529", "#9ec5fe") }),
            base({ id: "sample-2", title: "SK Scholarship Applications Open", category: "Education",
                description: "Submit your requirements at the SK Office until November 15.",
                details: "Open to qualified KK youth of Barangay Sikat.\nSubmit your requirements at the SK Office during office hours.",
                date: "2026-11-15", startTime: "08:00", endTime: "17:00", venue: "SK Office, Barangay Hall",
                organizer: "SK Committee on Education", banner: makeBanner("#212529", "#9ec5fe", "#9ec5fe") }),
            base({ id: "sample-3", title: "Inter-Purok Basketball League", category: "Health",
                description: "Team registration is now open. Contact your purok coordinators.",
                details: "Each purok may register one team.\nContact your purok coordinator for the team list and schedule.",
                date: "2026-10-24", startTime: "15:00", endTime: "18:00", venue: "Barangay Covered Court",
                organizer: "SK Committee on Sports", banner: makeBanner("#9ec5fe", "#212529", "#ffffff") })
        ];
    }

    /* ---------- load / save (MySQL through storage.js) ---------- */
    function normalize(a) {
        if (CATEGORIES.indexOf(a.category) === -1) a.category = LEGACY_CATEGORY[a.category] || "Governance";
        a.startTime = a.startTime || a.time || "00:00";      // older records used "time"
        a.endTime = a.endTime || "";
        a.contact = a.contact || ""; a.regLink = a.regLink || ""; a.notes = a.notes || "";
        a.banner = a.banner || ""; a.poster = a.poster || "";
        a.details = a.details || ""; a.description = a.description || "";
        a.venue = a.venue || ""; a.organizer = a.organizer || "";
        a.cancelled = !!a.cancelled;
        a.publishState = ["Draft", "Published", "Unpublished"].indexOf(a.publishState) === -1 ? "Published" : a.publishState;
        a.publishDate = a.publishDate || "";
        a.createdAt = a.createdAt || (a.date + "T00:00:00.000Z");
        a.updatedAt = a.updatedAt || a.createdAt;
        delete a.status;      // the status is now calculated automatically
        delete a.time;
        return a;
    }
    function load() {
        var saved = DB.getItem(ANN_KEY);
        if (saved === null) {
            announcements = OFFICIAL ? sampleAnnouncements() : [];
            if (OFFICIAL) save();
            return;
        }
        try {
            announcements = JSON.parse(saved);
            if (!Array.isArray(announcements)) announcements = [];
        } catch (e) { announcements = []; }
        announcements = announcements.filter(function (a) { return a && a.id && a.date; }).map(normalize);
    }
    function save() {
        try {
            DB.setItem(ANN_KEY, JSON.stringify(announcements));
            return true;
        } catch (e) {
            toast("Browser storage is full. Use smaller images or delete old announcements.");
            return false;
        }
    }
    function find(id) { return announcements.filter(function (a) { return a.id === id; })[0]; }

    /* ---------- pieces of HTML ---------- */
    function placeholder() { return '<div class="ann-banner-empty"><i class="bi bi-megaphone-fill"></i></div>'; }
    function imgTag(src, alt, extra) {
        return '<img class="ann-img" loading="lazy" src="' + escapeHtml(src) + '" alt="' + escapeHtml(alt) + '"' + (extra || "") + '>';
    }
    function imageOrPlaceholder(a) {
        var src = a.banner || a.poster;
        return src ? imgTag(src, "Banner for " + a.title) : placeholder();
    }
    function statusBadge(status, inline) {
        return '<span class="ann-status ' + (inline ? "inline " : "") + status.toLowerCase() + '">' + escapeHtml(status) + '</span>';
    }
    function metaRow(icon, text) {
        return '<div class="ann-meta-row"><div class="ann-meta-icon"><i class="bi ' + icon + '"></i></div><span>' + escapeHtml(text) + '</span></div>';
    }
    function countHtml(a, status) {
        if (status === "Upcoming") {
            return '<div class="ann-count" data-start="' + eventStart(a).getTime() + '"><i class="bi bi-hourglass-split"></i><span></span></div>';
        }
        if (status === "Ongoing") return '<div class="ann-count live"><i class="bi bi-broadcast"></i><span>Happening now</span></div>';
        if (status === "Cancelled") return '<div class="ann-count off"><i class="bi bi-x-octagon"></i><span>Event cancelled</span></div>';
        return "";
    }
    function iconBtn(act, id, icon, label, cls, disabled) {
        return '<button type="button" class="btn ' + cls + ' icon-btn" title="' + label + '" aria-label="' + label + '" data-act="' + act +
            '" data-id="' + escapeHtml(id) + '"' + (disabled ? " disabled" : "") + '><i class="bi ' + icon + '"></i></button>';
    }

    function buildCard(a) {
        var status = getStatus(a);
        var d = toDate(a.date);
        var month = d.toLocaleDateString("en-US", { month: "short" }).toUpperCase();
        var day = pad2(d.getDate());
        var pub = publishLabel(a);
        var footer = '<button type="button" class="btn btn-success btn-view" data-act="view" data-id="' + escapeHtml(a.id) + '">' +
            '<i class="bi bi-eye me-1"></i> View Details</button>';
        if (OFFICIAL) {
            var live = a.publishState === "Published";
            footer +=
                iconBtn("edit", a.id, "bi-pencil-square", "Edit announcement", "btn-outline-success") +
                iconBtn("togglePublish", a.id, live ? "bi-eye-slash" : "bi-cloud-arrow-up", live ? "Unpublish" : "Publish", "btn-outline-success") +
                iconBtn("toggleCancel", a.id, a.cancelled ? "bi-arrow-counterclockwise" : "bi-x-octagon",
                        a.cancelled ? "Restore event" : "Cancel event", "btn-outline-success", status === "Completed") +
                iconBtn("delete", a.id, "bi-trash3", "Delete announcement", "btn-outline-danger");
        }
        return '<div class="col"><div class="ann-card' + (a.cancelled ? " is-cancelled" : "") + (OFFICIAL && pub !== "Published" ? " is-draft" : "") + '">' +
            '<div class="ann-banner">' + imageOrPlaceholder(a) +
                statusBadge(status) +
                '<div class="ann-date-chip"><span class="m">' + month + '</span><span class="d">' + day + '</span></div>' +
                '<span class="ann-tag cat">' + escapeHtml(a.category) + '</span>' +
                (OFFICIAL ? '<span class="ann-tag pub ' + pub.toLowerCase() + '">' + pub + '</span>' : "") +
            '</div>' +
            '<div class="ann-body">' +
                '<h3 class="ann-title">' + escapeHtml(a.title) + '</h3>' +
                '<p class="ann-desc">' + escapeHtml(a.description) + '</p>' +
                '<div class="ann-meta">' +
                    metaRow("bi-calendar-event", formatDate(a.date)) +
                    metaRow("bi-clock", timeRange(a)) +
                    metaRow("bi-geo-alt", a.venue) +
                    metaRow("bi-person-badge", a.organizer) +
                '</div>' + countHtml(a, status) +
            '</div>' +
            '<div class="ann-footer">' + footer + '</div>' +
        '</div></div>';
    }

    function buildUpcomingCard(a) {
        var status = getStatus(a);
        var src = a.banner || a.poster;
        return '<div class="col"><div class="up-card" role="button" tabindex="0" data-act="view" data-id="' + escapeHtml(a.id) + '">' +
            '<div class="up-thumb">' + (src ? imgTag(src, "Banner for " + a.title) : placeholder()) + '</div>' +
            '<div class="up-info">' +
                '<span class="mini-badge ' + status.toLowerCase() + '">' + status + '</span> ' +
                '<span class="mini-badge">' + escapeHtml(a.category) + '</span>' +
                '<div class="up-title">' + escapeHtml(a.title) + '</div>' +
                '<div class="up-line"><i class="bi bi-calendar-event"></i><span>' + formatDate(a.date) + ' \u2022 ' + escapeHtml(timeRange(a)) + '</span></div>' +
                '<div class="up-line"><i class="bi bi-geo-alt"></i><span>' + escapeHtml(a.venue) + '</span></div>' +
                '<div class="up-line"><i class="bi bi-person-badge"></i><span>' + escapeHtml(a.organizer) + '</span></div>' +
                countHtml(a, status) +
            '</div></div></div>';
    }

    /* ---------- filter / search / sort ---------- */
    function sortList(list, mode) {
        var rank = { Ongoing: 0, Upcoming: 1, Completed: 2, Cancelled: 2 };
        return list.slice().sort(function (a, b) {
            if (mode === "newest") return b.createdAt.localeCompare(a.createdAt);
            if (mode === "oldest") return a.createdAt.localeCompare(b.createdAt);
            if (mode === "updated") return b.updatedAt.localeCompare(a.updatedAt);
            var sa = getStatus(a), sb = getStatus(b);          // "soonest"
            if (rank[sa] !== rank[sb]) return rank[sa] - rank[sb];
            var diff = eventStart(a) - eventStart(b);
            return rank[sa] === 2 ? -diff : diff;               // finished events: newest first
        });
    }
    function value(id) { var el = $(id); return el ? el.value : ""; }

    // true when the person is searching or using any filter (the sort order does not count)
    function isFiltering() {
        return currentFilter !== "All" || value("searchInput").trim() !== "" ||
               value("categoryFilter") !== "" || value("dateFilter") !== "" || value("publishFilter") !== "";
    }

    function filteredList() {
        var search = value("searchInput").trim().toLowerCase();
        var cat = value("categoryFilter"), date = value("dateFilter"), pub = value("publishFilter");
        var list = announcements.filter(canSee).filter(function (a) {
            if (currentFilter !== "All" && getStatus(a) !== currentFilter) return false;
            if (cat && a.category !== cat) return false;
            if (date && a.date !== date) return false;
            if (pub && publishLabel(a) !== pub) return false;
            if (search) {
                var text = (a.title + " " + a.description + " " + a.details + " " + a.venue + " " + a.organizer).toLowerCase();
                if (text.indexOf(search) === -1) return false;
            }
            return true;
        });
        return sortList(list, value("sortSelect") || "soonest");
    }

    /* ---------- drawing the page ---------- */
    function setText(id, text) { var el = $(id); if (el) el.textContent = text; }

    function renderUpcoming() {
        var list = announcements.filter(canSee).filter(function (a) {
            var s = getStatus(a);
            return s === "Upcoming" || s === "Ongoing";
        }).sort(function (a, b) { return eventStart(a) - eventStart(b); }).slice(0, 6);
        $("upcomingGrid").innerHTML = list.map(buildUpcomingCard).join("");
        $("upcomingEmpty").classList.toggle("d-none", list.length > 0);
        $("viewAllUpcomingBtn").classList.toggle("d-none", list.length === 0);
    }

    function renderCounts() {
        var visible = announcements.filter(canSee);
        var c = { Upcoming: 0, Ongoing: 0, Completed: 0, Cancelled: 0 };
        visible.forEach(function (a) { c[getStatus(a)]++; });
        setText("countAll", visible.length);
        setText("countUpcoming", c.Upcoming);
        setText("countOngoing", c.Ongoing);
        setText("countCompleted", c.Completed);
        setText("countCancelled", c.Cancelled);
        var bell = $("bellCount");
        if (bell) {
            var n = visible.filter(function (a) { return a.publishState === "Published" && !a.cancelled; })
                .filter(function (a) { var s = getStatus(a); return s === "Upcoming" || s === "Ongoing"; }).length;
            bell.textContent = n;
            bell.style.display = n === 0 ? "none" : "";
        }
    }

    function render() {
        var shown = filteredList();
        var filtering = isFiltering();
        $("announcementGrid").innerHTML = shown.map(buildCard).join("");
        // while filtering, hide "Upcoming Events" so the results show right under the filter bar
        $("upcomingSection").classList.toggle("d-none", filtering);
        setText("resultsTitle", filtering
            ? (currentFilter !== "All" ? currentFilter + " Programs & Activities" : "Search Results") + " (" + shown.length + ")"
            : "All Programs & Activities");
        var empty = $("emptyState");
        empty.classList.toggle("d-none", shown.length > 0);
        if (shown.length === 0) {
            var none = announcements.filter(canSee).length === 0;
            setText("emptyTitle", none ? "No announcements yet" : "No matching announcements");
            setText("emptyText", none
                ? (OFFICIAL ? "Click \u201cAdd Announcement\u201d to post the first one." : "Please check back soon for news and events.")
                : "Try a different search or filter.");
        }
        renderUpcoming();
        renderCounts();
        lastSig = signature();
        updateCountdowns();
    }

    function signature() {
        return announcements.map(function (a) { return a.id + getStatus(a) + (canSee(a) ? 1 : 0); }).join("|");
    }

    /* ---------- details window ---------- */
    function injectModals() {
        document.body.insertAdjacentHTML("beforeend",
            '<div class="modal fade" id="detailsModal" tabindex="-1" aria-labelledby="detailTitle" aria-hidden="true">' +
              '<div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable"><div class="modal-content">' +
                '<button type="button" class="btn-close position-absolute top-0 end-0 m-3 bg-white rounded-circle p-2" style="z-index:5" data-bs-dismiss="modal" aria-label="Close"></button>' +
                '<div class="modal-body p-0" id="detailBody"></div>' +
                '<div class="modal-footer" id="detailFooter"></div>' +
              '</div></div></div>' +
            '<div id="posterLightbox" class="d-none" role="dialog" aria-label="Event poster">' +
              '<button type="button" class="btn btn-light" id="closePosterBtn"><i class="bi bi-x-lg me-1"></i> Close</button>' +
              '<img id="posterFull" alt="Event poster">' +
            '</div>');
    }

    function renderDetails(a) {
        var status = getStatus(a);
        var html = '<div class="detail-banner">' + imageOrPlaceholder(a) + '</div><div class="p-4">';
        html += '<div class="d-flex flex-wrap align-items-center gap-2 mb-2">' + statusBadge(status, true) +
            '<span class="mini-badge">' + escapeHtml(a.category) + '</span>' +
            (OFFICIAL ? '<span class="mini-badge">' + publishLabel(a) + '</span>' : "") + '</div>';
        html += '<h2 class="h4 fw-bold mb-3" id="detailTitle">' + escapeHtml(a.title) + '</h2>';

        if (status === "Upcoming") {
            html += '<div class="cd-panel mb-4"><div class="fw-bold small mb-2"><i class="bi bi-hourglass-split me-1"></i>Event starts in:</div>' +
                '<div class="ann-countdown cd-live" data-start="' + eventStart(a).getTime() + '">' +
                ["d:Days", "h:Hours", "m:Minutes", "s:Seconds"].map(function (u) {
                    var p = u.split(":");
                    return '<div class="cd-box"><span class="cd-num" data-u="' + p[0] + '">00</span><span class="cd-lbl">' + p[1] + '</span></div>';
                }).join("") + '</div></div>';
        } else if (status === "Ongoing") {
            html += '<div class="cd-panel mb-4"><strong><i class="bi bi-broadcast me-1"></i>Happening now</strong>' +
                (a.endTime ? ' \u2013 ends at ' + formatTime(a.endTime) : ' \u2013 until the end of the day') + '</div>';
        } else if (status === "Cancelled") {
            html += '<div class="alert alert-danger"><i class="bi bi-x-octagon me-1"></i>This event has been cancelled.</div>';
        } else {
            html += '<div class="cd-panel mb-4 text-muted"><i class="bi bi-check-circle me-1"></i>This event has ended.</div>';
        }

        html += '<div class="row g-3 mb-4">' +
            '<div class="col-md-6">' + metaRow("bi-calendar-event", formatDate(a.date)) + '</div>' +
            '<div class="col-md-6">' + metaRow("bi-clock", timeRange(a)) + '</div>' +
            '<div class="col-md-6">' + metaRow("bi-geo-alt", a.venue) + '</div>' +
            '<div class="col-md-6">' + metaRow("bi-person-badge", a.organizer) + '</div>' +
            (a.contact ? '<div class="col-12">' + metaRow("bi-telephone", a.contact) + '</div>' : "") + '</div>';

        html += '<h3 class="h6 fw-bold">About this announcement</h3><p class="detail-text">' + escapeHtml(a.details || a.description) + '</p>';
        if (a.notes) {
            html += '<div class="alert alert-warning mt-3 mb-0"><strong>Additional notes</strong><br><span class="detail-text">' + escapeHtml(a.notes) + '</span></div>';
        }
        html += '</div>';
        $("detailBody").innerHTML = html;
        $("detailFooter").innerHTML = '<button type="button" class="btn btn-outline-success" data-bs-dismiss="modal">Close</button>' +
            (OFFICIAL ? '<button type="button" class="btn btn-success" data-act="edit" data-id="' + escapeHtml(a.id) + '"><i class="bi bi-pencil-square me-1"></i> Edit</button>' : "");
        updateCountdowns();
    }

    function openDetails(id) {
        var a = find(id);
        if (!a || !canSee(a)) return;
        viewingId = id;
        renderDetails(a);
        bootstrap.Modal.getOrCreateInstance($("detailsModal")).show();
    }
    function closeDetails() { bootstrap.Modal.getOrCreateInstance($("detailsModal")).hide(); }

    function closePoster() { $("posterLightbox").classList.add("d-none"); }

    /* ---------- events ---------- */
    function setFilter(name) {
        currentFilter = name;
        document.querySelectorAll("#filterPills .filter-pill").forEach(function (b) {
            b.classList.toggle("active", b.dataset.filter === name);
        });
        render();
    }

    function init() {
        load();
        injectModals();
        var cat = $("categoryFilter");
        CATEGORIES.forEach(function (c) { var o = document.createElement("option"); o.value = c; o.textContent = c; cat.appendChild(o); });

        ["searchInput", "categoryFilter", "dateFilter", "sortSelect", "publishFilter"].forEach(function (id) {
            var el = $(id);
            if (el) { el.addEventListener("input", render); el.addEventListener("change", render); }
        });
        document.querySelectorAll("#filterPills .filter-pill").forEach(function (b) {
            b.addEventListener("click", function () { setFilter(b.dataset.filter); });
        });
        $("resetFiltersBtn").addEventListener("click", function () {
            ["searchInput", "categoryFilter", "dateFilter", "publishFilter"].forEach(function (id) { var el = $(id); if (el) el.value = ""; });
            $("sortSelect").value = "soonest";
            setFilter("All");
        });
        $("viewAllUpcomingBtn").addEventListener("click", function () {
            setFilter("Upcoming");
            $("allAnnouncementsHead").scrollIntoView({ behavior: "smooth" });
        });

        document.addEventListener("click", function (e) {
            if (e.target.closest("#closePosterBtn") || e.target.id === "posterLightbox") { closePoster(); return; }
            var poster = e.target.closest("img[data-poster]");
            if (poster) { $("posterFull").src = poster.src; $("posterLightbox").classList.remove("d-none"); return; }
            var b = e.target.closest("[data-act]");
            if (!b || b.disabled) return;
            var act = b.dataset.act, id = b.dataset.id;
            if (act === "view") openDetails(id);
            else if (actions[act]) actions[act](id);
        });
        document.addEventListener("keydown", function (e) {
            if (e.key === "Escape" && !$("posterLightbox").classList.contains("d-none")) {
                e.stopPropagation(); closePoster(); return;
            }
            if ((e.key === "Enter" || e.key === " ") && e.target.classList && e.target.classList.contains("up-card")) {
                e.preventDefault(); openDetails(e.target.dataset.id);
            }
        }, true);

        // a picture that fails to load never leaves a broken image
        document.addEventListener("error", function (e) {
            var img = e.target;
            if (!img || img.tagName !== "IMG" || !img.classList.contains("ann-img")) return;
            if (img.dataset.fallback === "remove") {
                var box = img.closest(".poster-box");
                if (box) box.remove();
            } else {
                img.outerHTML = placeholder();
            }
        }, true);

        $("detailsModal").addEventListener("hidden.bs.modal", function () { viewingId = null; });

        // update everything when another tab changes the data
        window.addEventListener("storage", function (e) {
            if (e.key === ANN_KEY) { load(); render(); if (viewingId) { var a = find(viewingId); if (a && canSee(a)) renderDetails(a); else closeDetails(); } }
        });

        // every second: countdowns tick, and statuses change by themselves
        setInterval(function () {
            if (signature() !== lastSig) {
                render();
                if (viewingId) { var a = find(viewingId); if (a) renderDetails(a); }
            } else {
                updateCountdowns();
            }
        }, 1000);

        render();
    }

    window.AnnCore = {
        mode: MODE, categories: CATEGORIES, actions: actions,
        get data() { return announcements; },
        get viewingId() { return viewingId; },
        find: find, save: save, render: render, toast: toast, esc: escapeHtml,
        todayISO: todayISO, getStatus: getStatus, openDetails: openDetails, closeDetails: closeDetails
    };

    document.addEventListener("DOMContentLoaded", init);
})();

</script>

<script>
/* =========================================================
   OFFICIAL TOOLS: add, edit, publish, cancel event, delete
   (the cards, filters, countdown and details window are in the shared script above)
   ========================================================= */
(function () {

    if (window.ANN_MODE !== "official") return;     // public view = view only

    const Core = window.AnnCore;
    const $ = function (id) { return document.getElementById(id); };

    const formModal    = new bootstrap.Modal($("announcementModal"));
    const confirmModal = new bootstrap.Modal($("confirmModal"));

    const ALLOWED_TYPES = ["image/jpeg", "image/png", "image/webp"];
    const MAX_FILE_SIZE = 5 * 1024 * 1024;       // 5 MB

    const images = { banner: "" };   // pictures (Base64) shown in the form
    let editingState = "new";                    // new / Draft / Unpublished / Published
    let onConfirm = null;                        // what the confirm window does when OK is clicked

    /* ---------- dropdown lists: add or remove choices here ---------- */
    const VENUES = ["Barangay Hall", "SK Office", "Barangay Covered Court", "Barangay Health Center",
                    "Barangay Plaza", "School Gym / Campus", "Online (Facebook / Zoom)"];
    const ORGANIZERS = ["SK Chairman", "SK Secretary", "SK Treasurer", "SK Kagawad"];

    function fillPick(id, list, placeholder) {
        const sel = $(id);
        sel.innerHTML = "";
        [["", placeholder]].concat(list.map(function (t) { return [t, t]; })).concat([["Other", "Other (specify)"]])
            .forEach(function (p) {
                const o = document.createElement("option");
                o.value = p[0]; o.textContent = p[1];
                sel.appendChild(o);
            });
        sel.addEventListener("change", function () { toggleOther(id); });
    }
    function toggleOther(id) { $(id + "OtherBox").classList.toggle("d-none", $(id).value !== "Other"); }
    function getPick(id) { return $(id).value === "Other" ? $(id + "Other").value.trim() : $(id).value; }
    function setPick(id, value) {
        const sel = $(id);
        const known = Array.prototype.some.call(sel.options, function (o) { return o.value === value && value !== "Other"; });
        $(id + "Other").value = "";
        if (!value) sel.value = "";
        else if (known) sel.value = value;
        else { sel.value = "Other"; $(id + "Other").value = value; }
        toggleOther(id);
    }
    fillPick("annVenue", VENUES, "Select venue / location");
    fillPick("annOrganizer", ORGANIZERS, "Select organizer");

    // category dropdown (same list as the filter)
    Core.categories.forEach(function (c) {
        const o = document.createElement("option");
        o.value = c; o.textContent = c;
        $("annCategory").appendChild(o);
    });

    /* ---------- pictures: upload, preview, replace, remove ---------- */
    function showImage(kind) {
        const box = $(kind + "Preview");
        const removeBtn = $("removeBannerBtn");
        if (images[kind]) {
            box.innerHTML = '<img src="' + images[kind] + '" alt="' + kind + ' preview">';
            removeBtn.classList.remove("d-none");
        } else {
            box.innerHTML = '<span><i class="bi bi-image fs-3 d-block"></i>No banner selected</span>';
            removeBtn.classList.add("d-none");
        }
    }

    function imageError(kind, message) {
        const el = $(kind + "Error");
        el.textContent = message;
        el.classList.toggle("d-none", !message);
    }

    // shrink the picture so it fits in the browser storage
    function resizeImage(file, maxSide, done, fail) {
        const reader = new FileReader();
        reader.onerror = fail;
        reader.onload = function (event) {
            const image = new Image();
            image.onerror = fail;
            image.onload = function () {
                const scale = Math.min(1, maxSide / Math.max(image.width, image.height));
                const canvas = document.createElement("canvas");
                canvas.width = Math.round(image.width * scale);
                canvas.height = Math.round(image.height * scale);
                const ctx = canvas.getContext("2d");
                ctx.fillStyle = "#ffffff";                       // white behind transparent PNGs
                ctx.fillRect(0, 0, canvas.width, canvas.height);
                ctx.drawImage(image, 0, 0, canvas.width, canvas.height);
                done(canvas.toDataURL("image/jpeg", 0.8));
            };
            image.src = event.target.result;
        };
        reader.readAsDataURL(file);
    }

    function setupImage(kind, maxSide) {
        const cap = "Banner";
        const input = $("ann" + cap);

        input.addEventListener("change", function () {
            const file = input.files[0];
            imageError(kind, "");
            if (!file) return;
            const okType = ALLOWED_TYPES.indexOf(file.type) !== -1 && /\.(jpe?g|png|webp)$/i.test(file.name);
            if (!okType) {
                imageError(kind, "That file type is not supported. Please choose a JPG, JPEG, PNG or WEBP image.");
                input.value = "";
                return;
            }
            if (file.size > MAX_FILE_SIZE) {
                imageError(kind, "That image is larger than 5 MB. Please choose a smaller one.");
                input.value = "";
                return;
            }
            resizeImage(file, maxSide, function (dataUrl) {
                images[kind] = dataUrl;                          // choosing a new file replaces the old one
                showImage(kind);
            }, function () {
                imageError(kind, "This image could not be read. Please try a different file.");
                input.value = "";
            });
        });

        $("remove" + cap + "Btn").addEventListener("click", function () {
            images[kind] = "";
            input.value = "";
            imageError(kind, "");
            showImage(kind);
        });
    }
    setupImage("banner", 1200);

    /* ---------- form ---------- */
    function setFooterButtons(state) {
        editingState = state;
        const draftBtn = $("saveDraftBtn");
        const mainLabel = $("saveBtn").querySelector("span");
        if (state === "Published") {
            draftBtn.classList.add("d-none");
            mainLabel.textContent = "Save Changes";
        } else {
            draftBtn.classList.remove("d-none");
            draftBtn.querySelector("span").textContent = state === "Unpublished" ? "Save as Unpublished" : "Save as Draft";
            mainLabel.textContent = "Publish Announcement";
        }
    }

    function resetForm() {
        $("announcementForm").reset();
        $("announcementForm").querySelectorAll(".is-invalid").forEach(function (el) { el.classList.remove("is-invalid"); });
        $("annId").value = "";
        images.banner = "";
        $("annBanner").value = "";
        imageError("banner", "");
        showImage("banner");
        toggleOther("annVenue"); toggleOther("annOrganizer");
    }

    function openAdd() {
        resetForm();
        $("formModalTitle").textContent = "Add Announcement";
        setFooterButtons("new");
        formModal.show();
    }

    function openEdit(id) {
        const a = Core.find(id);
        if (!a) return;
        Core.closeDetails();
        resetForm();
        $("annId").value = a.id;
        $("annTitle").value = a.title;
        $("annCategory").value = a.category;
        $("annDescription").value = a.description;
        $("annDetails").value = a.details;
        $("annDate").value = a.date;
        $("annStart").value = a.startTime;
        $("annEnd").value = a.endTime;
        setPick("annVenue", a.venue);
        setPick("annOrganizer", a.organizer);
        $("annContact").value = a.contact;
        $("annNotes").value = a.notes;
        $("annEventStatus").value = a.cancelled ? "Cancelled" : "Automatic";
        $("annPublishDate").value = a.publishDate;
        images.banner = a.banner;
        showImage("banner");
        $("formModalTitle").textContent = "Edit Announcement";
        setFooterButtons(a.publishState);
        // wait for the details window to close before opening this one
        setTimeout(function () { formModal.show(); }, 250);
    }

    function validate() {
        let firstBad = null;
        function bad(id, message) {
            const el = $(id);
            el.classList.add("is-invalid");
            if (message && el.nextElementSibling && el.nextElementSibling.classList.contains("invalid-feedback")) {
                el.nextElementSibling.textContent = message;
            }
            if (!firstBad) firstBad = el;
        }
        $("announcementForm").querySelectorAll(".is-invalid").forEach(function (el) { el.classList.remove("is-invalid"); });

        ["annTitle", "annDescription", "annDate", "annStart"].forEach(function (id) {
            if (!$(id).value.trim()) bad(id);
        });
        ["annVenue", "annOrganizer"].forEach(function (id) {
            if (!$(id).value) bad(id);
            else if ($(id).value === "Other" && !$(id + "Other").value.trim()) bad(id + "Other");
        });
        if ($("annEnd").value && $("annStart").value && $("annEnd").value <= $("annStart").value) bad("annEnd");

        if (firstBad) { firstBad.scrollIntoView({ block: "center", behavior: "smooth" }); firstBad.focus({ preventScroll: true }); }
        return !firstBad;
    }

    // target = "Draft", "Published" or "Unpublished"
    function saveAnnouncement(target) {
        if (!validate()) return;
        const data = {
            title: $("annTitle").value.trim(),
            category: $("annCategory").value,
            description: $("annDescription").value.trim(),
            details: $("annDetails").value.trim(),
            date: $("annDate").value,
            startTime: $("annStart").value,
            endTime: $("annEnd").value,
            venue: getPick("annVenue"),
            organizer: getPick("annOrganizer"),
            contact: $("annContact").value.trim(),
            notes: $("annNotes").value.trim(),
            banner: images.banner,
            cancelled: $("annEventStatus").value === "Cancelled",
            publishDate: $("annPublishDate").value
        };
        if (target === "Published" && !data.publishDate) data.publishDate = Core.todayISO();

        const now = new Date().toISOString();
        const id = $("annId").value;
        const list = Core.data;
        let message;

        if (id) {
            const a = Core.find(id);
            if (!a) return;
            Object.assign(a, data);
            a.publishState = target;
            a.updatedAt = now;
            message = target === "Published" ? "Announcement saved." : "Announcement saved as " + target.toLowerCase() + ".";
        } else {
            data.id = "ann-" + Date.now();
            data.publishState = target;
            data.createdAt = now;
            data.updatedAt = now;
            list.push(data);
            message = target === "Published" ? "Announcement published." : "Announcement saved as a draft.";
        }

        if (!Core.save()) return;
        formModal.hide();
        Core.render();
        Core.toast(message);
    }

    $("announcementForm").addEventListener("submit", function (e) {
        e.preventDefault();
        saveAnnouncement("Published");              // Publish Announcement / Save Changes
    });
    $("saveDraftBtn").addEventListener("click", function () {
        saveAnnouncement(editingState === "Unpublished" ? "Unpublished" : "Draft");
    });
    $("openAddBtn").addEventListener("click", openAdd);

    /* ---------- confirm window ---------- */
    function ask(options, action) {
        $("confirmIcon").innerHTML = '<i class="bi ' + options.icon + '"></i>';
        $("confirmIcon").style.color = options.danger ? "#dc3545" : "#212529";
        $("confirmTitle").textContent = options.title;
        $("confirmName").textContent = options.name || "";
        $("confirmText").textContent = options.text;
        $("confirmBackBtn").textContent = options.back || "Cancel";
        const ok = $("confirmOkBtn");
        ok.textContent = options.ok;
        ok.className = "btn px-4 fw-bold " + (options.danger ? "btn-danger" : "btn-success");
        onConfirm = action;
        confirmModal.show();
    }
    $("confirmOkBtn").addEventListener("click", function () {
        const run = onConfirm;
        onConfirm = null;
        confirmModal.hide();
        if (run) run();
    });

    /* ---------- card buttons ---------- */
    Core.actions.edit = openEdit;

    Core.actions.delete = function (id) {
        const a = Core.find(id);
        if (!a) return;
        Core.closeDetails();
        ask({ icon: "bi-trash3", danger: true, title: "Delete Announcement?", name: a.title,
              text: "This action cannot be undone.", ok: "Delete" }, function () {
            const list = Core.data;
            list.splice(list.indexOf(a), 1);
            if (Core.save()) { Core.render(); Core.toast("Announcement deleted."); }
        });
    };

    Core.actions.togglePublish = function (id) {
        const a = Core.find(id);
        if (!a) return;
        if (a.publishState === "Published") {
            ask({ icon: "bi-eye-slash", title: "Unpublish Announcement?", name: a.title,
                  text: "It will no longer be visible on the public page. You can publish it again anytime.", ok: "Unpublish" }, function () {
                a.publishState = "Unpublished";
                a.updatedAt = new Date().toISOString();
                if (Core.save()) { Core.render(); Core.toast("Announcement unpublished."); }
            });
        } else {
            a.publishState = "Published";
            if (!a.publishDate) a.publishDate = Core.todayISO();
            a.updatedAt = new Date().toISOString();
            if (Core.save()) {
                Core.render();
                Core.toast(a.publishDate > Core.todayISO() ? "Scheduled for " + a.publishDate + "." : "Announcement published.");
            }
        }
    };

    Core.actions.toggleCancel = function (id) {
        const a = Core.find(id);
        if (!a) return;
        if (a.cancelled) {
            a.cancelled = false;
            a.updatedAt = new Date().toISOString();
            if (Core.save()) { Core.render(); Core.toast("Event restored. Its status follows the schedule again."); }
        } else {
            ask({ icon: "bi-x-octagon", danger: true, title: "Cancel this event?", name: a.title,
                  text: "It will stay visible and be marked Cancelled. You can restore it later.", ok: "Cancel Event", back: "Keep Event" }, function () {
                a.cancelled = true;
                a.updatedAt = new Date().toISOString();
                if (Core.save()) { Core.render(); Core.toast("Event cancelled."); }
            });
        }
    };

})();
</script>

<!-- event sign-up (open to everyone, no account needed) -->
<script src="event-register.js"></script>

</body>
</html>