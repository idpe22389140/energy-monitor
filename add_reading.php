<?php
// ============================================================
// add_reading.php - Καταχώρηση Νέας Μέτρησης
// ============================================================
require_once 'config/db.php';

$success = '';
$error   = '';

// ── Αποθήκευση φόρμας (POST) ──
if (isset($_POST['submit'])) {
    $machine_id  = (int) $_POST['machine_id'];
    $kwh         = (float) $_POST['kwh'];
    $recorded_at = trim($_POST['recorded_at']);
    $notes       = trim($_POST['notes']);

    if ($machine_id <= 0 || $kwh <= 0 || empty($recorded_at)) {
        $error = 'Παρακαλώ συμπληρώστε όλα τα υποχρεωτικά πεδία.';
    } else {
        $sql = "INSERT INTO energy_readings (machine_id, kwh, recorded_at, notes)
                VALUES ('$machine_id', '$kwh', '$recorded_at', '$notes')";
        $rs  = mysqli_query($con, $sql);

        if ($rs) {
            $success = 'Η μέτρηση καταχωρήθηκε επιτυχώς!';
        } else {
            $error = 'Σφάλμα: ' . mysqli_error($con);
        }
    }
}

// ── Φόρτωση μηχανημάτων για dropdown ──
$mach_q = mysqli_query($con, "
    SELECT m.id, m.name, z.name AS zone_name
    FROM machines m
    JOIN zones z ON m.zone_id = z.id
    WHERE m.status = 'active'
    ORDER BY z.name, m.name
");

mysqli_close($con);
?>
<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Energy Monitor | Καταχώρηση</title>
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

        .container { max-width:680px; margin:3rem auto; padding:0 2rem; }
        .page-title { font-family:'Orbitron',monospace; font-size:1.3rem; color:var(--accent); margin-bottom:0.3rem; }
        .page-sub { color:var(--muted); font-size:0.9rem; margin-bottom:2rem; }

        .form-card {
            background:var(--surface); border:1px solid var(--border);
            border-radius:14px; padding:2rem;
        }
        .form-group { margin-bottom:1.4rem; }
        label {
            display:block; font-size:0.75rem; text-transform:uppercase;
            letter-spacing:1.5px; color:var(--muted); margin-bottom:0.5rem; font-weight:600;
        }
        .required { color:var(--accent); }
        input, select, textarea {
            width:100%; background:var(--surface2); border:1px solid var(--border);
            border-radius:8px; padding:0.8rem 1rem; color:var(--text);
            font-family:'Exo 2',sans-serif; font-size:0.95rem;
            transition:border-color 0.2s; outline:none;
        }
        input:focus, select:focus, textarea:focus { border-color:var(--accent); }
        select option { background:var(--surface2); }
        textarea { resize:vertical; min-height:80px; }

        .btn-submit {
            width:100%; padding:1rem; background:var(--accent); color:#0d1117;
            border:none; border-radius:8px; font-size:0.95rem; font-weight:700;
            cursor:pointer; font-family:'Orbitron',monospace; letter-spacing:2px;
            transition:opacity 0.2s;
        }
        .btn-submit:hover { opacity:0.85; }

        .alert { padding:1rem 1.2rem; border-radius:8px; margin-bottom:1.5rem; font-size:0.92rem; }
        .alert-success {
            background:rgba(63,185,80,0.1); border:1px solid rgba(63,185,80,0.35); color:var(--green);
        }
        .alert-error {
            background:rgba(248,81,73,0.1); border:1px solid rgba(248,81,73,0.35); color:var(--red);
        }
    </style>
</head>
<body>

<nav>
    <div class="logo">ENERGY<span>MONITOR</span></div>
    <div class="nav-links">
        <a href="index.php">⚡ Dashboard</a>
        <a href="add_reading.php" class="active">➕ Καταχώρηση</a>
        <a href="history.php">📋 Ιστορικό</a>
    </div>
</nav>

<div class="container">
    <div class="page-title">➕ ΚΑΤΑΧΩΡΗΣΗ ΜΕΤΡΗΣΗΣ</div>
    <div class="page-sub">Εισαγωγή νέας μέτρησης ενεργειακής κατανάλωσης μηχανήματος</div>

    <?php if ($success): ?>
        <div class="alert alert-success">✅ <?= htmlspecialchars($success) ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-error">❌ <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div class="form-card">
        <form method="POST" action="add_reading.php">

            <div class="form-group">
                <label>Μηχάνημα <span class="required">*</span></label>
                <select name="machine_id" required>
                    <option value="">— Επιλέξτε μηχάνημα —</option>
                    <?php
                    $cur_zone = '';
                    while ($m = mysqli_fetch_assoc($mach_q)):
                        if ($m['zone_name'] !== $cur_zone) {
                            if ($cur_zone !== '') echo '</optgroup>';
                            echo '<optgroup label="' . htmlspecialchars($m['zone_name']) . '">';
                            $cur_zone = $m['zone_name'];
                        }
                    ?>
                        <option value="<?= $m['id'] ?>"><?= htmlspecialchars($m['name']) ?></option>
                    <?php endwhile; ?>
                    </optgroup>
                </select>
            </div>

            <div class="form-group">
                <label>Κατανάλωση (kWh) <span class="required">*</span></label>
                <input type="number" name="kwh" step="0.001" min="0.001"
                       placeholder="π.χ. 45.250" required>
            </div>

            <div class="form-group">
                <label>Ημερομηνία &amp; Ώρα Μέτρησης <span class="required">*</span></label>
                <input type="datetime-local" name="recorded_at"
                       value="<?= date('Y-m-d\TH:i') ?>" required>
            </div>

            <div class="form-group">
                <label>Σημειώσεις <small style="text-transform:none;letter-spacing:0">(προαιρετικό)</small></label>
                <textarea name="notes" placeholder="π.χ. Κανονική λειτουργία, βλάβη, συντήρηση..."></textarea>
            </div>

            <input type="submit" name="submit" value="⚡  ΚΑΤΑΧΩΡΗΣΗ" class="btn-submit">
        </form>
    </div>
</div>
</body>
</html>
