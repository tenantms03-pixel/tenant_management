<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Manager Dashboard')</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">

    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f4f6fa;
            overflow-x: hidden;
        }

        /* Sidebar */
        .sidebar {
            width: 250px;
            background-color: #01017c !important; /* Navy Blue */
            color: #f8f9fa !important;
            border-radius: 0 15px 15px 0;
            box-shadow: 2px 0 15px rgba(0, 0, 0, 0.2);
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            transition: all 0.3s ease;
            z-index: 1050;
        }

        .sidebar .nav-link {
            display: flex;
            align-items: center;
            gap: 10px;
            color: #d1d5f0 !important;
            font-weight: 500;
            padding: 10px 20px;
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .sidebar .nav-link:hover {
            background: rgba(255, 255, 255, 0.15);
            color: #ffffff !important;
            transform: translateX(5px);
        }

        .sidebar .nav-link.active {
            background: linear-gradient(135deg, #01017c, #3030d1);
            color: #ffffff !important;
            font-weight: 600;
            box-shadow: 0 4px 12px rgba(0,0,0,0.3);
        }

        .sidebar h4 {
            color: #ffffff;
        }

        /* Main Content */
        .main-content {
            margin-left: 250px;
            padding: 2rem;
            transition: all 0.3s ease;
        }

        /* Toggle Button */
        .toggle-btn {
            display: none;
            position: fixed;
            top: 15px;
            left: 15px;
            background-color: #01017c;
            color: white;
            border: none;
            border-radius: 8px;
            padding: 8px 12px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.2);
            z-index: 1100;
        }

        /* Overlay */
        .overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.4);
            z-index: 1040;
            transition: opacity 0.3s ease;
        }

        .overlay.show {
            display: block;
        }

        /* Logout Button */
        .btn-danger {
            background: linear-gradient(90deg, #3030d1);
            border: none;
        }

        .btn-danger:hover {
            opacity: 0.9;
        }

        /* Scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
        }
        ::-webkit-scrollbar-thumb {
            background-color: #3030d1;
            border-radius: 10px;
        }

        /* Responsive */
        @media (max-width: 992px) {
            .sidebar {
                left: -250px;
            }

            .sidebar.active {
                left: 0;
            }

            .main-content {
                margin-left: 0;
            }

            .toggle-btn {
                display: block;
            }
        }
    </style>
</head>
<body>

    <!-- Toggle Button -->
    <button class="toggle-btn" id="toggle-btn">
        <i class="bi bi-list" style="font-size: 1.5rem;"></i>
    </button>

    <!-- Overlay -->
    <div class="overlay" id="overlay"></div>

    {{-- Sidebar --}}
    <nav class="sidebar p-3">
        <div class="text-center mb-4">
            <img src="{{ asset('images/logo.png') }}" alt="Logo" width="180">
        </div>
        <div class="text-center mb-4">
            <h4 class="fw-bold">Manager</h4>
        </div>
        <ul class="nav flex-column">
            <li class="nav-item mb-2">
                <a class="nav-link {{ request()->routeIs('manager.dashboard') ? 'active' : '' }}" href="{{ route('manager.dashboard') }}">
                    <i class="bi bi-house-fill"></i> <span>Dashboard</span>
                </a>
            </li>
            <li class="nav-item mb-2">
                <a class="nav-link {{ request()->routeIs('manager.reports') ? 'active' : '' }}" href="{{ route('manager.reports') }}">
                    <i class="bi bi-bar-chart-fill"></i> <span>Reports</span>
                </a>
            </li>
            <li class="nav-item mb-2">
                <a class="nav-link {{ request()->routeIs('manager.tenants') ? 'active' : '' }}" href="{{ route('manager.tenants') }}">
                    <i class="bi bi-people-fill"></i> <span>Tenants</span>
                </a>
            </li>
            <li class="nav-item mb-2">
                <a class="nav-link {{ request()->routeIs('manager.units.*') ? 'active' : '' }}" href="{{ route('manager.units.index') }}">
                    <i class="bi bi-building-fill-add"></i> <span>Units</span>
                </a>
            </li>
            <li class="nav-item mb-3">
                <a class="nav-link {{ request()->routeIs('manager.utilities.*') ? 'active' : '' }}" href="{{ route('manager.utilities.index') }}">
                    <i class="bi bi-lightning-charge-fill"></i> <span>Utilities</span>
                </a>
            </li>

            <li class="nav-item mt-3">
                <form action="{{ route('logout') }}" method="POST">
                    @csrf
                    <button class="btn btn-danger w-100">
                        <i class="bi bi-box-arrow-right me-2"></i> Logout
                    </button>
                </form>
            </li>
        </ul>
    </nav>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Sidebar Toggle Script -->
    <script>
        const toggleBtn = document.getElementById('toggle-btn');
        const sidebar = document.querySelector('.sidebar');
        const overlay = document.getElementById('overlay');

        toggleBtn.addEventListener('click', () => {
            sidebar.classList.toggle('active');
            overlay.classList.toggle('show');
        });

        overlay.addEventListener('click', () => {
            sidebar.classList.remove('active');
            overlay.classList.remove('show');
        });
    </script>
</body>
</html>
