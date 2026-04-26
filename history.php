<?php
// ============================================================
// history.php - Ιστορικό Μετρήσεων & Αναφορές
// ============================================================
require_once 'config/db.php';

// ── Διαγραφή εγγραφής ──
if (isset($_POST['delete_id'])) {
    $del_id = (int) $_POST['delete_id'];
    mysqli_query($con, "DELETE FROM energy_readings WHERE id = $del_id");
    header('Location: history.php?' . http_build_query($_GET));
    exit;
}

// ── Φίλτρα ──
$f_machine = isset($_GET['machine_id']) ? (int) $_GET['machine_id'] : 0;
$f_zone    = isset($_GET['zone_id'])    ? (int) $_GET['zone_id']    : 0;
$f_from    = (!empty($_GET['from'])) ? $_GET['from'] : date('Y-m-d', strtotime('-30 days'));
$f_to      = (!empty($_GET['to']))   ? $_GET['to']   : date('Y-m-d');

// ── WHERE clause ──
$where = ["DATE(er.recorded_at) BETWEEN '$f_from' AND '$f_to'"];
if ($f_machine > 0) $where[] = "er.machine_id = $f_machine";
if ($f_zone    > 0) $where[] = "m.zone_id = $f_zone";
$where_sql = 'WHERE ' . implode(' AND ', $where);

// ── Αποτελέσματα ──
$res = mysqli_query($con, "
    SELECT er.id, er.kwh, er.recorded_at, er.notes,
           m.name AS machine_name, z.name AS zone_name
    FROM energy_readings er
    JOIN machines m ON er.machine_id = m.id
    JOIN zones    z ON m.zone_id     = z.id
    $where_sql
    ORDER BY er.recorded_at DESC
    LIMIT 200
");
$rows = [];
while ($row = mysqli_fetch_assoc($res)) $rows[] = $row;

// ── Σύνολα ──
$sr = mysqli_query($con, "
    SELECT COUNT(*) AS cnt, SUM(er.kwh) AS total_kwh,
           AVG(er.kwh) AS avg_kwh, MAX(er.kwh) AS max_kwh
    FROM energy_readings er
    JOIN machines m ON er.machine_id = m.id
    $where_sql
");
$stats = mysqli_fetch_assoc($sr);

// ── Dropdowns ──
$zones_q = mysqli_query($con, "SELECT id, name FROM zones ORDER BY name");
$mach_all = mysqli_query($con, "
    SELECT m.id, m.name, z.name AS zone_name
    FROM machines m JOIN zones z ON m.zone_id = z.id
    ORDER BY z.name, m.name
");

mysqli_close($con);
?>
<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Energy Monitor | Ιστορικό</title>
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@700&family=Exo+2:wght@300;400;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg:#0d1117; --surface:#161b22; --surface2:#21262d;
            --accent:#f0a500; --accent2:#58a6ff; --green:#3fb950;
            --red:#f85149; --text:#e6edf3; --muted:#8b949e; --border:#30363d;
        }
        * { margin:0; padding:0; box-sizing:border-box; }
        body { background:var(--bg); color:var(--text); font-family:'Exo 2',sans-serif; min-height:100vh; }
        nav {
            background:var(--surface); border-bottom:1px solid var(--border);
            display:flex; align-items:center; justify-content:space-between;
            padding:0 2rem; height:62px; position:sticky; top:0; z-index:100;
        }
        .logo { font-family:'Orbitron',monospace; font-size:1rem; color:var(--accent); letter-spacing:3px; }
        .logo span { color:var(--text); }
        .nav-links { display:flex; gap:0.4rem; }
        .nav-links a {
            padding:0.45rem 1rem; border-radius:6px; text-decoration:none;
            color:var(--muted); font-size:0.88rem; font-weight:600; transition:all 0.18s;
        }
        .nav-links a:hover { background:var(--surface2); color:var(--accent); }
        .nav-links a.active { background:var(--surface2); color:var(--accent); border:1px solid var(--accent); }

        .container { max-width:1300px; margin:0 auto; padding:1.8rem 2rem; }
        .page-title { font-family:'Orbitron',monospace; font-size:1.3rem; color:var(--accent); margin-bottom:0.3rem; }
        .page-sub { color:var(--muted); font-size:0.9rem; margin-bottom:1.6rem; }

        /* Filter card */
        .filter-card {
            background:var(--surface); border:1px solid var(--border);
            border-radius:12px; padding:1.4rem 1.6rem; margin-bottom:1.2rem;
        }
        .filter-title { font-size:0.75rem; text-transform:uppercase; letter-spacing:1.5px; color:var(--muted); margin-bottom:1rem; }
        .filter-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(160px,1fr)); gap:1rem; align-items:end; }
        .filter-grid label { display:block; font-size:0.72rem; text-transform:uppercase; letter-spacing:1px; color:var(--muted); margin-bottom:0.4rem; font-weight:600; }
        input, select {
            width:100%; background:var(--surface2); border:1px solid var(--border);
            border-radius:7px; padding:0.65rem 0.9rem; color:var(--text);
            font-family:'Exo 2',sans-serif; font-size:0.88rem; outline:none;
        }
        input:focus, select:focus { border-color:var(--accent); }
        .btn-filter {
            padding:0.68rem 1.4rem; background:var(--accent); color:#0d1117;
            border:none; border-radius:7px; font-weight:700; cursor:pointer;
            font-family:'Orbitron',monospace; font-size:0.75rem; letter-spacing:1px;
        }
        .btn-filter:hover { opacity:0.85; }

        /* Mini stats */
        .mini-stats { display:grid; grid-template-columns:repeat(4,1fr); gap:1rem; margin-bottom:1.2rem; }
        .mini-stat {
            background:var(--surface); border:1px solid var(--border);
            border-radius:10px; padding:1rem 1.2rem;
        }
        .mini-stat-label { font-size:0.72rem; text-transform:uppercase; letter-spacing:1px; color:var(--muted); }
        .mini-stat-value { font-family:'Orbitron',monospace; font-size:1.35rem; color:var(--accent); margin-top:0.3rem; }

        /* Table */
        .table-card { background:var(--surface); border:1px solid var(--border); border-radius:12px; overflow:hidden; }
        .table-header {
            padding:1rem 1.5rem; border-bottom:1px solid var(--border);
            display:flex; justify-content:space-between; align-items:center;
        }
        .table-header-title { font-size:0.75rem; text-transform:uppercase; letter-spacing:1.5px; color:var(--muted); }
        .table-header-count { font-size:0.82rem; color:var(--muted); }
        table { width:100%; border-collapse:collapse; }
        th {
            padding:0.65rem 1.2rem; font-size:0.72rem; color:var(--muted);
            text-transform:uppercase; letter-spacing:1px; text-align:left;
            border-bottom:1px solid var(--border); font-weight:600;
        }
        td { padding:0.85rem 1.2rem; font-size:0.88rem; border-bottom:1px solid var(--border); }
        tr:last-child td { border-bottom:none; }
        tr:hover td { background:var(--surface2); }
        .badge { display:inline-block; padding:0.18rem 0.65rem; border-radius:20px; font-size:0.72rem; font-weight:700; background:rgba(88,166,255,0.12); color:var(--accent2); border:1px solid rgba(88,166,255,0.25); }
        .kwh { font-family:'Orbitron',monospace; color:var(--green); font-size:0.82rem; }
        .btn-del {
            background:rgba(248,81,73,0.1); border:1px solid rgba(248,81,73,0.3);
            color:var(--red); padding:0.28rem 0.7rem; border-radius:5px;
            cursor:pointer; font-size:0.8rem;
        }
        .btn-del:hover { background:rgba(248,81,73,0.2); }
        .no-data { text-align:center; padding:3rem; color:var(--muted); }

        @media (max-width:700px) { .mini-stats { grid-template-columns:repeat(2,1fr); } }
    </style>
</head>
<body>

<nav>
    <div class="logo">ENERGY<span>MONITOR</span></div>
    <div class="nav-links">
        <a href="index.php">⚡ Dashboard</a>
        <a href="add_reading.php">➕ Καταχώρηση</a>
        <a href="history.php" class="active">📋 Ιστορικό</a>
    </div>
</nav>

<div class="container">
    <div class="page-title">📋 ΙΣΤΟΡΙΚΟ ΜΕΤΡΗΣΕΩΝ</div>
    <div class="page-sub">Αναζήτηση, φιλτράρισμα και ανάλυση ενεργειακών δεδομένων</div>

    <!-- FILTERS -->
    <div class="filter-card">
        <div class="filter-title">🔍 Φίλτρα Αναζήτησης</div>
        <form method="GET" action="history.php">
            <div class="filter-grid">
                <div>
                    <label>Από Ημερομηνία</label>
                    <input type="date" name="from" value="<?= htmlspecialchars($f_from) ?>">
                </div>
                <div>
                    <label>Έως Ημερομηνία</label>
                    <input type="date" name="to" value="<?= htmlspecialchars($f_to) ?>">
                </div>
                <div>
                    <label>Ζώνη</label>
                    <select name="zone_id">
                        <option value="0">Όλες οι ζώνες</option>
                        <?php while ($z = mysqli_fetch_assoc($zones_q)): ?>
                        <option value="<?= $z['id'] ?>" <?= $f_zone == $z['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($z['name']) ?>
                        </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div>
                    <label>Μηχάνημα</label>
                    <select name="machine_id">
                        <option value="0">Όλα τα μηχανήματα</option>
                        <?php while ($m = mysqli_fetch_assoc($mach_all)): ?>
                        <option value="<?= $m['id'] ?>" <?= $f_machine == $m['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($m['name']) ?>
                        </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div style="display:flex;align-items:flex-end">
                    <button type="submit" class="btn-filter">ΦΙΛΤΡΟ</button>
                </div>
            </div>
        </form>
    </div>

    <!-- MINI STATS -->
    <div class="mini-stats">
        <div class="mini-stat">
            <div class="mini-stat-label">Εγγραφές</div>
            <div class="mini-stat-value"><?= number_format((int)$stats['cnt']) ?></div>
        </div>
        <div class="mini-stat">
            <div class="mini-stat-label">Σύνολο kWh</div>
            <div class="mini-stat-value"><?= number_format((float)$stats['total_kwh'], 1) ?></div>
        </div>
        <div class="mini-stat">
            <div class="mini-stat-label">Μέσος Όρος kWh</div>
            <div class="mini-stat-value"><?= number_format((float)$stats['avg_kwh'], 2) ?></div>
        </div>
        <div class="mini-stat">
            <div class="mini-stat-label">Μέγιστο kWh</div>
            <div class="mini-stat-value"><?= number_format((float)$stats['max_kwh'], 2) ?></div>
        </div>
    </div>

    <!-- TABLE -->
    <div class="table-card">
        <div class="table-header">
            <span class="table-header-title">📄 Αποτελέσματα</span>
            <span class="table-header-count"><?= count($rows) ?> εγγραφές</span>
        </div>

        <?php if (empty($rows)): ?>
            <div class="no-data">⚡ Δεν βρέθηκαν μετρήσεις για τα επιλεγμένα φίλτρα.</div>
        <?php else: ?>
        <div style="overflow-x:auto">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Μηχάνημα</th>
                    <th>Ζώνη</th>
                    <th>kWh</th>
                    <th>Ημερομηνία / Ώρα</th>
                    <th>Σημειώσεις</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rows as $row): ?>
                <tr>
                    <td style="color:var(--muted);font-size:0.78rem"><?= $row['id'] ?></td>
                    <td><?= htmlspecialchars($row['machine_name']) ?></td>
                    <td><span class="badge"><?= htmlspecialchars($row['zone_name']) ?></span></td>
                    <td class="kwh"><?= number_format($row['kwh'], 3) ?></td>
                    <td><?= date('d/m/Y H:i', strtotime($row['recorded_at'])) ?></td>
                    <td style="color:var(--muted)"><?= htmlspecialchars($row['notes'] ?? '—') ?></td>
                    <td>
                        <form method="POST" onsubmit="return confirm('Διαγραφή αυτής της μέτρησης;')">
                            <input type="hidden" name="delete_id" value="<?= $row['id'] ?>">
                            <button type="submit" class="btn-del">🗑</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        </div>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
