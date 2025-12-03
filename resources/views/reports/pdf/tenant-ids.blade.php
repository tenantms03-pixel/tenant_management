<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Tenant IDs</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: DejaVu Sans, sans-serif;
            padding: 20px;
            color: #333;
            line-height: 1.6;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 15px;
            border-bottom: 3px solid #2563eb;
        }
        .header h1 {
            font-size: 20px;
            color: #1e40af;
            margin-bottom: 10px;
            font-weight: bold;
        }
        .header p {
            font-size: 11px;
            color: #666;
            margin: 5px 0;
        }
        .tenant-info {
            background-color: #eff6ff;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 25px;
            border-left: 4px solid #2563eb;
        }
        .tenant-info p {
            margin: 5px 0;
            font-size: 11px;
        }
        .tenant-info strong {
            color: #1e40af;
        }
        img {
            width: 300px;
            height: auto;
            margin: 15px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .id-section {
            margin-bottom: 40px;
        }
        .id-section h3 {
            color: #1e40af;
            font-size: 14px;
            margin-bottom: 15px;
            font-weight: bold;
        }
        .footer {
            margin-top: 40px;
            padding-top: 15px;
            border-top: 2px solid #e5e7eb;
            text-align: center;
            font-size: 9px;
            color: #6b7280;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Tenant ID Verification</h1>
        <p><strong>Generated:</strong> {{ now()->format('F d, Y \a\t h:i A') }}</p>
    </div>
    
    <div class="tenant-info">
        <p><strong>Name:</strong> {{ $tenant->name }}</p>
        <p><strong>Email:</strong> {{ $tenant->email }}</p>
    </div>

    <div class="id-section">
        <h3>Valid ID</h3>
        @php
            $validIdPath = storage_path('app/public/' . $validId);
            if (file_exists($validIdPath)) {
                $validIdData = base64_encode(file_get_contents($validIdPath));
                $extension = strtolower(pathinfo($validIdPath, PATHINFO_EXTENSION));
                $mimeTypes = [
                    'jpg' => 'image/jpeg',
                    'jpeg' => 'image/jpeg',
                    'png' => 'image/png',
                    'gif' => 'image/gif',
                    'webp' => 'image/webp'
                ];
                $validIdMime = $mimeTypes[$extension] ?? 'image/jpeg';
                $validIdBase64 = 'data:' . $validIdMime . ';base64,' . $validIdData;
            } else {
                $validIdBase64 = null;
            }
        @endphp
        @if($validIdBase64)
            <img src="{{ $validIdBase64 }}" alt="Valid ID">
        @else
            <p style="color: #999;">Image not found</p>
        @endif
    </div>

    <div class="id-section">
        <h3>ID Picture</h3>
        @php
            $idPicturePath = storage_path('app/public/' . $idPicture);
            if (file_exists($idPicturePath)) {
                $idPictureData = base64_encode(file_get_contents($idPicturePath));
                $extension = strtolower(pathinfo($idPicturePath, PATHINFO_EXTENSION));
                $mimeTypes = [
                    'jpg' => 'image/jpeg',
                    'jpeg' => 'image/jpeg',
                    'png' => 'image/png',
                    'gif' => 'image/gif',
                    'webp' => 'image/webp'
                ];
                $idPictureMime = $mimeTypes[$extension] ?? 'image/jpeg';
                $idPictureBase64 = 'data:' . $idPictureMime . ';base64,' . $idPictureData;
            } else {
                $idPictureBase64 = null;
            }
        @endphp
        @if($idPictureBase64)
            <img src="{{ $idPictureBase64 }}" alt="ID Picture">
        @else
            <p style="color: #9ca3af; font-style: italic;">Image not found</p>
        @endif
    </div>
    
    <div class="footer">
        <p>Property Management System | Generated on {{ now()->format('F d, Y') }}</p>
    </div>
</body>
</html>
