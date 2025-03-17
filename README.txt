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
        Si cliente no existe → opción de creación rápida.

3. Productos
Atributos:

    id
    proveedor
    stock
    nombre
    precio_venta (para clientes)
    precio_coste (para la empresa)

4. Servicios
Atributos:

    id
    nombre
    empleados_asignados (relación N:M, por flexibilidad)
    tiempo_estimado
    precio
    Bonos:
        id_bono
        servicios_incluidos (relación N:M)
        precio_total
        fecha_limite_uso
        cliente_asociado (FK opcional si es bono personalizado)
        Control de cuántos servicios del bono se han usado.

5. Cobro de Servicio

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

6. Facturación de Empleados

    Informe por período (día/semana/mes).
    Total ventas por empleado:
        Total servicios realizados.
        Total productos vendidos (si empleado también vende).
    Exportable a PDF.

7. Caja Diaria

    Fecha.
    Total ingresos:
        Total efectivo.
        Total tarjeta.
    Total servicios peluqueria.
    Total servicios estetica.
    Total productos peluqueria.
    Total productos estética.
    Opción de cerrar caja al final del día (bloqueo para evitar modificaciones posteriores).
    exportable a PDF.
