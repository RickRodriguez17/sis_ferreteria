import './bootstrap';
import Swal from 'sweetalert2';

const alertDefaults = {
    confirmButtonColor: '#4f46e5',
    cancelButtonColor: '#64748b',
    buttonsStyling: true,
};

window.erpAlert = (options = {}) => Swal.fire({
    ...alertDefaults,
    ...options,
});

window.erpToast = (message, icon = 'success') => Swal.fire({
    ...alertDefaults,
    toast: true,
    position: 'bottom-end',
    showConfirmButton: false,
    timer: 3500,
    timerProgressBar: true,
    icon,
    title: message,
});

window.confirmLivewireAction = async (wire, method, params = [], message = '¿Deseas continuar?') => {
    const result = await window.erpAlert({
        icon: 'warning',
        title: 'Confirmar acción',
        text: message,
        showCancelButton: true,
        confirmButtonText: 'Sí, continuar',
        cancelButtonText: 'Cancelar',
    });

    if (result.isConfirmed) {
        await wire.call(method, ...params);
    }
};

document.addEventListener('livewire:init', () => {
    Livewire.on('toast', ({ message, type = 'success' }) => {
        window.erpToast(message, type);
    });

    Livewire.on('alert', ({ message, type = 'info', title = 'Aviso' }) => {
        window.erpAlert({ icon: type, title, text: message });
    });
});

window.addEventListener('erp-alert', (event) => {
    const detail = event.detail || {};
    window.erpAlert(detail);
});

document.addEventListener('click', (event) => {
    const element = event.target.closest('[wire\\:click]');

    if (! element || ! window.Livewire) {
        return;
    }

    const expression = element.getAttribute('wire:click');
    const match = expression?.match(/^\s*(delete|cancel|annul|anular)\s*(?:\((.*)\))?\s*$/i);

    if (! match || element.dataset.confirmationHandled === 'true') {
        return;
    }

    event.preventDefault();
    event.stopImmediatePropagation();
    element.dataset.confirmationHandled = 'true';

    const params = (match[2] ?? '').split(',').map((value) => value.trim()).filter(Boolean).map((value) => {
        const numeric = Number(value);
        return Number.isNaN(numeric) ? value.replace(/^['"]|['"]$/g, '') : numeric;
    });
    const messages = {
        delete: 'Esta acción eliminará el registro seleccionado.',
        cancel: 'Esta acción cancelará la operación seleccionada.',
        annul: 'Esta acción anulará el registro seleccionado.',
        anular: 'Esta acción anulará el registro seleccionado.',
    };
    const component = element.closest('[wire\\:id]');
    const instance = component ? window.Livewire.find(component.getAttribute('wire:id')) : null;

    if (instance) {
        window.confirmLivewireAction(instance, match[1], params, element.dataset.confirmMessage || messages[match[1].toLowerCase()])
            .finally(() => {
                delete element.dataset.confirmationHandled;
            });
    } else {
        delete element.dataset.confirmationHandled;
    }
}, true);
