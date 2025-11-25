jQuery(document).ready(function($) {
    const menuToggle = $('.haika-menu-btn');
    const sidebar = $('#sidebar');
    let isMenuOpen = false;

    // Get values from localized script
    const { spacing_desktop, spacing_tablet, spacing_mobile, animation_type } = haika_menu_vars;

    const spacing = {
        desktop: spacing_desktop || 65,
        tablet: spacing_tablet || 55,
        mobile: spacing_mobile || 50,
    };

    const getcurrentSpacing = () => {
        const screenWidth = $(window).width();
        if (screenWidth <= 480) return spacing.mobile;
        if (screenWidth <= 768) return spacing.tablet;
        return spacing.desktop;
    };

    const openMenu = () => {
        isMenuOpen = true;
        if (animation_type === 'slide') {
            sidebar.removeClass('-translate-x-full').css('transform', `translateX(${getcurrentSpacing()}px)`);
        } else {
            sidebar.removeClass('opacity-0 pointer-events-none').css('transform', `translateX(${getcurrentSpacing()}px)`);
        }
    };

    const closeMenu = () => {
        isMenuOpen = false;
        if (animation_type === 'slide') {
            sidebar.addClass('-translate-x-full').css('transform', '');
        } else {
            sidebar.addClass('opacity-0 pointer-events-none');
        }
    };

    if (menuToggle.length && sidebar.length) {
        menuToggle.on('click', (e) => {
            e.preventDefault();
            e.stopPropagation();
            isMenuOpen ? closeMenu() : openMenu();
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
            closeMenu();
        }
    });

    // Re-apply spacing on window resize
    $(window).on('resize', () => {
        if (isMenuOpen) {
            sidebar.css('transform', `translateX(${getcurrentSpacing()}px)`);
        }
    });

    // Dynamically set max-height and position for level 2 submenus
    $('#sidebar').on('mouseenter', '.group', function() {
        const $this = $(this);
        const $submenuWrapper = $this.find('.level2-box');
        const $submenuContent = $submenuWrapper.find('.overflow-y-auto');

        if ($submenuWrapper.length) {
            const topOffset = $this.offset().top;
            const sidebarWidth = $('#sidebar').outerWidth();
            const spacing = getcurrentSpacing();

            // Position the submenu
            $submenuWrapper.css({
                top: topOffset,
                left: sidebarWidth + spacing,
            });

            // Calculate and set max-height
            const windowHeight = $(window).height();
            const maxHeight = windowHeight - topOffset - 20; // 20px buffer
            $submenuContent.css('max-height', `${maxHeight}px`);
        }
    });
});
