<?php
// GPA Calculator - FCBS 22/23
// index.php
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GPA Calculator | FCBS DIGI KUPPIYA</title>
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --navy:    #0A0F2C;
            --indigo:  #4F46E5;
            --indigo2: #6366F1;
            --gold:    #F59E0B;
            --frost:   #F0F4FF;
            --muted:   #8892B0;
            --card-bg: #111530;
            --border:  rgba(99,102,241,0.25);
        }

        html { scroll-behavior: smooth; }

        body {
            font-family: 'Inter', sans-serif;
            background: var(--navy);
            color: var(--frost);
            min-height: 100vh;
            overflow-x: hidden;
            -webkit-tap-highlight-color: transparent;
        }

        /* ─── Animated starfield background ─── */
        .stars {
            position: fixed; inset: 0; z-index: 0; pointer-events: none;
            background:
                radial-gradient(ellipse at 20% 50%, rgba(79,70,229,0.15) 0%, transparent 60%),
                radial-gradient(ellipse at 80% 20%, rgba(245,158,11,0.08) 0%, transparent 50%),
                var(--navy);
        }
        .stars::before, .stars::after {
            content: '';
            position: absolute; inset: 0;
            background-image:
                radial-gradient(1px 1px at 10% 15%, rgba(240,244,255,0.6) 0%, transparent 100%),
                radial-gradient(1px 1px at 30% 70%, rgba(240,244,255,0.5) 0%, transparent 100%),
                radial-gradient(1.5px 1.5px at 55% 35%, rgba(240,244,255,0.7) 0%, transparent 100%),
                radial-gradient(1px 1px at 75% 80%, rgba(240,244,255,0.4) 0%, transparent 100%),
                radial-gradient(1px 1px at 90% 10%, rgba(240,244,255,0.6) 0%, transparent 100%),
                radial-gradient(1.5px 1.5px at 45% 55%, rgba(99,102,241,0.8) 0%, transparent 100%),
                radial-gradient(1px 1px at 65% 25%, rgba(240,244,255,0.5) 0%, transparent 100%),
                radial-gradient(2px 2px at 15% 90%, rgba(99,102,241,0.6) 0%, transparent 100%);
            animation: twinkle 4s ease-in-out infinite alternate;
        }
        .stars::after { animation-delay: 2s; animation-duration: 6s; opacity: 0.6; }
        @keyframes twinkle { from { opacity: 0.4; } to { opacity: 1; } }

        /* ─── Layout wrapper ─── */
        .wrapper { position: relative; z-index: 1; }

        /* ─── Topbar ─── */
        .topbar {
            display: flex; align-items: center; justify-content: space-between;
            padding: 1.2rem 2.5rem;
            border-bottom: 1px solid var(--border);
            backdrop-filter: blur(12px);
            background: rgba(10,15,44,0.7);
            position: sticky; top: 0; z-index: 100;
        }
        .topbar-brand {
            font-family: 'Syne', sans-serif;
            font-weight: 800; font-size: 1.1rem;
            letter-spacing: 0.04em;
            color: var(--frost);
        }
        .topbar-brand span { color: var(--gold); }
        .topbar-badge {
            font-size: 0.72rem; font-weight: 600; letter-spacing: 0.08em;
            padding: 0.3rem 0.9rem; border-radius: 100px;
            border: 1px solid var(--border);
            background: rgba(99,102,241,0.12);
            color: var(--indigo2);
            text-transform: uppercase;
        }

        /* ─── Hero ─── */
        .hero {
            text-align: center;
            padding: 5rem 1.5rem 3.5rem;
        }
        .hero-eyebrow {
            display: inline-block;
            font-size: 0.75rem; font-weight: 600; letter-spacing: 0.18em;
            text-transform: uppercase; color: var(--gold);
            margin-bottom: 1.4rem;
            opacity: 0; animation: fadeUp 0.7s ease 0.2s forwards;
        }
        .hero-title {
            font-family: 'Syne', sans-serif;
            font-size: clamp(2.2rem, 6vw, 4.2rem);
            font-weight: 800; line-height: 1.1;
            letter-spacing: -0.02em;
            background: linear-gradient(135deg, #fff 30%, var(--indigo2) 70%, var(--gold) 100%);
            -webkit-background-clip: text; -webkit-text-fill-color: transparent;
            background-clip: text;
            opacity: 0; animation: fadeUp 0.7s ease 0.4s forwards;
        }
        .hero-subtitle {
            margin-top: 1.2rem;
            font-size: 1rem; color: var(--muted); font-weight: 400; line-height: 1.7;
            max-width: 480px; margin-left: auto; margin-right: auto;
            opacity: 0; animation: fadeUp 0.7s ease 0.6s forwards;
        }
        .hero-divider {
            width: 60px; height: 3px; border-radius: 2px;
            background: linear-gradient(90deg, var(--indigo), var(--gold));
            margin: 2rem auto 0;
            opacity: 0; animation: fadeUp 0.7s ease 0.8s forwards;
        }

        /* ─── Cards section ─── */
        .cards-section {
            padding: 2rem 1.5rem 5rem;
            display: flex; justify-content: center;
            opacity: 0; animation: fadeUp 0.8s ease 1s forwards;
        }
        .cards-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem; max-width: 780px; width: 100%;
        }

        /* ─── Flip card ─── */
        .flip-card { 
            perspective: 1000px; 
            height: 400px; 
            cursor: pointer; 
            outline: none;
        }
        .flip-card-inner {
            position: relative; width: 100%; height: 100%;
            transition: transform 0.7s cubic-bezier(0.4, 0.2, 0.2, 1);
            transform-style: preserve-3d;
        }
        
        .flip-card:hover .flip-card-inner,
        .flip-card:focus-within .flip-card-inner,
        .flip-card:active .flip-card-inner { 
            transform: rotateY(180deg); 
        }

        /* ─── CRITICAL FIX FOR CLICKABLE BUTTONS ─── */
        /* Disable clicks on front when flipped, ensure back is fully clickable */
        .flip-card:hover .flip-front,
        .flip-card:focus-within .flip-front {
            pointer-events: none;
        }
        .flip-card:hover .flip-back,
        .flip-card:focus-within .flip-back {
            pointer-events: auto;
        }

        .flip-front, .flip-back {
            position: absolute; inset: 0; backface-visibility: hidden;
            border-radius: 20px;
            border: 1px solid var(--border);
            overflow: hidden;
        }

        /* Front face */
        .flip-front {
            background: var(--card-bg);
            display: flex; flex-direction: column; align-items: center; justify-content: flex-end;
            padding: 2rem;
            pointer-events: auto; /* Default state */
        }
        .flip-front::before {
            content: '';
            position: absolute; inset: 0;
            background: linear-gradient(to top, rgba(10,15,44,0.95) 40%, transparent 100%);
            z-index: 1;
        }
        .card-img {
            position: absolute; inset: 0; width: 100%; height: 100%;
            object-fit: cover; object-position: center;
            filter: saturate(0.7) brightness(0.65);
            transition: transform 0.6s ease, filter 0.6s ease;
        }
        .flip-card:hover .card-img { transform: scale(1.06); filter: saturate(0.9) brightness(0.75); }

        .card-front-content {
            position: relative; z-index: 2;
            text-align: center; width: 100%;
        }
        .card-program-tag {
            display: inline-block;
            font-size: 0.68rem; font-weight: 700; letter-spacing: 0.14em;
            text-transform: uppercase; padding: 0.3rem 0.8rem;
            border-radius: 100px; margin-bottom: 0.8rem;
            border: 1px solid rgba(245,158,11,0.4);
            background: rgba(245,158,11,0.12);
            color: var(--gold);
        }
        .card-title-front {
            font-family: 'Syne', sans-serif;
            font-size: 1.45rem; font-weight: 700;
            line-height: 1.25; color: var(--frost);
            margin-bottom: 0.5rem;
        }
        .card-hint {
            font-size: 0.78rem; color: var(--muted);
            display: flex; align-items: center; justify-content: center; gap: 0.4rem;
        }
        .card-hint svg { width: 14px; height: 14px; }

        /* Back face */
        .flip-back {
            transform: rotateY(180deg);
            background: linear-gradient(145deg, #141836 0%, #1a1f4a 100%);
            display: flex; flex-direction: column; align-items: center; justify-content: center;
            padding: 2.5rem 2rem; text-align: center;
            gap: 1rem;
        }
        .back-glow {
            position: absolute; top: -40px; left: 50%; transform: translateX(-50%);
            width: 200px; height: 200px; border-radius: 50%;
            background: radial-gradient(circle, rgba(99,102,241,0.25) 0%, transparent 70%);
            pointer-events: none;
        }
        .back-program {
            font-size: 0.72rem; font-weight: 600; letter-spacing: 0.15em;
            text-transform: uppercase; color: var(--gold);
        }
        .back-title {
            font-family: 'Syne', sans-serif;
            font-size: 1.3rem; font-weight: 700; color: var(--frost);
            line-height: 1.3;
        }
        .back-desc {
            font-size: 0.82rem; color: var(--muted); line-height: 1.65;
            max-width: 220px;
        }
        .back-btn {
            position: relative; 
            z-index: 10; 
            display: inline-flex; align-items: center; gap: 0.5rem;
            margin-top: 0.5rem;
            padding: 0.75rem 1.8rem;
            border-radius: 100px;
            background: linear-gradient(135deg, var(--indigo) 0%, #7C3AED 100%);
            color: #fff; font-weight: 600; font-size: 0.88rem;
            text-decoration: none;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            box-shadow: 0 4px 20px rgba(79,70,229,0.45);
            cursor: pointer;
        }
        .back-btn:hover, .back-btn:focus {
            transform: translateY(-2px);
            box-shadow: 0 8px 30px rgba(79,70,229,0.6);
            color: #fff; text-decoration: none;
            outline: none;
        }
        .back-btn svg { width: 16px; height: 16px; }

        /* ─── Footer ─── */
        .footer {
            text-align: center;
            padding: 1.5rem 2rem;
            border-top: 1px solid var(--border);
            font-size: 0.78rem; color: var(--muted);
            position: relative; z-index: 1;
        }
        .footer b { color: var(--indigo2); }

        /* ─── Floating orbs ─── */
        .orb {
            position: fixed; border-radius: 50%; pointer-events: none; z-index: 0;
            filter: blur(80px);
        }
        .orb-1 {
            width: 400px; height: 400px;
            background: rgba(79,70,229,0.15);
            top: -100px; right: -100px;
            animation: floatOrb 8s ease-in-out infinite alternate;
        }
        .orb-2 {
            width: 300px; height: 300px;
            background: rgba(245,158,11,0.08);
            bottom: 100px; left: -80px;
            animation: floatOrb 10s ease-in-out infinite alternate-reverse;
        }
        @keyframes floatOrb {
            from { transform: translate(0, 0); }
            to   { transform: translate(30px, 40px); }
        }
        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(24px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        /* ─── Responsive Media Queries ─── */
        @media (max-width: 768px) {
            .cards-grid {
                grid-template-columns: 1fr;
                max-width: 450px;
                margin: 0 auto;
            }
        }

        @media (max-width: 480px) {
            .topbar { 
                flex-direction: column; 
                gap: 0.8rem; 
                padding: 1rem; 
            }
            .hero { padding: 3.5rem 1rem 2.5rem; }
            .hero-title { font-size: 2rem; }
            .hero-subtitle { font-size: 0.9rem; }
            
            .cards-section { padding: 1.5rem 1rem 4rem; }
            .flip-card { height: 360px; }
            
            .flip-front { padding: 1.5rem; }
            .card-title-front { font-size: 1.3rem; }
            
            .flip-back { padding: 2rem 1.5rem; }
            .back-title { font-size: 1.2rem; }
            .back-desc { font-size: 0.8rem; }
        }

        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after { animation: none !important; transition: none !important; }
        }
    </style>
</head>
<body>

<div class="stars"></div>
<div class="orb orb-1"></div>
<div class="orb orb-2"></div>

<div class="wrapper">

    <nav class="topbar">
        <span class="topbar-brand">GPA<span>Calculator</span></span>
        <span class="topbar-badge">FCBS &mdash; DIGI KUPPIYA</span>
    </nav>

    <header class="hero">
        <p class="hero-eyebrow">Faculty of Communication &amp; Business Studies</p>
        <h1 class="hero-title">GPA Calculator</h1>
        <p class="hero-subtitle">Select your degree programme below to calculate and track your Grade Point Average for the academic year.</p>
        <div class="hero-divider"></div>
    </header>

    <main class="cards-section">
        <div class="cards-grid">

            <?php
            // Programme data
            $programmes = [
                [
                    'tag'   => 'BMS',
                    'title' => 'Business Management Studies',
                    'img'   => 'img/DBMS.png',
                    'alt'   => 'BMS Student',
                    'desc'  => 'Calculate your GPA across all Business Management modules and semesters.',
                    'link'  => 'bms.php',
                ],
                [
                    'tag'   => 'LCS',
                    'title' => 'Languages &amp; Communication Studies',
                    'img'   => 'img/DLCS.png',
                    'alt'   => 'LCS Student',
                    'desc'  => 'Calculate your GPA across all Logistics &amp; Computing modules and semesters.',
                    'link'  => 'lcs.php',
                ],
            ];
            ?>

            <?php foreach ($programmes as $p): ?>
            <div class="flip-card" tabindex="0" role="article" aria-label="<?= htmlspecialchars($p['tag']) ?> Programme">
                <div class="flip-card-inner">

                    <div class="flip-front">
                        <img
                            src="<?= htmlspecialchars($p['img']) ?>"
                            alt="<?= htmlspecialchars($p['alt']) ?>"
                            class="card-img"
                        >
                        <div class="card-front-content">
                            <span class="card-program-tag"><?= $p['tag'] ?></span>
                            <h2 class="card-title-front">I am a <?= $p['tag'] ?> Student</h2>
                            <p class="card-hint">
                                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                          d="M7 16l-4-4m0 0l4-4m-4 4h18"/>
                                </svg>
                                Hover to explore
                                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                          d="M17 8l4 4m0 0l-4 4m4-4H3"/>
                                </svg>
                            </p>
                        </div>
                    </div>

                    <div class="flip-back">
                        <div class="back-glow"></div>
                        <p class="back-program"><?= $p['tag'] ?> Programme</p>
                        <h3 class="back-title"><?= $p['title'] ?></h3>
                        <p class="back-desc"><?= $p['desc'] ?></p>
                        <a href="<?= htmlspecialchars($p['link']) ?>" class="back-btn">
                            Open Calculator
                            <svg fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                            </svg>
                        </a>
                    </div>

                </div>
            </div>
            <?php endforeach; ?>

        </div>
    </main>

    <footer class="footer">
        Developed by <b>Mr. Thanush</b> &nbsp;&middot;&nbsp; FCBS &copy; <?= date('Y') ?>
    </footer>

</div></body>
</html>