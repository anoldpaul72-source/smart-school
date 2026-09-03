<?php
session_start();
// If a user is already logged in, show a quick shortcut link to their dashboard
$dashboard_link = "";
$dashboard_text = "";
if (isset($_SESSION['role'])) {
    $dashboard_text = "Go to My Dashboard";
    if ($_SESSION['role'] === 'Admin') { $dashboard_link = "admin.php"; }
    elseif ($_SESSION['role'] === 'Teacher') { $dashboard_link = "weka_alama.php"; }
    elseif ($_SESSION['role'] === 'Parent') { $dashboard_link = "ripoti.php"; }
    else { $dashboard_link = "dashboard_leaders.php"; }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Smart-Results | School Management Portal</title>
    <style>
        :root {
            --primary-color: #2563eb;
            --secondary-color: #1e3a8a;
            --accent-color: #3b82f6;
            --text-dark: #1e293b;
            --text-light: #64748b;
            --bg-light: #f8fafc;
        }

        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: var(--bg-light);
            color: var(--text-dark);
            line-height: 1.6;
        }

        /* Header & Navigation */
        header {
            background-color: white;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .nav-container {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 20px;
        }

        .logo {
            font-size: 24px;
            font-weight: 800;
            color: var(--primary-color);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .nav-links a {
            text-decoration: none;
            color: var(--text-dark);
            font-weight: 600;
            margin-left: 25px;
            transition: color 0.2s;
        }

        .nav-links a:hover {
            color: var(--primary-color);
        }

        .btn-portal {
            background-color: var(--primary-color);
            color: white !important;
            padding: 10px 20px;
            border-radius: 6px;
            transition: background 0.2s !important;
        }

        .btn-portal:hover {
            background-color: var(--secondary-color);
        }

        /* Hero Section */
        .hero {
            background: linear-gradient(135deg, #1e3a8a 0%, #2563eb 100%);
            color: white;
            padding: 80px 20px;
            text-align: center;
        }

        .hero-container {
            max-width: 800px;
            margin: 0 auto;
        }

        .hero h1 {
            font-size: 42px;
            font-weight: 800;
            margin-top: 0;
            margin-bottom: 20px;
            letter-spacing: -0.5px;
        }

        .hero p {
            font-size: 18px;
            opacity: 0.9;
            margin-bottom: 35px;
        }

        .hero-btns {
            display: flex;
            justify-content: center;
            gap: 15px;
            flex-wrap: wrap;
        }

        .btn-primary {
            background-color: white;
            color: var(--primary-color);
            padding: 14px 28px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: bold;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(0,0,0,0.15);
        }

        /* Portals Section */
        .portals {
            max-width: 1200px;
            margin: -40px auto 60px auto;
            padding: 0 20px;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 25px;
        }

        .portal-card {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.05);
            text-align: center;
            transition: transform 0.3s;
            border-top: 4px solid transparent;
        }

        .portal-card:hover {
            transform: translateY(-5px);
        }

        .card-admin { border-top-color: #ef4444; }
        .card-teacher { border-top-color: #10b981; }
        .card-parent { border-top-color: #f59e0b; }

        .portal-icon {
            font-size: 40px;
            margin-bottom: 15px;
        }

        .portal-card h3 {
            margin: 0 0 10px 0;
            font-size: 20px;
            color: var(--text-dark);
        }

        .portal-card p {
            color: var(--text-light);
            font-size: 14px;
            margin-bottom: 25px;
        }

        .btn-card {
            display: inline-block;
            padding: 10px 20px;
            border: 2px solid #e2e8f0;
            border-radius: 6px;
            text-decoration: none;
            color: var(--text-dark);
            font-weight: bold;
            transition: all 0.2s;
        }

        .portal-card:hover .btn-card {
            background-color: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
        }

        /* Features Section */
        .features {
            max-width: 1000px;
            margin: 0 auto 80px auto;
            padding: 0 20px;
            text-align: center;
        }

        .features h2 {
            font-size: 32px;
            margin-bottom: 40px;
            position: relative;
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 30px;
        }

        .feature-item h4 {
            font-size: 18px;
            margin: 10px 0;
        }

        .feature-item p {
            color: var(--text-light);
            font-size: 14px;
        }

        /* Footer */
        footer {
            background-color: #0f172a;
            color: #94a3b8;
            padding: 40px 20px;
            text-align: center;
            font-size: 14px;
            border-top: 1px solid #1e293b;
        }

        footer b {
            color: white;
        }
    </style>
</head>
<body>

    <header>
        <div class="nav-container">
            <a href="index.php" class="logo">📊 Smart-Results</a>
            <nav class="nav-links">
                <a href="#portals">Portals</a>
                <a href="#features">Features</a>
                <a href="login.php" class="btn-portal">Login Entry</a>
            </nav>
        </div>
    </header>

    <section class="hero">
        <div class="hero-container">
            <h1>Multi-School Performance Analytics Platform</h1>
            <p>A cloud-integrated school architecture designed to streamline student registration, monitor evaluation scores, and deliver instant academic report tracking directly to parents.</p>
            <div class="hero-btns">
                <?php if (!empty($dashboard_link)): ?>
                    <a href="<?php echo $dashboard_link; ?>" class="btn-primary">🔄 <?php echo $dashboard_text; ?></a>
                <?php else: ?>
                    <a href="#portals" class="btn-primary">🚀 Access Your Portal</a>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <section class="portals" id="portals">
        <div class="portal-card card-admin">
            <div class="portal-icon">⚙️</div>
            <h3>Administration Portal</h3>
            <p>Manage multiple schools, ingest bulk CSV student lists, map systems, and manage registration records safely.</p>
            <a href="login.php" class="btn-card">Enter Admin Panel</a>
        </div>

        <div class="portal-card card-teacher">
            <div class="portal-icon">👨‍🏫</div>
            <h3>Academic Portal</h3>
            <p>Input term grades, compute test evaluation benchmarks, modify subject points, and export dynamic result metrics.</p>
            <a href="login.php" class="btn-card">Enter Teacher Desk</a>
        </div>

        <div class="portal-card card-parent">
            <div class="portal-icon">👪</div>
            <h3>Parent Portal</h3>
            <p>Track your student's live terminal growth data, view direct report sheets, and configure profile parameters.</p>
            <a href="login.php" class="btn-card">View Student Report</a>
        </div>
    </section>

    <section class="features" id="features">
        <h2>Engineered for Modern Institutions</h2>
        <div class="features-grid">
            <div class="feature-item">
                <div style="font-size: 30px;">⏱️</div>
                <h4>Zero-Latency Parsing</h4>
                <p>Process entire classroom data directories via streamlined CSV ingestion models instantly without timing out.</p>
            </div>
            <div class="feature-item">
                <div style="font-size: 30px;">🔒</div>
                <h4>Secure Cryptography</h4>
                <p>Equipped with industry-standard hashing protocols protecting user records and managing individual account privacy.</p>
            </div>
            <div class="feature-item">
                <div style="font-size: 30px;">📱</div>
                <h4>Fluid Architecture</h4>
                <p>Engineered responsively to fluidly adapt down to handheld mobile devices for seamless parent access anywhere.</p>
            </div>
        </div>
    </section>

    <footer>
        <p>&copy; 2026 <b>Smart-Results Engine</b>. All Institutional Data Rights Reserved.</p>
        <p style="font-size: 11px; margin-top: 5px; opacity: 0.6;">Powered by dynamic cross-school database distribution frameworks.</p>
    </footer>

</body>
</html>