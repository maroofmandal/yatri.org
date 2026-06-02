<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Japan + South Korea — 15-Day Interactive Trip Plan</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.css"/>
<style>
  :root{
    --ink:#191a23;--muted:#5d6072;--line:#e7e7ef;--bg:#f6f5f2;--card:#fff;
    --accent:#e0345a;--accent2:#2b6cb0;--gold:#c98a2b;--green:#2f8f6b;--purple:#7b2ff7;
    --shadow:0 10px 30px rgba(25,26,35,.08);
  }
  *{box-sizing:border-box}
  body{margin:0;font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Helvetica,Arial,sans-serif;
    color:var(--ink);background:var(--bg);line-height:1.55;-webkit-font-smoothing:antialiased}
  a{color:var(--accent2);text-decoration:none}a:hover{text-decoration:underline}
  .wrap{max-width:1100px;margin:0 auto;padding:0 20px}
  h2{font-size:23px;margin:0 0 4px}.lead{color:var(--muted);font-size:14px;margin:0 0 16px}

  /* CURRENCY BAR */
  .curbar{position:sticky;top:0;z-index:1200;background:rgba(255,255,255,.96);backdrop-filter:blur(8px);
    border-bottom:1px solid var(--line);box-shadow:0 2px 14px rgba(0,0,0,.05)}
  .curbar .wrap{display:flex;align-items:center;justify-content:space-between;padding:9px 20px}
  .curbar .brand{font-weight:800;font-size:14px;letter-spacing:.02em}
  .curbar .brand small{color:var(--muted);font-weight:600}
  .curtoggle{display:flex;gap:0;border:2px solid var(--accent);border-radius:30px;overflow:hidden}
  .curtoggle button{border:none;background:#fff;color:var(--accent);font-weight:800;font-size:13px;
    padding:6px 16px;cursor:pointer;transition:.15s}
  .curtoggle button.on{background:var(--accent);color:#fff}
  .fxnote{font-size:11px;color:var(--muted);margin-left:10px}

  /* HERO */
  header.hero{background:linear-gradient(135deg,#191a23,#2a2140 55%,#7a1f37);color:#fff;padding:48px 0 38px;position:relative;overflow:hidden}
  header.hero::after{content:"";position:absolute;inset:0;background:radial-gradient(900px 380px at 85% -10%,rgba(224,52,90,.35),transparent 60%)}
  .hero .wrap{position:relative;z-index:1}
  .eyebrow{letter-spacing:.22em;text-transform:uppercase;font-size:12px;color:#ffd0dc;font-weight:700;margin:0 0 10px}
  h1{font-size:clamp(28px,5vw,46px);line-height:1.06;margin:0 0 12px;font-weight:800}
  h1 .sub{display:block;font-size:clamp(14px,2.3vw,18px);font-weight:500;color:#e9e7f5;margin-top:10px;opacity:.92}
  .facts{display:flex;flex-wrap:wrap;gap:12px;margin-top:22px}
  .fact{background:rgba(255,255,255,.10);border:1px solid rgba(255,255,255,.16);border-radius:14px;padding:11px 15px;min-width:128px}
  .fact b{display:block;font-size:18px}.fact small{color:#d9d7ea;font-size:11px;text-transform:uppercase;letter-spacing:.07em}

  /* ALERT */
  .alert{background:#fff7ed;border:1px solid #f4d8b0;border-left:5px solid var(--gold);border-radius:12px;padding:15px 18px;margin:24px 0;box-shadow:var(--shadow)}
  .alert h3{margin:0 0 6px;font-size:16px;color:#92560c}
  .alert p{margin:5px 0;font-size:13.5px;color:#5b4321}
  .alert .pill{display:inline-block;background:#fdeccf;color:#8a5a12;border-radius:6px;padding:1px 8px;font-size:12px;font-weight:700;margin-right:6px}

  .block{background:var(--card);border:1px solid var(--line);border-radius:18px;padding:22px;margin:22px 0;box-shadow:var(--shadow)}

  /* MAP */
  #map{height:440px;border-radius:14px;border:1px solid var(--line);z-index:1}
  .maplegend{display:flex;gap:16px;flex-wrap:wrap;align-items:center;margin-top:12px;font-size:13px}
  .maplegend i{display:inline-block;width:22px;height:4px;border-radius:3px;vertical-align:middle;margin-right:6px}
  .leaflet-popup-content{font-size:13px;margin:10px 12px}
  .leaflet-popup-content b{font-size:14px}

  /* TABS */
  .tabs{display:flex;gap:10px;margin:8px 0 6px;flex-wrap:wrap}
  .tab{flex:1;min-width:250px;cursor:pointer;border:2px solid var(--line);background:var(--card);border-radius:16px;padding:14px 16px;transition:.15s;box-shadow:var(--shadow)}
  .tab:hover{border-color:#c9c9d6;transform:translateY(-1px)}
  .tab.active{border-color:var(--accent)}.tab.b.active{border-color:var(--accent2)}
  .tab .tname{font-weight:800;font-size:16px;display:flex;align-items:center;gap:8px;flex-wrap:wrap}
  .tab .tdesc{color:var(--muted);font-size:13px;margin-top:4px}
  .tab .badge{font-size:11px;font-weight:700;color:#fff;background:var(--accent);border-radius:20px;padding:2px 9px}
  .tab.b .badge{background:var(--accent2)}

  .intro{background:#fbfbfd;border:1px solid var(--line);border-radius:14px;padding:14px 18px;margin:12px 0}
  .intro .seq{color:var(--muted);font-size:14px;margin:2px 0 12px}
  .pc{display:grid;grid-template-columns:1fr 1fr;gap:12px}
  .pc div{border-radius:12px;padding:11px 13px;font-size:13px}
  .pc .pro{background:#eef8f2;border:1px solid #cfe9dc}.pc .con{background:#fdeef1;border:1px solid #f6d6de}
  .pc b{display:block;margin-bottom:3px}

  /* FLIGHTS */
  .flights{display:grid;grid-template-columns:1fr 1fr;gap:14px}
  .fcard{border:1px solid var(--line);border-radius:14px;padding:14px 16px;background:#fbfbfd}
  .fcard .leg{font-weight:800;font-size:15px;display:flex;align-items:center;gap:8px}
  .fcard .air{color:var(--muted);font-size:13px;margin:5px 0}
  .fcard .pr{font-size:20px;font-weight:800;color:var(--accent)}
  .fcard .pr small{font-size:12px;color:var(--muted);font-weight:600}
  .fcard a{font-size:12.5px;font-weight:700}
  .tag{display:inline-block;font-size:11px;font-weight:700;border-radius:20px;padding:2px 9px;background:#eef1f8;color:#2b6cb0}
  .tag.direct{background:#e7f6ee;color:#2f8f6b}.tag.stop{background:#fdf0e6;color:#b5701f}

  /* DAY CARDS */
  .day{display:grid;grid-template-columns:150px 1fr;gap:0;background:var(--card);border:1px solid var(--line);border-radius:16px;overflow:hidden;margin:12px 0;box-shadow:var(--shadow)}
  .dside{padding:14px;color:#fff;display:flex;flex-direction:column;justify-content:space-between;min-height:120px}
  .dside .dt{font-size:12px;font-weight:700;opacity:.92}
  .dside .badges{display:flex;flex-direction:column;gap:5px;margin-top:8px;align-items:flex-start}
  .bdg{font-size:10.5px;font-weight:800;color:#fff;border-radius:20px;padding:2px 9px;background:rgba(255,255,255,.22)}
  .dbody{padding:14px 18px}
  .dbody h3{margin:0 0 3px;font-size:17px}
  .dbody .loc{color:var(--muted);font-size:12.5px;margin:0 0 8px;font-weight:600}
  .dbody ul{margin:6px 0 8px;padding-left:18px}.dbody li{margin:3px 0;font-size:14px}
  .review{background:#f4f6fb;border-left:4px solid var(--accent2);border-radius:0 8px 8px 0;padding:7px 11px;font-size:13px;color:#33384a;margin:8px 0}
  .review b{color:var(--accent2)}
  .mlink{font-size:12px;font-weight:700;background:#eef1f8;color:#2b6cb0;border-radius:8px;padding:4px 9px;display:inline-block;margin-top:4px}
  .mlink:hover{background:#e0e7f6;text-decoration:none}

  /* DESTINATIONS */
  .city{border:1px solid var(--line);border-radius:18px;overflow:hidden;margin:18px 0;box-shadow:var(--shadow);background:#fff}
  .chead{padding:16px 20px;color:#fff;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:8px}
  .chead h3{margin:0;font-size:21px}
  .chead .nights{font-size:13px;opacity:.9}
  .chead button{border:1px solid rgba(255,255,255,.6);background:rgba(255,255,255,.15);color:#fff;border-radius:20px;padding:5px 12px;font-size:12px;font-weight:700;cursor:pointer}
  .cbody{padding:18px 20px}
  .chips{display:flex;flex-wrap:wrap;gap:8px;margin-bottom:16px}
  .chip{background:#f3f4f8;border:1px solid #e6e7f0;border-radius:10px;padding:7px 12px;font-size:12.5px}
  .chip b{display:block;font-size:14px;margin-top:1px}
  .subh{font-size:13px;font-weight:800;text-transform:uppercase;letter-spacing:.06em;color:var(--muted);margin:14px 0 8px}
  .hotels{display:grid;grid-template-columns:1fr 1fr 1fr;gap:10px}
  .hcard{border:1px solid var(--line);border-radius:12px;padding:11px 13px;background:#fbfbfd}
  .hcard .hn{font-weight:700;font-size:13.5px;line-height:1.3}
  .hcard .rt{color:var(--gold);font-size:12.5px;font-weight:700;margin:3px 0}
  .hcard .hp{font-size:13px;font-weight:800}.hcard .hp small{color:var(--muted);font-weight:600;font-size:11px}
  .hcard a{font-size:11.5px;font-weight:700}
  .spot{border-top:1px solid var(--line);padding:14px 0}
  .spot:first-of-type{border-top:none}
  .gallery{display:grid;grid-template-columns:1fr 1fr 1fr;gap:6px;margin-bottom:8px}
  .gallery .ph{position:relative;padding-top:66%;border-radius:8px;overflow:hidden;background-size:cover;background-position:center}
  .gallery .ph img{position:absolute;inset:0;width:100%;height:100%;object-fit:cover;display:block}
  .spot h4{margin:2px 0 3px;font-size:16px;display:flex;justify-content:space-between;align-items:baseline;gap:8px;flex-wrap:wrap}
  .spot h4 .ec{font-size:12.5px;font-weight:700;color:var(--green);white-space:nowrap}
  .spot .gr{color:var(--gold);font-weight:700;font-size:12.5px}
  .spot p{margin:5px 0;font-size:13.5px}

  /* IMAGE GALLERY */
  .gallery-nav{display:flex;justify-content:space-between;align-items:center;margin-bottom:8px;padding:0 2px}
  .gbtn{background:rgba(25,26,35,.06);border:1px solid var(--line);border-radius:50%;width:32px;height:32px;
    cursor:pointer;font-size:14px;display:flex;align-items:center;justify-content:center;transition:.15s;color:var(--ink)}
  .gbtn:hover{background:var(--accent);color:#fff;border-color:var(--accent);transform:scale(1.1)}
  .gbtn:active{transform:scale(.95)}
  .gdots{display:flex;gap:5px}
  .gdot{width:7px;height:7px;border-radius:50%;background:#d4d4dc;cursor:pointer;transition:.2s;border:none;padding:0}
  .gdot.on{background:var(--accent);transform:scale(1.3)}
  .gallery{position:relative;border-radius:10px;overflow:hidden;margin-bottom:10px;
    box-shadow:0 2px 12px rgba(25,26,35,.08)}
  .gtrack{display:flex;overflow-x:auto;scroll-snap-type:x mandatory;scroll-behavior:smooth;
    -webkit-overflow-scrolling:touch;scrollbar-width:none;-ms-overflow-style:none}
  .gtrack::-webkit-scrollbar{display:none}
  .gslide{min-width:100%;scroll-snap-align:start;position:relative;aspect-ratio:16/10;overflow:hidden;
    cursor:zoom-in;background:#2a2140}
  .gslide img{width:100%;height:100%;object-fit:cover;display:block;transition:transform .4s ease}
  .gslide:hover img{transform:scale(1.05)}
  .gcap{position:absolute;bottom:0;left:0;right:0;padding:24px 10px 7px;font-size:11px;color:#fff;
    background:linear-gradient(transparent,rgba(0,0,0,.65));pointer-events:none}
  .preview-badge{position:absolute;top:10px;left:10px;background:var(--accent);color:#fff;font-size:10px;
    font-weight:700;text-transform:uppercase;letter-spacing:.05em;padding:4px 10px;border-radius:20px;z-index:2;
    box-shadow:0 2px 8px rgba(0,0,0,.3)}

  /* LIGHTBOX */
  .lb{position:fixed;inset:0;background:rgba(10,10,18,.96);z-index:9999;display:flex;
    align-items:center;justify-content:center;opacity:0;pointer-events:none;transition:opacity .25s ease}
  .lb.open{opacity:1;pointer-events:all}
  .lb img{max-width:92vw;max-height:84vh;object-fit:contain;border-radius:10px;
    box-shadow:0 20px 60px rgba(0,0,0,.5);transition:transform .3s}
  .lb.open img{animation:lbIn .3s ease forwards}
  @keyframes lbIn{from{transform:scale(.92);opacity:0}to{transform:scale(1);opacity:1}}
  .lbx{position:absolute;top:14px;right:18px;color:#fff;font-size:32px;cursor:pointer;
    background:rgba(255,255,255,.08);border:none;width:44px;height:44px;border-radius:50%;
    display:flex;align-items:center;justify-content:center;transition:.15s}
  .lbx:hover{background:var(--accent);transform:rotate(90deg)}
  .lbn{position:absolute;top:50%;transform:translateY(-50%);color:#fff;font-size:28px;cursor:pointer;
    background:rgba(255,255,255,.08);border:none;width:50px;height:50px;border-radius:50%;
    display:flex;align-items:center;justify-content:center;transition:.15s}
  .lbn:hover{background:rgba(255,255,255,.2)}
  .lbn.prev{left:16px}.lbn.next{right:16px}
  .lbc{position:absolute;bottom:18px;left:50%;transform:translateX(-50%);color:#fff;font-size:13px;
    background:rgba(0,0,0,.5);padding:5px 14px;border-radius:20px;backdrop-filter:blur(8px)}
  .lb-cap{position:absolute;bottom:50px;left:50%;transform:translateX(-50%);color:#fff;font-size:14px;
    font-weight:600;background:rgba(0,0,0,.45);padding:6px 16px;border-radius:20px;max-width:80vw;
    text-align:center;backdrop-filter:blur(6px)}

  /* SCROLL REVEAL */
  .rv{opacity:0;transform:translateY(24px);transition:opacity .55s ease,transform .55s ease}
  .rv.vis{opacity:1;transform:translateY(0)}

  /* DAY NAV */
  .dnav{display:flex;gap:5px;flex-wrap:wrap;margin:0 0 14px}
  .dnbtn{font-size:11px;font-weight:700;padding:4px 10px;border-radius:18px;border:1.5px solid var(--line);
    background:var(--card);cursor:pointer;transition:.15s;color:var(--muted);white-space:nowrap}
  .dnbtn:hover,.dnbtn.on{background:var(--accent);color:#fff;border-color:var(--accent)}

  /* BACK TO TOP */
  .btt{position:fixed;bottom:24px;right:24px;width:44px;height:44px;border-radius:50%;
    background:var(--accent);color:#fff;border:none;font-size:20px;cursor:pointer;z-index:1100;
    box-shadow:0 4px 16px rgba(224,52,90,.35);opacity:0;transform:translateY(20px);
    transition:opacity .3s,transform .3s;display:flex;align-items:center;justify-content:center}
  .btt.show{opacity:1;transform:translateY(0)}
  .btt:hover{transform:translateY(-3px);box-shadow:0 6px 20px rgba(224,52,90,.5)}

  .budget-row{display:flex;align-items:center;gap:12px;margin:7px 0}
  .budget-row .lbl{width:150px;font-weight:700;font-size:13.5px}
  .bar{flex:1;background:#eee;border-radius:8px;height:20px;overflow:hidden}
  .bar span{display:block;height:100%;border-radius:8px;background:linear-gradient(90deg,#e0345a,#7b2ff7)}
  .budget-row .amt{width:180px;text-align:right;font-size:13px;color:var(--muted)}
  .total{font-size:14.5px;margin-top:12px;padding-top:12px;border-top:1px dashed var(--line)}
  .twocol{display:grid;grid-template-columns:1fr 1fr;gap:14px}
  .twocol .tt{border:1px solid var(--line);border-radius:12px;padding:14px;background:#fbfbfd}
  .twocol .tt b.h{display:block;font-size:15px;margin-bottom:6px}
  .twocol .big{font-size:24px;font-weight:800;color:var(--accent)}

  .cols{display:grid;grid-template-columns:1fr 1fr;gap:20px}
  .cols h4{margin:0 0 8px;font-size:15px}
  .chk{list-style:none;padding:0;margin:0}
  .chk li{padding:4px 0 4px 24px;position:relative;font-size:13.5px;border-bottom:1px solid #f1f1f6}
  .chk li::before{content:"";position:absolute;left:2px;top:9px;width:13px;height:13px;border:2px solid #c2c4d2;border-radius:4px}
  .dos li::before{content:"✓";border:none;color:var(--green);font-weight:900;left:2px;top:2px}
  .donts li::before{content:"✕";border:none;color:var(--accent);font-weight:900;left:2px;top:2px}
  .timeline div{border-left:3px solid var(--accent2);padding:4px 0 12px 16px;margin-left:6px;position:relative}
  .timeline div::before{content:"";position:absolute;left:-8px;top:6px;width:12px;height:12px;border-radius:50%;background:var(--accent2)}
  .timeline b{font-size:13.5px}.timeline small{display:block;color:var(--muted);font-size:12.5px;margin-top:3px}

  footer{padding:26px 0 60px;color:var(--muted);font-size:12px}
  footer a{color:var(--muted);text-decoration:underline}
  .hide{display:none}
  @media(max-width:760px){
    .flights,.pc,.twocol,.cols{grid-template-columns:1fr}
    .hotels{grid-template-columns:1fr}
    .day{grid-template-columns:1fr}.dside{flex-direction:row;align-items:center;min-height:auto}
    .dside .badges{flex-direction:row;flex-wrap:wrap;margin:0}
    .budget-row{flex-wrap:wrap}.budget-row .amt{width:auto}
    .gbtn{width:28px;height:28px;font-size:12px}
    .lbn{width:40px;height:40px;font-size:22px}
    .lbn.prev{left:8px}.lbn.next{right:8px}
    .dnav{gap:4px}.dnbtn{font-size:10px;padding:3px 8px}
    .tbtn{width:34px;height:34px;font-size:16px}
  }

  /* DARK MODE */
  [data-theme="dark"]{--ink:#e8e6f0;--muted:#9a98ab;--line:#2e2d3a;--bg:#13121c;--card:#1c1b28}
  [data-theme="dark"] .curbar{background:rgba(19,18,28,.96)}
  [data-theme="dark"] .curtoggle{border-color:var(--accent)}
  [data-theme="dark"] .curtoggle button{background:var(--card);color:var(--accent)}
  [data-theme="dark"] .curtoggle button.on{background:var(--accent);color:#fff}
  [data-theme="dark"] .block{background:var(--card);border-color:var(--line)}
  [data-theme="dark"] .fcard,[data-theme="dark"] .hcard,[data-theme="dark"] .twocol .tt{background:#1a1926;border-color:var(--line)}
  [data-theme="dark"] .intro{background:#1a1926;border-color:var(--line)}
  [data-theme="dark"] .pc .pro{background:#14261b;border-color:#1e3d28}
  [data-theme="dark"] .pc .con{background:#261418;border-color:#3d1e26}
  [data-theme="dark"] .review{background:#1a1930;border-color:var(--accent2)}
  [data-theme="dark"] .alert{background:#261e10;border-color:#4a3818;color:#e8d8b0}
  [data-theme="dark"] .alert h3{color:#f0c060}
  [data-theme="dark"] .alert p{color:#c8b888}
  [data-theme="dark"] .chip{background:#1a1926;border-color:var(--line)}
  [data-theme="dark"] .gbtn{background:rgba(255,255,255,.06);border-color:var(--line);color:var(--ink)}
  [data-theme="dark"] .bar{background:#2a2a3a}
  [data-theme="dark"] .chk li{border-color:#2a2a3a}
  [data-theme="dark"] .chk li::before{border-color:#4a4a5a}
  [data-theme="dark"] .dnbtn{background:var(--card);border-color:var(--line);color:var(--muted)}
  [data-theme="dark"] .tab{background:var(--card);border-color:var(--line)}
  [data-theme="dark"] .mlink{background:#1a1930;color:#6ea8e0}
  [data-theme="dark"] .spot{border-color:var(--line)}
  [data-theme="dark"] .city{background:var(--card)}
  [data-theme="dark"] .fcard a,[data-theme="dark"] .hcard a{color:#6ea8e0}
  [data-theme="dark"] .gallery{box-shadow:0 2px 12px rgba(0,0,0,.3)}
  [data-theme="dark"] .preview-badge{background:var(--accent)}
  [data-theme="dark"] .day{background:var(--card);border-color:var(--line)}
  [data-theme="dark"] .dbody h3{color:var(--ink)}
  [data-theme="dark"] .btt{box-shadow:0 4px 16px rgba(224,52,90,.2)}

  /* THEME TOGGLE */
  .tbtn{width:38px;height:38px;border-radius:50%;border:2px solid var(--line);background:var(--card);
    cursor:pointer;font-size:18px;display:flex;align-items:center;justify-content:center;
    transition:.2s;margin-left:10px;flex-shrink:0}
  .tbtn:hover{border-color:var(--accent);transform:scale(1.1)}
</style>
</head>
<body>

<!-- CURRENCY BAR -->
<div class="curbar"><div class="wrap">
  <div class="brand">🗾 Japan + Korea · 15 Days <small>· interactive plan</small></div>
  <div style="display:flex;align-items:center">
    <div class="curtoggle">
      <button id="btnINR" class="on" data-action="currency" data-cur="INR">₹ INR</button>
      <button id="btnUSD" data-action="currency" data-cur="USD">$ USD</button>
    </div>
    <span class="fxnote">1 USD ≈ ₹95</span>
    <button class="tbtn" id="themeToggle" data-action="theme" title="Toggle dark/light mode" aria-label="Toggle theme">🌙</button>
  </div>
</div></div>

<header class="hero"><div class="wrap">
  <p class="eyebrow">Mumbai · Japan · South Korea</p>
  <h1>15 Days Across Japan &amp; Korea
    <span class="sub">Two routes on a live map, every hotel &amp; spot with real costs, best flights, and prices in ₹ or $ — toggle up top. Arrival window 10–17 Oct 2026.</span>
  </h1>
  <div class="facts">
    <div class="fact"><b>15 days</b><small>Mumbai to Mumbai</small></div>
    <div class="fact"><b>9 cities</b><small>Japan + Korea</small></div>
    <div class="fact"><b id="hType">Mid-range</b><small>~<span class="money" data-usd="3400"></span> + flights</small></div>
    <div class="fact"><b>2 routes</b><small>compare on map</small></div>
  </div>
</div></header>

<div class="wrap">

  <div class="alert">
    <h3>⚠️ Visas first — Indian passport</h3>
    <p><span class="pill">JAPAN</span> Visa required (no visa-free). Single-entry tourist <b>eVisa</b> via accredited agency / VFS, ~5 working days. Single-entry is fine — you enter once and exit to Korea.</p>
    <p><span class="pill">KOREA</span> K-ETA does <b>not</b> apply — get a <b>C-3 tourist visa</b> (sticker, via embassy/VFS) and complete the online <b>e-Arrival Card</b> before landing. Apply for both ~6–8 weeks out.</p>
  </div>

  <!-- ROUTE TABS -->
  <div class="block">
    <h2>🧭 Choose your route</h2>
    <p class="lead">Pick a route to highlight it on the map and switch the day-by-day plan below.</p>
    <div class="tabs">
      <div class="tab a active" id="tabA" data-action="trip" data-trip="A">
        <div class="tname">🗾 Trip A — Classic Order <span class="badge">Your route</span></div>
        <div class="tdesc">Tokyo first, Golden Route west, mountains, Fuji, back to Tokyo, finish in Seoul.</div>
      </div>
      <div class="tab b" id="tabB" data-action="trip" data-trip="B">
        <div class="tname">🚄 Trip B — Smooth Sweep <span class="badge">No backtracking</span></div>
        <div class="tdesc">Fly into Osaka, one clean west→east line, Fuji as finale before Tokyo, then Seoul.</div>
      </div>
    </div>

    <div id="map" style="margin-top:14px"></div>
    <div class="maplegend">
      <span><i style="background:#e0345a"></i>Trip A route</span>
      <span><i style="background:#2b6cb0"></i>Trip B route</span>
      <span><i style="background:#888;border-top:2px dashed #888;height:0"></i>✈ dashed = flight leg</span>
      <span style="color:var(--muted)">Tap any city pin for hotel &amp; cost.</span>
    </div>

    <div id="introA" class="intro">
      <div class="seq"><b>Trip A:</b> Mumbai → <b>Tokyo</b> → Kyoto → Hiroshima → Osaka → Takayama → Lake Kawaguchi → <b>Tokyo</b> → Seoul → Mumbai</div>
      <div class="pc">
        <div class="pro"><b>Why you'll like it</b>Tokyo while you're freshest, follows your original order, easy non-stop Mumbai→Tokyo flights. Fuji sits naturally on the way back.</div>
        <div class="con"><b>Trade-off</b>You touch Tokyo twice — a little doubling-back from Kawaguchi into Tokyo before Seoul (minor; Fuji is Tokyo's backyard).</div>
      </div>
    </div>
    <div id="introB" class="intro hide">
      <div class="seq"><b>Trip B:</b> Mumbai → <b>Osaka</b> → Hiroshima → Kyoto → Takayama → Lake Kawaguchi → <b>Tokyo</b> → Seoul → Mumbai</div>
      <div class="pc">
        <div class="pro"><b>Why you'll like it</b>One continuous west→east line, no backtracking, quieter Kansai start, Fuji as the climax, then Tokyo neon before Seoul. Least train time.</div>
        <div class="con"><b>Trade-off</b>Mumbai→Osaka is usually one stop (fewer non-stops), and the big shinkansen legs come early while jet-lagged.</div>
      </div>
    </div>
  </div>

  <!-- FLIGHTS -->
  <div class="block rv">
    <h2>✈️ Best flights &amp; prices</h2>
    <p class="lead">Indicative one-way fares for Oct 2026 (October is a low-demand, cheaper month on these routes). Prices update with your ₹/$ toggle. Tap to check live fares.</p>
    <div class="subh">Trip A flights</div>
    <div class="flights" style="margin-bottom:16px">
      <div class="fcard">
        <div class="leg">Mumbai → Tokyo <span class="tag direct">Non-stop</span></div>
        <div class="air">Air India / ANA · ~7h30 direct</div>
        <div class="pr"><span class="money" data-usd="280"></span> <small>/ one-way</small></div>
        <a href="https://www.google.com/travel/flights?q=flights%20BOM%20to%20HND%20Oct%2010%202026" target="_blank" rel="noopener">Check fares ↗</a>
      </div>
      <div class="fcard">
        <div class="leg">Tokyo → Seoul <span class="tag direct">Non-stop</span></div>
        <div class="air">ANA / Korean Air / Asiana · ~2h30</div>
        <div class="pr"><span class="money" data-usd="140"></span> <small>/ one-way</small></div>
        <a href="https://www.google.com/travel/flights?q=flights%20HND%20to%20ICN%20Oct%2021%202026" target="_blank" rel="noopener">Check fares ↗</a>
      </div>
      <div class="fcard">
        <div class="leg">Seoul → Mumbai <span class="tag stop">1 stop</span></div>
        <div class="air">Korean Air / Asiana / Air India · ~11–14h</div>
        <div class="pr"><span class="money" data-usd="240"></span> <small>/ one-way</small></div>
        <a href="https://www.google.com/travel/flights?q=flights%20ICN%20to%20BOM%20Oct%2024%202026" target="_blank" rel="noopener">Check fares ↗</a>
      </div>
      <div class="fcard" style="background:#fff5f8;border-color:#f6d6de">
        <div class="leg">🧮 Trip A air total</div>
        <div class="air">Mumbai → Tokyo → Seoul → Mumbai (open-jaw)</div>
        <div class="pr"><span class="money" data-usd="660"></span> <small>/ person</small></div>
        <span class="tag">Book Mumbai–Tokyo early for non-stop</span>
      </div>
    </div>
    <div class="subh">Trip B flights</div>
    <div class="flights">
      <div class="fcard">
        <div class="leg">Mumbai → Osaka <span class="tag stop">1 stop</span></div>
        <div class="air">Cathay / Singapore / Thai · ~12–18h via hub</div>
        <div class="pr"><span class="money" data-usd="250"></span> <small>/ one-way</small></div>
        <a href="https://www.google.com/travel/flights?q=flights%20BOM%20to%20KIX%20Oct%2010%202026" target="_blank" rel="noopener">Check fares ↗</a>
      </div>
      <div class="fcard">
        <div class="leg">Tokyo → Seoul <span class="tag direct">Non-stop</span></div>
        <div class="air">ANA / Korean Air / Asiana · ~2h30</div>
        <div class="pr"><span class="money" data-usd="140"></span> <small>/ one-way</small></div>
        <a href="https://www.google.com/travel/flights?q=flights%20HND%20to%20ICN%20Oct%2021%202026" target="_blank" rel="noopener">Check fares ↗</a>
      </div>
      <div class="fcard">
        <div class="leg">Seoul → Mumbai <span class="tag stop">1 stop</span></div>
        <div class="air">Korean Air / Asiana / Air India · ~11–14h</div>
        <div class="pr"><span class="money" data-usd="240"></span> <small>/ one-way</small></div>
        <a href="https://www.google.com/travel/flights?q=flights%20ICN%20to%20BOM%20Oct%2024%202026" target="_blank" rel="noopener">Check fares ↗</a>
      </div>
      <div class="fcard" style="background:#eef5fc;border-color:#cfe0f3">
        <div class="leg">🧮 Trip B air total</div>
        <div class="air">Mumbai → Osaka → Seoul → Mumbai (open-jaw)</div>
        <div class="pr"><span class="money" data-usd="630"></span> <small>/ person</small></div>
        <span class="tag">Slightly cheaper, but no Mumbai non-stop</span>
      </div>
    </div>
  </div>

  <!-- DAY BY DAY -->
  <div class="block rv">
    <h2>🗓️ Day-by-day</h2>
    <p class="lead">Switching the route at the top changes this plan. Balanced pace, 2–3 stops/day.</p>
    <div id="daysA"></div>
    <div id="daysB" class="hide"></div>
  </div>

  <!-- DESTINATIONS -->
  <div class="block rv">
    <h2>📍 Destinations — spots, hotels &amp; costs</h2>
    <p class="lead">Every city with the best-reviewed spots, interactive photo galleries (click to enlarge!), top-rated hotels near them, and estimated costs. Swipe or click arrows to browse photos. All prices follow your ₹/$ toggle.</p>
    <div id="cities"></div>
  </div>

  <!-- BUDGET -->
  <div class="block rv">
    <h2>💰 Budget — mid-range, per person</h2>
    <p class="lead">15 days, excluding international flights (shown above). Tuned to real Japan/Korea 2026 costs.</p>
    <div class="budget-row"><div class="lbl">Accommodation</div><div class="bar"><span style="width:35%"></span></div><div class="amt"><span class="money" data-usd="1190"></span> · ~<span class="money" data-usd="85"></span>/night</div></div>
    <div class="budget-row"><div class="lbl">Food</div><div class="bar"><span style="width:25%"></span></div><div class="amt"><span class="money" data-usd="850"></span> · ~<span class="money" data-usd="57"></span>/day</div></div>
    <div class="budget-row"><div class="lbl">Activities</div><div class="bar"><span style="width:25%"></span></div><div class="amt"><span class="money" data-usd="850"></span> · tickets &amp; tours</div></div>
    <div class="budget-row"><div class="lbl">In-region transport</div><div class="bar"><span style="width:18%"></span></div><div class="amt"><span class="money" data-usd="520"></span> · rail + buses</div></div>
    <div class="budget-row"><div class="lbl">Misc / SIM / gifts</div><div class="bar"><span style="width:10%"></span></div><div class="amt"><span class="money" data-usd="170"></span></div></div>
    <div class="total"><b>In-trip ≈ <span class="money" data-usd="3400"></span> / person</b> (~<span class="money" data-usd="226"></span>/day). A 14-day nationwide JR Pass (¥80,000) does <b>not</b> pay off on this route — buy point-to-point shinkansen tickets instead.</div>
    <div class="twocol" style="margin-top:16px">
      <div class="tt"><b class="h">Trip A — all-in / person</b><div class="big"><span class="money" data-usd="4060"></span></div><small style="color:var(--muted)">In-trip <span class="money" data-usd="3400"></span> + flights <span class="money" data-usd="660"></span></small></div>
      <div class="tt"><b class="h">Trip B — all-in / person</b><div class="big"><span class="money" data-usd="4030"></span></div><small style="color:var(--muted)">In-trip <span class="money" data-usd="3400"></span> + flights <span class="money" data-usd="630"></span></small></div>
    </div>
  </div>

  <!-- PACKING + CULTURE -->
  <div class="block rv">
    <h2>🎒 Packing — mid-October (10–20°C)</h2>
    <div class="cols">
      <div><h4>🧳 Essentials</h4><ul class="chk">
        <li>Passport (6+ mo, 2 blank pages)</li>
        <li>Japan eVisa notice on phone + Korea C-3 visa</li>
        <li>Korea e-Arrival Card confirmation</li>
        <li>Travel insurance + all confirmations offline</li>
        <li>Suica/ICOCA (Japan) &amp; T-money (Seoul) IC cards</li>
        <li>Power bank, eSIM, plug adapters (JP 100V / KR Type-C)</li>
      </ul></div>
      <div><h4>👕 Clothing &amp; day kit</h4><ul class="chk">
        <li>Warm mid-layer + packable down jacket</li>
        <li>Long sleeves, a sweater, comfy pants</li>
        <li>Very comfortable walking shoes (10k+ steps/day)</li>
        <li>Scarf/beanie for cold Fuji &amp; Takayama mornings</li>
        <li>Compact umbrella; one smart dinner outfit</li>
        <li>Slip-on shoes (you'll remove them often)</li>
      </ul></div>
    </div>
  </div>

  <div class="block rv">
    <h2>🙏 Culture — do's &amp; don'ts</h2>
    <div class="cols">
      <div><h4>🇯🇵 Japan</h4>
        <ul class="chk dos"><li>Stand left on Tokyo escalators (right in Osaka)</li><li>Carry a trash bag — bins are rare</li><li>Slurp noodles; cash goes in the tray</li><li>Remove shoes at ryokan, temples, some eateries</li></ul>
        <ul class="chk donts"><li>Don't tip — it confuses staff</li><li>Don't eat/talk loudly on trains</li><li>Don't photograph or chase geisha in Gion</li><li>Don't stick chopsticks upright in rice</li></ul>
      </div>
      <div><h4>🇰🇷 South Korea</h4>
        <ul class="chk dos"><li>Pour for elders with two hands</li><li>Tap T-money in and out on transit</li><li>Enjoy free banchan side-dish refills</li><li>Be quiet at palaces &amp; memorials</li></ul>
        <ul class="chk donts"><li>Don't tip; not expected</li><li>Don't write names in red ink</li><li>Don't eat before the eldest starts</li><li>Don't blow your nose at the table</li></ul>
      </div>
    </div>
  </div>

  <div class="block rv">
    <h2>🗓️ Pre-trip countdown</h2>
    <div class="timeline">
      <div><b>8 weeks before (mid-Aug)</b><small>Book international flights. Start Japan eVisa &amp; Korea C-3 visa — gather bank statements, ITR, itinerary, hotel bookings.</small></div>
      <div><b>6 weeks before</b><small>Submit both visas. Book hotels/ryokan (Takayama &amp; Kawaguchiko sell out in foliage season). Grab teamLab Planets tickets when your dates open.</small></div>
      <div><b>1 month before</b><small>Book Tokyo→Seoul flight, shinkansen seats &amp; the Hida limited express. Buy an eSIM. (Ghibli Museum Mitaka is closed Oct 2026 — skip it.)</small></div>
      <div><b>2 weeks before</b><small>Confirm everything, complete the Korea e-Arrival Card, exchange some yen + won, download offline maps + Google Translate.</small></div>
      <div><b>1 week before</b><small>Web check-in, load Suica/ICOCA + T-money, screenshot confirmations, pack layers, charge devices.</small></div>
    </div>
  </div>

  <footer>
    <p><b>How this was built:</b> spot picks &amp; review snippets distilled from current Google Maps / TripAdvisor reviews and Japan/Korea guides; flights, FX (≈₹95/$), JR Pass &amp; visa facts verified against June 2026 sources; itinerary, budget, packing &amp; timeline use the <i>travel-planner</i> skill. Photos are illustrative (LoremFlickr / Creative Commons) and load live, with coloured fallbacks. Map © OpenStreetMap contributors, Leaflet. Hotel ratings are indicative — tap the map link for live Google ratings &amp; reviews.</p>
  </footer>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.js"></script>
<script>
/* ===== CURRENCY ===== */
var RATE=95, CUR='INR';
function inrGroup(n){var s=Math.round(n).toString();var l=s.length;if(l<=3)return s;
  var last3=s.slice(l-3),rest=s.slice(0,l-3);return rest.replace(/\B(?=(\d{2})+(?!\d))/g,",")+","+last3;}
function fmt(usd){usd=+usd;if(CUR==='USD')return '$'+usd.toLocaleString('en-US');return '₹'+inrGroup(usd*RATE);}
function applyCurrency(){document.querySelectorAll('.money').forEach(function(e){e.textContent=fmt(e.dataset.usd);});}
function setCurrency(c){CUR=c;document.getElementById('btnINR').classList.toggle('on',c==='INR');
  document.getElementById('btnUSD').classList.toggle('on',c==='USD');applyCurrency();}

/* ===== IMAGES & GALLERY ===== */
var GRADS=['#33384a,#7a1f37','#1f3a5f,#2b6cb0','#3a2150,#7b2ff7','#1f4d3a,#2f8f6b','#5a3a12,#c98a2b'];
var GALDATA=[];
function spotImgs(tags,base){var r=[];var seed=tags.replace(/[^a-z]/g,'')+(base||0);
  for(var i=0;i<4;i++)r.push('https://picsum.photos/seed/'+seed+'abc'+i+'/600/400');return r;}
function gmaps(q){return 'https://www.google.com/maps/search/?api=1&query='+encodeURIComponent(q);}
function regGal(imgs){GALDATA.push(imgs);return GALDATA.length-1;}
function spotGal(spotId,imgs,name,ci,hasPreview){
  var gIdx=regGal(imgs);
  var h='<div class="gallery" id="gal-'+ci+'-'+spotId+'">';
  h+='<div class="gallery-nav"><button class="gbtn" data-action="gslide" data-gal="'+ci+'-'+spotId+'" data-dir="-1" aria-label="Previous">&#8249;</button>';
  h+='<div class="gdots">';
  for(var d=0;d<imgs.length;d++)h+='<button class="gdot'+(d===0?' on':'')+'" data-action="ggo" data-gal="'+ci+'-'+spotId+'" data-idx="'+d+'"></button>';
  h+='</div><button class="gbtn" data-action="gslide" data-gal="'+ci+'-'+spotId+'" data-dir="1" aria-label="Next">&#8250;</button></div>';
  h+='<div class="gtrack">';
  imgs.forEach(function(u,i){
    h+='<div class="gslide" data-action="lb" data-gidx="'+gIdx+'" data-name="'+name.replace(/"/g,'&quot;')+'" data-idx="'+i+'">';
    if(i===0&&hasPreview) h+='<div class="preview-badge">Preview</div>';
    h+='<img src="'+u+'" loading="lazy" alt="'+name+' photo '+(i+1)+'">';
    h+='<div class="gcap">'+name+' · '+(i+1)+'/'+imgs.length+' · click to enlarge</div></div>';
  });
  return h+'</div></div>';
}
function gSlide(id,dir){var t=document.querySelector('#gal-'+id+' .gtrack');if(!t)return;t.scrollBy({left:dir*t.offsetWidth,behavior:'smooth'});}
function gGo(id,idx){var t=document.querySelector('#gal-'+id+' .gtrack');if(!t)return;t.scrollTo({left:idx*t.offsetWidth,behavior:'smooth'});}
function gSync(id){var t=document.querySelector('#gal-'+id+' .gtrack');if(!t)return;var idx=Math.round(t.scrollLeft/t.offsetWidth);
  document.querySelectorAll('#gal-'+id+' .gdot').forEach(function(d,i){d.classList.toggle('on',i===idx);});}

/* ===== DAY DATA ===== */
var B={anime:'🎮 Anime Day',village:'🏡 Village Day',city:'🌆 City Vibes',culture:'⛩️ Culture',fuji:'🗻 Mt. Fuji',move:'🚄 Travel'};
var GRAD=['#33384a,#7a1f37','#1f3a5f,#2b6cb0','#3a2150,#7b2ff7','#1f4d3a,#2f8f6b','#5a3a12,#c98a2b'];
var tripA=[
 ['Day 1 · Fri 10 Oct','Land in Tokyo','Mumbai ✈ Tokyo',['Direct Mumbai→Tokyo (~7h30). Suica card + eSIM at airport','Check in around Shinjuku / Shibuya','Easy first night in the Shinjuku neon'],'Arriving into Shinjuku\'s neon is the perfect low-effort jet-lag evening.',['move'],'Shinjuku Tokyo'],
 ['Day 2 · Sat 11 Oct','Old Tokyo meets new','Asakusa · Meiji · Shibuya',['Sensō-ji & Nakamise in Asakusa','Meiji Jingu shrine + Harajuku','Shibuya Sky over the Scramble at night'],'"Tokyo\'s two personalities in one walk" — serene shrine to chaos crossing.',['city','culture'],'Senso-ji Temple Asakusa'],
 ['Day 3 · Sun 12 Oct','Anime Day','Akihabara · teamLab · Nakano',['Akihabara: Animate, Radio Kaikan, arcades, a maid café','Nakano Broadway for collector manga & figures','teamLab Planets digital art (pre-book)','Optional Unicorn Gundam, Odaiba'],'Otaku reviewers rate Radio Kaikan + Nakano above tourist shops; teamLab is the must-book.',['anime'],'Akihabara Tokyo'],
 ['Day 4 · Mon 13 Oct','Bullet train to Kyoto','Tokyo → Kyoto · Higashiyama',['Shinkansen Tokyo→Kyoto (~2h15)','Kiyomizu-dera + Sannenzaka lanes','Dusk in Gion\'s teahouse streets'],'Kiyomizu-dera is "magical in autumn"; do Higashiyama before 9am or at sunset.',['move','culture'],'Kiyomizu-dera Kyoto'],
 ['Day 5 · Tue 14 Oct','Torii gates & bamboo','Fushimi Inari · Arashiyama',['Dawn at Fushimi Inari before the buses','Arashiyama bamboo + Tenryū-ji','Golden Kinkaku-ji'],'"Be at Fushimi Inari by 8am" is the most-repeated review tip.',['culture'],'Fushimi Inari Taisha'],
 ['Day 6 · Wed 15 Oct','Hiroshima & remembrance','Kyoto → Hiroshima',['Shinkansen Kyoto→Hiroshima (~2h)','Peace Memorial Park, A-Bomb Dome & Museum','Hiroshima-style okonomiyaki dinner'],'Reviewers call the Peace Museum "moving and essential."',['move','culture'],'Hiroshima Peace Memorial Park'],
 ['Day 7 · Thu 16 Oct','Floating torii → Osaka','Miyajima → Osaka',['Ferry to Miyajima: floating torii & deer','Midday shinkansen to Osaka (~1h30)','Dōtonbori neon, canal cruise, takoyaki'],'"Peace Park lets you reflect; Miyajima uplifts." Dōtonbori is best after dark.',['culture','city'],'Itsukushima Shrine Miyajima'],
 ['Day 8 · Fri 17 Oct','Into the alps','Osaka → Takayama',['Shinkansen to Nagoya + scenic Hida express','Sanmachi Suji Edo streets & sake breweries','Hida beef skewers'],'Travelers call Takayama "Kyoto without the crowds."',['move'],'Sanmachi Suji Takayama'],
 ['Day 9 · Sat 18 Oct','Japan Village Day','Shirakawa-go · Morning market',['Early bus to Shirakawa-gō thatched farmhouses','Shiroyama viewpoint over the valley','Riverside morning market + Hida Folk Village'],'Rated 4.9/5 — "like stepping into a fairy tale" before the buses arrive.',['village'],'Shirakawa-go Gassho Village'],
 ['Day 10 · Sun 19 Oct','To the foot of Fuji','Takayama → Kawaguchiko',['Transfer to the Fuji Five Lakes (~half day)','Ōishi Park on the north shore','Sunset Fuji reflections over the lake'],'"North shore is the money shot" for Fuji mirrored in the water.',['move','fuji'],'Oishi Park Lake Kawaguchi'],
 ['Day 11 · Mon 20 Oct','Fuji morning, Tokyo night','Chūreitō Pagoda → Tokyo',['Dawn at Chūreitō Pagoda (the iconic view)','Mt. Tenjō ropeway panorama','Express back to Tokyo; Ginza shopping'],'"Before 9am without exception" — Fuji clouds over by midday.',['fuji','move'],'Chureito Pagoda Arakurayama Sengen Park'],
 ['Day 12 · Tue 21 Oct','Hop to Seoul','Tokyo ✈ Seoul',['Flight Tokyo→Seoul (~2h30); T-money card','Myeongdong K-beauty & street food','N Seoul Tower at sunset'],'Ride Namsan "just before sunset" for the best city light.',['move','city'],'N Seoul Tower'],
 ['Day 13 · Wed 22 Oct','Palaces & hanok','Gyeongbokgung · Bukchon',['Gyeongbokgung + 10am guard change (hanbok = free entry)','Bukchon Hanok Village early + Insadong','Gwangjang Market food crawl'],'Gwangjang is "where Seoul actually eats." Bukchon best early.',['culture','city'],'Gyeongbokgung Palace'],
 ['Day 14 · Thu 23 Oct','Modern Seoul','Seongsu · Hongdae · Han River',['Seongsu-dong cafés & street art','Hongdae buskers & shopping','Han River cruise + Banpo fountain show'],'Seongsu tops 2026 "cool neighbourhood" reviews.',['city'],'Seongsu-dong Seoul'],
 ['Day 15 · Fri 24 Oct','Fly home','Seoul ✈ Mumbai',['Souvenir run & last Korean breakfast','AREX/limousine bus to Incheon','Seoul→Mumbai flight home'],'Leave a 3-hour buffer — Incheon is big and duty-free is good.',['move'],'Incheon International Airport']
];
var tripB=[
 ['Day 1 · Fri 10 Oct','Land in Osaka','Mumbai ✈ Osaka · Dōtonbori',['Fly Mumbai→Osaka KIX (usually 1 stop). ICOCA + eSIM','Settle near Namba/Shinsaibashi','Ease in at Dōtonbori: Glico, canal lights, takoyaki'],'"Dōtonbori after dark" is the unanimous first-night pick.',['move','city'],'Dotonbori Osaka'],
 ['Day 2 · Sat 11 Oct','Osaka city life','Osaka Castle · Kuromon',['Osaka Castle & park moats','Kuromon Market seafood lunch','Umeda Sky floating garden at dusk'],'Reviewers find Osaka "friendlier and more relaxed than Tokyo."',['city','culture'],'Osaka Castle'],
 ['Day 3 · Sun 12 Oct','Hiroshima & remembrance','Osaka → Hiroshima',['Shinkansen Osaka→Hiroshima (~1h30)','Peace Park, A-Bomb Dome & Museum','Okonomiyaki dinner'],'The Peace Museum is the most-cited emotional highlight.',['move','culture'],'Hiroshima Peace Memorial Park'],
 ['Day 4 · Mon 13 Oct','Floating torii → Kyoto','Miyajima → Kyoto',['Morning ferry to Miyajima','Shinkansen to Kyoto (~2h)','Higashiyama & Gion in the evening'],'The floating torii at high tide is "worth the early start."',['culture','move'],'Itsukushima Shrine Miyajima'],
 ['Day 5 · Tue 14 Oct','Torii gates & bamboo','Fushimi Inari · Arashiyama',['Dawn at Fushimi Inari','Arashiyama bamboo + Tenryū-ji','Golden Kinkaku-ji'],'Fushimi Inari at 8am, bamboo early — both transform without crowds.',['culture'],'Fushimi Inari Taisha'],
 ['Day 6 · Wed 15 Oct','Into the alps','Kyoto → Takayama',['Train via Nagoya + scenic Hida express','Sanmachi Suji streets, sake, Hida beef','Quiet riverside evening'],'"Kyoto without the crowds" — a favourite for slowing down.',['move'],'Sanmachi Suji Takayama'],
 ['Day 7 · Thu 16 Oct','Japan Village Day','Shirakawa-go · Morning market',['Early bus to Shirakawa-gō farmhouses','Shiroyama viewpoint','Takayama morning market + Hida Folk Village'],'Top-rated day trip (4.9/5) — "like a living folk tale."',['village'],'Shirakawa-go Gassho Village'],
 ['Day 8 · Fri 17 Oct','To the foot of Fuji','Takayama → Kawaguchiko',['Transfer to the Fuji Five Lakes (~half day)','Ōishi Park & the north shore','Sunset by the water'],'Reviewers send you to the north shore / Ōishi Park.',['move','fuji'],'Oishi Park Lake Kawaguchi'],
 ['Day 9 · Sat 18 Oct','Fuji finale → Tokyo','Chūreitō Pagoda → Tokyo',['Dawn at Chūreitō Pagoda','Mt. Tenjō ropeway panorama','Express into Tokyo; first city night'],'"Before 9am without exception" — catch Fuji clear, then Tokyo.',['fuji','move'],'Chureito Pagoda Arakurayama Sengen Park'],
 ['Day 10 · Sun 19 Oct','Old Tokyo meets new','Asakusa · Meiji · Shibuya',['Sensō-ji & Nakamise','Meiji Jingu + Harajuku','Shibuya Sky over the Scramble'],'"Tokyo\'s two personalities in one walk."',['city','culture'],'Senso-ji Temple Asakusa'],
 ['Day 11 · Mon 20 Oct','Anime Day','Akihabara · teamLab · Nakano',['Akihabara figures, arcades, maid café','Nakano Broadway for collectors','teamLab Planets (pre-book)'],'Radio Kaikan + Nakano beat the tourist shops; teamLab is the pre-book.',['anime'],'Akihabara Tokyo'],
 ['Day 12 · Tue 21 Oct','Hop to Seoul','Tokyo ✈ Seoul',['Flight Tokyo→Seoul (~2h30); T-money','Myeongdong K-beauty & food','N Seoul Tower at sunset'],'Namsan "just before sunset" for the best panorama.',['move','city'],'N Seoul Tower'],
 ['Day 13 · Wed 22 Oct','Palaces & hanok','Gyeongbokgung · Bukchon',['Gyeongbokgung + 10am guard change','Bukchon early + Insadong','Gwangjang Market food crawl'],'"Where Seoul actually eats" — Gwangjang.',['culture','city'],'Gyeongbokgung Palace'],
 ['Day 14 · Thu 23 Oct','Modern Seoul','Seongsu · Hongdae · Han River',['Seongsu cafés & street art','Hongdae buskers & shopping','Han River cruise + Banpo fountain'],'Seongsu tops 2026 neighbourhood reviews.',['city'],'Seongsu-dong Seoul'],
 ['Day 15 · Fri 24 Oct','Fly home','Seoul ✈ Mumbai',['Souvenir run & breakfast','AREX/limousine to Incheon','Seoul→Mumbai flight home'],'Leave a 3-hour buffer — Incheon is big.',['move'],'Incheon International Airport']
];
function renderDays(arr,id){var el=document.getElementById(id);el.innerHTML=arr.map(function(x,i){
  return '<div class="day rv"><div class="dside" style="background:linear-gradient(135deg,'+GRAD[i%GRAD.length]+')">' +
    '<div class="dt">'+x[0]+'</div><div class="badges">'+x[5].map(function(b){return '<span class="bdg">'+B[b]+'</span>';}).join('')+'</div></div>'+
    '<div class="dbody"><h3>'+x[1]+'</h3><p class="loc">'+x[2]+'</p><ul>'+x[3].map(function(p){return '<li>'+p+'</li>';}).join('')+'</ul>'+
    '<div class="review"><b>From the reviews —</b> '+x[4]+'</div>'+
    '<a class="mlink" target="_blank" rel="noopener" href="'+gmaps(x[6])+'">📍 Open in Google Maps</a></div></div>';
}).join('');}
renderDays(tripA,'daysA');renderDays(tripB,'daysB');

/* ===== CITY / SPOT / HOTEL DATA ===== */
/* hotel: [name, rating, usd/night, mapQuery] ; spot: [name, gRating, entryUSD, review, tags, [locks], mapQuery, previewImg] */
var CITIES=[
 {name:'Tokyo',coord:[35.6762,139.6503],color:'#7a1f37',nights:'3 nights (Trip A) · 2 (Trip B)',
  chips:[['🏨 Hotel/night',95],['🍜 Food/day',55],['🎟️ Activities',70],['🚆 Metro/3d',18]],
  hotels:[['Hotel Century Southern Tower',4.5,130,'Hotel Century Southern Tower Shinjuku'],['JR Kyushu Hotel Blossom Shinjuku',4.4,110,'JR Kyushu Hotel Blossom Shinjuku'],['Tokyu Stay Shinjuku',4.3,95,'Tokyu Stay Shinjuku']],
  spots:[
   ['Sensō-ji & Asakusa',4.6,0,'"The most atmospheric old-Tokyo temple" — go early or after dark when the lanterns glow and Nakamise stalls quiet down.','tokyo,sensoji,temple',[301,302,303],'Senso-ji Temple Asakusa','https://images.unsplash.com/photo-1545569341-9eb8b30979d9?w=800&q=80'],
   ['Shibuya Sky & Scramble',4.6,20,'Reviewers call the open-air deck "the best view in Tokyo at sunset" — book a slot to watch the Scramble swirl below.','shibuya,tokyo,crossing',[304,305,306],'Shibuya Sky','https://images.unsplash.com/photo-1540959733332-eab4deabeeaf?w=800&q=80'],
   ['Meiji Jingu',4.6,0,'"A forest in the middle of the city" — calm, free, and a short walk from Harajuku\'s buzz.','meiji,shrine,forest',[307,308,309],'Meiji Jingu','https://images.unsplash.com/photo-1528360983277-13d401cdc186?w=800&q=80'],
   ['Akihabara',4.4,0,'Otaku heaven — "Radio Kaikan and the back-street figure shops beat the big chains," say repeat visitors.','akihabara,anime,neon',[310,311,312],'Akihabara Electric Town','https://images.unsplash.com/photo-1569323112693-162b730bfc97?w=800&q=80'],
   ['teamLab Planets',4.5,26,'"Worth pre-booking" — wade through water and mirrored light. The single most-recommended ticket for first-timers.','digital,art,lights',[313,314,315],'teamLab Planets Toyosu','https://images.unsplash.com/photo-1570459027562-4a916cc6113f?w=800&q=80']
  ]},
 {name:'Kyoto',coord:[35.0116,135.7681],color:'#8a2d52',nights:'2–3 nights',
  chips:[['🏨 Hotel/night',110],['🍜 Food/day',50],['🎟️ Activities',35],['🚌 Bus/2d',12]],
  hotels:[['Hotel Granvia Kyoto',4.5,160,'Hotel Granvia Kyoto'],['The Royal Park Hotel Kyoto Sanjo',4.4,130,'Royal Park Hotel Kyoto Sanjo'],['Hearton Hotel Kyoto',4.2,100,'Hearton Hotel Kyoto']],
  spots:[
   ['Fushimi Inari Taisha',4.7,0,'"Be there by 8am" is the universal tip — the thousand vermilion torii empty out and the upper trail rewards you with views. Free, open 24h.','fushimi,inari,torii',[316,317,318],'Fushimi Inari Taisha','https://images.unsplash.com/photo-1478436127897-769e1b3f0f36?w=800&q=80'],
   ['Arashiyama Bamboo Grove',4.4,5,'Magical early; "a walkway of people by 10am." Pair with Tenryū-ji garden and the riverside.','arashiyama,bamboo,kyoto',[319,320,321],'Arashiyama Bamboo Grove','https://images.unsplash.com/photo-1576487503231-0c8b9e948f61?w=800&q=80'],
   ['Kiyomizu-dera',4.6,3,'"Magical in autumn" — the wooden stage frames the whole city. Combine with the Sannenzaka lanes.','kiyomizu,kyoto,pagoda',[322,323,324],'Kiyomizu-dera','https://images.unsplash.com/photo-1493976040374-85c8e12f0c0e?w=800&q=80'],
   ['Gion District',4.4,0,'Lantern-lit machiya streets at dusk; reviewers remind you not to chase or photograph geisha.','gion,kyoto,geisha,street',[325,326,327],'Gion Kyoto','https://images.unsplash.com/photo-1492571350019-22de08371fd3?w=800&q=80'],
   ['Kinkaku-ji',4.5,4,'The golden pavilion mirrored in its pond — "smaller than expected but unforgettable."','kinkakuji,golden,temple',[328,329,330],'Kinkaku-ji','https://images.unsplash.com/photo-1545569341-9eb8b30979d9?w=800&q=80']
  ]},
 {name:'Hiroshima + Miyajima',coord:[34.3853,132.4553],color:'#1f3a5f',nights:'1 night',
  chips:[['🏨 Hotel/night',90],['🍜 Food/day',45],['🎟️ Activities',15],['⛴️ Ferry',5]],
  hotels:[['Sheraton Grand Hiroshima',4.5,140,'Sheraton Grand Hiroshima'],['Hotel Granvia Hiroshima',4.4,110,'Hotel Granvia Hiroshima'],['Hotel Active Hiroshima',4.2,75,'Hotel Active Hiroshima']],
  spots:[
   ['Peace Memorial Park & Museum',4.7,1.5,'"Moving and essential" — heavy but the emotional highlight of many Japan trips. The A-Bomb Dome stands across the river.','hiroshima,peace,dome',[331,332,333],'Hiroshima Peace Memorial Park','https://images.unsplash.com/photo-1576675466969-38eeae4b41f6?w=800&q=80'],
   ['Itsukushima Shrine, Miyajima',4.6,2,'The "floating" torii at high tide is "worth the early ferry"; friendly deer roam the island.','miyajima,torii,itsukushima',[334,335,336],'Itsukushima Shrine','https://images.unsplash.com/photo-1528360983277-13d401cdc186?w=800&q=80']
  ]},
 {name:'Osaka',coord:[34.6937,135.5023],color:'#b5701f',nights:'1–2 nights',
  chips:[['🏨 Hotel/night',85],['🍜 Food/day',50],['🎟️ Activities',20],['🚇 Metro',10]],
  hotels:[['Cross Hotel Osaka',4.5,130,'Cross Hotel Osaka'],['Candeo Hotels Osaka Namba',4.4,110,'Candeo Hotels Osaka Namba'],['Comfort Hotel Osaka Shinsaibashi',4.2,80,'Comfort Hotel Osaka Shinsaibashi']],
  spots:[
   ['Dōtonbori',4.5,0,'"Best after dark when the neon turns on" — Glico Running Man, canal cruise, takoyaki and okonomiyaki crawl.','osaka,dotonbori,neon',[337,338,339],'Dotonbori','https://images.unsplash.com/photo-1590559899731-a382839e5549?w=800&q=80'],
   ['Osaka Castle',4.5,4,'Reconstructed keep in a moated park — "lovely in autumn colour," great city views from the top.','osaka,castle,japan',[340,341,342],'Osaka Castle','https://images.unsplash.com/photo-1590559899731-a382839e5549?w=800&q=80'],
   ['Kuromon Ichiba Market',4.2,0,'"Osaka\'s kitchen" — grilled scallops, uni, wagyu skewers eaten on the spot.','osaka,market,seafood',[343,344,345],'Kuromon Ichiba Market','https://images.unsplash.com/photo-1555212697-194d092e3b8f?w=800&q=80']
  ]},
 {name:'Takayama + Shirakawa-go',coord:[36.1408,137.2520],color:'#1f4d3a',nights:'2 nights',
  chips:[['🏨 Ryokan/night',120],['🍜 Food/day',55],['🎟️ Activities',20],['🚌 Village bus',30]],
  hotels:[['Honjin Hiranoya Kachoan',4.6,260,'Honjin Hiranoya Kachoan Takayama'],['Hotel Wood Takayama',4.5,120,'Hotel Wood Takayama'],['Sumiyoshi Ryokan',4.3,110,'Sumiyoshi Ryokan Takayama']],
  spots:[
   ['Shirakawa-gō',4.6,3,'UNESCO thatched gasshō farmhouses — rated 4.9/5 on day trips. "Go early; it feels like a fairy tale before the buses."','shirakawa,village,gassho',[346,347,348],'Shirakawa-go Gassho Village','https://images.unsplash.com/photo-1522383225653-ed111181a951?w=800&q=80'],
   ['Sanmachi Suji Old Town',4.5,0,'Preserved Edo merchant streets, sake breweries and Hida beef — "Kyoto without the crowds."','takayama,old,town',[349,350,351],'Sanmachi Suji Takayama','https://images.unsplash.com/photo-1480796927426-f609979314bd?w=800&q=80'],
   ['Miyagawa Morning Market',4.2,0,'Riverside stalls of pickles, crafts and snacks — "a calm, local way to start the day."','takayama,morning,market',[352,353,354],'Miyagawa Morning Market','https://images.unsplash.com/photo-1545569341-9eb8b30979d9?w=800&q=80']
  ]},
 {name:'Lake Kawaguchiko',coord:[35.5104,138.7689],color:'#2b6cb0',nights:'1–2 nights',
  chips:[['🏨 Onsen/night',140],['🍜 Food/day',50],['🎟️ Activities',20],['🚞 Ropeway/bus',15]],
  hotels:[['La Vista Fuji Kawaguchiko',4.6,260,'La Vista Fuji Kawaguchiko'],['Fuji View Hotel',4.4,180,'Fuji View Hotel Kawaguchiko'],['Mizno Hotel',4.3,150,'Mizno Hotel Kawaguchiko']],
  spots:[
   ['Chūreitō Pagoda',4.6,0,'The iconic pagoda-with-Fuji shot — "before 9am without exception." ~400 steps up to the viewpoint.','chureito,pagoda,fuji',[355,356,357],'Chureito Pagoda Arakurayama Sengen Park','https://images.unsplash.com/photo-1490806843957-31f4c9a91c65?w=800&q=80'],
   ['Ōishi Park (north shore)',4.6,0,'"The north shore is the money shot" — flowers, lake and Fuji mirrored behind.','fuji,kawaguchiko,lake',[358,359,360],'Oishi Park Lake Kawaguchi','https://images.unsplash.com/photo-1490806843957-31f4c9a91c65?w=800&q=80'],
   ['Mt. Tenjō Ropeway',4.4,6,'Cable car to a deck over the lake and the Aokigahara "sea of trees" — "a different perspective from above."','ropeway,fuji,view',[361,362,363],'Mt Fuji Panoramic Ropeway','https://images.unsplash.com/photo-1490806843957-31f4c9a91c65?w=800&q=80']
  ]},
 {name:'Seoul',coord:[37.5665,126.9780],color:'#2a2140',nights:'3 nights',
  chips:[['🏨 Hotel/night',80],['🍜 Food/day',45],['🎟️ Activities',35],['🚇 T-money/3d',15]],
  hotels:[['ENA Suite Hotel Namdaemun',4.6,90,'ENA Suite Hotel Namdaemun Seoul'],['L7 Myeongdong by Lotte',4.5,130,'L7 Myeongdong by Lotte'],['LOTTE City Hotel Myeongdong',4.5,120,'Lotte City Hotel Myeongdong']],
  spots:[
   ['Gyeongbokgung Palace',4.6,2,'Time it for the 10am changing of the guard; "rent a hanbok and palace entry is free." Grand and photogenic.','gyeongbokgung,palace,seoul',[364,365,366],'Gyeongbokgung Palace','https://images.unsplash.com/photo-1538669717975-78d9bb336a9a?w=800&q=80'],
   ['Bukchon Hanok Village',4.3,0,'Traditional hanok lanes between two palaces — "go first thing in the morning before the crowds."','bukchon,hanok,seoul',[367,368,369],'Bukchon Hanok Village','https://images.unsplash.com/photo-1538669717975-78d9bb336a9a?w=800&q=80'],
   ['N Seoul Tower (Namsan)',4.5,16,'"Enter just before sunset" for the light fading over the whole city.','namsan,seoul,tower',[370,371,372],'N Seoul Tower','https://images.unsplash.com/photo-1538669717975-78d9bb336a9a?w=800&q=80'],
   ['Gwangjang Market',4.4,0,'"Where Seoul actually eats" — bindaetteok, mayak gimbap and live-octopus stalls since 1905.','seoul,market,food',[373,374,375],'Gwangjang Market','https://images.unsplash.com/photo-1555212697-194d092e3b8f?w=800&q=80'],
   ['Seongsu-dong',4.4,0,'The "Brooklyn of Seoul" — concept cafés, street art and indie boutiques in converted factories.','seoul,city,cafe',[376,377,378],'Seongsu-dong Seoul','https://images.unsplash.com/photo-1538669717975-78d9bb336a9a?w=800&q=80']
  ]}
];
function stars(r){return '★ '+r.toFixed(1);}
function renderCities(){document.getElementById('cities').innerHTML=CITIES.map(function(c,ci){
  var chips=c.chips.map(function(ch){return '<div class="chip">'+ch[0]+'<b><span class="money" data-usd="'+ch[1]+'"></span></b></div>';}).join('');
  var hotels=c.hotels.map(function(h){return '<div class="hcard"><div class="hn">'+h[0]+'</div><div class="rt">'+stars(h[1])+'</div>'+
    '<div class="hp"><span class="money" data-usd="'+h[2]+'"></span> <small>/ night</small></div>'+
    '<a target="_blank" rel="noopener" href="'+gmaps(h[3])+'">Map &amp; reviews ↗</a></div>';}).join('');
  var spots=c.spots.map(function(s,si){
    var previewImg=s[7]||'';
    var imgs=spotImgs(s[4],s[5]);
    var hasPreview=!!previewImg;
    if(hasPreview) imgs.unshift(previewImg);
    var gal=spotGal(si,imgs,s[0],ci,hasPreview);
    return '<div class="spot rv">'+gal+
      '<h4>'+s[0]+'<span class="ec">'+(s[2]===0?'Free':'Entry <span class="money" data-usd="'+s[2]+'"></span>')+'</span></h4>'+
      '<div class="gr">'+stars(s[1])+' Google rating (indicative)</div>'+
      '<p>'+s[3]+'</p>'+
      '<a class="mlink" target="_blank" rel="noopener" href="'+gmaps(s[6])+'">📍 Maps &amp; live reviews</a></div>';
  }).join('');
  return '<div class="city rv" id="city'+ci+'"><div class="chead" style="background:linear-gradient(135deg,'+c.color+',#191a23)">'+
    '<div><h3>'+c.name+'</h3><div class="nights">'+c.nights+'</div></div>'+
    '<button data-action="fly" data-ci="'+ci+'">📍 Show on map</button></div>'+
    '<div class="cbody"><div class="chips">'+chips+'</div>'+
    '<div class="subh">🏨 Best-rated hotels near the spots</div><div class="hotels">'+hotels+'</div>'+
    '<div class="subh">⭐ Spots to visit</div>'+spots+'</div></div>';
}).join('');}
renderCities();

/* ===== MAP ===== */
var map,groupA,groupB,markers;
function leg(from,to,flight){return L.polyline([from,to],{color:flight?'#888':'#888',weight:flight?2:3,
  dashArray:flight?'6,8':null,opacity:.9});}
function initMap(){
  map=L.map('map',{scrollWheelZoom:false}).setView([36.5,134],4.4);
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',{maxZoom:18,
    attribution:'© OpenStreetMap'}).addTo(map);
  var MB=[19.0760,72.8777],TK=[35.6762,139.6503],KY=[35.0116,135.7681],HI=[34.3853,132.4553],
      OS=[34.6937,135.5023],TA=[36.1408,137.2520],KW=[35.5104,138.7689],SE=[37.5665,126.9780];
  /* Trip A legs: flight Mumbai-Tokyo; ground Tokyo..Kawaguchi; ground Kawaguchi-Tokyo; flight Tokyo-Seoul; flight Seoul-Mumbai */
  groupA=L.layerGroup([
    L.polyline([MB,TK],{color:'#e0345a',weight:2,dashArray:'6,8'}),
    L.polyline([TK,KY,HI,OS,TA,KW,TK],{color:'#e0345a',weight:3.5}),
    L.polyline([TK,SE],{color:'#e0345a',weight:2,dashArray:'6,8'}),
    L.polyline([SE,MB],{color:'#e0345a',weight:2,dashArray:'6,8'})
  ]);
  groupB=L.layerGroup([
    L.polyline([MB,OS],{color:'#2b6cb0',weight:2,dashArray:'6,8'}),
    L.polyline([OS,HI,KY,TA,KW,TK],{color:'#2b6cb0',weight:3.5}),
    L.polyline([TK,SE],{color:'#2b6cb0',weight:2,dashArray:'6,8'}),
    L.polyline([SE,MB],{color:'#2b6cb0',weight:2,dashArray:'6,8'})
  ]);
  /* city markers with popups (hotel + nightly cost) */
  markers={};
  CITIES.forEach(function(c,ci){
    var top=c.hotels[0];
    var m=L.marker(c.coord).addTo(map).bindPopup(
      '<b>'+c.name+'</b><br>'+c.nights+'<br>🏨 '+top[0]+' '+stars(top[1])+
      '<br>~<span class="money" data-usd="'+top[2]+'">'+fmt(top[2])+'</span>/night'+
      '<br><a href="'+gmaps(c.name)+'" target="_blank">Open in Maps ↗</a>');
    markers[ci]=m;
  });
  L.marker([19.0760,72.8777]).addTo(map).bindPopup('<b>Mumbai (BOM)</b><br>Start &amp; end');
  groupA.addTo(map);
}
function flyCity(ci){var c=CITIES[ci];map.flyTo(c.coord,8,{duration:1});markers[ci].openPopup();
  document.getElementById('map').scrollIntoView({behavior:'smooth',block:'center'});}
function showTrip(w){var a=w==='A';
  document.getElementById('tabA').classList.toggle('active',a);
  document.getElementById('tabB').classList.toggle('active',!a);
  document.getElementById('introA').classList.toggle('hide',!a);
  document.getElementById('introB').classList.toggle('hide',a);
  document.getElementById('daysA').classList.toggle('hide',!a);
  document.getElementById('daysB').classList.toggle('hide',a);
  document.getElementById('hType').textContent='Mid-range';
  if(map){if(a){map.removeLayer(groupB);groupA.addTo(map);}else{map.removeLayer(groupA);groupB.addTo(map);}}
  renderDayNav();
}

/* ===== LIGHTBOX ===== */
var lbImgs=[],lbIdx=0;
function openLB(gIdx,name,idx){lbImgs=GALDATA[gIdx]||[];lbIdx=idx||0;
  var lb=document.getElementById('lb');
  document.getElementById('lbi').src=lbImgs[lbIdx]||'';
  document.getElementById('lbc').textContent=(lbIdx+1)+' / '+lbImgs.length;
  document.getElementById('lbname').textContent=name||'';
  lb.classList.add('open');document.body.style.overflow='hidden';}
function closeLB(){document.getElementById('lb').classList.remove('open');document.body.style.overflow='';}
function lbNav(d){lbIdx=(lbIdx+d+lbImgs.length)%lbImgs.length;
  var img=document.getElementById('lbi');img.style.opacity='0';
  setTimeout(function(){img.src=lbImgs[lbIdx];img.style.opacity='1';
    document.getElementById('lbc').textContent=(lbIdx+1)+' / '+lbImgs.length;},150);}
document.addEventListener('keydown',function(e){var lb=document.getElementById('lb');
  if(!lb||!lb.classList.contains('open'))return;
  if(e.key==='Escape')closeLB();if(e.key==='ArrowLeft')lbNav(-1);if(e.key==='ArrowRight')lbNav(1);});

/* ===== SCROLL REVEAL ===== */
var revealObs=new IntersectionObserver(function(entries){entries.forEach(function(e){
  if(e.isIntersecting){e.target.classList.add('vis');revealObs.unobserve(e.target);}
});},{threshold:0.08,rootMargin:'0px 0px -40px 0px'});
function initReveal(){document.querySelectorAll('.rv').forEach(function(el){revealObs.observe(el);});}

/* ===== DAY NAV ===== */
function renderDayNav(){var old=document.querySelector('.dnav');if(old)old.remove();
  var isA=!document.getElementById('daysA').classList.contains('hide');
  var arr=isA?tripA:tripB;var el=document.getElementById(isA?'daysA':'daysB');if(!el)return;
  var nav=document.createElement('div');nav.className='dnav';
  arr.forEach(function(d,i){var btn=document.createElement('button');btn.className='dnbtn';
    btn.textContent='D'+(i+1);
    btn.addEventListener('click',function(){var days=el.querySelectorAll('.day');
      if(days[i])days[i].scrollIntoView({behavior:'smooth',block:'center'});});
    nav.appendChild(btn);});
  el.parentNode.insertBefore(nav,el);}

/* ===== BACK TO TOP ===== */
var btt=document.createElement('button');btt.className='btt';btt.innerHTML='↑';btt.title='Back to top';
btt.addEventListener('click',function(){window.scrollTo({top:0,behavior:'smooth'});});
document.body.appendChild(btt);
window.addEventListener('scroll',function(){btt.classList.toggle('show',window.scrollY>400);});

/* ===== SMOOTH SCROLL ===== */
document.documentElement.style.scrollBehavior='smooth';

/* ===== DARK MODE ===== */
function initTheme(){var saved=localStorage.getItem('trip-theme');
  if(saved==='dark'||(!saved&&window.matchMedia('(prefers-color-scheme:dark)').matches))
    document.documentElement.setAttribute('data-theme','dark');
  updateThemeBtn();}
function toggleTheme(){var isDark=document.documentElement.getAttribute('data-theme')==='dark';
  if(isDark){document.documentElement.removeAttribute('data-theme');localStorage.setItem('trip-theme','light');}
  else{document.documentElement.setAttribute('data-theme','dark');localStorage.setItem('trip-theme','dark');}
  updateThemeBtn();}
function updateThemeBtn(){var btn=document.getElementById('themeToggle');if(!btn)return;
  btn.textContent=document.documentElement.getAttribute('data-theme')==='dark'?'☀️':'🌙';}

/* ===== EVENT DELEGATION (CSP-safe) ===== */
document.addEventListener('click',function(e){
  var t=e.target.closest('[data-action]');if(!t)return;
  var action=t.dataset.action;
  if(action==='currency'){setCurrency(t.dataset.cur);}
  else if(action==='theme'){toggleTheme();}
  else if(action==='trip'){showTrip(t.dataset.trip);}
  else if(action==='gslide'){gSlide(t.dataset.gal,parseInt(t.dataset.dir));}
  else if(action==='ggo'){gGo(t.dataset.gal,parseInt(t.dataset.idx));}
  else if(action==='lb'){openLB(parseInt(t.dataset.gidx),t.dataset.name,parseInt(t.dataset.idx));}
  else if(action==='fly'){flyCity(parseInt(t.dataset.ci));}
  else if(action==='closeLb'){closeLB();}
  else if(action==='lbNav'){lbNav(parseInt(t.dataset.dir));}
  else if(action==='lbBg'&&e.target===t){closeLB();}
});

/* Gallery scroll sync via delegation */
document.addEventListener('scroll',function(e){
  if(e.target.classList&&e.target.classList.contains('gtrack')){
    var gal=e.target.closest('.gallery');if(gal)gSync(gal.id.replace('gal-',''));
  }
},true);

/* Image error handling via capture delegation */
document.addEventListener('error',function(e){
  if(e.target.tagName==='IMG'&&e.target.closest('.gslide')){
    e.target.style.background='linear-gradient(135deg,#33384a,#7a1f37)';
    e.target.style.display='block';e.target.alt='Image unavailable';
    e.target.removeAttribute('src');
  }
},true);

/* ===== LIGHTBOX OVERLAY (created by JS, no inline handlers) ===== */
function createLightbox(){
  var lb=document.createElement('div');lb.className='lb';lb.id='lb';
  lb.setAttribute('data-action','lbBg');
  var xb=document.createElement('button');xb.className='lbx';xb.setAttribute('data-action','closeLb');
  xb.setAttribute('aria-label','Close');xb.textContent='×';
  var pb=document.createElement('button');pb.className='lbn prev';pb.setAttribute('data-action','lbNav');
  pb.setAttribute('data-dir','-1');pb.setAttribute('aria-label','Previous');pb.textContent='\u2039';
  var img=document.createElement('img');img.id='lbi';img.alt='Enlarged photo';
  var nb=document.createElement('button');nb.className='lbn next';nb.setAttribute('data-action','lbNav');
  nb.setAttribute('data-dir','1');nb.setAttribute('aria-label','Next');nb.textContent='\u203A';
  var cap=document.createElement('div');cap.className='lb-cap';cap.id='lbname';
  var cnt=document.createElement('div');cnt.className='lbc';cnt.id='lbc';cnt.textContent='1 / 4';
  lb.appendChild(xb);lb.appendChild(pb);lb.appendChild(img);lb.appendChild(nb);lb.appendChild(cap);lb.appendChild(cnt);
  document.body.appendChild(lb);
}

/* INIT */
window.addEventListener('load',function(){createLightbox();initTheme();initMap();applyCurrency();renderDayNav();setTimeout(initReveal,100);});
applyCurrency();
</script>
</body>
</html>
