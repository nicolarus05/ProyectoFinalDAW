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
                        <td style="background-color: #DC3545; padding: 30px; text-align: center;">
                            <h1 style="color: #ffffff; margin: 0; font-size: 24px;">Cita Cancelada</h1>
                        </td>
                    </tr>
                    
                    <!-- Contenido -->
                    <tr>
                        <td style="padding: 30px;">
                            <p style="font-size: 16px; color: #333333; margin-top: 0;">
                                Hola <strong>{{ $cita->cliente->user->nombre ?? 'Cliente' }}</strong>,
                            </p>
                            
                            <p style="color: #666666;">
                                Lamentamos informarte que tu cita ha sido <strong>cancelada</strong>.
                            </p>
                            
                            <!-- Info Box -->
                            <table width="100%" cellpadding="15" cellspacing="0" style="background-color: #f8f9fa; border-left: 4px solid #DC3545; margin: 20px 0;">
                                <tr>
                                    <td style="color: #721c24; font-weight: bold; width: 40%;">Fecha cancelada:</td>
                                    <td style="color: #333333;">{{ \Carbon\Carbon::parse($cita->fecha_hora)->locale('es')->isoFormat('dddd, D [de] MMMM [de] YYYY') }}</td>
                                </tr>
                                <tr>
                                    <td style="color: #721c24; font-weight: bold;">Hora:</td>
                                    <td style="color: #333333;">{{ \Carbon\Carbon::parse($cita->fecha_hora)->format('H:i') }}</td>
                                </tr>
                                <tr>
                                    <td style="color: #721c24; font-weight: bold;">Servicio(s):</td>
                                    <td style="color: #333333;">
                                        @if($cita->servicios->isNotEmpty())
                                            {{ $cita->servicios->pluck('nombre')->join(', ') }}
                                        @else
                                            No especificado
                                        @endif
                                    </td>
                                </tr>
                                @if($motivo)
                                <tr>
                                    <td style="color: #721c24; font-weight: bold;">Motivo:</td>
                                    <td style="color: #333333;">{{ $motivo }}</td>
                                </tr>
                                @endif
                            </table>
                            
                            <p style="color: #666666;">
                                Si deseas agendar una nueva cita, no dudes en contactarnos.
                            </p>
                            
                            <p style="color: #666666; margin-bottom: 0;">
                                <strong>Contacto:</strong><br>
                                Salon de Belleza Lola Hernandez<br>
                                C. Romero Civantos, 3<br>
                                18600 Motril, Granada
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
