<?php
// DBMS Semester Navigator
$page_title = "DBMS Navigator";
$developer = "Mr. Thanush";

$semesters = [
    [
        "title" => "Year I — Semester I",
        "subtitle" => "Foundation modules",
        "href" => "year1sem1.php",
        "icon" => "ti-school",
        "tag" => "Sem 1",
        "color" => "purple"
    ],
    [
        "title" => "Up to Year I — Sem II",
        "subtitle" => "Core concepts",
        "href" => "year1sem2.php",
        "icon" => "ti-book",
        "tag" => "Sem 2",
        "color" => "blue"
    ],
    [
        "title" => "Up to Year II — Sem I",
        "subtitle" => "Advanced topics",
        "href" => "year2sem1.php",
        "icon" => "ti-database",
        "tag" => "Sem 3",
        "color" => "teal"
    ],
    [
        "title" => "Up to Year II — Sem II",
        "subtitle" => "Applied DBMS",
        "href" => "year2sem2.php",
        "icon" => "ti-server",
        "tag" => "Sem 4",
        "color" => "coral"
    ],
];

$general_degree = [
    [
        "title" => "Up to Year III — Sem I",
        "subtitle" => "Specialised modules",
        "href" => "year3gsem1.php",
        "icon" => "ti-chart-dots",
        "tag" => "Sem 5",
        "color" => "amber"
    ],
    [
        "title" => "Up to Year III — Sem II",
        "subtitle" => "Final year content",
        "href" => "year3gsem2.php",
        "icon" => "ti-award",
        "tag" => "Sem 6",
        "color" => "green"
    ],
];

function render_cards(array $cards): string {
    $html = '';
    foreach ($cards as $card) {
        $color = htmlspecialchars($card['color']);
        $tag   = htmlspecialchars($card['tag']);
        $icon  = htmlspecialchars($card['icon']);
        $title = htmlspecialchars($card['title']);
        $sub   = htmlspecialchars($card['subtitle']);
        $href  = htmlspecialchars($card['href']);
        $html .= <<<CARD
        <a href="{$href}" class="card card--{$color}" aria-label="{$title}">
            <div class="card__tag">{$tag}</div>
            <div class="card__icon-wrap">
                <i class="ti {$icon}" aria-hidden="true"></i>
            </div>
            <div class="card__body">
                <p class="card__title">{$title}</p>
                <p class="card__sub">{$sub}</p>
            </div>
            <div class="card__arrow">
                <i class="ti ti-arrow-right" aria-hidden="true"></i>
            </div>
        </a>
CARD;
    }
    return $html;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title) ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@2.47.0/tabler-icons.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --bg:       #0a0a12;
            --surface:  rgba(255,255,255,0.045);
            --surface-h:rgba(255,255,255,0.08);
            --border:   rgba(255,255,255,0.10);
            --border-h: rgba(255,255,255,0.22);
            --text:     #f0f0f8;
            --muted:    rgba(240,240,248,0.50);
            --radius:   18px;
            --font:     'Inter', system-ui, sans-serif;

            --purple-mid: #7F77DD;
            --purple-lite:#EEEDFE;
            --blue-mid:   #378ADD;
            --blue-lite:  #E6F1FB;
            --teal-mid:   #1D9E75;
            --teal-lite:  #E1F5EE;
            --coral-mid:  #D85A30;
            --coral-lite: #FAECE7;
            --amber-mid:  #BA7517;
            --amber-lite: #FAEEDA;
            --green-mid:  #639922;
            --green-lite: #EAF3DE;
        }

        html { scroll-behavior: smooth; }

        body {
            font-family: var(--font);
            background: var(--bg);
            color: var(--text);
            min-height: 100dvh;
            overflow-x: hidden;
            position: relative;
        }

        /* ── Ambient orbs ── */
        .orb {
            position: fixed;
            border-radius: 50%;
            filter: blur(120px);
            pointer-events: none;
            z-index: 0;
            opacity: 0.35;
        }
        .orb1 { width: 500px; height: 500px; background: #5340c8; top: -150px; left: -150px; }
        .orb2 { width: 400px; height: 400px; background: #0f6e56; bottom: -100px; right: -100px; }
        .orb3 { width: 300px; height: 300px; background: #d85a30; top: 50%;  left: 55%; opacity: 0.18; }

        /* ── Layout ── */
        .page {
            position: relative;
            z-index: 1;
            max-width: 900px;
            margin: 0 auto;
            padding: 48px 20px 80px;
        }

        /* ── Header ── */
        .header {
            text-align: center;
            margin-bottom: 56px;
        }
        .header__eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 12px;
            font-weight: 600;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            color: var(--muted);
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 100px;
            padding: 5px 14px;
            margin-bottom: 22px;
        }
        .header__eyebrow i { font-size: 14px; }
        .header__title {
            font-size: clamp(2rem, 6vw, 3.4rem);
            font-weight: 700;
            line-height: 1.12;
            letter-spacing: -0.03em;
            background: linear-gradient(135deg, #fff 30%, rgba(255,255,255,0.55));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 14px;
        }
        .header__sub {
            font-size: 16px;
            color: var(--muted);
            max-width: 400px;
            margin: 0 auto;
            line-height: 1.6;
        }

        /* ── Section label ── */
        .section-label {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 20px;
        }
        .section-label__text {
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.14em;
            text-transform: uppercase;
            color: var(--muted);
            white-space: nowrap;
        }
        .section-label__line {
            flex: 1;
            height: 1px;
            background: var(--border);
        }

        /* ── Card grid ── */
        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 14px;
            margin-bottom: 44px;
        }

        /* ── Cards ── */
        .card {
            display: flex;
            flex-direction: column;
            gap: 16px;
            padding: 22px 20px 18px;
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            text-decoration: none;
            color: inherit;
            position: relative;
            overflow: hidden;
            transition: background 0.22s, border-color 0.22s, transform 0.18s;
            cursor: pointer;
            -webkit-tap-highlight-color: transparent;
        }
        .card::before {
            content: '';
            position: absolute;
            inset: 0;
            border-radius: inherit;
            opacity: 0;
            transition: opacity 0.22s;
        }
        .card:hover, .card:focus-visible {
            background: var(--surface-h);
            border-color: var(--border-h);
            transform: translateY(-3px);
        }
        .card:hover::before, .card:focus-visible::before { opacity: 1; }
        .card:active { transform: translateY(0); }
        .card:focus-visible { outline: 2px solid rgba(255,255,255,0.5); outline-offset: 2px; }

        /* Color accent line */
        .card::after {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 2px;
            border-radius: var(--radius) var(--radius) 0 0;
            opacity: 0.75;
            transition: opacity 0.22s;
        }
        .card:hover::after { opacity: 1; }
        .card--purple::after { background: var(--purple-mid); }
        .card--blue::after   { background: var(--blue-mid); }
        .card--teal::after   { background: var(--teal-mid); }
        .card--coral::after  { background: var(--coral-mid); }
        .card--amber::after  { background: var(--amber-mid); }
        .card--green::after  { background: var(--green-mid); }

        .card__tag {
            position: absolute;
            top: 14px; right: 14px;
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            padding: 3px 8px;
            border-radius: 100px;
            background: var(--border);
            color: var(--muted);
        }

        .card__icon-wrap {
            width: 40px; height: 40px;
            border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            font-size: 20px;
            flex-shrink: 0;
        }
        .card--purple .card__icon-wrap { background: rgba(127,119,221,0.18); color: #a9a2ed; }
        .card--blue   .card__icon-wrap { background: rgba(55,138,221,0.18);  color: #85b7eb; }
        .card--teal   .card__icon-wrap { background: rgba(29,158,117,0.18);  color: #5dcaa5; }
        .card--coral  .card__icon-wrap { background: rgba(216,90,48,0.18);   color: #f0997b; }
        .card--amber  .card__icon-wrap { background: rgba(186,117,23,0.18);  color: #ef9f27; }
        .card--green  .card__icon-wrap { background: rgba(99,153,34,0.18);   color: #97c459; }

        .card__body { flex: 1; }
        .card__title {
            font-size: 14px;
            font-weight: 600;
            color: var(--text);
            line-height: 1.35;
            margin-bottom: 4px;
        }
        .card__sub {
            font-size: 12px;
            color: var(--muted);
        }
        .card__arrow {
            align-self: flex-end;
            font-size: 16px;
            color: var(--muted);
            transition: transform 0.2s, color 0.2s;
        }
        .card:hover .card__arrow, .card:focus-visible .card__arrow {
            transform: translateX(4px);
            color: var(--text);
        }

        /* ── Divider badge ── */
        .divider {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 14px;
            margin: 8px 0 40px;
        }
        .divider__line { flex: 1; height: 1px; background: var(--border); }
        .divider__badge {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 13px;
            font-weight: 600;
            color: var(--text);
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 100px;
            padding: 7px 18px;
        }
        .divider__badge i { font-size: 15px; color: #ef9f27; }

        /* ── Footer ── */
        .footer {
            text-align: center;
            padding: 24px 0 0;
            border-top: 1px solid var(--border);
        }
        .footer__text {
            font-size: 13px;
            color: var(--muted);
        }
        .footer__text b { color: rgba(240,240,248,0.80); font-weight: 600; }

        /* ── Mobile ── */
        @media (max-width: 480px) {
            .page { padding: 32px 16px 60px; }
            .grid { grid-template-columns: 1fr 1fr; gap: 10px; }
            .card { padding: 16px 14px 14px; gap: 12px; }
            .card__title { font-size: 13px; }
            .orb1, .orb2, .orb3 { display: none; }
        }
    </style>
</head>
<body>

<div class="orb orb1"></div>
<div class="orb orb2"></div>
<div class="orb orb3"></div>

<main class="page">

    <header class="header">
        <div class="header__eyebrow" aria-hidden="true">
            <i class="ti ti-database"></i>
            DBMS Study Portal
        </div>
        <h1 class="header__title">Semesters<br>at a glance</h1>
        <p class="header__sub">Pick a semester to jump straight to its content.</p>
    </header>

    <div class="section-label" aria-hidden="true">
        <span class="section-label__text">Semesters separately</span>
        <span class="section-label__line"></span>
    </div>

    <nav class="grid" aria-label="Semester navigation">
        <?= render_cards($semesters) ?>
    </nav>

    <div class="divider" role="separator">
        <span class="divider__line"></span>
        <span class="divider__badge">
            <i class="ti ti-certificate" aria-hidden="true"></i>
            General degree
        </span>
        <span class="divider__line"></span>
    </div>

    <nav class="grid" aria-label="General degree navigation">
        <?= render_cards($general_degree) ?>
    </nav>

    <footer class="footer">
        <p class="footer__text">Developed by <b><?= htmlspecialchars($developer) ?></b></p>
    </footer>

</main>

</body>
</html>