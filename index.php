<?php
// ============================================================
// index.php - Dashboard Παρακολούθησης Ενεργειακής Κατανάλωσης
// ============================================================
require_once 'config/db.php';

// --- Στατιστικά για τα KPI cards ---

// Σύνολο kWh σήμερα
$r = mysqli_query($con, "SELECT COALESCE(SUM(kwh), 0) AS total FROM energy_readings WHERE DATE(recorded_at) = CURDATE()");
$today_kwh = mysqli_fetch_assoc($r)['total'];

// Σύνολο kWh τελευταίες 7 μέρες
$r = mysqli_query($con, "SELECT COALESCE(SUM(kwh), 0) AS total FROM energy_readings WHERE recorded_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
$week_kwh = mysqli_fetch_assoc($r)['total'];

// Ενεργά μηχανήματα
$r = mysqli_query($con, "SELECT COUNT(*) AS cnt FROM machines WHERE status = 'active'");
$active_machines = mysqli_fetch_assoc($r)['cnt'];

// Μηχάνημα με μέγιστη κατανάλωση σήμερα
$r = mysqli_query($con, "
    SELECT m.name, COALESCE(SUM(er.kwh), 0) AS total
    FROM machines m
    LEFT JOIN energy_readings er ON m.id = er.machine_id AND DATE(er.recorded_at) = CURDATE()
    GROUP BY m.id, m.name
    ORDER BY total DESC
    LIMIT 1
");
$top = mysqli_fetch_assoc($r);

// --- Δεδομένα γραφήματος: ημερήσια κατανάλωση 7 ημερών ---
$r = mysqli_query($con, "
    SELECT DATE(recorded_at) AS day, SUM(kwh) AS total
    FROM energy_readings
    WHERE recorded_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    GROUP BY DATE(recorded_at)
    ORDER BY day ASC
");
$chart_labels = [];
$chart_values = [];
while ($row = mysqli_fetch_assoc($r)) {
    $chart_labels[] = date('d/m', strtotime($row['day']));
    $chart_values[] = (float)$row['total'];
}

// --- Δεδομένα γραφήματος: κατανάλωση ανά ζώνη ---
$r = mysqli_query($con, "
    SELECT z.name AS zone_name, COALESCE(SUM(er.kwh), 0) AS total
    FROM zones z
    LEFT JOIN machines m ON z.id = m.zone_id
    LEFT JOIN energy_readings er ON m.id = er.machine_id AND er.recorded_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    GROUP BY z.id, z.name
    ORDER BY total DESC
");
$zone_labels = [];
$zone_values = [];
while ($row = mysqli_fetch_assoc($r)) {
    $zone_labels[] = $row['zone_name'];
    $zone_values[] = (float)$row['total'];
}

// --- Πρόσφατες μετρήσεις (8 τελευταίες) ---
$r = mysqli_query($con, "
    SELECT er.kwh, er.recorded_at, er.notes,
           m.name AS machine_name,
           z.name AS zone_name
    FROM energy_readings er
    JOIN machines m ON er.machine_id = m.id
    JOIN zones z ON m.zone_id = z.id
    ORDER BY er.recorded_at DESC
    LIMIT 8
");
$recent = [];
while ($row = mysqli_fetch_assoc($r)) {
    $recent[] = $row;
}

mysqli_close($con);
?>
<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Energy Monitor | Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@700&family=Exo+2:wght@300;400;600&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --bg:       #0d1117;
            --surface:  #161b22;
            --surface2: #21262d;
            --accent:   #f0a500;
            --accent2:  #58a6ff;
            --green:    #3fb950;
            --red:      #f85149;
            --text:     #e6edf3;
            --muted:    #8b949e;
            --border:   #30363d;
        }
        * { margin:0; padding:0; box-sizing:border-box; }
        body {
            background: var(--bg);
            color: var(--text);
            font-family: 'Exo 2', sans-serif;
            min-height: 100vh;
        }

        /* ── NAV ── */
        nav {
            background: var(--surface);
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 2rem;
            height: 62px;
            position: sticky; top: 0; z-index: 100;
        }
        .logo {
            font-family: 'Orbitron', monospace;
            font-size: 1rem;
            color: var(--accent);
            letter-spacing: 3px;
        }
        .logo span { color: var(--text); }
        .nav-links { display: flex; gap: 0.4rem; }
        .nav-links a {
            padding: 0.45rem 1rem;
            border-radius: 6px;
            text-decoration: none;
            color: var(--muted);
            font-size: 0.88rem;
            font-weight: 600;
            transition: all 0.18s;
        }
        .nav-links a:hover { background: var(--surface2); color: var(--accent); }
        .nav-links a.active { background: var(--surface2); color: var(--accent); border: 1px solid var(--accent); }

        /* ── LAYOUT ── */
        .container { max-width: 1380px; margin: 0 auto; padding: 1.8rem 2rem; }
        .page-title { font-family: 'Orbitron', monospace; font-size: 1.3rem; color: var(--accent); margin-bottom: 0.3rem; }
        .page-sub { color: var(--muted); font-size: 0.9rem; margin-bottom: 1.8rem; }

        /* ── KPI CARDS ── */
        .kpi-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1rem;
            margin-bottom: 1.6rem;
        }
        .kpi-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 1.4rem 1.5rem;
            position: relative;
            overflow: hidden;
            transition: transform 0.2s;
        }
        .kpi-card:hover { transform: translateY(-3px); }
        .kpi-card::after {
            content: '';
            position: absolute;
            bottom: 0; left: 0; right: 0;
            height: 3px;
        }
        .kpi-card.c-yellow::after { background: var(--accent); }
        .kpi-card.c-blue::after   { background: var(--accent2); }
        .kpi-card.c-green::after  { background: var(--green); }
        .kpi-card.c-red::after    { background: var(--red); }
        .kpi-label { font-size: 0.72rem; text-transform: uppercase; letter-spacing: 1.5px; color: var(--muted); margin-bottom: 0.7rem; }
        .kpi-value { font-family: 'Orbitron', monospace; font-size: 1.9rem; font-weight: 700; }
        .kpi-value.c-yellow { color: var(--accent); }
        .kpi-value.c-blue   { color: var(--accent2); }
        .kpi-value.c-green  { color: var(--green); }
        .kpi-value.c-red    { color: var(--red); }
        .kpi-unit { font-size: 0.78rem; color: var(--muted); margin-top: 0.3rem; }

        /* ── CHARTS ── */
        .charts-row {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 1rem;
            margin-bottom: 1.6rem;
        }
        .chart-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 1.4rem;
        }
        .card-title {
            font-size: 0.78rem;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            color: var(--muted);
            margin-bottom: 1.2rem;
        }

        /* ── TABLE ── */
        .table-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 12px;
            overflow: hidden;
        }
        .table-header {
            padding: 1rem 1.5rem;
            border-bottom: 1px solid var(--border);
            font-size: 0.78rem;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            color: var(--muted);
        }
        table { width: 100%; border-collapse: collapse; }
        th {
            padding: 0.65rem 1.2rem;
            font-size: 0.75rem;
            color: var(--muted);
            text-transform: uppercase;
            letter-spacing: 1px;
            text-align: left;
            border-bottom: 1px solid var(--border);
            font-weight: 600;
        }
        td {
            padding: 0.85rem 1.2rem;
            font-size: 0.88rem;
            border-bottom: 1px solid var(--border);
        }
        tr:last-child td { border-bottom: none; }
        tr:hover td { background: var(--surface2); }
        .badge {
            display: inline-block;
            padding: 0.18rem 0.65rem;
            border-radius: 20px;
            font-size: 0.72rem;
            font-weight: 700;
            background: rgba(88,166,255,0.12);
            color: var(--accent2);
            border: 1px solid rgba(88,166,255,0.25);
        }
        .kwh { font-family: 'Orbitron', monospace; color: var(--green); font-size: 0.85rem; }

        @media (max-width: 900px) {
            .kpi-grid { grid-template-columns: repeat(2,1fr); }
            .charts-row { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

<nav>
    <div class="logo">ENERGY<span>MONITOR</span></div>
    <div class="nav-links">
        <a href="index.php" class="active">⚡ Dashboard</a>
        <a href="add_reading.php">➕ Καταχώρηση</a>
        <a href="history.php">📋 Ιστορικό</a>
    </div>
</nav>

<div class="container">
    <div class="page-title">⚡ DASHBOARD</div>
    <div class="page-sub">Παρακολούθηση Ενεργειακής Κατανάλωσης — Εργοστάσιο Βιομηχανικής Παραγωγής</div>

    <!-- KPI CARDS -->
    <div class="kpi-grid">
        <div class="kpi-card c-yellow">
            <div class="kpi-label">Κατανάλωση Σήμερα</div>
            <div class="kpi-value c-yellow"><?= number_format($today_kwh, 1) ?></div>
            <div class="kpi-unit">kWh</div>
        </div>
        <div class="kpi-card c-blue">
            <div class="kpi-label">Κατανάλωση Εβδομάδας</div>
            <div class="kpi-value c-blue"><?= number_format($week_kwh, 1) ?></div>
            <div class="kpi-unit">kWh (7 ημέρες)</div>
        </div>
        <div class="kpi-card c-green">
            <div class="kpi-label">Ενεργά Μηχανήματα</div>
            <div class="kpi-value c-green"><?= $active_machines ?></div>
            <div class="kpi-unit">σε λειτουργία</div>
        </div>
        <div class="kpi-card c-red">
            <div class="kpi-label">Κορυφαίο Μηχάνημα</div>
            <div class="kpi-value c-red"><?= number_format($top['total'], 1) ?></div>
            <div class="kpi-unit"><?= htmlspecialchars($top['name']) ?></div>
        </div>
    </div>

    <!-- CHARTS -->
    <div class="charts-row">
        <div class="chart-card">
            <div class="card-title">📈 Ημερήσια Κατανάλωση — Τελευταίες 7 Ημέρες (kWh)</div>
            <canvas id="barChart" height="90"></canvas>
        </div>
        <div class="chart-card">
            <div class="card-title">🏭 Κατανάλωση ανά Ζώνη</div>
            <canvas id="pieChart" height="180"></canvas>
        </div>
    </div>

    <!-- RECENT TABLE -->
    <div class="table-card">
        <div class="table-header">🕐 Πρόσφατες Μετρήσεις</div>
        <table>
            <thead>
                <tr>
                    <th>Μηχάνημα</th>
                    <th>Ζώνη</th>
                    <th>kWh</th>
                    <th>Ημερομηνία / Ώρα</th>
                    <th>Σημειώσεις</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($recent as $row): ?>
                <tr>
                    <td><?= htmlspecialchars($row['machine_name']) ?></td>
                    <td><span class="badge"><?= htmlspecialchars($row['zone_name']) ?></span></td>
                    <td class="kwh"><?= number_format($row['kwh'], 3) ?></td>
                    <td><?= date('d/m/Y H:i', strtotime($row['recorded_at'])) ?></td>
                    <td style="color:var(--muted)"><?= htmlspecialchars($row['notes'] ?? '—') ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
Chart.defaults.color = '#8b949e';
Chart.defaults.borderColor = '#30363d';

// Bar Chart — Ημερήσια
new Chart(document.getElementById('barChart'), {
    type: 'bar',
    data: {
        labels: <?= json_encode($chart_labels) ?>,
        datasets: [{
            label: 'kWh',
            data: <?= json_encode($chart_values) ?>,
            backgroundColor: 'rgba(240,165,0,0.25)',
            borderColor: '#f0a500',
            borderWidth: 2,
            borderRadius: 5
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: {
            y: { grid: { color: 'rgba(48,54,61,0.8)' } },
            x: { grid: { display: false } }
        }
    }
});

// Pie Chart — Ζώνες
new Chart(document.getElementById('pieChart'), {
    type: 'doughnut',
    data: {
        labels: <?= json_encode($zone_labels) ?>,
        datasets: [{
            data: <?= json_encode($zone_values) ?>,
            backgroundColor: [
                'rgba(240,165,0,0.75)',
                'rgba(88,166,255,0.75)',
                'rgba(63,185,80,0.75)',
                'rgba(248,81,73,0.75)'
            ],
            borderWidth: 0
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { position: 'bottom', labels: { padding: 14, font: { size: 11 } } }
        }
    }
});
</script>
</body>
</html>
