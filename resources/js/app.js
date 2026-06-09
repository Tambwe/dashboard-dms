import './bootstrap';
import Alpine from 'alpinejs';
import ApexCharts from 'apexcharts';
import './map'; // Importer le module de carte
import Swal from 'sweetalert2';
import 'sweetalert2/dist/sweetalert2.min.css';

window.Alpine = Alpine;
window.ApexCharts = ApexCharts;
window.Swal = Swal;

Alpine.start();

const swalDefaultOptions = {
    confirmButtonColor: '#2563eb',
    cancelButtonColor: '#6b7280',
};

window.swalAlert = function(message, title = 'Information', icon = 'info') {
    return Swal.fire({
        ...swalDefaultOptions,
        title,
        text: String(message ?? ''),
        icon,
        confirmButtonText: 'OK',
    });
};

window.swalConfirm = async function(message, options = {}) {
    const result = await Swal.fire({
        ...swalDefaultOptions,
        title: options.title || 'Confirmation',
        text: String(message ?? ''),
        icon: options.icon || 'warning',
        showCancelButton: true,
        confirmButtonText: options.confirmButtonText || 'Confirmer',
        cancelButtonText: options.cancelButtonText || 'Annuler',
        reverseButtons: true,
        focusCancel: true,
    });

    return result.isConfirmed;
};

// Remplace les alertes natives par SweetAlert.
window.alert = function(message) {
    window.swalAlert(message);
};

const parseInlineConfirmMessage = (handler) => {
    if (!handler) {
        return null;
    }

    const trimmed = handler.trim();
    const match = trimmed.match(/^return\s+confirm\((['"`])((?:\\.|(?!\1).)*)\1\);?$/i);
    if (!match) {
        return null;
    }

    return match[2]
        .replace(/\\'/g, "'")
        .replace(/\\"/g, '"')
        .replace(/\\n/g, '\n');
};

document.addEventListener('DOMContentLoaded', () => {
    // Intercepte les formulaires avec confirm inline.
    document.querySelectorAll('form[onsubmit]').forEach((form) => {
        const handler = form.getAttribute('onsubmit');
        const message = parseInlineConfirmMessage(handler);
        if (!message) {
            return;
        }

        form.removeAttribute('onsubmit');
        form.addEventListener('submit', async (event) => {
            if (form.dataset.swalSubmitting === '1') {
                form.dataset.swalSubmitting = '0';
                return;
            }

            event.preventDefault();
            const confirmed = await window.swalConfirm(message);
            if (!confirmed) {
                return;
            }

            form.dataset.swalSubmitting = '1';
            if (typeof form.requestSubmit === 'function') {
                form.requestSubmit();
            } else {
                form.submit();
            }
        });
    });

    // Intercepte les boutons/liens avec confirm inline.
    document.querySelectorAll('[onclick]').forEach((element) => {
        const handler = element.getAttribute('onclick');
        const message = parseInlineConfirmMessage(handler);
        if (!message) {
            return;
        }

        element.removeAttribute('onclick');
        element.addEventListener('click', async (event) => {
            event.preventDefault();
            const confirmed = await window.swalConfirm(message);
            if (!confirmed) {
                return;
            }

            const tagName = (element.tagName || '').toLowerCase();
            if (tagName === 'a' && element.href) {
                window.location.href = element.href;
                return;
            }

            if (tagName === 'button') {
                const buttonType = ((element.getAttribute('type') || '').toLowerCase());
                const form = element.closest('form');

                if (form && (buttonType === 'submit' || buttonType === '')) {
                    if (typeof form.requestSubmit === 'function') {
                        form.requestSubmit();
                    } else {
                        form.submit();
                    }
                    return;
                }
            }

            const form = element.closest('form');
            if (form) {
                if (typeof form.requestSubmit === 'function') {
                    form.requestSubmit();
                } else {
                    form.submit();
                }
            }
        });
    });
});

// Dark mode toggle
window.toggleDarkMode = function() {
    if (localStorage.theme === 'dark') {
        localStorage.theme = 'light';
        document.documentElement.classList.remove('dark');
    } else {
        localStorage.theme = 'dark';
        document.documentElement.classList.add('dark');
    }
}

// Initialize dark mode on page load
if (localStorage.theme === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
    document.documentElement.classList.add('dark');
} else {
    document.documentElement.classList.remove('dark');
}

