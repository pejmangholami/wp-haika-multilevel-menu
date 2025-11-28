jQuery(document).ready(function($) {
    const menuToggle = $('.haika-menu-btn');
    const sidebar = $('#sidebar');
    let isMenuOpen = false;

    // Get values from localized script
    const { spacing_desktop, spacing_tablet, spacing_mobile, animation_type, shape_right_offset } = haika_menu_vars;

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
        const spacing = getcurrentSpacing();
        if (animation_type === 'slide') {
            sidebar.css('transform', `translateX(${spacing}px)`);
        } else {
            sidebar.css('transform', `translateX(${spacing}px)`).removeClass('opacity-0 pointer-events-none');
        }
    };

    const closeMenu = () => {
        isMenuOpen = false;
        // Also hide any open L2 or L3 submenus
        if (openSubmenu) {
            hideSubmenu(openSubmenu);
        }
        sidebar.find('.level3-submenu').addClass('hidden');

        if (animation_type === 'slide') {
            sidebar.css('transform', 'translateX(-100%)');
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

    const isTouchDevice = ('ontouchstart' in window) || (navigator.maxTouchPoints > 0);
    let openSubmenu = null;
    let lastTappedItem = null;

    // Close menu when clicking outside of it
    $(document).on('click', function(e) {
        const isClickInsideSidebar = sidebar.is(e.target) || sidebar.has(e.target).length > 0;
        const isClickInsideMenuToggle = menuToggle.is(e.target) || menuToggle.has(e.target).length > 0;

        // Close main menu if click is outside
        if (isMenuOpen && !isClickInsideMenuToggle && !isClickInsideSidebar) {
            closeMenu();
        }

        // On touch devices, also close L2 submenu if click is outside the sidebar
        if (isTouchDevice && openSubmenu && !isClickInsideSidebar) {
            hideSubmenu(openSubmenu);
        }
    });

    // Re-apply spacing on window resize
    $(window).on('resize', () => {
        if (isMenuOpen) {
            sidebar.css('transform', `translateX(${getcurrentSpacing()}px)`);
        }
    });

    // --- Sticky Level 2 Menu Logic ---
    const showSubmenu = ($submenuWrapper) => {
        if (!$submenuWrapper || $submenuWrapper.length === 0) return;

        // Hide any other open submenu first
        if (openSubmenu && openSubmenu[0] !== $submenuWrapper[0]) {
            hideSubmenu(openSubmenu);
        }

        // Calculate position and max-height
        const $parentLi = $submenuWrapper.closest('.group');
        const rect = $parentLi[0].getBoundingClientRect();
        const sidebarRect = sidebar[0].getBoundingClientRect();
        const rightOffset = parseInt(shape_right_offset, 10) || 0;
        const windowHeight = $(window).height();
        const maxHeight = windowHeight - rect.top - 20; // 20px buffer

        $submenuWrapper.css({
            top: rect.top,
            left: sidebarRect.right - 100 + rightOffset,
        });
        $submenuWrapper.find('.overflow-y-auto').css('max-height', `${maxHeight}px`);

        $submenuWrapper.removeClass('hidden').addClass('flex');
        openSubmenu = $submenuWrapper;
    };

    const hideSubmenu = ($submenuWrapper) => {
        if ($submenuWrapper && $submenuWrapper.length > 0) {
            $submenuWrapper.removeClass('flex').addClass('hidden');
        }
        if (openSubmenu && openSubmenu[0] === $submenuWrapper[0]) {
            openSubmenu = null;
        }
        lastTappedItem = null; // Reset for touch devices
    };

    if (isTouchDevice) {
        // --- Touch Device Logic ---
        sidebar.on('click', '.group > a', function(e) {
            const $parentLi = $(this).closest('.group');
            const $submenu = $parentLi.find('.level2-box');

            if ($submenu.length === 0) return;

            if (lastTappedItem && lastTappedItem[0] === $parentLi[0]) {
                lastTappedItem = null;
                return; // Allow navigation on second tap
            }

            e.preventDefault();
            showSubmenu($submenu);
            lastTappedItem = $parentLi;
        });

    } else {
        // --- Desktop Mouse Logic ---
        sidebar.on('mouseenter', '.group', function() {
            const $submenu = $(this).find('.level2-box');
            if ($submenu.length) {
                showSubmenu($submenu);
            }
        });

        sidebar.on('mouseleave', function(e) {
            // Check if there's an open submenu and if the mouse is not moving into it
            if (openSubmenu && !$(e.relatedTarget).closest(openSubmenu).length) {
                hideSubmenu(openSubmenu);
            }
        });
    }
});
