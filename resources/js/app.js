import './bootstrap';

import Alpine from 'alpinejs';

document.addEventListener('alpine:init', () => {
    Alpine.data('shell', () => ({
        sidebarOpen: false,
        sidebarExpanded: localStorage.getItem('sidebarExpanded') !== 'false',

        toggleExpanded() {
            this.sidebarExpanded = ! this.sidebarExpanded;
            localStorage.setItem('sidebarExpanded', String(this.sidebarExpanded));
        },

        toggleSidebar() {
            if (window.matchMedia('(min-width: 1024px)').matches) {
                this.toggleExpanded();
                return;
            }

            this.sidebarOpen = ! this.sidebarOpen;
        },

        sidebarIsOpen() {
            return window.matchMedia('(min-width: 1024px)').matches
                ? this.sidebarExpanded
                : this.sidebarOpen;
        },
    }));
});

window.Alpine = Alpine;

Alpine.start();
