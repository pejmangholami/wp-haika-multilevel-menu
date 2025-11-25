jQuery(document).ready(function($) {
    const menuToggle = $('.haika-menu-btn');
    const sidebar = $('#sidebar');
    let isMenuOpen = false;

    // Get spacing values from localized script
    const spacing = {
        desktop: haika_menu_vars.spacing_desktop || 65,
        tablet: haika_menu_vars.spacing_tablet || 55,
        mobile: haika_menu_vars.spacing_mobile || 50,
    };

    const applySpacing = () => {
        if (!isMenuOpen) {
            sidebar.css('transform', 'translateX(-100%)');
            return;
        }

        const screenWidth = $(window).width();
        let currentSpacing = spacing.desktop;

        if (screenWidth <= 768) { // Tablet
            currentSpacing = spacing.tablet;
        }
        if (screenWidth <= 480) { // Mobile
            currentSpacing = spacing.mobile;
        }

        sidebar.css('transform', `translateX(${currentSpacing}px)`);
    };

    if (menuToggle.length && sidebar.length) {
        menuToggle.on('click', (e) => {
            e.preventDefault();
            e.stopPropagation();
            isMenuOpen = !isMenuOpen;
            applySpacing();
        });
    }

    // Level 3 submenu toggle
    $(document).on('click', '.level3-toggle', function(e) {
        e.stopPropagation(); // Prevent document click handler from firing
        const toggle = $(e.currentTarget);
        const parent = toggle.closest('.level3-parent');
        const submenu = parent.find('.level3-submenu');
        
        if (submenu.length) {
            submenu.toggleClass('hidden');
        }
    });

    // Close menu when clicking outside of it
    $(document).on('click', function(e) {
        if (isMenuOpen && !menuToggle.is(e.target) && menuToggle.has(e.target).length === 0 && !sidebar.is(e.target) && sidebar.has(e.target).length === 0) {
            isMenuOpen = false;
            applySpacing();
        }
    });

    // Re-apply spacing on window resize
    $(window).on('resize', () => {
        if (isMenuOpen) {
            applySpacing();
        }
    });

    // Dynamically set max-height for level 2 submenus
    $('#sidebar').on('mouseenter', '.group', function() {
        const $this = $(this);
        const $submenu = $this.find('.level2-box .overflow-y-auto');

        if ($submenu.length) {
            const topOffset = $this.offset().top;
            const windowHeight = $(window).height();
            const maxHeight = windowHeight - topOffset - 20; // 20px buffer

            $submenu.css('max-height', `${maxHeight}px`);
        }
    });
});
