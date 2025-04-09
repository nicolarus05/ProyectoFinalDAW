Gestión Centro de Belleza y Estética

1. Usuarios
Tipos de Usuarios:

    Administrador:
        Permisos: acceso completo (gestión de usuarios, agenda, productos, servicios, caja, facturación).
    Empleado:
        Permisos: acceso limitado (solo puede gestionar su horario, registrar entrada/salida, consultar sus citas y ventas).
    Cliente:
        Permisos: solo pueden visualizar sus citas y datos personales (si hay sistema online).

Atributos Comunes (User):

    id
    nombre
    apellido
    teléfono
    email
    genero
    edad

Atributos Cliente (hereda de User):

    dirección
    notas_adicionales
    fecha_registro
    (Opcional) Historial de citas y servicios adquiridos (útil para promociones personalizadas).

Atributos Empleado (hereda de User):

    horario_trabajo (podría estar normalizado en otra tabla para flexibilidad)
    servicios_realizados (relación N:M con tabla de Servicios)
    registro_entrada_salida (tabla con fecha y hora)
    Sugerencia extra: Rol/especialización (esteticista, peluquera)

2. Agenda / Citas
Atributos:

    id
    fecha (fecha y hora)
    empleado_id (FK)
    cliente_id (FK)
    servicio_id (FK)
    duración
    Estado de cita: pendiente, confirmada, cancelada, completada.
    Notas adicionales (opcional, por si cliente pide algo específico).
    Validación:
        Margen mínimo 15 min entre citas.
        No solapar citas para un mismo empleado.
        Si cliente no existe → opción de creación rápida

3. Servicios
Atributos:

    id
    nombre
    empleados_asignados (relación N:M, por flexibilidad)
    tiempo_estimado
    precio

4. Cobro de Servicio

Proceso:

    Confirmar asistencia cliente.
    Cobro:
        Datos mostrados:
            empleado
            servicio
            coste (precio base)
            descuento (en % o €)
            total_final
            tipo_pago (efectivo, tarjeta, otros como Bizum)
            cambio (si aplica)
            Factura generada en PDF.
