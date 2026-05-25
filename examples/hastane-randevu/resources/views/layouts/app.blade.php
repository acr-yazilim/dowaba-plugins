<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="dowaba-base-url" content="{{ config('dowaba.url') }}">
    <title>@yield('title', 'Demo Hastane') — Dowaba Bridge Demo</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; margin: 0; background: #f8fafc; color: #1f2937; }
        .nav { background: #1e3a8a; color: #fff; padding: 14px 24px; display: flex; gap: 24px; align-items: center; }
        .nav a { color: #fff; text-decoration: none; font-weight: 500; }
        .nav .brand { font-weight: 800; font-size: 18px; margin-right: auto; }
        .container { max-width: 1100px; margin: 24px auto; padding: 0 20px; }
        .card { background: #fff; border: 1px solid #e5e7eb; border-radius: 12px; padding: 20px; margin-bottom: 16px; }
        .card h2 { margin: 0 0 12px; font-size: 18px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 10px 12px; text-align: left; border-bottom: 1px solid #f3f4f6; font-size: 14px; }
        th { background: #f9fafb; font-weight: 600; color: #4b5563; font-size: 12px; text-transform: uppercase; letter-spacing: 0.3px; }
        .btn { display: inline-block; padding: 8px 16px; background: #1e3a8a; color: #fff; text-decoration: none; border-radius: 6px; border: 0; cursor: pointer; font-size: 14px; }
        .btn-ghost { background: transparent; color: #1e3a8a; border: 1px solid #1e3a8a; }
        .btn-danger { background: #dc2626; }
        .form-row { display: grid; gap: 8px; margin-bottom: 14px; }
        .form-row label { font-size: 13px; color: #4b5563; font-weight: 500; }
        .form-row input, .form-row select, .form-row textarea { padding: 9px 12px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 14px; }
        .stat-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 14px; }
        .stat { background: #fff; border: 1px solid #e5e7eb; border-radius: 10px; padding: 18px; }
        .stat-num { font-size: 28px; font-weight: 800; color: #1e3a8a; line-height: 1; margin-bottom: 4px; }
        .stat-lbl { font-size: 12px; color: #6b7280; text-transform: uppercase; letter-spacing: 0.4px; }
        .flash { padding: 12px 16px; background: #dcfce7; color: #166534; border-radius: 8px; margin-bottom: 16px; border: 1px solid #bbf7d0; }
        .badge { display: inline-block; padding: 2px 8px; border-radius: 4px; font-size: 11px; font-weight: 600; }
        .badge-confirmed { background: #dcfce7; color: #166534; }
        .badge-pending { background: #fef3c7; color: #92400e; }
        .badge-cancelled { background: #fee2e2; color: #991b1b; }
    </style>
</head>
<body>
    <nav class="nav">
        <div class="brand">🏥 Demo Hastane</div>
        <a href="{{ route('home') }}">Dashboard</a>
        <a href="{{ route('appointments.index') }}">Randevular</a>
        <a href="{{ route('appointments.create') }}">+ Yeni Randevu</a>
    </nav>

    <div class="container">
        @if(session('status'))
            <div class="flash">{{ session('status') }}</div>
        @endif

        @yield('content')
    </div>

    {{-- Dowaba widget — sağ alt köşe destek butonu --}}
    @if(config('dowaba.widget.site_id'))
        <x-dowaba::widget-script :site-id="(int) config('dowaba.widget.site_id')" :user="null" />
    @endif
</body>
</html>
