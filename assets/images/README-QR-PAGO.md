# Imagen QR de Pago

Para habilitar el paso de pago en el proceso de creación de tenants, necesitas agregar una imagen de código QR en esta ubicación:

**Ruta:** `public/assets/images/qr-pago.png`

## Opciones para generar el QR:

1. **Banco:** Solicita a tu banco un QR de pago estático
2. **Generador Online:** Usa https://www.qr-code-generator.com/ con tus datos bancarios
3. **API de pago:** Si usas una pasarela de pago como Stripe, PayPal, etc.

## Formato recomendado:
- **Formato:** PNG o JPG
- **Tamaño:** 300x300 px mínimo
- **Nombre:** `qr-pago.png`

## Datos bancarios a configurar:

También puedes editar los datos bancarios mostrados en el paso de pago en:
- **Archivo:** `resources/views/livewire/crear-tenant.blade.php`
- **Línea:** Buscar "Datos de transferencia"

Actualmente muestra:
- Banco: Banco Union
- Cuenta: 1234567890
- Titular: LicoPOS SRL

**Nota:** Si no agregas el QR, se mostrará un placeholder con los datos bancarios.
