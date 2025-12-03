@extends('layouts.app')

@section('title', 'Login')

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
    body {
        background: linear-gradient(135deg, #f0f2f5, #d9e0eb);
        font-family: 'Inter', sans-serif;
    }

    .login-container {
        background: rgba(255,255,255,0.95);
        border-radius: 20px;
        padding: 40px 30px;
        box-shadow: 0 8px 30px rgba(0,0,0,0.1);
        backdrop-filter: blur(6px);
        width: 350px;
    }

    h3 {
        background: linear-gradient(135deg, #01017c, #2d3b9a);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        font-weight: 700;
        text-align: center;
        margin-bottom: 1.5rem;
    }

    .form-control {
        border-radius: 0.75rem;
        padding: 0.65rem 1rem;
        border: 1px solid #ced4da;
        transition: all 0.3s ease;
    }

    .form-control:focus {
        border-color: #01017c;
        box-shadow: 0 0 10px rgba(1,1,124,0.2);
    }

    .btn-gradient {
        background: linear-gradient(135deg, #01017c, #2d3b9a);
        border: none;
        border-radius: 50px;
        padding: 0.65rem 1.5rem;
        font-weight: 600;
        color: #fff;
        transition: all 0.3s ease;
    }

    .btn-gradient:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 18px rgba(1,1,124,0.25);
    }

    .btn-outline-secondary {
        border-radius: 50px;
        transition: all 0.3s ease;
    }

    .btn-outline-secondary:hover {
        background-color: #01017c;
        color: #fff;
        border-color: #01017c;
    }

    a {
        text-decoration: none;
    }
</style>
@endpush

@section('content')
<div class="container d-flex justify-content-center align-items-center vh-100">
    <div class="login-container card shadow p-4">
        <h3>Login</h3>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        @if($errors->any())
            <div class="alert alert-danger">{{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ route('login.submit') }}">
            @csrf

            <div class="mb-3">
                <label for="email" class="form-label">Email</label>
                <input type="email" name="email" class="form-control"
                       required autofocus
                       pattern="[a-zA-Z0-9]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$"
                       title="Only letters/numbers before @, valid domain after @">
            </div>

            <div class="mb-3">
                <label for="password" class="form-label">Password</label>
                <input type="password" name="password" class="form-control" required>
            </div>

            <button type="submit" class="btn btn-gradient w-100 mb-3">Login</button>
        </form>

        <div class="text-center">
            <p class="mb-2">Don’t have an account?</p>
            <a href="{{ route('register') }}" class="btn btn-outline-secondary w-100 mb-2">Register</a>

            <a href="{{ route('password.request') }}" class="d-block mt-2 text-decoration-none" style="color: #01017c;">
                Forgot Password?
            </a>
        </div>
    </div>
</div>
@endsection
