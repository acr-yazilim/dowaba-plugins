<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dowaba Bağlantı Hatası</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; max-width: 560px; margin: 80px auto; padding: 24px; color: #1f2937; }
        .badge { display: inline-block; padding: 4px 10px; background: #fee2e2; color: #991b1b; border-radius: 6px; font-size: 12px; font-weight: 600; letter-spacing: 0.4px; }
        h1 { font-size: 22px; margin: 16px 0 12px; }
        p { line-height: 1.6; color: #4b5563; }
        code { background: #f3f4f6; padding: 2px 6px; border-radius: 4px; font-size: 14px; }
        .actions { margin-top: 28px; }
        a { display: inline-block; padding: 10px 18px; background: #111827; color: #fff; text-decoration: none; border-radius: 8px; font-size: 14px; }
        a.secondary { background: transparent; color: #4b5563; margin-left: 8px; }
    </style>
</head>
<body>
    <span class="badge">DOWABA BAĞLANTI HATASI</span>
    <h1>Dowaba ile bağlantı kurulamadı</h1>
    <p>
        <strong>Hata kodu:</strong> <code>{{ $errorCode }}</code><br>
        <strong>Mesaj:</strong> {{ $errorMessage }}
    </p>
    <p>
        Bu hata genellikle <code>DOWABA_CLIENT_ID</code> / <code>DOWABA_CLIENT_SECRET</code> uyuşmazlığı,
        callback URL'in Dowaba admin panelindeki <em>redirect_uri</em> ile birebir eşleşmemesi, ya da
        OAuth state parametresinin oturumda bulunamamasından kaynaklanır.
    </p>
    <div class="actions">
        <a href="{{ url('/') }}">Ana sayfaya dön</a>
        <a class="secondary" href="{{ route('dowaba.auth.login') }}">Tekrar dene</a>
    </div>
</body>
</html>
