<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="{{ asset('images/logo.png') }}">
    <title>@yield('title', 'Apartment Portal')</title>

    <!-- Bootstrap 5.3 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- Custom Styles -->
    <style>
        /* General */
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #f0f2f5, #d9e0eb);
            color: #333;
        }

        a {
            text-decoration: none;
            transition: all 0.3s ease;
        }

        a:hover {
            color: #01017c;
        }

        /* Navbar */
        .navbar-brand {
            font-weight: 700;
            font-size: 1.5rem;
            color: #01017c !important;
        }

        header {
            background-color: rgba(255,255,255,0.95);
            backdrop-filter: blur(6px);
            border-bottom: 1px solid #e3e6ea;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
        }

        header .btn {
            font-size: 0.875rem;
            font-weight: 600;
            border-radius: 50px;
            padding: 6px 16px;
            transition: all 0.3s ease;
        }

        header .btn-primary {
            background: linear-gradient(135deg, #01017c, #2d3b9a);
            color: #fff;
            border: none;
        }

        header .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 18px rgba(1,1,124,0.25);
        }

        header .btn-outline-primary {
            border: 2px solid #01017c;
            color: #01017c;
            background-color: transparent;
        }

        header .btn-outline-primary:hover {
            background: linear-gradient(135deg, #01017c, #2d3b9a);
            color: #fff;
            border-color: #01017c;
        }

        /* Main content */
        main {
            padding: 2rem 0;
        }

        /* Footer */
        footer {
            background-color: #01017c;
            color: #f9fafc;
            font-size: 0.9rem;
        }

        footer a {
            color: #f9fafc;
            transition: opacity 0.2s;
        }

        footer a:hover {
            opacity: 0.8;
        }

        /* Utility for glass cards */
        .glass-card {
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(12px);
            border-radius: 1rem;
            box-shadow: 0 6px 25px rgba(0,0,0,0.08);
            transition: all 0.3s ease;
        }

        .glass-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.12);
        }

    </style>

    @stack('styles')
</head>
<body class="d-flex flex-column min-vh-100">

    <!-- Header / Navbar -->
    <header class="shadow-sm">
        <div class="container d-flex justify-content-between align-items-center py-3">
            <a class="navbar-brand" href="{{ url('/') }}">TenantMS</a>

            @if (Route::has('login'))
            <nav class="d-flex gap-2">
                @php
                    $user = auth()->user();
                @endphp
                @auth
                    @if($user->role === 'manager')
                        <a href="{{ route('manager.dashboard') }}" class="btn btn-primary">Dashboard</a>
                    @else
                        <a href="{{ route('tenant.home') }}" class="btn btn-primary">Dashboard</a>
                    @endif
                @else
                    <a href="{{ route('login') }}" class="btn btn-primary">Log in</a>

                    @if (Route::has('register'))
                        <a href="{{ route('register') }}" class="btn btn-outline-primary">Register</a>
                    @endif
                @endauth
            </nav>
            @endif
        </div>
    </header>

    <!-- Main Content -->
    <main class="flex-fill">
        @yield('content')
    </main>

    <!-- Footer -->
    <footer class="mt-auto py-4">
        <div class="container text-center">
            <p class="mb-1">&copy; {{ date('Y') }} ApartmentPortal. All rights reserved.</p>
            <p class="mb-0">
                <a href="#">Privacy Policy</a> |
                <a href="#">Terms & Conditions</a>
            </p>
        </div>
    </footer>

    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    @stack('scripts')
</body>
</html>
