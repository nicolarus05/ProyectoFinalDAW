function initSidebar() {
    const sidebar = document.querySelector('.sidebar');

    if (!sidebar || sidebar.dataset.sidebarReady === 'true') {
        return;
    }

    sidebar.dataset.sidebarReady = 'true';
    document.body.classList.add('has-sidebar');

    const sidebarId = sidebar.id || 'app-sidebar';
    sidebar.id = sidebarId;

    const mobileQuery = window.matchMedia('(max-width: 768px)');
    const overlay = document.createElement('button');
    overlay.type = 'button';
    overlay.className = 'sidebar-overlay';
    overlay.setAttribute('aria-label', 'Cerrar menu lateral');
    document.body.appendChild(overlay);

    const existingToggles = Array.from(document.querySelectorAll('.menu-btn, #sidebarToggle, [data-sidebar-toggle]'))
        .filter((toggle) => !sidebar.contains(toggle));

    if (existingToggles.length === 0) {
        const injectedToggle = document.createElement('button');
        injectedToggle.type = 'button';
        injectedToggle.className = 'global-sidebar-toggle';
        injectedToggle.setAttribute('aria-label', 'Abrir menu lateral');
        injectedToggle.setAttribute('data-sidebar-toggle', 'true');
        injectedToggle.dataset.sidebarLabelManaged = 'true';
        injectedToggle.textContent = '\u2630';

        const topbar = document.querySelector('.topbar');
        if (topbar) {
            injectedToggle.classList.add('in-topbar');
            topbar.insertBefore(injectedToggle, topbar.firstChild);
        } else {
            injectedToggle.classList.add('floating');
            document.body.appendChild(injectedToggle);
        }

        document.body.classList.add('has-injected-sidebar-toggle');
    }

    const getToggles = () => Array.from(document.querySelectorAll('.menu-btn, #sidebarToggle, [data-sidebar-toggle]'))
        .filter((toggle) => !sidebar.contains(toggle));

    const isMobile = () => mobileQuery.matches;

    const updateToggleState = () => {
        const isOpen = isMobile()
            ? document.body.classList.contains('sidebar-mobile-open')
            : !document.body.classList.contains('sidebar-collapsed');

        getToggles().forEach((toggle) => {
            toggle.setAttribute('aria-controls', sidebarId);
            toggle.setAttribute('aria-expanded', String(isOpen));

            if (toggle.tagName !== 'BUTTON') {
                toggle.setAttribute('role', 'button');

                if (!toggle.hasAttribute('tabindex')) {
                    toggle.setAttribute('tabindex', '0');
                }
            }

            if (!toggle.getAttribute('aria-label') || toggle.dataset.sidebarLabelManaged === 'true') {
                toggle.setAttribute('aria-label', isOpen ? 'Cerrar menu lateral' : 'Abrir menu lateral');
                toggle.dataset.sidebarLabelManaged = 'true';
            }
        });
    };

    const closeMobileSidebar = () => {
        document.body.classList.remove('sidebar-mobile-open');
        updateToggleState();
    };

    const toggleSidebar = () => {
        if (isMobile()) {
            document.body.classList.remove('sidebar-collapsed');
            document.body.classList.toggle('sidebar-mobile-open');
        } else {
            document.body.classList.remove('sidebar-mobile-open');
            document.body.classList.toggle('sidebar-collapsed');
        }

        updateToggleState();
    };

    document.addEventListener('click', (event) => {
        const toggle = event.target.closest?.('.menu-btn, #sidebarToggle, [data-sidebar-toggle]');

        if (!toggle || sidebar.contains(toggle)) {
            return;
        }

        event.preventDefault();
        event.stopPropagation();
        event.stopImmediatePropagation();
        toggleSidebar();
    }, true);

    document.addEventListener('keydown', (event) => {
        const toggle = event.target.closest?.('.menu-btn, #sidebarToggle, [data-sidebar-toggle]');

        if (toggle && (event.key === 'Enter' || event.key === ' ')) {
            event.preventDefault();
            toggleSidebar();
            return;
        }

        if (event.key === 'Escape') {
            if (isMobile()) {
                closeMobileSidebar();
            } else {
                document.body.classList.remove('sidebar-collapsed');
                updateToggleState();
            }
        }
    });

    overlay.addEventListener('click', closeMobileSidebar);

    sidebar.addEventListener('click', (event) => {
        if (isMobile() && event.target.closest?.('a')) {
            closeMobileSidebar();
        }
    });

    const handleViewportChange = () => {
        document.body.classList.remove('sidebar-mobile-open');
        updateToggleState();
    };

    if (typeof mobileQuery.addEventListener === 'function') {
        mobileQuery.addEventListener('change', handleViewportChange);
    } else {
        mobileQuery.addListener(handleViewportChange);
    }

    updateToggleState();
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initSidebar);
} else {
    initSidebar();
}
