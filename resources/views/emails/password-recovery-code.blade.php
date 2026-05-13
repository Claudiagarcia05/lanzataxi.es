<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Código de recuperación</title>
</head>
<body style="margin:0;padding:0;background:#f6f7fb;font-family:Arial,Helvetica,sans-serif;color:#1f2937;">
    <div style="max-width:640px;margin:0 auto;padding:32px 16px;">
        <div style="background:#ffffff;border-radius:20px;padding:32px;border:1px solid #e5e7eb;box-shadow:0 8px 24px rgba(15,23,42,.08);">
            <h1 style="margin:0 0 16px;font-size:24px;line-height:1.2;color:#0f172a;">Código de recuperación de contraseña</h1>
            <p style="margin:0 0 20px;font-size:16px;line-height:1.6;">Hemos recibido una solicitud para restablecer tu contraseña. Usa este código de 5 dígitos en la página de recuperación:</p>

            <div style="text-align:center;margin:28px 0;">
                <div style="display:inline-block;letter-spacing:10px;font-size:32px;font-weight:700;color:#0f172a;background:#f3f4f6;border:1px solid #d1d5db;border-radius:16px;padding:18px 24px;min-width:240px;">
                    {{ $code }}
                </div>
            </div>

            <p style="margin:0 0 10px;font-size:14px;line-height:1.5;color:#4b5563;">Este código caduca en {{ $expiresInMinutes }} minutos y solo puede usarse una vez.</p>
            <p style="margin:0;font-size:14px;line-height:1.5;color:#4b5563;">Si no has solicitado este correo, puedes ignorarlo con total seguridad.</p>
        </div>
    </div>
</body>
</html>