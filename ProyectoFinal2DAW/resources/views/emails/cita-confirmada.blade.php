<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirmación de Cita</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f3f4f6;
            margin: 0;
            padding: 20px;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .header {
            background: linear-gradient(135deg, #C41C34 0%, #DC8A97 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 28px;
        }
        .content {
            padding: 30px;
        }
        .info-box {
            background: #F3FAFA;
            border-left: 4px solid #6EC7C5;
            padding: 20px;
            margin: 20px 0;
            border-radius: 8px;
        }
        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #e5e7eb;
        }
        .info-row:last-child {
            border-bottom: none;
        }
        .info-label {
            font-weight: 600;
            color: #4F7C7A;
        }
        .info-value {
            color: #1f2937;
        }
        .button {
            display: inline-block;
            background: #6EC7C5;
            color: #000000;
            padding: 12px 30px;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            margin: 20px 0;
        }
        .footer {
            background: #f9fafb;
            padding: 20px;
            text-align: center;
            color: #6b7280;
            font-size: 14px;
        }
        .alert {
            background: #FEF3C7;
            border-left: 4px solid #F59E0B;
            padding: 15px;
            margin: 20px 0;
            border-radius: 8px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🎉 ¡Cita Confirmada!</h1>
        </div>
        
        <div class="content">
            <p style="font-size: 18px; color: #1f2937;">
                Hola <strong>{{ $cita->cliente->user->nombre ?? 'Cliente' }}</strong>,
            </p>
            
            <p style="color: #4b5563;">
                Tu cita ha sido <strong>confirmada exitosamente</strong>. A continuación te mostramos todos los detalles:
            </p>
            
            <div class="info-box">
                <div class="info-row">
                    <span class="info-label">📅 Fecha:</span>
                    <span class="info-value">{{ \Carbon\Carbon::parse($cita->fecha_hora)->locale('es')->isoFormat('dddd, D [de] MMMM [de] YYYY') }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">🕐 Hora:</span>
                    <span class="info-value">{{ \Carbon\Carbon::parse($cita->fecha_hora)->format('H:i') }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">💇 Servicio(s):</span>
                    <span class="info-value">
                        @if($cita->servicios->isNotEmpty())
                            {{ $cita->servicios->pluck('nombre')->join(', ') }}
                        @else
                            No especificado
                        @endif
                    </span>
                </div>
                <div class="info-row">
                    <span class="info-label">👤 Profesional:</span>
                    <span class="info-value">{{ $cita->empleado->user->nombre ?? 'Por asignar' }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">⏱️ Duración:</span>
                    <span class="info-value">{{ $cita->duracion_minutos }} minutos</span>
                </div>
                @if($cita->servicios->isNotEmpty())
                <div class="info-row">
                    <span class="info-label">💰 Precio:</span>
                    <span class="info-value">€{{ number_format($cita->servicios->sum('precio'), 2) }}</span>
                </div>
                @endif
            </div>
            
            <div class="alert">
                <strong>⚠️ Importante:</strong> Por favor, llega <strong>5 minutos antes</strong> de tu cita. Si necesitas cancelar o reprogramar, contáctanos con al menos 24 horas de anticipación.
            </div>
            
            <p style="color: #4b5563;">
                📍 <strong>Ubicación:</strong><br>
                Salón de Belleza Lola Hernández<br>
                C. Romero Civantos, 3<br>
                18600 Motril, Granada
            </p>
            
            <center>
                <a href="{{ config('app.url') }}" class="button">Ver mis Citas</a>
            </center>
        </div>
        
        <div class="footer">
            <p>Este es un correo automático, por favor no respondas a este mensaje.</p>
            <p style="margin: 5px 0;">Si tienes alguna duda, contáctanos directamente.</p>
            <p style="margin-top: 15px; font-size: 12px;">© {{ date('Y') }} Salón de Belleza. Todos los derechos reservados.</p>
        </div>
    </div>
</body>
</html>
