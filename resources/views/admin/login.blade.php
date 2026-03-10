@extends('layouts.app')

@section('title', 'Admin Login')

@section('content')
<div class="mt-5 d-flex justify-content-center">
    <div class="pr-card" style="width: 100%; max-width: 420px;">
        <div class="text-center mb-4">
            <div class="icon-box icon-box-purple mx-auto mb-3" style="width:64px;height:64px;font-size:1.8rem">
                <i class="bi bi-shield-lock"></i>
            </div>
            <h2 class="fw-800 gradient-text">Admin Access</h2>
            <p style="color:var(--pr-text-muted);font-size:0.875rem">Enter the admin password to continue</p>
        </div>

        @if ($errors->any())
            <div class="pr-alert pr-alert-danger mb-3">
                <i class="bi bi-exclamation-circle-fill me-2"></i>{{ $errors->first('password') }}
            </div>
        @endif

        <form method="POST" action="{{ route('admin.login.post') }}">
            @csrf
            <div class="mb-3">
                <label class="form-label fw-600" style="color:var(--pr-text)">Password</label>
                <input type="password" name="password" class="form-control pr-form-control" placeholder="Admin password" autofocus required>
            </div>
            <button type="submit" class="btn btn-pr-primary w-100">
                <i class="bi bi-unlock me-1"></i>Login
            </button>
        </form>
    </div>
</div>
@endsection
