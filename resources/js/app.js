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

const swalThemeOptions = () => document.documentElement.classList.contains('dark')
    ? { background: '#1f2937', color: '#f9fafb' }
    : {};

const clearServerWaitTimer = () => {
    if (window.swalServerWaitTimer) {
        window.clearTimeout(window.swalServerWaitTimer);
        window.swalServerWaitTimer = null;
    }
};

window.swalAlert = function(message, title = 'Information', icon = 'info') {
    return Swal.fire({
        ...swalDefaultOptions,
        ...swalThemeOptions(),
        title,
        text: String(message ?? ''),
        icon,
        confirmButtonText: 'OK',
    });
};

window.swalConfirm = async function(message, options = {}) {
    const result = await Swal.fire({
        ...swalDefaultOptions,
        ...swalThemeOptions(),
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

window.swalToast = function(message, icon = 'success') {
    return Swal.fire({
        ...swalThemeOptions(),
        toast: true,
        position: 'top-end',
        icon,
        title: String(message ?? ''),
        showConfirmButton: false,
        timer: 4500,
        timerProgressBar: true,
    });
};

window.swalLoading = function(options = {}) {
    clearServerWaitTimer();

    const promise = Swal.fire({
        ...swalThemeOptions(),
        title: options.title || 'Traitement en cours',
        text: options.message || 'Veuillez patienter pendant la réponse du serveur.',
        allowOutsideClick: false,
        allowEscapeKey: false,
        showConfirmButton: false,
        didOpen: () => Swal.showLoading(),
    });

    window.swalServerWaitTimer = window.setTimeout(() => {
        if (!Swal.isLoading()) {
            return;
        }

        Swal.update({
            ...swalThemeOptions(),
            title: options.slowTitle || 'Le serveur prend plus de temps',
            text: options.slowMessage || 'La demande est toujours en cours. Veuillez patienter sans fermer cette page.',
        });
        Swal.showLoading();
    }, options.slowAfter ?? 10000);

    promise.finally(clearServerWaitTimer);
    return promise;
};

window.swalCloseLoading = function() {
    clearServerWaitTimer();
    if (Swal.isLoading()) {
        Swal.close();
    }
};

const serverErrorMessage = (error) => {
    const responseData = error?.response?.data;
    return responseData?.message
        || responseData?.error
        || error?.message
        || 'La requête n’a pas pu être exécutée. Vérifiez votre connexion puis réessayez.';
};

window.withSwalLoading = async function(request, options = {}) {
    window.swalLoading(options);

    try {
        return await (typeof request === 'function' ? request() : request);
    } catch (error) {
        window.swalCloseLoading();
        if (options.showError !== false) {
            await window.swalAlert(
                serverErrorMessage(error),
                options.errorTitle || 'Erreur du serveur',
                'error'
            );
        }
        throw error;
    } finally {
        window.swalCloseLoading();
    }
};

window.swalFetch = function(input, init = {}, options = {}) {
    return window.withSwalLoading(() => window.fetch(input, init), options);
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
    document.querySelectorAll('form[onsubmit], form[data-swal-confirm]').forEach((form) => {
        const message = form.dataset.swalConfirm
            || parseInlineConfirmMessage(form.getAttribute('onsubmit'));
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
                form.requestSubmit(event.submitter || undefined);
            } else {
                form.submit();
            }
        });
    });

    document.querySelectorAll('[onclick], [data-swal-confirm]').forEach((element) => {
        if (element.tagName.toLowerCase() === 'form') {
            return;
        }

        const message = element.dataset.swalConfirm
            || parseInlineConfirmMessage(element.getAttribute('onclick'));
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

    const flashMessages = Array.isArray(window.appFlashMessages)
        ? window.appFlashMessages
        : [];

    flashMessages.forEach(({ message }) => {
        const normalizedMessage = String(message ?? '').trim();
        document.querySelectorAll('[role="alert"], .alert, ul, main [class*="bg-green-"], main [class*="bg-red-"], main [class*="bg-amber-"], main [class*="bg-blue-"]').forEach((element) => {
            if (element.textContent.trim() === normalizedMessage) {
                element.remove();
            }
        });
    });

    (async () => {
        for (const flash of flashMessages) {
            if (flash.type === 'success' || flash.type === 'status') {
                await window.swalToast(flash.message, 'success');
                continue;
            }

            await window.swalAlert(
                flash.message,
                flash.title || (flash.type === 'error' ? 'Erreur' : 'Information'),
                flash.type === 'warning' ? 'warning' : (flash.type === 'info' ? 'info' : 'error')
            );
        }
    })();
});

window.addEventListener('submit', (event) => {
    const form = event.target;
    if (!(form instanceof HTMLFormElement)
        || form.dataset.noSwalLoading !== undefined
        || event.submitter?.dataset?.noSwalLoading !== undefined
        || (form.target && form.target !== '_self')
        || (form.method || '').toLowerCase() === 'dialog') {
        return;
    }

    const submitter = event.submitter;
    window.queueMicrotask(() => {
        if (event.defaultPrevented) {
            return;
        }

        window.swalLoading({
            title: form.dataset.loadingTitle || submitter?.dataset?.loadingTitle,
            message: form.dataset.loadingMessage || submitter?.dataset?.loadingMessage,
        });
    });
});

window.addEventListener('pageshow', () => window.swalCloseLoading());

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
