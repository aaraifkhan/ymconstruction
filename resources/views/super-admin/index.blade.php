<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Super Admin</title>
    <style>
        :root { color-scheme: light; font-family: Inter, ui-sans-serif, system-ui, sans-serif; }
        * { box-sizing: border-box; }
        body { min-height: 100vh; margin: 0; background: #f8fafc; color: #172033; }
        main { width: min(1120px, calc(100% - 2rem)); margin: 0 auto; padding: 4rem 0; }
        .back { color: #35626c; text-decoration: none; }
        h1 { margin: 1.25rem 0 .5rem; font-size: clamp(1.8rem, 4vw, 2.75rem); }
        header p { color: #607087; margin: 0 0 2rem; }
        .grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(230px, 1fr)); gap: 1.25rem; }
        .card { display: flex; min-height: 250px; flex-direction: column; justify-content: space-between; border: 1px solid #dce5ea; border-radius: 1rem; padding: 1.25rem; background: #fff; box-shadow: 0 14px 34px rgba(33, 55, 79, .08); color: inherit; text-decoration: none; transition: transform .15s ease, box-shadow .15s ease, border-color .15s ease; }
        .card:hover, .card:focus-visible { transform: translateY(-4px); border-color: #159779; box-shadow: 0 18px 40px rgba(18, 84, 74, .18); outline: none; }
        .logo { display: flex; height: 130px; align-items: center; justify-content: center; border-radius: .75rem; padding: 1rem; background: #f8fafc; }
        .logo.medical { background: #1e293b; }
        .logo.ymc { background: #000000; }
        .logo img { max-width: 100%; max-height: 100%; object-fit: contain; }
        .card h2 { margin: 1rem 0 .4rem; font-size: 1.1rem; }
        .card p { margin: 0; color: #607087; font-size: .9rem; }
    </style>
</head>
<body>
    <main>
        <a class="back" href="{{ route('portal') }}">&larr; Back to company selection</a>
        <header>
            <h1>Super Admin</h1>
            <p>Collective administration and reporting across four separate companies. Open a company to view its operational records.</p>
        </header>
        <section class="grid" aria-label="Company operations">
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
        </section>
    </main>
</body>
</html>
