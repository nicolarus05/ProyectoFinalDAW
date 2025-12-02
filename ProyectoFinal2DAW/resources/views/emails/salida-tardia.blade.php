<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Salida Fuera de Horario</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
    <div style="background: linear-gradient(135deg, #ff6b6b 0%, #ee5a6f 100%); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0;">
        <h1 style="margin: 0; font-size: 28px;">⚠️ Alerta de Salida Tardía</h1>
        <p style="margin: 10px 0 0 0; font-size: 14px; opacity: 0.9;">Control de Asistencia</p>
    </div>

    <div style="background-color: #ffffff; padding: 30px; border: 1px solid #e0e0e0; border-top: none;">
        <p style="font-size: 16px; margin-bottom: 20px;">
            Se ha detectado una salida fuera del horario programado:
        </p>

        <div style="background-color: #fff3cd; border-left: 4px solid #ff6b6b; padding: 15px; margin: 20px 0; border-radius: 4px;">
            <table style="width: 100%; border-collapse: collapse;">
                <tr>
                    <td style="padding: 8px 0; font-weight: bold; color: #666;">👤 Empleado:</td>
                    <td style="padding: 8px 0; color: #333;">
                        {{ $registro->empleado->user->nombre }} {{ $registro->empleado->user->apellidos }}
                    </td>
                </tr>
                <tr>
                    <td style="padding: 8px 0; font-weight: bold; color: #666;">📅 Fecha:</td>
                    <td style="padding: 8px 0; color: #333;">
                        {{ \Carbon\Carbon::parse($registro->fecha)->locale('es')->isoFormat('dddd, D [de] MMMM [de] YYYY') }}
                    </td>
                </tr>
                <tr>
                    <td style="padding: 8px 0; font-weight: bold; color: #666;">🕐 Hora de Entrada:</td>
                    <td style="padding: 8px 0; color: #333;">
                        {{ \Carbon\Carbon::parse($registro->hora_entrada)->format('H:i') }}
                    </td>
                </tr>
                <tr>
                    <td style="padding: 8px 0; font-weight: bold; color: #666;">🕐 Hora de Salida:</td>
                    <td style="padding: 8px 0; color: #333;">
                        {{ \Carbon\Carbon::parse($registro->hora_salida)->format('H:i') }}
                    </td>
                </tr>
                <tr style="background-color: #fee; ">
                    <td style="padding: 8px 0; font-weight: bold; color: #d32f2f;">⏱️ Tiempo Extra:</td>
                    <td style="padding: 8px 0; color: #d32f2f; font-weight: bold; font-size: 18px;">
                        +{{ $registro->minutos_extra }} minutos
                    </td>
                </tr>
            </table>
        </div>

        <div style="background-color: #f8f9fa; padding: 15px; border-radius: 4px; margin-top: 20px;">
            <p style="margin: 0; font-size: 14px; color: #666;">
                ℹ️ <strong>Nota:</strong> Este empleado se quedó más de 5 minutos después de su horario de salida programado.
            </p>
        </div>

        <div style="margin-top: 30px; text-align: center;">
            <a href="{{ route('asistencia.index') }}" 
               style="display: inline-block; background-color: #4CAF50; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; font-weight: bold;">
                Ver Registro de Asistencia
            </a>
        </div>
    </div>

    <div style="background-color: #f8f9fa; padding: 20px; text-align: center; border-radius: 0 0 10px 10px; border: 1px solid #e0e0e0; border-top: none;">
        <p style="margin: 0; font-size: 12px; color: #999;">
            Este es un email automático del sistema de control de asistencia.<br>
            Salón de Belleza - {{ config('app.name') }}
        </p>
    </div>
</body>
</html>
