<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Choose a company</title>
    <style>
        :root { color-scheme: light; font-family: Inter, ui-sans-serif, system-ui, sans-serif; }
        * { box-sizing: border-box; }
        body { min-height: 100vh; margin: 0; background: linear-gradient(135deg, #f8fafc, #eef6f5); color: #172033; }
        main { width: min(1120px, calc(100% - 2rem)); margin: 0 auto; padding: 4rem 0; }
        header { margin-bottom: 2rem; }
        h1 { margin: 0; font-size: clamp(1.8rem, 4vw, 2.75rem); }
        header p { color: #607087; margin: .75rem 0 0; }
        .grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(230px, 1fr)); gap: 1.25rem; }
        .card { display: flex; min-height: 280px; flex-direction: column; justify-content: space-between; border: 1px solid #dce5ea; border-radius: 1rem; padding: 1.25rem; background: #fff; box-shadow: 0 14px 34px rgba(33, 55, 79, .08); color: inherit; text-decoration: none; transition: transform .15s ease, box-shadow .15s ease, border-color .15s ease; }
        .card:hover, .card:focus-visible { transform: translateY(-4px); border-color: #159779; box-shadow: 0 18px 40px rgba(18, 84, 74, .18); outline: none; }
        .logo { display: flex; height: 150px; align-items: center; justify-content: center; border-radius: .75rem; padding: 1.25rem; background: #f8fafc; }
        .logo.medical { background: #1e293b; }
        .logo.ymc { background: #000000; }
        .logo img { max-width: 100%; max-height: 100%; object-fit: contain; }
        .card h2 { margin: 1rem 0 .4rem; font-size: 1.1rem; }
        .card p { margin: 0; color: #607087; font-size: .9rem; }
        .admin { border-color: #27364e; background: #172033; color: #fff; }
        .admin .logo { background: rgba(255, 255, 255, .1); font-size: 3.25rem; }
        .admin p { color: #c6d3e1; }
        .empty { max-width: 580px; border: 1px solid #dce5ea; border-radius: 1rem; background: #fff; padding: 1.5rem; }
        .sign-out-wrapper { display: flex; justify-content: center; margin-top: 3rem; }
        .sign-out-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.65rem 1.75rem;
            border-radius: 9999px;
            border: 1px solid #cbd5e1;
            background: #ffffff;
            color: #334155;
            font-size: 0.925rem;
            font-weight: 500;
            font-family: inherit;
            cursor: pointer;
            box-shadow: 0 2px 6px rgba(15, 23, 42, 0.05);
            transition: all 0.2s ease;
        }
        .sign-out-btn:hover, .sign-out-btn:focus-visible {
            background: #f1f5f9;
            border-color: #94a3b8;
            color: #0f172a;
            box-shadow: 0 4px 12px rgba(15, 23, 42, 0.1);
            transform: translateY(-1px);
            outline: none;
        }
        .sign-out-btn:active {
            transform: translateY(0);
            box-shadow: 0 1px 3px rgba(15, 23, 42, 0.05);
        }
    </style>
</head>
<body>
    <main>
        <header>
            <h1>Choose a company</h1>
            <p>Select an authorized company to open its separate operations panel.</p>
        </header>

        @if ($companies->isEmpty() && ! $isSuperAdmin)
            <section class="empty">
                <h2>Access pending</h2>
                <p>Your account does not currently have access to a company. Please contact an administrator.</p>
            </section>
        @else
            <section class="grid" aria-label="Available panels">
                @foreach ($companies as $company)
                    <a class="card" href="{{ route('portal.company', $company) }}">
                        <div class="logo {{ $company->slug === '7-orbit-medical-billing' ? 'medical' : ($company->slug === 'ymc-construction' ? 'ymc' : '') }}">
                            <img src="{{ asset($company->logo_path) }}" alt="{{ $company->name }} logo">
                        </div>
                        <div>
                            <h2>{{ $company->name }}</h2>
                            <p>Open company operations</p>
                        </div>
                    </a>
                @endforeach

                @if ($isSuperAdmin)
                    <a class="card admin" href="{{ route('portal.super-admin') }}">
                        <div class="logo" aria-hidden="true">&#128737;</div>
                        <div>
                            <h2>Super Admin</h2>
                            <p>Open collective administration and reporting</p>
                        </div>
                    </a>
                @endif
            </section>
        @endif

        <form method="POST" action="{{ route('filament.admin.auth.logout') }}" class="sign-out-wrapper">
            @csrf
            <button class="sign-out-btn" type="submit">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                    <polyline points="16 17 21 12 16 7"></polyline>
                    <line x1="21" y1="12" x2="9" y2="12"></line>
                </svg>
                <span>Sign out</span>
            </button>
        </form>
    </main>
</body>
</html>
