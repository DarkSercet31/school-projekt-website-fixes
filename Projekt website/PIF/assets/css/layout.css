:root {
    --bg-main-dark: radial-gradient(circle at top left, #1d4ed8 0, #020617 45%, #020617 100%);
    --bg-main-light: radial-gradient(circle at top left, #e0f2fe 0, #f9fafb 40%, #f3f4f6 100%);
}

/* Grundschrift / Transition */
body {
    margin: 0;
    font-family: system-ui, -apple-system, "Segoe UI", sans-serif;
    transition: background 0.4s ease, color 0.4s ease;
}

/* ---------- Gemeinsames Layout ---------- */

.navbar-brand-badge {
    width: 26px;
    height: 26px;
    border-radius: 999px;
    font-size: 0.7rem;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    margin-right: .5rem;
}

.main-shell {
    min-height: 100vh;
    padding-top: 80px;   /* Platz für Navbar */
    padding-bottom: 40px;
}

.glass-card {
    border-radius: 1rem;
    padding: 1.25rem 1.25rem 1.1rem;
    margin-bottom: 1.25rem;
    backdrop-filter: blur(14px);
}

.glass-card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 0.75rem;
    margin-bottom: 0.9rem;
}

.glass-card-title {
    font-size: 1rem;
    font-weight: 600;
    letter-spacing: .02em;
    margin-bottom: .1rem;
}

.glass-card-sub {
    font-size: .8rem;
    margin: 0;
}

.pill {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    border-radius: 999px;
    padding: 0.15rem 0.7rem;
    font-size: .75rem;
    white-space: nowrap;
}

.pill-dot {
    width: 7px;
    height: 7px;
    border-radius: 999px;
}

.chart-placeholder {
    height: 180px;
    border-radius: .9rem;
    font-size: .8rem;
}

.table-glass {
    width: 100%;
    border-collapse: collapse;
    font-size: .78rem;
}

.table-glass th,
.table-glass td {
    padding: 0.45rem 0.6rem;
}

.theme-toggle {
    font-size: .78rem;
    border-radius: 999px;
    padding: 0.25rem 0.8rem;
}

/* ---------- DARK MODE ---------- */

html[data-theme="dark"] body {
    background: var(--bg-main-dark);
    color: #e5e7eb;
}

html[data-theme="dark"] .app-navbar {
    background: rgba(15, 23, 42, 0.9);
    backdrop-filter: blur(14px);
    border-bottom: 1px solid rgba(148, 163, 184, 0.35);
}

html[data-theme="dark"] .navbar-brand-badge {
    background: linear-gradient(135deg, #38bdf8, #4f46e5);
    box-shadow: 0 0 0 1px rgba(15, 23, 42, 0.6);
    color: #ecfeff;
}

html[data-theme="dark"] .nav-link {
    color: #cbd5f5 !important;
}

html[data-theme="dark"] .nav-link.active,
html[data-theme="dark"] .nav-link:hover {
    color: #ffffff !important;
}

html[data-theme="dark"] .glass-card {
    border: 1px solid rgba(148, 163, 184, 0.45);
    background: radial-gradient(circle at top left,
                rgba(148, 163, 184, 0.22),
                rgba(15, 23, 42, 0.92));
    box-shadow:
        0 22px 45px rgba(15, 23, 42, 0.85),
        0 0 0 1px rgba(15, 23, 42, 0.8);
}

html[data-theme="dark"] .glass-card-title { color: #f9fafb; }
html[data-theme="dark"] .glass-card-sub { color: #9ca3af; }

html[data-theme="dark"] .pill {
    border: 1px solid rgba(148, 163, 184, 0.55);
    color: #e5e7eb;
    background: radial-gradient(circle at top left,
                rgba(148, 163, 184, 0.22),
                rgba(15, 23, 42, 0.96));
}
html[data-theme="dark"] .pill-dot { background:#4ade80; }

html[data-theme="dark"] .form-label {
    font-size: .8rem;
    font-weight: 500;
    color: #e5e7eb;
}

html[data-theme="dark"] .form-control,
html[data-theme="dark"] .form-select {
    color: #e5e7eb;
    background: rgba(15, 23, 42, 0.85);
    border-radius: .7rem;
    border: 1px solid rgba(148, 163, 184, 0.55);
    font-size: .86rem;
}

html[data-theme="dark"] .form-control:focus,
html[data-theme="dark"] .form-select:focus {
    box-shadow: 0 0 0 1px #38bdf8;
    border-color: #38bdf8;
    background: rgba(15, 23, 42, 0.95);
    color: #f9fafb;
}

html[data-theme="dark"] .btn-primary-soft {
    background: linear-gradient(135deg, #38bdf8, #4f46e5);
    border: 0;
    color: #ecfeff;
    box-shadow: 0 8px 20px rgba(15, 23, 42, 0.7);
    font-size: .86rem;
    padding-inline: 0.9rem;
    border-radius: 999px;
}

html[data-theme="dark"] .btn-chip {
    background: transparent;
    border-radius: 999px;
    border: 1px solid rgba(148, 163, 184, 0.5);
    color: #cbd5f5;
    font-size: .78rem;
    padding-inline: 0.75rem;
}

html[data-theme="dark"] .btn-chip:hover {
    background: rgba(15, 23, 42, 0.9);
    color: #ffffff;
}

html[data-theme="dark"] .chart-placeholder {
    border: 1px dashed rgba(148, 163, 184, 0.6);
    color: #9ca3af;
    background: radial-gradient(circle at top left,
                rgba(30, 64, 175, 0.35),
                rgba(15, 23, 42, 0.9));
}

html[data-theme="dark"] .table-glass {
    color: #e5e7eb;
}

html[data-theme="dark"] .table-glass th,
html[data-theme="dark"] .table-glass td {
    border-bottom: 1px solid rgba(55, 65, 81, 0.85);
}

html[data-theme="dark"] .table-glass th {
    color: #9ca3af;
    text-transform: uppercase;
    letter-spacing: .06em;
}

html[data-theme="dark"] .table-glass tr:hover td {
    background: rgba(15, 23, 42, 0.78);
}

html[data-theme="dark"] .value-up { color: #4ade80; }
html[data-theme="dark"] .value-down { color: #f97373; }

/* ---------- LIGHT MODE ---------- */

html[data-theme="light"] body {
    background: var(--bg-main-light);
    color: #111827;
}

html[data-theme="light"] .app-navbar {
    background: rgba(255, 255, 255, 0.94);
    backdrop-filter: blur(12px);
    border-bottom: 1px solid rgba(209, 213, 219, 0.9);
}

html[data-theme="light"] .navbar-brand-badge {
    background: linear-gradient(135deg, #2563eb, #22c55e);
    color: #f9fafb;
    box-shadow: 0 0 0 1px rgba(255, 255, 255, 0.7);
}

html[data-theme="light"] .nav-link {
    color: #4b5563 !important;
}

html[data-theme="light"] .nav-link.active,
html[data-theme="light"] .nav-link:hover {
    color: #111827 !important;
}

html[data-theme="light"] .glass-card {
    border: 1px solid rgba(209, 213, 219, 0.9);
    background: radial-gradient(circle at top left,
                rgba(248, 250, 252, 0.98),
                rgba(241, 245, 249, 0.98));
    box-shadow:
        0 22px 45px rgba(15, 23, 42, 0.08),
        0 0 0 1px rgba(255, 255, 255, 0.9);
}

html[data-theme="light"] .glass-card-title { color: #0f172a; }
html[data-theme="light"] .glass-card-sub { color: #6b7280; }

html[data-theme="light"] .pill {
    border: 1px solid rgba(209, 213, 219, 0.9);
    color: #374151;
    background: linear-gradient(135deg, #eff6ff, #f9fafb);
}
html[data-theme="light"] .pill-dot { background:#16a34a; }

html[data-theme="light"] .form-label {
    font-size: .8rem;
    font-weight: 500;
    color: #374151;
}

html[data-theme="light"] .form-control,
html[data-theme="light"] .form-select {
    color: #111827;
    background: #ffffff;
    border-radius: .7rem;
    border: 1px solid #d1d5db;
    font-size: .86rem;
}

html[data-theme="light"] .form-control:focus,
html[data-theme="light"] .form-select:focus {
    box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.3);
    border-color: #3b82f6;
    background: #ffffff;
}

html[data-theme="light"] .btn-primary-soft {
    background: linear-gradient(135deg, #3b82f6, #22c55e);
    border: 0;
    color: #f9fafb;
    box-shadow: 0 10px 20px rgba(15, 23, 42, 0.18);
    font-size: .86rem;
    padding-inline: 0.9rem;
    border-radius: 999px;
}

html[data-theme="light"] .btn-chip {
    background: #ffffff;
    border-radius: 999px;
    border: 1px solid #d1d5db;
    color: #4b5563;
    font-size: .78rem;
    padding-inline: 0.75rem;
}

html[data-theme="light"] .btn-chip:hover {
    background: #eff6ff;
    color: #1f2937;
}

html[data-theme="light"] .chart-placeholder {
    border: 1px dashed #cbd5e1;
    color: #6b7280;
    background: radial-gradient(circle at top left,
                rgba(219, 234, 254, 0.9),
                rgba(248, 250, 252, 0.95));
}

html[data-theme="light"] .table-glass {
    color: #111827;
}

html[data-theme="light"] .table-glass th,
html[data-theme="light"] .table-glass td {
    border-bottom: 1px solid #e5e7eb;
}

html[data-theme="light"] .table-glass th {
    color: #6b7280;
    text-transform: uppercase;
    letter-spacing: .06em;
    background: #f9fafb;
}

html[data-theme="light"] .table-glass tr:hover td {
    background: #f3f4f6;
}

html[data-theme="light"] .value-up { color: #16a34a; }
html[data-theme="light"] .value-down { color: #dc2626; }
.admin-table thead th {
    border-bottom-color: rgba(148, 163, 184, 0.35);
    font-size: 0.78rem;
    text-transform: uppercase;
    letter-spacing: 0.07em;
}

.admin-table tbody td {
    font-size: 0.85rem;
}

.btn-primary-soft {
    background: linear-gradient(135deg, #667eea, #764ba2);
    border: none;
    color: #fff;
}
.btn-primary-soft:hover {
    filter: brightness(1.05);
}
/* Dunkler Tabellenstil in Glass-Karten */
.glass-card .table,
.glass-card .table thead th,
.glass-card .table tbody td {
    background-color: transparent;
    color: #e5e7eb;
    border-color: rgba(148, 163, 184, 0.25);
}

/* Inputs in Admin-Tabellen dunkel machen */
.glass-card .form-control,
.glass-card .form-select {
    background-color: rgba(15, 23, 42, 0.9);
    border-color: rgba(148, 163, 184, 0.4);
    color: #e5e7eb;
}

.glass-card .form-control:focus,
.glass-card .form-select:focus {
    background-color: rgba(15, 23, 42, 1);
    border-color: #4f46e5;
    box-shadow: 0 0 0 1px rgba(79, 70, 229, 0.4);
}

/* Tabellenzeilen leicht abwechselnd abdunkeln */
.admin-table tbody tr:nth-child(even) td {
    background-color: rgba(15, 23, 42, 0.75);
}
.admin-table tbody tr:nth-child(odd) td {
    background-color: rgba(15, 23, 42, 0.6);
}
.table {
    --bs-table-bg: transparent;
    background-color: transparent;
    color: #e5e7eb;
}

.table thead th {
    background-color: transparent;
}
.admin-table {
    border-collapse: separate;
    border-spacing: 0;
}

.admin-table tbody tr:nth-child(even) td {
    background-color: rgba(15, 23, 42, 0.78);
}

.admin-table tbody tr:nth-child(odd) td {
    background-color: rgba(15, 23, 42, 0.68);
}

.admin-table td,
.admin-table th {
    border-color: rgba(148, 163, 184, 0.25);
}
