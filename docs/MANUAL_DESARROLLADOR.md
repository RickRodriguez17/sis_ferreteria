# Manual del desarrollador

## Convenciones

- Interfaz y mensajes en español; conservar nombres de dominio existentes.
- Ejecutar Pint y PHPStan antes de entregar.
- Usar enums para estados, tipos y métodos.
- Mantener Livewire delgado y no duplicar inventario, saldos, caja o precios en vistas.
- Autorizar acciones en backend y ocultarlas con `@can`.
- Validar cantidades positivas, montos no negativos, unicidad, fechas y correos.

## Estructura

- `app/Domain`: enums.
- `app/Models`: Eloquent y relaciones.
- `app/Policies`: autorización.
- `app/Repositories`: contratos y consultas.
- `app/Services`: transacciones y reglas.
- `app/Livewire`: componentes.
- `resources/views/livewire`: vistas.
- `app/Http/Controllers`: endpoints HTTP.
- `database/seeders`: permisos y datos demo.
- `tests/Feature`: flujos por módulo.

## Añadir un módulo

1. Revisar los documentos de arquitectura y módulos vecinos.
2. Definir permisos y policy.
3. Centralizar consultas en repositorio y mutaciones en servicio.
4. Crear componente con `mount` autorizado y reglas de validación.
5. Agregar rutas al grupo `auth`.
6. Ocultar enlaces con `@can` y reautorizar acciones.
7. Añadir tests de autorización, validación y efectos persistidos.
8. Actualizar documentación y ejecutar verificaciones.

## Servicios y calidad

Las mutaciones de inventario, saldo, caja y precios deben pasar por sus servicios y respetar eventos existentes. Para validar:

```bash
vendor/bin/pint
vendor/bin/phpstan analyse
composer dump-autoload -o
php artisan test
npm run build
```

No incluya `.env` ni secretos. Revise `git diff` y preserve archivos locales no rastreados.
