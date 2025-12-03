@php
use App\Models\Payment;
use App\Models\Notification;
use App\Models\Lease;

$hasPaidDeposit = Payment::where('tenant_id', auth()->id())
    ->whereRaw('LOWER(payment_for) = ?', ['deposit'])
    ->exists();

$hasUnreadNotifications = Notification::where('user_id', auth()->id())
    ->where('is_read', false)
    ->exists();

$hasActiveLease = Lease::where('user_id', auth()->id())
    ->whereIn('lea_status', ['active', 'pending', 'Active', 'Pending'])
    ->exists();

$hasTerminatedLease = Lease::where('user_id', auth()->id())
    ->whereRaw('LOWER(lea_status) = ?', ['terminated'])
    ->exists();

$hasLease = $hasActiveLease || $hasTerminatedLease;

@endphp

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Tenant Dashboard')</title>

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
            background-color: #01017c !important; /* Main Navy */
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
            text-align: left !important;
            display: flex;
            align-items: center;
            gap: 10px;
            width: 100%;
            color: #d1d5f0 !important;
            font-weight: 500;
            padding-left: 20px;
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

        /* Main content */
        .main-content {
            margin-left: 250px;
            padding: 2rem;
            transition: all 0.3s ease;
        }

        /* Toggle button */
        .toggle-btn {
            display: none;
            position: fixed;
            top: 15px;
            left: 15px;
            z-index: 1100;
            background-color: #01017c;
            border: none;
            padding: 8px 12px;
            border-radius: 8px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.2);
            color: white;
        }

        /* Overlay */
        .overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.4);
            display: none;
            z-index: 1040;
            transition: all 0.3s ease;
        }

        .overlay.show {
            display: block;
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

        /* Disabled menu */
        .nav-link.disabled {
            opacity: 0.5;
            pointer-events: none;
        }

        /* Logout button */
        .btn-danger {
            background: linear-gradient(90deg, #3030d1, #01017c);
            border: none;
        }

        .btn-danger:hover {
            opacity: 0.9;
        }

        /* Scrollbar aesthetic */
        ::-webkit-scrollbar {
            width: 8px;
        }

        ::-webkit-scrollbar-thumb {
            background-color: #3030d1;
            border-radius: 10px;
        }
    </style>
</head>
<body>

<!-- Toggle Button (Visible on Mobile) -->
<button class="toggle-btn" id="toggle-btn">
    <i class="bi bi-list" style="font-size: 1.5rem;"></i>
</button>

<!-- Overlay -->
<div class="overlay" id="overlay"></div>

<div class="d-flex" id="dashboardWrapper">

    {{-- Sidebar --}}
    <nav class="sidebar p-3">
        <div class="text-center mb-4">
            <img src="{{ asset('images/logo.png') }}" alt="Logo" width="180">
        </div>
        <div class="text-center mb-4">
            <h4 class="fw-bold">Tenant</h4>
        </div>

        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('tenant.home') ? 'active' : '' }}"
                   href="{{ route('tenant.home') }}">
                    <i class="bi bi-house-fill me-2"></i> Dashboard
                </a>
            </li>

            @if(!$hasActiveLease)
                <li class="nav-item">
                    <a class="nav-link disabled" href="#">
                        <i class="bi bi-cash-stack me-2"></i> Payments
                    </a>
                </li>
            @else
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('tenant.payments') ? 'active' : '' }}"
                       href="{{ route('tenant.payments') }}">
                        <i class="bi bi-cash-stack me-2"></i> Payments
                    </a>
                </li>
            @endif

            @if(!$hasActiveLease)
                <li class="nav-item">
                    <a class="nav-link disabled" href="#">
                        <i class="bi bi-tools me-2"></i> Maintenance Requests
                    </a>
                </li>
            @else
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('tenant.requests') ? 'active' : '' }}"
                       href="{{ route('tenant.requests') }}">
                        <i class="bi bi-tools me-2"></i> Maintenance Requests
                    </a>
                </li>
            @endif

            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('tenant.notifications') ? 'active' : '' }} {{ $hasUnreadNotifications ? 'fw-bold text-warning' : '' }}"
                href="{{ route('tenant.notifications') }}">
                    <i class="bi bi-bell me-2"></i> Notifications
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('tenant.leases') ? 'active' : '' }}"
                href="{{ route('tenant.leases') }}">
                    <i class="bi bi-door-open-fill me-2"></i> Lease Management
                </a>
            </li>



            <li class="nav-item mt-4">
                <form action="{{ route('logout') }}" method="POST">
                    @csrf
                    <button class="btn btn-danger w-100">
                        <i class="bi bi-box-arrow-right me-2"></i> Logout
                    </button>
                </form>
            </li>
        </ul>
    </nav>

    {{-- Main Content --}}
    <main class="main-content flex-grow-1">
        @yield('content')
    </main>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<!-- Sidebar Toggle -->
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
