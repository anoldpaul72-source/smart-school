<!DOCTYPE html>
<html lang="sw">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MFUMO WA MATOKEO NA TAARIFA ZA SHULE - School Results Portal</title>
    <!-- Google Fonts & Font Awesome Icons -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root {
            --primary: #4f46e5;
            --primary-hover: #4338ca;
            --secondary: #06b6d4;
            --accent: #f59e0b;
            --success: #10b981;
            --danger: #ef4444;
            --dark-bg: #0f172a;
            --card-bg: #1e293b;
            --card-border: rgba(255, 255, 255, 0.08);
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Outfit', sans-serif;
        }

        body {
            background-color: var(--dark-bg);
            color: var(--text-main);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* Top Navbar */
        .navbar {
            background: linear-gradient(135deg, #1e1b4b 0%, #0f172a 100%);
            border-bottom: 1px solid var(--card-border);
            padding: 0.85rem 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 100;
            backdrop-filter: blur(10px);
        }

        .navbar-brand {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            text-decoration: none;
            color: #fff;
            font-size: 1.25rem;
            font-weight: 700;
            letter-spacing: -0.025em;
        }

        .navbar-brand i {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            padding: 0.6rem;
            border-radius: 12px;
            font-size: 1.1rem;
            color: #fff;
        }

        .user-nav {
            display: flex;
            align-items: center;
            gap: 1.25rem;
        }

        .user-info {
            text-align: right;
            line-height: 1.2;
        }

        .user-info .username {
            font-weight: 600;
            font-size: 0.95rem;
        }

        .user-info .role-badge {
            display: inline-block;
            padding: 0.15rem 0.5rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
            background: rgba(79, 70, 229, 0.2);
            color: #a5b4fc;
            border: 1px solid rgba(165, 180, 252, 0.2);
            margin-top: 0.2rem;
        }

        .btn-logout {
            background: rgba(239, 68, 68, 0.15);
            color: #fca5a5;
            border: 1px solid rgba(239, 68, 68, 0.3);
            padding: 0.5rem 0.85rem;
            border-radius: 8px;
            font-size: 0.85rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
        }

        .btn-logout:hover {
            background: var(--danger);
            color: #fff;
        }

        /* Layout Container */
        .app-container {
            display: flex;
            flex: 1;
        }

        /* Sidebar Navigation */
        .sidebar {
            width: 260px;
            background: #111827;
            border-right: 1px solid var(--card-border);
            padding: 1.25rem 0.85rem;
            display: flex;
            flex-direction: column;
            gap: 0.4rem;
        }

        .nav-link {
            display: flex;
            align-items: center;
            gap: 0.85rem;
            padding: 0.75rem 1rem;
            color: var(--text-muted);
            text-decoration: none;
            border-radius: 10px;
            font-weight: 500;
            font-size: 0.92rem;
            transition: all 0.2s ease;
        }

        .nav-link i {
            font-size: 1.1rem;
            width: 22px;
            text-align: center;
        }

        .nav-link:hover, .nav-link.active {
            background: linear-gradient(135deg, var(--primary), #3b82f6);
            color: #ffffff;
            box-shadow: 0 4px 12px rgba(79, 70, 229, 0.3);
        }

        .nav-section-title {
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #64748b;
            margin: 1.25rem 0.5rem 0.4rem 0.5rem;
        }

        /* Main Content */
        .main-content {
            flex: 1;
            padding: 2rem;
            max-width: 1400px;
            width: 100%;
        }

        /* Card Styles */
        .card {
            background: var(--card-bg);
            border: 1px solid var(--card-border);
            border-radius: 16px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.3);
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.25rem;
            padding-bottom: 0.85rem;
            border-bottom: 1px solid var(--card-border);
        }

        .card-title {
            font-size: 1.2rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 0.6rem;
        }

        /* Grid System */
        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.25rem;
            margin-bottom: 1.5rem;
        }

        .stat-card {
            background: linear-gradient(135deg, rgba(30, 41, 59, 0.8), rgba(15, 23, 42, 0.9));
            border: 1px solid var(--card-border);
            border-radius: 14px;
            padding: 1.25rem;
            display: flex;
            align-items: center;
            gap: 1.25rem;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.4);
        }

        .stat-icon {
            width: 54px;
            height: 54px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.4rem;
            color: #fff;
        }

        .stat-val {
            font-size: 1.75rem;
            font-weight: 800;
            line-height: 1;
        }

        .stat-label {
            color: var(--text-muted);
            font-size: 0.85rem;
            font-weight: 500;
            margin-top: 0.3rem;
        }

        /* Buttons & Forms */
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.65rem 1.2rem;
            border-radius: 10px;
            font-size: 0.9rem;
            font-weight: 600;
            border: none;
            cursor: pointer;
            transition: all 0.2s ease;
            text-decoration: none;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary), var(--primary-hover));
            color: #fff;
            box-shadow: 0 4px 12px rgba(79, 70, 229, 0.3);
        }

        .btn-primary:hover {
            opacity: 0.92;
            transform: translateY(-1px);
        }

        .btn-secondary {
            background: rgba(255, 255, 255, 0.08);
            color: #fff;
            border: 1px solid var(--card-border);
        }

        .btn-secondary:hover {
            background: rgba(255, 255, 255, 0.15);
        }

        .btn-success {
            background: linear-gradient(135deg, #10b981, #059669);
            color: #fff;
        }

        .form-group {
            margin-bottom: 1.1rem;
        }

        .form-label {
            display: block;
            font-size: 0.88rem;
            font-weight: 600;
            margin-bottom: 0.4rem;
            color: #cbd5e1;
        }

        .form-control {
            width: 100%;
            padding: 0.7rem 1rem;
            border-radius: 10px;
            background: rgba(15, 23, 42, 0.6);
            border: 1px solid var(--card-border);
            color: #fff;
            font-size: 0.92rem;
            outline: none;
            transition: border-color 0.2s ease;
        }

        .form-control:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.25);
        }

        /* Tables */
        .table-responsive {
            width: 100%;
            overflow-x: auto;
            border-radius: 12px;
            border: 1px solid var(--card-border);
        }

        .table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
            font-size: 0.9rem;
        }

        .table th {
            background: #111827;
            padding: 0.9rem 1.1rem;
            font-weight: 700;
            color: #94a3b8;
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.05em;
            border-bottom: 1px solid var(--card-border);
        }

        .table td {
            padding: 0.85rem 1.1rem;
            border-bottom: 1px solid var(--card-border);
            color: #e2e8f0;
        }

        .table tr:last-child td {
            border-bottom: none;
        }

        .table tr:hover {
            background: rgba(255, 255, 255, 0.02);
        }

        /* Alerts */
        .alert {
            padding: 0.9rem 1.25rem;
            border-radius: 12px;
            margin-bottom: 1.25rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-size: 0.92rem;
        }

        .alert-success {
            background: rgba(16, 185, 129, 0.15);
            color: #6ee7b7;
            border: 1px solid rgba(16, 185, 129, 0.3);
        }

        .alert-danger {
            background: rgba(239, 68, 68, 0.15);
            color: #fca5a5;
            border: 1px solid rgba(239, 68, 68, 0.3);
        }
    </style>
</head>
<body>

    <!-- Top Navigation -->
    <nav class="navbar">
        <a href="{{ route('dashboard') }}" class="navbar-brand">
            <i class="fa-solid fa-graduation-cap"></i>
            <span>SCHOOL RESULTS PORTAL</span>
        </a>

        @auth
        <div class="user-nav">
            <div class="user-info">
                <div class="username">{{ Auth::user()->username }}</div>
                <span class="role-badge">{{ Auth::user()->role ?? 'Mtumiaji' }}</span>
            </div>
            <form action="{{ route('logout') }}" method="POST" style="display: inline;">
                @csrf
                <button type="submit" class="btn-logout">
                    <i class="fa-solid fa-right-from-bracket"></i> Ondoka
                </button>
            </form>
        </div>
        @endauth
    </nav>

    <div class="app-container">
        @auth
        <!-- Sidebar Navigation -->
        <aside class="sidebar">
            <a href="{{ route('dashboard') }}" class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                <i class="fa-solid fa-chart-pie"></i> Dashibodi
            </a>

            @if(Auth::user()->isAdmin())
            <div class="nav-section-title">Utawala (Admin)</div>
            <a href="{{ route('admin.users.index') }}" class="nav-link {{ request()->routeIs('admin.users.*') ? 'active' : '' }}">
                <i class="fa-solid fa-users-gear"></i> Watumiaji & Role
            </a>
            <a href="{{ route('admin.schools.index') }}" class="nav-link {{ request()->routeIs('admin.schools.*') ? 'active' : '' }}">
                <i class="fa-solid fa-school"></i> Orodha Ya Shule
            </a>
            <a href="{{ route('admin.subjects.index') }}" class="nav-link {{ request()->routeIs('admin.subjects.*') ? 'active' : '' }}">
                <i class="fa-solid fa-book-open"></i> Masomo All
            </a>
            <a href="{{ route('admin.timetable.index') }}" class="nav-link {{ request()->routeIs('admin.timetable.*') ? 'active' : '' }}">
                <i class="fa-solid fa-calendar-days"></i> Ratiba Za Masomo
            </a>
            @endif

            @if(Auth::user()->isAdmin() || Auth::user()->isTeacher() || Auth::user()->isLeader())
            <div class="nav-section-title">Taarifa & Wanafunzi</div>
            <a href="{{ route('students.index') }}" class="nav-link {{ request()->routeIs('students.*') ? 'active' : '' }}">
                <i class="fa-solid fa-user-graduate"></i> Wanafunzi
            </a>
            <a href="{{ route('marks.index') }}" class="nav-link {{ request()->routeIs('marks.*') ? 'active' : '' }}">
                <i class="fa-solid fa-pen-to-square"></i> Matokeo & Alama
            </a>
            <a href="{{ route('attendance.index') }}" class="nav-link {{ request()->routeIs('attendance.*') ? 'active' : '' }}">
                <i class="fa-solid fa-clipboard-user"></i> Mahudhurio
            </a>
            <a href="{{ route('payments.index') }}" class="nav-link {{ request()->routeIs('payments.*') ? 'active' : '' }}">
                <i class="fa-solid fa-money-check-dollar"></i> Malipo Ya Ada
            </a>
            @endif

            @if(Auth::user()->isLeader())
            <div class="nav-section-title">Ripoti Za Viongozi</div>
            <a href="{{ route('reports.leaders') }}" class="nav-link {{ request()->routeIs('reports.leaders') ? 'active' : '' }}">
                <i class="fa-solid fa-chart-line"></i> Performance Leaders
            </a>
            @endif

            <div class="nav-section-title">Akaunti Yangu</div>
            <a href="{{ route('password.change') }}" class="nav-link {{ request()->routeIs('password.change') ? 'active' : '' }}">
                <i class="fa-solid fa-key"></i> Badili Nenosiri
            </a>
        </aside>
        @endauth

        <!-- Main Workspace -->
        <main class="main-content">
            @if(session('success'))
            <div class="alert alert-success">
                <i class="fa-solid fa-circle-check"></i>
                <span>{{ session('success') }}</span>
            </div>
            @endif

            @if($errors->any())
            <div class="alert alert-danger">
                <i class="fa-solid fa-triangle-exclamation"></i>
                <div>
                    @foreach($errors->all() as $err)
                    <div>{{ $err }}</div>
                    @endforeach
                </div>
            </div>
            @endif

            @yield('content')
        </main>
    </div>

</body>
</html>
