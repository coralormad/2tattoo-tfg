// public/assets/js/main.js

document.addEventListener('DOMContentLoaded', () => {
    initConfirmaciones();
    initMensajesFlash();
    initAnimacionesEntrada();
    initBotonVolverArriba();
});

function initConfirmaciones() {
    const accionesPeligrosas = document.querySelectorAll('[data-confirm]');

    accionesPeligrosas.forEach((elemento) => {
        elemento.addEventListener('click', (event) => {
            const mensaje = elemento.getAttribute('data-confirm') || '¿Seguro que quieres realizar esta acción?';

            if (!confirm(mensaje)) {
                event.preventDefault();
            }
        });
    });
}

function initMensajesFlash() {
    const mensajes = document.querySelectorAll(
        '.success, .error, .alert, .mensaje-exito, .mensaje-error'
    );

    mensajes.forEach((mensaje) => {
        setTimeout(() => {
            mensaje.classList.add('fade-out');

            setTimeout(() => {
                mensaje.remove();
            }, 500);
        }, 4500);
    });
}

function initAnimacionesEntrada() {
    const elementos = document.querySelectorAll(
        '.card, .tatuaje, .form-card, .dashboard-card, .page-intro, table'
    );

    elementos.forEach((elemento, index) => {
        elemento.classList.add('js-entrada');

        setTimeout(() => {
            elemento.classList.add('visible');
        }, index * 80);
    });
}

function initBotonVolverArriba() {
    const botonExistente = document.querySelector('.btn-scroll-top');

    if (botonExistente) {
        return;
    }

    const boton = document.createElement('button');

    boton.textContent = '↑';
    boton.className = 'btn-scroll-top';
    boton.setAttribute('aria-label', 'Volver arriba');

    document.body.appendChild(boton);

    window.addEventListener('scroll', () => {
        if (window.scrollY > 300) {
            boton.classList.add('visible');
        } else {
            boton.classList.remove('visible');
        }
    });

    boton.addEventListener('click', () => {
        window.scrollTo({
            top: 0,
            behavior: 'smooth'
        });
    });
}