import './bootstrap';
import Alpine from 'alpinejs';
import ApexCharts from 'apexcharts';
import './map'; // Importer le module de carte

window.Alpine = Alpine;
window.ApexCharts = ApexCharts;

Alpine.start();

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

