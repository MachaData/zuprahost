{{--
    Ajustes visuales del panel Filament del cliente (Soporte y Facturación)
    para que combine con las vistas propias del portal: mismo lienzo gris,
    mismos bordes redondeados y la misma tipografía Inter.

    Se inyecta con un render hook en ClientPanelProvider, así no hace falta
    compilar un tema aparte de Filament.
--}}
<style>
    :root {
        --zp-canvas: #f7f8fa;
        --zp-line: #e6e8ec;
        --zp-ink: #16233f;
    }

    /* Lienzo y texto base */
    .fi-body {
        background-color: var(--zp-canvas);
        color: var(--zp-ink);
    }

    /* Títulos con el navy de marca */
    .fi-header-heading,
    .fi-section-header-heading {
        color: var(--zp-ink);
        font-weight: 800;
        letter-spacing: -0.01em;
    }

    /* Tarjetas, tablas y formularios: bordes suaves como .ys-card */
    .fi-section,
    .fi-ta-ctn,
    .fi-modal-window,
    .fi-fo-component-ctn {
        border-radius: 1rem;
        border-color: var(--zp-line);
    }

    /* Barra lateral y superior en blanco, separadas por línea fina */
    .fi-sidebar,
    .fi-topbar > nav {
        background-color: #fff;
        border-color: var(--zp-line);
    }

    /* Enlaces de navegación con el mismo radio que .zp-nav */
    .fi-sidebar-item-button {
        border-radius: 0.75rem;
    }

    /* Botones y pastillas de estado, algo más redondeados */
    .fi-btn {
        border-radius: 0.75rem;
    }

    .fi-badge {
        border-radius: 9999px;
    }
</style>
