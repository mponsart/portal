<style>
    /* ══════════════════════════════════════════════════════════════
       MATERIAL DESIGN 3 — Speed Cloud Design Tokens
       Seed color: #7c3aed (Violet)  |  Dark theme
    ══════════════════════════════════════════════════════════════ */
    :root {
        /* ── Background / Surface ──────────────────────────────── */
        --bg:              #0D0C14;
        --surface:         #1A1825;
        --surface-1:       #221F2E;   /* elevation 1 — +5 % primary */
        --surface-2:       #272434;   /* elevation 2 — +8 % */
        --surface-3:       #2C293A;   /* elevation 3 — +11 % */
        --surface-5:       #322F40;   /* elevation 5 — +14 % */
        --surface-hov:     rgba(208,188,255,.08);
        --surface-pressed: rgba(208,188,255,.12);

        /* ── Primary (Violet) ───────────────────────────────────── */
        --primary:         #D0BCFF;   /* primary on dark */
        --primary-on:      #381E72;
        --primary-cnt:     #4F378B;   /* primary container */
        --primary-cnt-on:  #EADDFF;   /* on primary container */
        --primary-dk:      #5b21b6;   /* compat */
        --primary-lt:      #D0BCFF;   /* compat alias */

        /* ── Secondary ─────────────────────────────────────────── */
        --secondary:       #CCC2DC;
        --secondary-cnt:   #4A4458;
        --secondary-cnt-on:#E8DEF8;

        /* ── Tertiary (Cyan) ────────────────────────────────────── */
        --tertiary:        #9EECEB;
        --tertiary-cnt:    #004F50;
        --accent:          #0891b2;   /* compat */
        --accent-lt:       #9EECEB;

        /* ── Semantic ───────────────────────────────────────────── */
        --success:         #6DD58C;
        --success-lt:      #89F5A8;
        --success-cnt:     rgba(5,83,32,.55);
        --warning:         #FFB951;
        --warning-lt:      #FFD8A2;
        --warning-cnt:     rgba(92,57,0,.55);
        --danger:          #F2B8B8;
        --danger-lt:       #F9DEDC;
        --danger-cnt:      rgba(140,29,24,.55);

        /* ── On-surface ─────────────────────────────────────────── */
        --on-surface:      #E6E1E5;
        --on-surface-var:  #CAC4D0;
        --outline:         #938F99;
        --outline-var:     #49454F;

        /* ── Backward-compat border aliases ─────────────────────── */
        --border:          rgba(208,188,255,.12);
        --border-hov:      rgba(208,188,255,.28);
        --border-focus:    var(--primary);

        /* ── MD3 Shape ──────────────────────────────────────────── */
        --shape-xs:    4px;
        --shape-sm:    8px;
        --shape-md:    12px;
        --shape-lg:    16px;
        --shape-xl:    28px;
        --shape-full:  9999px;

        /* ── Backward-compat radius aliases ─────────────────────── */
        --radius-sm:   var(--shape-sm);
        --radius-md:   var(--shape-md);
        --radius-lg:   var(--shape-lg);
        --radius-xl:   var(--shape-xl);

        /* ── MD3 Elevation (tonal, dark mode) ──────────────────── */
        --elev-1: 0 1px 2px rgba(0,0,0,.45), 0 1px 3px 1px rgba(0,0,0,.25);
        --elev-2: 0 1px 2px rgba(0,0,0,.45), 0 2px 6px 2px rgba(0,0,0,.25);
        --elev-3: 0 4px 8px 3px rgba(0,0,0,.25), 0 1px 3px rgba(0,0,0,.45);
        --elev-4: 0 6px 10px 4px rgba(0,0,0,.25), 0 2px 3px rgba(0,0,0,.45);
        --elev-5: 0 8px 12px 6px rgba(0,0,0,.25), 0 4px 4px rgba(0,0,0,.45);

        /* ── Focus ──────────────────────────────────────────────── */
        --focus-ring: 0 0 0 3px rgba(208,188,255,.32);

        /* ── Spacing (8dp grid) ─────────────────────────────────── */
        --gap-y: 1.5rem;
        --sp-1: 4px; --sp-2: 8px;  --sp-3: 12px; --sp-4: 16px;
        --sp-5: 20px; --sp-6: 24px; --sp-8: 32px; --sp-10: 40px;
    }

    *, *::before, *::after { box-sizing: border-box; }
    html { scroll-behavior: smooth; }
    main.page-stack > * + * { margin-top: var(--gap-y); }

    /* ── MD3 Buttons ─────────────────────────────────────────────── */
    button, .ui-btn {
        min-height: 40px;
        border-radius: var(--shape-full);
        font-size: .875rem;
        font-weight: 500;
        letter-spacing: .00625em;
        line-height: 1;
        font-family: 'Inter', sans-serif;
        transition: background .14s, box-shadow .14s, transform .1s;
        cursor: pointer;
    }
    button:active, .ui-btn:active { transform: scale(.97); }
    button:focus-visible, .ui-btn:focus-visible {
        outline: none;
        box-shadow: var(--focus-ring) !important;
    }

    /* ── Form Controls ──────────────────────────────────────────── */
    input[type="text"], input[type="search"], input[type="url"],
    input[type="email"], input[type="password"], input[type="number"],
    select, textarea, .ui-input {
        min-height: 40px;
        border-radius: var(--shape-md);
        font-size: .875rem;
        font-family: 'Inter', sans-serif;
        line-height: 1.4;
    }
    input:focus, select:focus, textarea:focus,
    button:focus-visible, .ui-btn:focus-visible, .ui-input:focus {
        outline: none;
        border-color: var(--primary) !important;
        box-shadow: var(--focus-ring) !important;
    }

    /* ── MD3 Cards ──────────────────────────────────────────────── */
    .panel,
    .md-card {
        background: var(--surface-1);
        border-radius: var(--shape-xl);
        box-shadow: var(--elev-1);
        transition: box-shadow .2s;
    }
    .md-card-outlined {
        background: var(--surface);
        border: 1px solid var(--outline-var);
        border-radius: var(--shape-xl);
        transition: border-color .14s, background .14s, box-shadow .2s;
    }
    .md-card-outlined:hover {
        border-color: var(--primary);
        background: var(--surface-1);
        box-shadow: var(--elev-2);
    }

    /* ── MD3 Chips ──────────────────────────────────────────────── */
    .md-chip {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        height: 32px;
        padding: 0 16px;
        border-radius: var(--shape-full);
        font-size: .8125rem;
        font-weight: 500;
        background: transparent;
        border: 1px solid var(--outline);
        color: var(--on-surface);
        transition: background .14s, border-color .14s;
        cursor: pointer;
        white-space: nowrap;
        font-family: 'Inter', sans-serif;
    }
    .md-chip:hover { background: rgba(208,188,255,.08); }
    .md-chip.active {
        background: var(--secondary-cnt);
        border-color: transparent;
        color: var(--secondary-cnt-on);
    }

    /* ── MD3 FAB ────────────────────────────────────────────────── */
    .md-fab {
        width: 56px; height: 56px;
        border-radius: var(--shape-lg);
        background: var(--primary-cnt);
        color: var(--primary-cnt-on);
        border: none;
        display: flex; align-items: center; justify-content: center;
        box-shadow: var(--elev-3);
        transition: box-shadow .2s, transform .2s;
        cursor: pointer;
    }
    .md-fab:hover { box-shadow: var(--elev-4); transform: scale(1.05); }
    .md-fab:active { transform: scale(.95); box-shadow: var(--elev-1); }

    /* ── MD3 List Item ──────────────────────────────────────────── */
    .md-list-item {
        display: flex; align-items: center; gap: 16px;
        padding: 12px 16px;
        border-radius: var(--shape-md);
        transition: background .14s;
    }
    .md-list-item:hover { background: rgba(208,188,255,.06); }

    /* ── Layout helpers ─────────────────────────────────────────── */
    .ui-pill  { border-radius: var(--shape-full); }
    .ui-panel { border-radius: var(--shape-xl); }

    /* ── MD3 Label Small ────────────────────────────────────────── */
    .text-label {
        font-size: .6875rem;
        font-weight: 500;
        letter-spacing: .05em;
        text-transform: uppercase;
        color: var(--on-surface-var);
    }

    /* ── Ambient glow ───────────────────────────────────────────── */
    .bg-ambient {
        position: fixed; inset: 0; pointer-events: none; z-index: 0;
        background:
            radial-gradient(ellipse 70% 55% at 8%   0%,  rgba(79,55,139,.38) 0%, transparent 55%),
            radial-gradient(ellipse 55% 45% at 94% 100%, rgba(0,79,80,.28)   0%, transparent 55%),
            radial-gradient(ellipse 40% 32% at 52%  52%, rgba(74,68,88,.12)  0%, transparent 68%);
    }

    /* ── Custom scrollbar ───────────────────────────────────────── */
    ::-webkit-scrollbar { width: 5px; height: 5px; }
    ::-webkit-scrollbar-track { background: transparent; }
    ::-webkit-scrollbar-thumb { background: var(--outline-var); border-radius: 3px; }
    ::-webkit-scrollbar-thumb:hover { background: var(--outline); }
</style>
