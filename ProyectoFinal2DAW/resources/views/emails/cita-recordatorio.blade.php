<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body style="margin: 0; padding: 0; font-family: Arial, sans-serif; background-color: #f5f5f5;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #f5f5f5; padding: 20px;">
        <tr>
            <td align="center">
                <table width="600" cellpadding="0" cellspacing="0" style="background-color: #ffffff; border-radius: 8px; overflow: hidden;">
                    <!-- Header -->
                    <tr>
                        <td style="background-color: #6EC7C5; padding: 30px; text-align: center;">
                            <h1 style="color: #ffffff; margin: 0; font-size: 24px;">Recordatorio de Cita</h1>
                        </td>
                    </tr>
                    
                    <!-- Contenido -->
                    <tr>
                        <td style="padding: 30px;">
                            <p style="font-size: 16px; color: #333333; margin-top: 0;">
                                Hola <strong>{{ $cita->cliente->user->nombre ?? 'Cliente' }}</strong>,
                            </p>
                            
                            <p style="color: #666666;">
                                Este es un recordatorio de tu cita programada para <strong>manana</strong>:
                            </p>
                            
                            <!-- Info Box -->
                            <table width="100%" cellpadding="15" cellspacing="0" style="background-color: #f8f9fa; border-left: 4px solid #6EC7C5; margin: 20px 0;">
                                <tr>
                                    <td style="color: #4F7C7A; font-weight: bold; width: 40%;">Fecha:</td>
                                    <td style="color: #333333;">{{ \Carbon\Carbon::parse($cita->fecha_hora)->locale('es')->isoFormat('dddd, D [de] MMMM [de] YYYY') }}</td>
                                </tr>
                                <tr>
                                    <td style="color: #4F7C7A; font-weight: bold;">Hora:</td>
                                    <td style="color: #333333;">{{ \Carbon\Carbon::parse($cita->fecha_hora)->format('H:i') }}</td>
                                </tr>
                                <tr>
                                    <td style="color: #4F7C7A; font-weight: bold;">Servicio(s):</td>
                                    <td style="color: #333333;">
                                        @if($cita->servicios->isNotEmpty())
                                            {{ $cita->servicios->pluck('nombre')->join(', ') }}
                                        @else
                                            No especificado
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <td style="color: #4F7C7A; font-weight: bold;">Profesional:</td>
                                    <td style="color: #333333;">{{ $cita->empleado->user->nombre ?? 'Por asignar' }}</td>
                                </tr>
                                <tr>
                                    <td style="color: #4F7C7A; font-weight: bold;">Duracion:</td>
                                    <td style="color: #333333;">{{ $cita->duracion_minutos ?? $cita->servicios->sum('duracion_minutos') }} minutos</td>
                                </tr>
                            </table>
                            
                            <!-- Alerta -->
                            <table width="100%" cellpadding="15" cellspacing="0" style="background-color: #E8F5E9; border-left: 4px solid: #4CAF50; margin: 20px 0;">
                                <tr>
                                    <td style="color: #2E7D32;">
                                        <strong>Recuerda:</strong> Por favor, llega 5 minutos antes de tu cita. 
                                        Si necesitas cancelar o reprogramar, contactanos lo antes posible.
                                    </td>
                                </tr>
                            </table>
                            
                            <p style="color: #666666; margin-bottom: 0;">
                                <strong>Ubicacion:</strong><br>
                                Salon de Belleza Lola Hernandez<br>
                                C. Romero Civantos, 3<br>
                                18600 Motril, Granada
                            </p>
                            
                            <p style="color: #666666;">
                                Nos vemos manana!
                            </p>
                        </td>
                    </tr>
                    
                    <!-- Footer -->
                    <tr>
                        <td style="background-color: #f8f9fa; padding: 20px; text-align: center;">
                            <p style="color: #999999; font-size: 12px; margin: 0;">
                                Este es un correo automatico, por favor no respondas a este mensaje.
                            </p>
                            <p style="color: #999999; font-size: 12px; margin: 5px 0 0 0;">
                                &copy; {{ date('Y') }} Salon de Belleza. Todos los derechos reservados.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
