<style>
    :root {
        --ui-radius-sm: 10px;
        --ui-radius-md: 12px;
        --ui-radius-lg: 16px;
        --ui-control-h: 42px;
        --ui-gap-y: 1.25rem;
        --ui-focus-ring: 0 0 0 2px rgba(52,84,209,.35);
        --ui-focus-border: rgba(107,143,255,.6);
    }

    main.page-stack > * + * {
        margin-top: var(--ui-gap-y);
    }

    button,
    .ui-btn {
        min-height: var(--ui-control-h);
        border-radius: var(--ui-radius-md);
        font-size: .875rem;
        font-weight: 600;
        line-height: 1;
        transition: background .15s ease, border-color .15s ease, transform .12s ease;
    }

    button:active,
    .ui-btn:active {
        transform: translateY(1px);
    }

    input[type="text"],
    input[type="search"],
    input[type="url"],
    input[type="email"],
    input[type="password"],
    input[type="number"],
    select,
    textarea,
    .ui-input {
        min-height: var(--ui-control-h);
        border-radius: var(--ui-radius-md);
        font-size: .875rem;
        line-height: 1.2;
    }

    input:focus,
    select:focus,
    textarea:focus,
    button:focus-visible,
    .ui-btn:focus-visible,
    .ui-input:focus {
        outline: none;
        border-color: var(--ui-focus-border) !important;
        box-shadow: var(--ui-focus-ring) !important;
    }

    .ui-pill {
        border-radius: 999px;
    }

    .ui-panel {
        border-radius: 1rem;
    }
</style>
