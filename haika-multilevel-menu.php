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
    }

    public function init() {
        // ثبت منطقه منو
        register_nav_menus(array(
            'haika_multilevel_menu' => 'منوی چندسطحی هایکا'
        ));
    }

    public function enqueue_scripts() {
        wp_enqueue_style('haika-menu-style', plugin_dir_url(__FILE__) . 'haika-style.css', array(), '1.3');
        wp_enqueue_script('haika-menu-script', plugin_dir_url(__FILE__) . 'haika-script.js', array('jquery'), '1.3', true);
        
        // اضافه کردن متغیرهای JavaScript
        wp_localize_script('haika-menu-script', 'haika_menu_vars', array(
            'button_icon' => get_option('haika_menu_button_icon', ''),
            'button_text' => get_option('haika_menu_button_text', '☰ منو')
        ));
    }

    public function render_menu($atts) {
        // تنظیمات شورت کد
        $atts = shortcode_atts(array(
            'button_text' => get_option('haika_menu_button_text', '☰ منو'),
            'button_icon' => get_option('haika_menu_button_icon', ''),
            'icon_size' => get_option('haika_menu_icon_size', '20'),
            'position' => 'right' // right یا left
        ), $atts);

        // دریافت منو از وردپرس
        $menu_html = wp_nav_menu(array(
            'theme_location' => 'haika_multilevel_menu',
            'container' => false,
            'menu_class' => 'haika-navbar',
            'echo' => false,
            'walker' => new HaikaMenuWalker()
        ));

        if (empty($menu_html)) {
            return '<p style="background: #fff3cd; padding: 10px; border: 1px solid #ffeaa7; border-radius: 5px;">منویی تعریف نشده است. از پنل ادمین > ظاهر > منوها یک منو ایجاد کنید و آن را به "منوی چندسطحی هایکا" اختصاص دهید.</p>';
        }

        $button_text = esc_html($atts['button_text']);
        $button_icon = esc_url($atts['button_icon']);
        $icon_size = intval($atts['icon_size']);
        $button_color = get_option('haika_menu_button_color', '#ff8c00');
        
        // تشخیص موقعیت منو
        $container_class = 'haika-menu-container';
        if ($atts['position'] === 'left') {
            $container_class .= ' left-position';
        }
        
        // ساخت محتوای دکمه
        $button_content = '';
        $button_class = 'haika-menu-btn';
        $button_style = '';
        
        if (!empty($button_icon)) {
            // اگر متن خالی است، فقط آیکون
            if (empty($button_text) || $button_text === '☰ منو' || trim($button_text) === '') {
                $button_class .= ' icon-only';
                $button_content = '<img src="' . $button_icon . '" alt="منو" class="menu-icon">';
                // برای آیکون فقط، پس‌زمینه شفاف
                $button_style = 'background: transparent !important;';
            } else {
                // آیکون + متن
                $button_content = '<img src="' . $button_icon . '" alt="منو" class="menu-icon"> ' . $button_text;
                $button_style = 'background-color: ' . $button_color . ';';
            }
        } else {
            // فقط متن
            $button_content = $button_text;
            $button_style = 'background-color: ' . $button_color . ';';
        }
        
        return "
        <div class='{$container_class}'>
            <button class='{$button_class}' 
                    data-text='{$button_text}' 
                    data-icon='{$button_icon}' 
                    data-icon-size='{$icon_size}'
                    style='{$button_style}'>{$button_content}</button>
            {$menu_html}
        </div>";
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
    }

    public function admin_page() {
        // ذخیره تنظیمات
        if (isset($_POST['submit'])) {
            update_option('haika_menu_button_text', sanitize_text_field($_POST['haika_menu_button_text']));
            update_option('haika_menu_button_color', sanitize_hex_color($_POST['haika_menu_button_color']));
            update_option('haika_menu_icon_size', intval($_POST['haika_menu_icon_size']));
            
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

// کلاس Walker ساده برای منو
class HaikaMenuWalker extends Walker_Nav_Menu {
    
    function start_lvl(&$output, $depth = 0, $args = null) {
        $output .= "\n<ul class='sub-menu'>\n";
    }

    function end_lvl(&$output, $depth = 0, $args = null) {
        $output .= "</ul>\n";
    }

    function start_el(&$output, $item, $depth = 0, $args = null, $id = 0) {
        $classes = empty($item->classes) ? array() : (array) $item->classes;
        $class_names = join(' ', apply_filters('nav_menu_css_class', array_filter($classes), $item, $args));
        $class_names = $class_names ? ' class="' . esc_attr($class_names) . '"' : '';
        
        $output .= "<li{$class_names}>";
        
        $link = '<a href="' . esc_attr($item->url) . '">';
        $link .= apply_filters('the_title', $item->title, $item->ID);
        $link .= '</a>';
        
        $output .= apply_filters('walker_nav_menu_start_el', $link, $item, $depth, $args);
    }

    function end_el(&$output, $item, $depth = 0, $args = null) {
        $output .= "</li>\n";
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