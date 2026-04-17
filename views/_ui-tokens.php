<style>
    :root {
        --bg:            #07080e;
        --surface:       rgba(255,255,255,.055);
        --surface-hov:   rgba(255,255,255,.088);
        --border:        rgba(255,255,255,.085);
        --border-hov:    rgba(255,255,255,.18);
        --border-focus:  rgba(167,139,250,.55);
        --primary:       #7c3aed;
        --primary-lt:    #a78bfa;
        --primary-dk:    #5b21b6;
        --accent:        #0891b2;
        --accent-lt:     #38bdf8;
        --success:       #059669;
        --success-lt:    #34d399;
        --warning:       #d97706;
        --warning-lt:    #fbbf24;
        --danger:        #dc2626;
        --danger-lt:     #f87171;
        --radius-sm:     8px;
        --radius-md:     12px;
        --radius-lg:     16px;
        --radius-xl:     22px;
        --gap-y:         1.25rem;
        --focus-ring:    0 0 0 3px rgba(167,139,250,.22);
    }

    *, *::before, *::after { box-sizing: border-box; }
    html { scroll-behavior: smooth; }

    main.page-stack > * + * { margin-top: var(--gap-y); }

    /* ── buttons ─────────────────────────────────────────────────────── */
    button, .ui-btn {
        min-height: 40px;
        border-radius: var(--radius-md);
        font-size: .875rem;
        font-weight: 600;
        line-height: 1;
        font-family: 'Inter', sans-serif;
        transition: background .14s ease, border-color .14s ease, transform .11s ease, box-shadow .14s ease;
    }
    button:active, .ui-btn:active { transform: translateY(1px); }

    /* ── form controls ───────────────────────────────────────────────── */
    input[type="text"], input[type="search"], input[type="url"],
    input[type="email"], input[type="password"], input[type="number"],
    select, textarea, .ui-input {
        min-height: 40px;
        border-radius: var(--radius-md);
        font-size: .875rem;
        font-family: 'Inter', sans-serif;
        line-height: 1.3;
    }

    input:focus, select:focus, textarea:focus,
    button:focus-visible, .ui-btn:focus-visible, .ui-input:focus {
        outline: none;
        border-color: var(--border-focus) !important;
        box-shadow: var(--focus-ring) !important;
    }

    /* ── shared layout helpers ───────────────────────────────────────── */
    .ui-pill  { border-radius: 999px; }
    .ui-panel { border-radius: var(--radius-xl); }

    /* ── text-label ──────────────────────────────────────────────────── */
    .text-label {
        font-size: .65rem;
        font-weight: 700;
        letter-spacing: .1em;
        text-transform: uppercase;
        color: rgba(255,255,255,.38);
    }
</style>
