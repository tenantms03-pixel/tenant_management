<!-- resources/views/layouts/managerdashboardlayout.blade.php -->

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manager Dashboard - @yield('title')</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="icon" type="image/png" href="{{ asset('images/logo.png') }}">

    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">

    <!-- Bootstrap Bundle JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" defer></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Custom CSS -->
    <link rel="stylesheet" href="{{ asset('css/managerdashboard.css') }}">
    <link rel="stylesheet" href="{{ asset('css/sidebar.css') }}">
</head>
<body>

<div class="d-flex" id="dashboardWrapper">
    <!-- Sidebar -->
    @include('layouts.sidebarlayout')

    <!-- Main Content -->
    <main class="flex-grow-1 px-5 py-5" id="mainContent">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <!-- Sidebar Toggle Button -->
            <button id="sidebarToggle" class="btn btn-outline-secondary d-lg-none">
                <i class="bi bi-list"></i>
            </button>
            <h2 class="fw-bold m-0">@yield('page-title')</h2>
        </div>

        @yield('content')
    </main>
</div>

<!-- Custom JS -->
<script>
    document.addEventListener("DOMContentLoaded", () => {
        const sidebar = document.getElementById("sidebarMenu");
        const toggleBtn = document.getElementById("sidebarToggle");

        toggleBtn?.addEventListener("click", () => {
            if (window.innerWidth <= 991) {
                sidebar.classList.toggle("active");
                document.body.classList.toggle("sidebar-open");
            } else {
                sidebar.classList.toggle("collapsed");
                document.getElementById("mainContent").classList.toggle("expanded");
            }
        });

        // Close sidebar when clicking overlay on mobile
        document.body.addEventListener("click", (e) => {
            if (document.body.classList.contains("sidebar-open") && !sidebar.contains(e.target) && !toggleBtn.contains(e.target)) {
                sidebar.classList.remove("active");
                document.body.classList.remove("sidebar-open");
            }
        });
    });
</script>

@stack('scripts')

</body>
</html>
