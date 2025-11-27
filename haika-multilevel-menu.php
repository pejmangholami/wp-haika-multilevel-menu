<?php
/**
 * Plugin Name: منو هایکا
 * Description: پلاگین منوی چندسطحی پیشرفته با قابلیت آپلود آیکون سفارشی
 * Version: 1.3
 * Author: کلینیک انفورماتیک هایکا
 * Author URI: https://haika.ir
 */

// جلوگیری از دسترسی مستقیم
if (!defined('ABSPATH')) {
    exit;
}

class HaikaMultilevelMenu {
    
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_shortcode('haika_menu', array($this, 'render_menu'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('admin_init', array($this, 'admin_init'));
        add_action('wp_ajax_upload_menu_icon', array($this, 'handle_icon_upload'));
        add_action('wp_head', array($this, 'inject_dynamic_styles'));
    }

    public function inject_dynamic_styles() {
        $lvl1_bg_color = get_option('haika_menu_lvl1_bg_color', '#D6D9DB');
        $lvl2_bg_color = get_option('haika_menu_lvl2_bg_color', '#e4e6e7');
        $text_color = get_option('haika_menu_text_color', '#817C7A');
        $text_hover_color = get_option('haika_menu_text_hover_color', '#000000');

        $shape_box_width = get_option('haika_menu_shape_box_width', '350');
        $shape_top_offset = get_option('haika_menu_shape_top_offset', '50'); // Changed default to 50px
        $shape_step_width = get_option('haika_menu_shape_step_width', '36');
        $shape_step_height = get_option('haika_menu_shape_step_height', '25');
        $shape_slope_height = get_option('haika_menu_shape_slope_height', '36');

        // New variables from user request
        $lvl1_padding_right = get_option('haika_menu_lvl1_padding_right', '0');
        $lvl1_top_padding = get_option('haika_menu_lvl1_top_padding', '0');
        $lvl1_item_spacing = get_option('haika_menu_lvl1_item_spacing', '24');
        $lvl2_item_spacing = get_option('haika_menu_lvl2_item_spacing', '16');
        $lvl3_item_spacing = get_option('haika_menu_lvl3_item_spacing', '8');

        echo <<<CSS
<style>
    :root {
        --haika-lvl1-bg: {$lvl1_bg_color};
        --haika-lvl2-bg: {$lvl2_bg_color};
        --haika-text-color: {$text_color};
        --haika-text-hover-color: {$text_hover_color};

        --haika-box-width: {$shape_box_width}px;
        --haika-top-offset: {$shape_top_offset}px;
        --haika-step-width: {$shape_step_width}px;
        --haika-step-height: {$shape_step_height}px;
        --haika-slope-height: {$shape_slope_height}px;

        /* New variables from user request */
        --haika-lvl1-padding-right: {$lvl1_padding_right}px;
        --haika-lvl1-top-padding: {$lvl1_top_padding}px;
        --haika-lvl1-item-spacing: {$lvl1_item_spacing}px;
        --haika-lvl2-item-spacing: {$lvl2_item_spacing}px;
        --haika-lvl3-item-spacing: {$lvl3_item_spacing}px;
    }
</style>
CSS;
    }

    public function init() {
        // ثبت منطقه منو
        register_nav_menus(array(
            'haika_multilevel_menu' => 'منوی چندسطحی هایکا'
        ));
    }

    public function enqueue_scripts() {
        wp_enqueue_style('haika-google-fonts', 'https://fonts.googleapis.com/css2?family=Vazirmatn:wght@400;500;600;700&display=swap', array(), null);
        wp_enqueue_style('haika-menu-style', plugin_dir_url(__FILE__) . 'haika-style.css', array(), '1.4');
        wp_enqueue_script('haika-tailwind-cdn', 'https://cdn.tailwindcss.com?plugins=forms,typography', array(), null, false);
        wp_add_inline_script('haika-tailwind-cdn', '
          tailwind.config = {
            darkMode: "class",
            theme: {
              extend: {
                colors: {
                  primary: "#FFA500",
                  "background-light": "#F8FAFC",
                  "background-dark": "#0B1120",
                },
                fontFamily: {
                  display: ["Vazirmatn", "sans-serif"],
                },
                borderRadius: {
                  DEFAULT: "0.5rem",
                },
              },
            },
          };
        ');
        wp_enqueue_script('haika-menu-script', plugin_dir_url(__FILE__) . 'haika-script.js', array('jquery'), '1.4', true);
        
        // اضافه کردن متغیرهای JavaScript
        wp_localize_script('haika-menu-script', 'haika_menu_vars', array(
            'button_icon' => get_option('haika_menu_button_icon', ''),
            'button_text' => get_option('haika_menu_button_text', '☰ منو'),
            'spacing_desktop' => get_option('haika_menu_spacing_desktop', '65'),
            'spacing_tablet' => get_option('haika_menu_spacing_tablet', '55'),
            'spacing_mobile' => get_option('haika_menu_spacing_mobile', '50'),
            'animation_type' => get_option('haika_menu_animation_type', 'slide'),
            'shape_right_offset' => get_option('haika_menu_shape_right_offset', '0'),
        ));
    }

    public function render_menu($atts) {
        // Retain the original button logic
        $atts = shortcode_atts(array(
            'button_text' => get_option('haika_menu_button_text', '☰ منو'),
            'button_icon' => get_option('haika_menu_button_icon', ''),
            'icon_size'   => get_option('haika_menu_icon_size', '20'),
            'position'    => 'right'
        ), $atts);

        $button_text    = esc_html($atts['button_text']);
        $button_icon    = esc_url($atts['button_icon']);
        $icon_size      = intval($atts['icon_size']);
        $button_color   = get_option('haika_menu_button_color', '#ff8c00');
        $container_class = 'haika-menu-container ' . ($atts['position'] === 'left' ? 'left-position' : '');
        $button_class   = 'haika-menu-btn';
        $button_style   = '';
        
        if (!empty($button_icon)) {
            if (empty($button_text) || $button_text === '☰ منو' || trim($button_text) === '') {
                $button_class .= ' icon-only';
                $button_content = '<img src="' . $button_icon . '" alt="منو" class="menu-icon">';
                $button_style = 'background: transparent !important;';
            } else {
                $button_content = '<img src="' . $button_icon . '" alt="منو" class="menu-icon"> ' . $button_text;
                $button_style = 'background-color: ' . $button_color . ';';
            }
        } else {
            $button_content = $button_text;
            $button_style = 'background-color: ' . $button_color . ';';
        }
        
        $button_html = "
            <button class='{$button_class}' 
                    data-text='{$button_text}' 
                    data-icon='{$button_icon}' 
                    data-icon-size='{$icon_size}'
                    style='{$button_style}'>{$button_content}</button>";

        // Get menu items using the new Tailwind walker
        $menu_items = wp_nav_menu(array(
            'theme_location' => 'haika_multilevel_menu',
            'container'      => false,
            'items_wrap'     => '%3$s',
            'echo'           => false,
            'walker'         => new Haika_Tailwind_Menu_Walker(),
            'depth'          => 3,
        ));

        if (empty($menu_items)) {
            return '<p style="background: #fff3cd; padding: 10px; border: 1px solid #ffeaa7; border-radius: 5px;">منویی تعریف نشده است. از پنل ادمین > ظاهر > منوها یک منو ایجاد کنید و آن را به "منوی چندسطحی هایکا" اختصاص دهید.</p>';
        }

        // Combine the original button with the new menu structure
        $animation_type = get_option('haika_menu_animation_type', 'slide');
        $sidebar_classes = 'fixed left-0 top-0 h-screen w-80 shadow-lg flex flex-col z-40';
        if ($animation_type === 'slide') {
            $sidebar_classes .= ' transition-transform duration-500 ease-in-out';
        } else {
            $sidebar_classes .= ' transition-opacity duration-500 ease-in-out opacity-0 pointer-events-none';
        }

        $sidebar_html = '
            <div id="sidebar" class="' . $sidebar_classes . '" style="background-color: var(--haika-lvl1-bg); transform: translateX(-100%); padding: var(--haika-lvl1-top-padding) 0 2rem 0;">
                <div style="padding-right: var(--haika-lvl1-padding-right);">
                    <div class="w-full text-right px-12">
                        <div class="w-2.5 h-2.5 rounded-full inline-block" style="background-color: var(--haika-text-color); margin-bottom: var(--haika-lvl1-item-spacing);"></div>
                    </div>
                    <nav class="w-full">
                        <ul class="text-center font-medium text-lg overflow-y-auto max-h-screen" style="color: var(--haika-text-color);">
                            ' . $menu_items . '
                        </ul>
                    </nav>
                </div>
            </div>';

        $button_container_html = "<div class='{$container_class}'>{$button_html}</div>";

        return $button_container_html . $sidebar_html;
    }

    public function admin_menu() {
        add_menu_page(
            'منو هایکا',
            'منو هایکا', 
            'manage_options',
            'haika-menu',
            array($this, 'admin_page'),
            'dashicons-menu'
        );
    }

    public function admin_init() {
        // ثبت تنظیمات
        register_setting('haika_menu_settings', 'haika_menu_button_text');
        register_setting('haika_menu_settings', 'haika_menu_button_icon');
        register_setting('haika_menu_settings', 'haika_menu_button_color');
        register_setting('haika_menu_settings', 'haika_menu_icon_size');
        register_setting('haika_menu_settings', 'haika_menu_spacing_desktop');
        register_setting('haika_menu_settings', 'haika_menu_spacing_tablet');
        register_setting('haika_menu_settings', 'haika_menu_spacing_mobile');
        register_setting('haika_menu_settings', 'haika_menu_animation_type');

        // New appearance settings
        register_setting('haika_menu_settings', 'haika_menu_lvl1_bg_color');
        register_setting('haika_menu_settings', 'haika_menu_lvl2_bg_color');
        register_setting('haika_menu_settings', 'haika_menu_text_color');
        register_setting('haika_menu_settings', 'haika_menu_text_hover_color');
        register_setting('haika_menu_settings', 'haika_menu_shape_box_width');
        register_setting('haika_menu_settings', 'haika_menu_shape_top_offset');
        register_setting('haika_menu_settings', 'haika_menu_shape_step_width');
        register_setting('haika_menu_settings', 'haika_menu_shape_step_height');
        register_setting('haika_menu_settings', 'haika_menu_shape_slope_height');

        // Register new settings from user request
        register_setting('haika_menu_settings', 'haika_menu_lvl1_padding_right');
        register_setting('haika_menu_settings', 'haika_menu_lvl1_top_padding');
        register_setting('haika_menu_settings', 'haika_menu_shape_right_offset');
        register_setting('haika_menu_settings', 'haika_menu_lvl1_item_spacing');
        register_setting('haika_menu_settings', 'haika_menu_lvl2_item_spacing');
        register_setting('haika_menu_settings', 'haika_menu_lvl3_item_spacing');
    }

    public function admin_page() {
        // ذخیره تنظیمات
        if (isset($_POST['submit'])) {
            update_option('haika_menu_button_text', sanitize_text_field($_POST['haika_menu_button_text']));
            update_option('haika_menu_button_color', sanitize_hex_color($_POST['haika_menu_button_color']));
            update_option('haika_menu_icon_size', intval($_POST['haika_menu_icon_size']));
            update_option('haika_menu_spacing_desktop', intval($_POST['haika_menu_spacing_desktop']));
            update_option('haika_menu_spacing_tablet', intval($_POST['haika_menu_spacing_tablet']));
            update_option('haika_menu_spacing_mobile', intval($_POST['haika_menu_spacing_mobile']));
            update_option('haika_menu_animation_type', sanitize_text_field($_POST['haika_menu_animation_type']));
            
            // Save new appearance settings
            update_option('haika_menu_lvl1_bg_color', sanitize_hex_color($_POST['haika_menu_lvl1_bg_color']));
            update_option('haika_menu_lvl2_bg_color', sanitize_hex_color($_POST['haika_menu_lvl2_bg_color']));
            update_option('haika_menu_text_color', sanitize_hex_color($_POST['haika_menu_text_color']));
            update_option('haika_menu_text_hover_color', sanitize_hex_color($_POST['haika_menu_text_hover_color']));
            update_option('haika_menu_shape_box_width', intval($_POST['haika_menu_shape_box_width']));
            update_option('haika_menu_shape_top_offset', intval($_POST['haika_menu_shape_top_offset']));
            update_option('haika_menu_shape_step_width', intval($_POST['haika_menu_shape_step_width']));
            update_option('haika_menu_shape_step_height', intval($_POST['haika_menu_shape_step_height']));
            update_option('haika_menu_shape_slope_height', intval($_POST['haika_menu_shape_slope_height']));

            // Save new settings from user request
            update_option('haika_menu_lvl1_padding_right', intval($_POST['haika_menu_lvl1_padding_right']));
            update_option('haika_menu_lvl1_top_padding', intval($_POST['haika_menu_lvl1_top_padding']));
            update_option('haika_menu_shape_right_offset', intval($_POST['haika_menu_shape_right_offset']));
            update_option('haika_menu_lvl1_item_spacing', intval($_POST['haika_menu_lvl1_item_spacing']));
            update_option('haika_menu_lvl2_item_spacing', intval($_POST['haika_menu_lvl2_item_spacing']));
            update_option('haika_menu_lvl3_item_spacing', intval($_POST['haika_menu_lvl3_item_spacing']));

            // مدیریت آپلود عکس
            if (!empty($_POST['haika_menu_button_icon'])) {
                update_option('haika_menu_button_icon', esc_url($_POST['haika_menu_button_icon']));
            }
            
            echo '<div class="notice notice-success"><p>تنظیمات ذخیره شد!</p></div>';
        }

        // دریافت تنظیمات فعلی
        $button_text = get_option('haika_menu_button_text', '☰ منو');
        $button_icon = get_option('haika_menu_button_icon', '');
        $button_color = get_option('haika_menu_button_color', '#ff8c00');
        $icon_size = get_option('haika_menu_icon_size', '20');
        $spacing_desktop = get_option('haika_menu_spacing_desktop', '65');
        $spacing_tablet = get_option('haika_menu_spacing_tablet', '55');
        $spacing_mobile = get_option('haika_menu_spacing_mobile', '50');
        $animation_type = get_option('haika_menu_animation_type', 'slide');
        
        // Get new appearance settings
        $lvl1_bg_color = get_option('haika_menu_lvl1_bg_color', '#D6D9DB');
        $lvl2_bg_color = get_option('haika_menu_lvl2_bg_color', '#e4e6e7');
        $text_color = get_option('haika_menu_text_color', '#817C7A');
        $text_hover_color = get_option('haika_menu_text_hover_color', '#000000');
        $shape_box_width = get_option('haika_menu_shape_box_width', '350');
        $shape_top_offset = get_option('haika_menu_shape_top_offset', '50'); // Changed default to 50px
        $shape_step_width = get_option('haika_menu_shape_step_width', '36');
        $shape_step_height = get_option('haika_menu_shape_step_height', '25');
        $shape_slope_height = get_option('haika_menu_shape_slope_height', '36');

        ?>
        <div class="wrap">
            <h1>تنظیمات منو هایکا</h1>
            
            <form method="post" enctype="multipart/form-data">
                <table class="form-table">
                    <tr>
                        <th scope="row">متن دکمه منو</th>
                        <td>
                            <input type="text" name="haika_menu_button_text" value="<?php echo esc_attr($button_text); ?>" class="regular-text" />
                            <p class="description">متنی که کنار آیکون نمایش داده می‌شود</p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">آیکون دکمه منو</th>
                        <td>
                            <div id="menu-icon-preview" style="margin-bottom: 10px;">
                                <?php if ($button_icon): ?>
                                    <img src="<?php echo esc_url($button_icon); ?>" style="max-width: 50px; max-height: 50px; border: 1px solid #ddd; padding: 5px;">
                                    <p>آیکون فعلی</p>
                                <?php else: ?>
                                    <p>هیچ آیکونی انتخاب نشده</p>
                                <?php endif; ?>
                            </div>
                            
                            <input type="hidden" id="haika_menu_button_icon" name="haika_menu_button_icon" value="<?php echo esc_attr($button_icon); ?>" />
                            <button type="button" class="button" id="upload-icon-btn">انتخاب/تغییر آیکون</button>
                            <button type="button" class="button" id="remove-icon-btn" style="margin-right: 10px;">حذف آیکون</button>
                            
                            <p class="description">
                                آیکون دکمه منو را انتخاب کنید. بهترین اندازه: 24×24 یا 32×32 پیکسل<br>
                                فرمت‌های پشتیبانی شده: PNG, JPG, SVG
                            </p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">اندازه آیکون</th>
                        <td>
                            <input type="number" name="haika_menu_icon_size" value="<?php echo esc_attr($icon_size); ?>" min="16" max="50" /> پیکسل
                            <p class="description">اندازه آیکون در دکمه (16 تا 50 پیکسل)</p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">رنگ پس‌زمینه دکمه</th>
                        <td>
                            <input type="color" name="haika_menu_button_color" value="<?php echo esc_attr($button_color); ?>" />
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">فاصله منو از چپ (دسکتاپ)</th>
                        <td>
                            <input type="number" name="haika_menu_spacing_desktop" value="<?php echo esc_attr($spacing_desktop); ?>" min="0" /> پیکسل
                            <p class="description">فاصله منو از لبه چپ صفحه در دستگاه‌های بزرگ.</p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">فاصله منو از چپ (تبلت)</th>
                        <td>
                            <input type="number" name="haika_menu_spacing_tablet" value="<?php echo esc_attr($spacing_tablet); ?>" min="0" /> پیکسل
                            <p class="description">فاصله منو از لبه چپ صفحه در تبلت.</p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">فاصله منو از چپ (موبایل)</th>
                        <td>
                            <input type="number" name="haika_menu_spacing_mobile" value="<?php echo esc_attr($spacing_mobile); ?>" min="0" /> پیکسل
                            <p class="description">فاصله منو از لبه چپ صفحه در موبایل.</p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">نوع انیمیشن باز شدن منو</th>
                        <td>
                            <select name="haika_menu_animation_type">
                                <option value="slide" <?php selected($animation_type, 'slide'); ?>>Slide</option>
                                <option value="fade" <?php selected($animation_type, 'fade'); ?>>Fade</option>
                            </select>
                            <p class="description">انیمیشن باز و بسته شدن منو را انتخاب کنید.</p>
                        </td>
                    </tr>

                    <!-- Spacing Settings -->
                    <tr><th colspan="2"><h3>تنظیمات فاصله آیتم‌ها</h3></th></tr>
                    <tr>
                        <th scope="row">فاصله بین آیتم‌های سطح ۱</th>
                        <td>
                            <input type="number" name="haika_menu_lvl1_item_spacing" value="<?php echo esc_attr(get_option('haika_menu_lvl1_item_spacing', '24')); ?>" /> پیکسل
                            <p class="description">فاصله عمودی بین هر آیتم در منوی سطح ۱.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">فاصله بین آیتم‌های سطح ۲</th>
                        <td>
                            <input type="number" name="haika_menu_lvl2_item_spacing" value="<?php echo esc_attr(get_option('haika_menu_lvl2_item_spacing', '16')); ?>" /> پیکسل
                            <p class="description">فاصله عمودی بین هر آیتم در منوی سطح ۲.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">فاصله بین آیتم‌های سطح ۳</th>
                        <td>
                            <input type="number" name="haika_menu_lvl3_item_spacing" value="<?php echo esc_attr(get_option('haika_menu_lvl3_item_spacing', '8')); ?>" /> پیکسل
                            <p class="description">فاصله عمودی بین هر آیتم در منوی سطح ۳.</p>
                        </td>
                    </tr>
                </table>

                <h2>تنظیمات ظاهری</h2>
                <table class="form-table">
                    <!-- General Menu Settings -->
                    <tr><th colspan="2"><h3>تنظیمات کلی منو</h3></th></tr>
                    <tr>
                        <th scope="row">فاصله داخلی منو سطح ۱ از راست</th>
                        <td>
                            <input type="number" name="haika_menu_lvl1_padding_right" value="<?php echo esc_attr(get_option('haika_menu_lvl1_padding_right', '0')); ?>" /> پیکسل
                            <p class="description">باکس عمودی منوی سطح ۱ را از سمت راست بزرگتر می‌کند بدون اینکه جایگاه نوشته‌ها تغییر کند.</p>
                        </td>
                    </tr>
                     <tr>
                        <th scope="row">فاصله آیتم‌ها از بالای منوی سطح ۱</th>
                        <td>
                            <input type="number" name="haika_menu_lvl1_top_padding" value="<?php echo esc_attr(get_option('haika_menu_lvl1_top_padding', '0')); ?>" /> پیکسل
                            <p class="description">یک فضای خالی در بالای آیتم‌های منوی اصلی (و بولت) اضافه می‌کند.</p>
                        </td>
                    </tr>

                    <!-- Color Settings -->
                    <tr><th colspan="2"><h3>تنظیمات رنگ‌بندی</h3></th></tr>
                    <tr>
                        <th scope="row">رنگ پس‌زمینه سطح ۱</th>
                        <td><input type="color" name="haika_menu_lvl1_bg_color" value="<?php echo esc_attr($lvl1_bg_color); ?>" /></td>
                    </tr>
                    <tr>
                        <th scope="row">رنگ پس‌زمینه سطح ۲</th>
                        <td><input type="color" name="haika_menu_lvl2_bg_color" value="<?php echo esc_attr($lvl2_bg_color); ?>" /></td>
                    </tr>
                    <tr>
                        <th scope="row">رنگ متن</th>
                        <td><input type="color" name="haika_menu_text_color" value="<?php echo esc_attr($text_color); ?>" /></td>
                    </tr>
                    <tr>
                        <th scope="row">رنگ متن (هاور)</th>
                        <td><input type="color" name="haika_menu_text_hover_color" value="<?php echo esc_attr($text_hover_color); ?>" /></td>
                    </tr>

                    <!-- Shape Settings -->
                    <tr><th colspan="2"><h3>تنظیمات شکل منوی سطح ۲</h3></th></tr>
                    <tr>
                        <th scope="row">عرض باکس</th>
                        <td>
                            <input type="number" name="haika_menu_shape_box_width" value="<?php echo esc_attr($shape_box_width); ?>" /> پیکسل
                            <p class="description">عرض کلی باکس منوی سطح ۲.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">فاصله از بالا (Top Offset)</th>
                        <td>
                            <input type="number" name="haika_menu_shape_top_offset" value="<?php echo esc_attr($shape_top_offset); ?>" /> پیکسل
                            <p class="description">موقعیت عمودی شروع پله از بالای باکس (به پیکسل).</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">فاصله از راست (Right Offset)</th>
                        <td>
                            <input type="number" name="haika_menu_shape_right_offset" value="<?php echo esc_attr(get_option('haika_menu_shape_right_offset', '0')); ?>" /> پیکسل
                            <p class="description">موقعیت افقی منوی سطح ۲ را کنترل می‌کند. مقدار منفی آن را به چپ می‌برد.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">عرض پله</th>
                        <td>
                            <input type="number" name="haika_menu_shape_step_width" value="<?php echo esc_attr($shape_step_width); ?>" /> پیکسل
                            <p class="description">عرض افقی پله در گوشه بالا سمت چپ.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">ارتفاع پله</th>
                        <td>
                            <input type="number" name="haika_menu_shape_step_height" value="<?php echo esc_attr($shape_step_height); ?>" /> پیکسل
                            <p class="description">ارتفاع عمودی پله در گوشه بالا سمت چپ.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">ارتفاع شیب</th>
                        <td>
                            <input type="number" name="haika_menu_shape_slope_height" value="<?php echo esc_attr($shape_slope_height); ?>" /> پیکسل
                            <p class="description">میزان افت ارتفاع خط شیب‌دار در بالای باکس.</p>
                        </td>
                    </tr>
                </table>
                
                <?php submit_button('ذخیره تنظیمات'); ?>
            </form>

            <div class="card" style="max-width: 600px; margin-top: 30px;">
                <h2>راهنمای استفاده</h2>
                <ol>
                    <li>برو <strong>ظاهر > منوها</strong></li>
                    <li>منوی جدید بساز</li>
                    <li>آیتم‌های منو رو اضافه کن</li>
                    <li>منو رو به <strong>"منوی چندسطحی هایکا"</strong> اختصاص بده</li>
                    <li>از شورت‌کد <code>[haika_menu]</code> استفاده کن</li>
                </ol>
                
                <h3>شورت‌کدهای موجود:</h3>
                <ul>
                    <li><code>[haika_menu]</code> - نمایش منو با تنظیمات پیش‌فرض</li>
                    <li><code>[haika_menu button_text="منوی من"]</code> - تغییر متن دکمه</li>
                    <li><code>[haika_menu icon_size="24"]</code> - تغییر اندازه آیکون</li>
                    <li><code>[haika_menu button_text="منو" icon_size="20"]</code> - ترکیبی</li>
                </ul>
            </div>

            <div class="card" style="max-width: 600px; margin-top: 20px;">
                <h2>نکات مهم برای آیکون</h2>
                <ul>
                    <li>بهترین اندازه آیکون: 24×24 یا 32×32 پیکسل</li>
                    <li>فرمت PNG برای شفافیت بهتر است</li>
                    <li>رنگ آیکون بهتر است سفید یا روشن باشد</li>
                    <li>حجم فایل را کم نگه دارید (زیر 50KB)</li>
                </ul>
            </div>
            
            <div class="card" style="max-width: 600px; margin-top: 20px; text-align: center;">
                <p style="color: #666;">طراحی و توسعه توسط <strong>کلینیک انفورماتیک هایکا</strong></p>
                <p><a href="https://haika.ir" target="_blank">haika.ir</a></p>
            </div>
        </div>

        <script>
        jQuery(document).ready(function($) {
            // Media Uploader
            var mediaUploader;
            
            $('#upload-icon-btn').click(function(e) {
                e.preventDefault();
                
                if (mediaUploader) {
                    mediaUploader.open();
                    return;
                }
                
                mediaUploader = wp.media({
                    title: 'انتخاب آیکون منو',
                    button: {
                        text: 'انتخاب آیکون'
                    },
                    multiple: false,
                    library: {
                        type: ['image']
                    }
                });
                
                mediaUploader.on('select', function() {
                    var attachment = mediaUploader.state().get('selection').first().toJSON();
                    $('#haika_menu_button_icon').val(attachment.url);
                    $('#menu-icon-preview').html('<img src="' + attachment.url + '" style="max-width: 50px; max-height: 50px; border: 1px solid #ddd; padding: 5px;"><p>آیکون انتخاب شده</p>');
                });
                
                mediaUploader.open();
            });
            
            // حذف آیکون
            $('#remove-icon-btn').click(function(e) {
                e.preventDefault();
                $('#haika_menu_button_icon').val('');
                $('#menu-icon-preview').html('<p>هیچ آیکونی انتخاب نشده</p>');
            });
        });
        </script>
        <?php
        
        // Load WordPress media scripts
        wp_enqueue_media();
    }
}



class Haika_Tailwind_Menu_Walker extends Walker_Nav_Menu {
    // start_lvl is called when a new submenu is created.
    public function start_lvl(&$output, $depth = 0, $args = null) {
        if ($depth === 0) {
            // Level 2 submenu wrapper
            $output .= '<div class="fixed shadow-xl transition-all duration-300 z-50 level2-box">';
            $output .= '<div class="w-full p-8 pt-20 relative z-10 overflow-y-auto">';
            $output .= '<ul class="text-xl font-semibold w-full text-left" style="color: var(--haika-text-color);">';
        } elseif ($depth === 1) {
            // Level 3 submenu
            $output .= '<ul class="hidden level3-submenu mr-6 mt-3 text-lg font-normal animate-slideIn text-right" style="color: var(--haika-text-color);">';
        }
    }

    // end_lvl is called when a submenu is closed.
    public function end_lvl(&$output, $depth = 0, $args = null) {
        if ($depth === 0) {
            $output .= '</ul></div></div>';
        } elseif ($depth === 1) {
            $output .= '</ul>';
        }
    }

    // start_el is called for each menu item.
    public function start_el(&$output, $item, $depth = 0, $args = null, $id = 0) {
        $spacing_style = '';
        if ($depth === 0) {
            // Level 1 items
            $spacing_style = 'style="margin-bottom: var(--haika-lvl1-item-spacing);"';
            $is_parent = in_array('menu-item-has-children', $item->classes);
            $li_classes = $is_parent ? 'group relative text-right px-12' : 'text-right px-12';
            $output .= '<li class="' . $li_classes . '" ' . $spacing_style . '>';
            $output .= '<a class="block py-2 hover:opacity-70 transition-opacity cursor-pointer" href="' . esc_url($item->url) . '">' . esc_html($item->title) . '</a>';
        } elseif ($depth === 1) {
            // Level 2 items
            $spacing_style = 'style="margin-bottom: var(--haika-lvl2-item-spacing);"';
            $is_parent = in_array('menu-item-has-children', $item->classes);
            if ($is_parent) {
                $output .= '<li class="level3-parent" ' . $spacing_style . '>';
                $output .= '<div class="flex items-center gap-3 cursor-pointer hover:opacity-70 transition-opacity level3-toggle">';
                $output .= '<span class="w-3 h-3 bg-primary rounded-full"></span>';
                $output .= '<span>' . esc_html($item->title) . '</span>';
                $output .= '</div>';
            } else {
                $output .= '<li class="flex items-center gap-3" ' . $spacing_style . '>';
                $output .= '<span class="w-3 h-3 bg-primary rounded-full"></span>';
                $output .= '<a href="' . esc_url($item->url) . '" class="hover:opacity-70 transition-opacity">' . esc_html($item->title) . '</a>';
                $output .= '</li>';
            }
        } elseif ($depth === 2) {
            // Level 3 items
            $spacing_style = 'style="margin-bottom: var(--haika-lvl3-item-spacing);"';
            $output .= '<li ' . $spacing_style . '><a class="hover:text-primary transition-colors block py-1" href="' . esc_url($item->url) . '">' . esc_html($item->title) . '</a></li>';
        }
    }

    // end_el is called when a menu item is closed.
    public function end_el(&$output, $item, $depth = 0, $args = null) {
        $output .= '</li>';
    }
}

// راه اندازی پلاگین
new HaikaMultilevelMenu();

// تنظیمات فعال سازی
register_activation_hook(__FILE__, function() {
    // تنظیمات پیش‌فرض
    if (!get_option('haika_menu_button_text')) {
        add_option('haika_menu_button_text', '☰ منو');
    }
    if (!get_option('haika_menu_button_color')) {
        add_option('haika_menu_button_color', '#ff8c00');
    }
    if (!get_option('haika_menu_icon_size')) {
        add_option('haika_menu_icon_size', '20');
    }
});
?>