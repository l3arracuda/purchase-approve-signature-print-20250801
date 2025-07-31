{{-- resources/views/layouts/app.blade.php (อัปเดตส่วน Navigation) --}}

<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel') }}</title>

    <!-- Fonts -->
    <link rel="dns-prefetch" href="//fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=Nunito" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <!-- Scripts -->
    @vite(['resources/sass/app.scss', 'resources/js/app.js'])
</head>
<body>
    <div id="app">
        <nav class="navbar navbar-expand-md navbar-light bg-white shadow-sm">
            <div class="container">
                <a class="navbar-brand" href="{{ url('/') }}">
                    <i class="fas fa-file-invoice-dollar"></i>
                    {{ config('app.name', 'Purchase System') }}
                </a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="{{ __('Toggle navigation') }}">
                    <span class="navbar-toggler-icon"></span>
                </button>

                <div class="collapse navbar-collapse" id="navbarSupportedContent">
                    <!-- Left Side Of Navbar -->
                    <ul class="navbar-nav me-auto">
                        @auth
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">
                                    <i class="fas fa-tachometer-alt"></i> Dashboard
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('po.*') ? 'active' : '' }}" href="{{ route('po.index') }}">
                                    <i class="fas fa-file-invoice"></i> Purchase Orders
                                </a>
                            </li>
                            
                            {{-- ========== NEW: Signature Management Link ========== --}}
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('signature.*') ? 'active' : '' }}" href="{{ route('signature.manage') }}">
                                    <i class="fas fa-signature"></i> 
                                    Digital Signature
                                    @if(!Auth::user()->hasActiveSignature())
                                        <span class="badge bg-warning text-dark ms-1">!</span>
                                    @endif
                                </a>
                            </li>
                        @endauth
                    </ul>

                    <!-- Right Side Of Navbar -->
                    <ul class="navbar-nav ms-auto">
                        <!-- Authentication Links -->
                        @guest
                            @if (Route::has('login'))
                                <li class="nav-item">
                                    <a class="nav-link" href="{{ route('login') }}">{{ __('Login') }}</a>
                                </li>
                            @endif

                            @if (Route::has('register'))
                                <li class="nav-item">
                                    <a class="nav-link" href="{{ route('register') }}">{{ __('Register') }}</a>
                                </li>
                            @endif
                        @else
                            {{-- User Info Dropdown --}}
                            <li class="nav-item dropdown">
                                <a id="navbarDropdown" class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false" v-pre>
                                    <i class="fas fa-user-circle"></i>
                                    {{ Auth::user()->full_name ?? Auth::user()->username }}
                                    <span class="badge {{ Auth::user()->getRoleBadgeClass() }} ms-1">
                                        {{ ucfirst(Auth::user()->role) }}
                                    </span>
                                </a>

                                <div class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                                    {{-- User Info --}}
                                    <div class="dropdown-header">
                                        <strong>{{ Auth::user()->full_name }}</strong><br>
                                        <small class="text-muted">{{ Auth::user()->getApprovalLevelName() }}</small>
                                    </div>
                                    <div class="dropdown-divider"></div>
                                    
                                    {{-- Profile & Settings --}}
                                    <a class="dropdown-item" href="{{ route('signature.manage') }}">
                                        <i class="fas fa-signature"></i> Manage Signatures
                                        @if(!Auth::user()->hasActiveSignature())
                                            <span class="badge bg-warning text-dark ms-1">Setup Required</span>
                                        @endif
                                    </a>
                                    
                                    {{-- Admin Links --}}
                                    @if(Auth::user()->isAdmin())
                                        <div class="dropdown-divider"></div>
                                        <h6 class="dropdown-header">Admin</h6>
                                        <a class="dropdown-item" href="#" onclick="alert('Coming Soon!')">
                                            <i class="fas fa-users"></i> User Management
                                        </a>
                                        <a class="dropdown-item" href="#" onclick="alert('Coming Soon!')">
                                            <i class="fas fa-chart-bar"></i> Reports
                                        </a>
                                    @endif
                                    
                                    <div class="dropdown-divider"></div>
                                    
                                    {{-- Logout --}}
                                    <a class="dropdown-item" href="{{ route('logout') }}"
                                       onclick="event.preventDefault();
                                                     document.getElementById('logout-form').submit();">
                                        <i class="fas fa-sign-out-alt"></i> {{ __('Logout') }}
                                    </a>

                                    <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                                        @csrf
                                    </form>
                                </div>
                            </li>
                        @endguest
                    </ul>
                </div>
            </div>
        </nav>

        <main class="py-4">
            @yield('content')
        </main>
    </div>

    {{-- Custom Scripts Section --}}
    @yield('scripts')
</body>
</html>