<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Maintenance Mode</title>
    <style>
        body {
            background: #f3f4f6;
            color: #22223b;
            font-family: 'Inter', Arial, sans-serif;
            min-height: 100vh;
            margin: 0;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .container {
            background: white;
            border-radius: 18px;
            box-shadow: 0 8px 32px rgba(102, 126, 234, 0.12);
            padding: 48px 32px;
            max-width: 420px;
            width: 100%;
            text-align: center;
        }
        .image-container {
            margin-bottom: 24px;
        }
        .image-container img {
            max-width: 240px;
            margin: 0 auto;
        }
        h1 {
            font-size: 2.2rem;
            margin-bottom: 12px;
            color: #764ba2;
        }
        .message {
            font-size: 1.1rem;
            margin-bottom: 18px;
        }
        .info {
            background: #f1f5f9;
            color: #22223b;
            border-radius: 10px;
            padding: 16px;
            margin-bottom: 24px;
            font-size: 1rem;
        }
        .refresh-btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 12px 30px;
            border-radius: 25px;
            text-decoration: none;
            font-weight: 600;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
        }
        .refresh-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.6);
        }
        @media (max-width: 768px) {
            .container {
                padding: 30px 20px;
            }
            h1 {
                font-size: 2rem;
            }
            .message {
                font-size: 1rem;
            }
            .image-container img {
                max-width: 200px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="image-container">
            <img src="{{ asset('503.png') }}" alt="Maintenance" onerror="this.style.display='none'">
        </div>
        <h1>Mohon Maaf!</h1>
        <p class="message">
            Kami sedang melakukan peningkatan layanan untuk memberikan pengalaman yang lebih baik untuk Anda.
        </p>
        @php
            $defaultMessage = 'Sistem akan kembali aktif dalam waktu dekat. Terima kasih atas pengertian dan kesabaran Anda.';
            if (\Storage::exists('framework/maintenance-message.txt')) {
                $maintenanceMessage = \Storage::get('framework/maintenance-message.txt');
            } elseif (config('app.maintenance_message')) {
                $maintenanceMessage = config('app.maintenance_message');
            } else {
                $maintenanceMessage = $defaultMessage;
            }
        @endphp
        <div class="info">
            <strong>Informasi:</strong>
            {!! nl2br(e($maintenanceMessage)) !!}
        </div>
        <a href="{{ url('/') }}" class="refresh-btn" onclick="event.preventDefault(); location.reload();">
            Muat Ulang Halaman
        </a>
    </div>
</body>
</html>
