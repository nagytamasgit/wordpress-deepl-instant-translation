<?php
/**
 * Plugin Name: DeepL Translator Widget
 * Plugin URI:  https://nstudio.hu
 * Description: Real-time DeepL translation via shortcode. Inline placement, Polylang-compatible.
 * Version:     1.1.0
 * Author:      nstudio.hu
 * License:     GPL-2.0+
 * Text Domain: deepl-translator
 */

defined( 'ABSPATH' ) || exit;

// ─── Style helpers ─────────────────────────────────────────────────────────────

function deepl_default_style() {
    return [
        'btn_bg'           => 'transparent',
        'btn_border'       => 'rgba(255,255,255,0.35)',
        'btn_border_width' => '1',
        'btn_radius'       => '7',
        'btn_text'         => '#ffffff',
        'btn_hover_bg'     => 'rgba(255,255,255,0.10)',
        'dd_bg'            => '#1e2535',
        'dd_border'        => 'rgba(255,255,255,0.12)',
        'dd_radius'        => '10',
        'dd_text'          => '#e2e8f0',
        'dd_text_muted'    => '#94a3b8',
        'dd_hover_bg'      => 'rgba(255,255,255,0.07)',
        'dd_active_bg'     => 'rgba(99,102,241,0.25)',
        'dd_active_text'   => '#a5b4fc',
        'dd_divider'       => 'rgba(255,255,255,0.08)',
    ];
}

function deepl_sanitize_style( $val ) {
    if ( ! is_array( $val ) ) return deepl_default_style();
    $clean = [];
    foreach ( deepl_default_style() as $key => $default ) {
        $clean[ $key ] = isset( $val[ $key ] ) ? sanitize_text_field( $val[ $key ] ) : $default;
    }
    return $clean;
}

function deepl_get_style() {
    return wp_parse_args( (array) get_option( 'deepl_style', [] ), deepl_default_style() );
}

function deepl_available_languages() {
    return [
        'EN' => 'English (EN)',    'DE' => 'German (DE)',    'FR' => 'French (FR)',
        'ES' => 'Spanish (ES)', 'IT' => 'Italian (IT)',    'NL' => 'Dutch (NL)',
        'PL' => 'Polish (PL)', 'PT' => 'Portuguese (PT)', 'RU' => 'Russian (RU)',
        'JA' => 'Japanese (JA)',   'ZH' => 'Chinese (ZH)',
    ];
}

function deepl_sanitize_langs( $val ) {
    if ( ! is_array( $val ) ) return [];
    return array_values( array_intersect( (array) $val, array_keys( deepl_available_languages() ) ) );
}

// ─── Strings helpers ─────────────────────────────────────────────────────────

function deepl_default_lang_labels() {
    return [
        'HU' => 'Magyar',     'EN' => 'English',    'DE' => 'Deutsch',
        'FR' => 'Français',   'ES' => 'Español',    'IT' => 'Italiano',
        'NL' => 'Nederlands', 'PL' => 'Polski',     'PT' => 'Português',
        'RU' => 'Русский',    'JA' => '日本語',     'ZH' => '中文',
    ];
}

function deepl_default_ui_strings() {
    return [
        'translating' => '⏳ Translating…',
        'error'       => '❌ Translation error',
        'aria_label'  => 'Language switcher',
    ];
}

function deepl_sanitize_strings( $val ) {
    if ( ! is_array( $val ) ) return [];
    $clean    = [];
    $all_keys = array_merge( array_keys( deepl_default_lang_labels() ), array_keys( deepl_default_ui_strings() ) );
    foreach ( $all_keys as $key ) {
        if ( isset( $val[ $key ] ) ) {
            $clean[ $key ] = sanitize_text_field( $val[ $key ] );
        }
    }
    return $clean;
}

function deepl_get_lang_labels() {
    $saved    = (array) get_option( 'deepl_strings', [] );
    $defaults = deepl_default_lang_labels();
    foreach ( $defaults as $code => $default ) {
        if ( isset( $saved[ $code ] ) && $saved[ $code ] !== '' ) {
            $defaults[ $code ] = $saved[ $code ];
        }
    }
    return $defaults;
}

function deepl_get_ui_strings() {
    $saved    = (array) get_option( 'deepl_strings', [] );
    $defaults = deepl_default_ui_strings();
    foreach ( $defaults as $key => $default ) {
        if ( isset( $saved[ $key ] ) && $saved[ $key ] !== '' ) {
            $defaults[ $key ] = $saved[ $key ];
        }
    }
    return $defaults;
}

// ─── Admin ───────────────────────────────────────────────────────────────────

add_action( 'admin_menu', function () {
    add_options_page( 'DeepL Translator', 'DeepL Translator', 'manage_options', 'deepl-translator', 'deepl_translator_settings_page' );
} );

add_action( 'admin_init', function () {
    register_setting( 'deepl_translator_group', 'deepl_api_key',      [ 'sanitize_callback' => 'sanitize_text_field' ] );
    register_setting( 'deepl_translator_group', 'deepl_target_langs', [ 'sanitize_callback' => 'deepl_sanitize_langs' ] );
    register_setting( 'deepl_translator_group', 'deepl_source_lang',  [ 'sanitize_callback' => 'sanitize_text_field', 'default' => 'HU' ] );
    register_setting( 'deepl_translator_group', 'deepl_cache_hours',  [ 'sanitize_callback' => 'absint', 'default' => 24 ] );
    register_setting( 'deepl_translator_group', 'deepl_style',        [ 'sanitize_callback' => 'deepl_sanitize_style', 'default' => deepl_default_style() ] );
    register_setting( 'deepl_translator_group', 'deepl_strings',       [ 'sanitize_callback' => 'deepl_sanitize_strings', 'default' => [] ] );
} );

function deepl_color_row( $label, $name, $value, $allow_alpha = false ) {
    $key    = str_replace( [ 'deepl_style[', ']' ], '', $name );
    $is_hex = (bool) preg_match( '/^#[0-9a-fA-F]{3,8}$/', $value );
    ?>
    <tr>
        <th style="padding:6px 10px 6px 0;font-weight:500;width:168px"><label><?php echo esc_html( $label ); ?></label></th>
        <td style="padding:6px 0">
            <div style="display:flex;align-items:center;gap:8px">
                <?php if ( $is_hex && ! $allow_alpha ) : ?>
                    <input type="color" value="<?php echo esc_attr( $value ); ?>"
                           style="width:36px;height:28px;border:none;padding:0;cursor:pointer;border-radius:4px;flex-shrink:0"
                           oninput="this.nextElementSibling.value=this.value;this.nextElementSibling.dispatchEvent(new Event('input'))">
                <?php else : ?>
                    <div style="width:28px;height:28px;border-radius:4px;border:1px solid #ddd;flex-shrink:0;background:<?php echo esc_attr( $value ); ?>"></div>
                <?php endif; ?>
                <input type="text" name="<?php echo esc_attr( $name ); ?>" value="<?php echo esc_attr( $value ); ?>"
                       data-preview="<?php echo esc_attr( $key ); ?>"
                       style="width:210px;font-family:monospace;font-size:12px"
                       placeholder="<?php echo $allow_alpha ? 'rgba(0,0,0,0.5) or #hex' : '#rrggbb'; ?>" />
                <?php if ( $allow_alpha ) : ?>
                    <span style="font-size:11px;color:#999">rgba OK</span>
                <?php endif; ?>
            </div>
        </td>
    </tr>
    <?php
}

function deepl_translator_settings_page() {
    $api_key      = get_option( 'deepl_api_key', '' );
    $target_langs = get_option( 'deepl_target_langs', [ 'EN', 'FR' ] );
    $source_lang  = get_option( 'deepl_source_lang', 'HU' );
    $cache_hours  = get_option( 'deepl_cache_hours', 24 );
    $all_langs    = deepl_available_languages();
    $s            = deepl_get_style();
    $lang_flags   = [ 'HU'=>'🇭🇺','EN'=>'🇬🇧','DE'=>'🇩🇪','FR'=>'🇫🇷','ES'=>'🇪🇸','IT'=>'🇮🇹','NL'=>'🇳🇱','PL'=>'🇵🇱','PT'=>'🇵🇹','RU'=>'🇷🇺','JA'=>'🇯🇵','ZH'=>'🇨🇳' ];
    $lang_labels  = [ 'HU'=>'Magyar','EN'=>'English','DE'=>'Deutsch','FR'=>'Français','ES'=>'Español','IT'=>'Italiano','NL'=>'Nederlands','PL'=>'Polski','PT'=>'Português','RU'=>'Русский','JA'=>'日本語','ZH'=>'中文' ];
    ?>
    <div class="wrap">
        <h1 style="margin-bottom:20px">DeepL Translator Settings</h1>
        <form method="post" action="options.php">
            <?php settings_fields( 'deepl_translator_group' ); ?>

            <h2 class="title">API &amp; Languages</h2>
            <table class="form-table" style="max-width:700px">
                <tr>
                    <th><label for="deepl_api_key">DeepL API kulcs</label></th>
                    <td>
                        <input type="password" id="deepl_api_key" name="deepl_api_key"
                               value="<?php echo esc_attr( $api_key ); ?>" class="regular-text" />
                        <p class="description"><a href="https://www.deepl.com/pro-api" target="_blank">Get a DeepL API key</a>. Free (ending in :fx) and Pro keys are both supported.</p>
                    </td>
                </tr>
                <tr>
                    <th>Source language</th>
                    <td>
                        <select name="deepl_source_lang">
                            <option value="HU" <?php selected( $source_lang, 'HU' ); ?>>Hungarian (HU)</option>
                            <?php foreach ( $all_langs as $code => $label ) : ?>
                                <option value="<?php echo esc_attr( $code ); ?>" <?php selected( $source_lang, $code ); ?>><?php echo esc_html( $label ); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th>Target languages</th>
                    <td>
                        <div style="display:flex;flex-wrap:wrap;gap:4px 20px">
                        <?php foreach ( $all_langs as $code => $label ) : ?>
                            <label>
                                <input type="checkbox" name="deepl_target_langs[]" value="<?php echo esc_attr( $code ); ?>"
                                    <?php checked( in_array( $code, (array) $target_langs, true ) ); ?> />
                                <?php echo esc_html( $label ); ?>
                            </label>
                        <?php endforeach; ?>
                        </div>
                    </td>
                </tr>
                <tr>
                    <th><label for="deepl_cache_hours">Cache (hours)</label></th>
                    <td>
                        <input type="number" id="deepl_cache_hours" name="deepl_cache_hours"
                               value="<?php echo esc_attr( $cache_hours ); ?>" min="0" max="720" class="small-text" /> hours
                        <p class="description">0 = no cache.</p>
                    </td>
                </tr>
            </table>

            <hr style="margin:28px 0">
            <h2 class="title" style="margin-bottom:4px">Widget Style</h2>
            <p style="color:#646970;margin-bottom:20px">Changes reflect instantly in the preview. Hex (#rrggbb) and rgba() values are both accepted.</p>

            <div style="display:flex;gap:40px;align-items:flex-start;flex-wrap:wrap">

                <!-- Bal: form -->
                <div style="flex:1;min-width:400px;max-width:560px">

                    <div style="background:#f6f7f7;border:1px solid #dde;border-radius:8px;padding:16px 20px;margin-bottom:20px">
                        <h3 style="margin:0 0 12px;font-size:12px;font-weight:600;text-transform:uppercase;letter-spacing:.6px;color:#50575e">Button</h3>
                        <table style="width:100%;border-collapse:collapse">
                            <?php deepl_color_row( 'Background color',     'deepl_style[btn_bg]',           $s['btn_bg'],           true ); ?>
                            <?php deepl_color_row( 'Text color',     'deepl_style[btn_text]',         $s['btn_text'] ); ?>
                            <?php deepl_color_row( 'Border color',      'deepl_style[btn_border]',       $s['btn_border'],       true ); ?>
                            <?php deepl_color_row( 'Hover background',    'deepl_style[btn_hover_bg]',     $s['btn_hover_bg'],     true ); ?>
                            <tr>
                                <th style="padding:6px 10px 6px 0;font-weight:500;width:168px"><label>Border width</label></th>
                                <td style="padding:6px 0">
                                    <input type="number" name="deepl_style[btn_border_width]" value="<?php echo esc_attr( $s['btn_border_width'] ); ?>"
                                           min="0" max="5" step="0.5" class="small-text" data-preview="btn_border_width" /> px
                                </td>
                            </tr>
                            <tr>
                                <th style="padding:6px 10px 6px 0;font-weight:500"><label>Border radius</label></th>
                                <td style="padding:6px 0">
                                    <input type="number" name="deepl_style[btn_radius]" value="<?php echo esc_attr( $s['btn_radius'] ); ?>"
                                           min="0" max="50" class="small-text" data-preview="btn_radius" /> px
                                </td>
                            </tr>
                        </table>
                    </div>

                    <div style="background:#f6f7f7;border:1px solid #dde;border-radius:8px;padding:16px 20px">
                        <h3 style="margin:0 0 12px;font-size:12px;font-weight:600;text-transform:uppercase;letter-spacing:.6px;color:#50575e">Dropdown menu</h3>
                        <table style="width:100%;border-collapse:collapse">
                            <?php deepl_color_row( 'Background color',          'deepl_style[dd_bg]',          $s['dd_bg'] ); ?>
                            <?php deepl_color_row( 'Border color',           'deepl_style[dd_border]',      $s['dd_border'],     true ); ?>
                            <?php deepl_color_row( 'Text color',          'deepl_style[dd_text]',        $s['dd_text'] ); ?>
                            <?php deepl_color_row( 'Muted text color',  'deepl_style[dd_text_muted]',  $s['dd_text_muted'] ); ?>
                            <?php deepl_color_row( 'Hover background',         'deepl_style[dd_hover_bg]',    $s['dd_hover_bg'],   true ); ?>
                            <?php deepl_color_row( 'Active row background',     'deepl_style[dd_active_bg]',   $s['dd_active_bg'],  true ); ?>
                            <?php deepl_color_row( 'Active text color',    'deepl_style[dd_active_text]', $s['dd_active_text'] ); ?>
                            <?php deepl_color_row( 'Divider color',     'deepl_style[dd_divider]',     $s['dd_divider'],    true ); ?>
                            <tr>
                                <th style="padding:6px 10px 6px 0;font-weight:500;width:168px"><label>Border radius</label></th>
                                <td style="padding:6px 0">
                                    <input type="number" name="deepl_style[dd_radius]" value="<?php echo esc_attr( $s['dd_radius'] ); ?>"
                                           min="0" max="50" class="small-text" data-preview="dd_radius" /> px
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>

                <!-- Jobb: preview -->
                <div style="position:sticky;top:32px;min-width:240px">
                    <h3 style="margin:0 0 10px;font-size:12px;font-weight:600;text-transform:uppercase;letter-spacing:.6px;color:#50575e">Live Preview</h3>

                    <div id="deepl-preview-bg" style="background:#1a1a2e;padding:20px 18px;border-radius:10px;display:inline-flex;align-items:center;gap:12px;margin-bottom:10px">
                        <span style="color:#fff;font-size:13px;font-weight:600;font-family:-apple-system,sans-serif;opacity:.7">TUDÁSTÁR</span>
                        <div style="position:relative">
                            <button id="dpp-btn" style="display:inline-flex;align-items:center;gap:6px;background:<?php echo esc_attr($s['btn_bg']); ?>;border:<?php echo esc_attr($s['btn_border_width']); ?>px solid <?php echo esc_attr($s['btn_border']); ?>;border-radius:<?php echo esc_attr($s['btn_radius']); ?>px;color:<?php echo esc_attr($s['btn_text']); ?>;padding:5px 9px 5px 8px;font-size:13px;font-weight:600;font-family:-apple-system,sans-serif;cursor:pointer;line-height:1">
                                <?php echo $lang_flags[$source_lang] ?? '🇭🇺'; ?> <span><?php echo esc_html($source_lang); ?></span>
                                <svg width="10" height="10" viewBox="0 0 10 10" fill="none"><path d="M2 3.5L5 6.5L8 3.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            </button>
                            <div id="dpp-menu" style="position:absolute;top:calc(100% + 6px);left:0;background:<?php echo esc_attr($s['dd_bg']); ?>;border:1px solid <?php echo esc_attr($s['dd_border']); ?>;border-radius:<?php echo esc_attr($s['dd_radius']); ?>px;min-width:148px;padding:4px 0;font-family:-apple-system,sans-serif;z-index:100">
                                <div class="dpp-row dpp-active" style="display:flex;align-items:center;gap:8px;padding:8px 13px;background:<?php echo esc_attr($s['dd_active_bg']); ?>">
                                    <span style="font-size:17px;line-height:1"><?php echo $lang_flags[$source_lang] ?? '🇭🇺'; ?></span>
                                    <span class="dpp-code dpp-active-code" style="font-size:12px;font-weight:700;color:<?php echo esc_attr($s['dd_active_text']); ?>;min-width:24px"><?php echo esc_html($source_lang); ?></span>
                                    <span class="dpp-label dpp-active-label" style="font-size:12px;color:<?php echo esc_attr($s['dd_active_text']); ?>"><?php echo esc_html($lang_labels[$source_lang] ?? ''); ?></span>
                                </div>
                                <div class="dpp-divider" style="height:1px;background:<?php echo esc_attr($s['dd_divider']); ?>;margin:3px 0"></div>
                                <?php
                                $preview_langs = array_slice( (array) $target_langs, 0, 3 );
                                foreach ( $preview_langs as $code ) : ?>
                                <div class="dpp-row" style="display:flex;align-items:center;gap:8px;padding:8px 13px">
                                    <span style="font-size:17px;line-height:1"><?php echo $lang_flags[$code] ?? ''; ?></span>
                                    <span class="dpp-code" style="font-size:12px;font-weight:700;color:<?php echo esc_attr($s['dd_text']); ?>;min-width:24px"><?php echo esc_html($code); ?></span>
                                    <span class="dpp-label" style="font-size:12px;color:<?php echo esc_attr($s['dd_text_muted']); ?>"><?php echo esc_html($lang_labels[$code] ?? $code); ?></span>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>

                    <div style="display:flex;align-items:center;gap:8px;margin-bottom:12px">
                        <label style="font-size:12px;color:#50575e">Preview background:</label>
                        <input type="color" id="dpp-bg-picker" value="#1a1a2e" style="width:36px;height:24px;border:none;padding:0;cursor:pointer;border-radius:4px">
                    </div>

                    <button type="button" id="deepl-reset-style" class="button">↺ Reset to defaults</button>
                </div>
            </div>

            <hr style="margin:28px 0 20px">
            <h2 class="title" style="margin-bottom:4px">Strings &amp; Language Names</h2>
            <p style="color:#646970;margin-bottom:20px">Leave any field empty to use the default value. Language names appear in the dropdown widget.</p>

            <div style="display:flex;gap:40px;flex-wrap:wrap;align-items:flex-start">

                <div style="background:#f6f7f7;border:1px solid #dde;border-radius:8px;padding:16px 20px;min-width:320px;flex:1;max-width:420px">
                    <h3 style="margin:0 0 12px;font-size:12px;font-weight:600;text-transform:uppercase;letter-spacing:.6px;color:#50575e">UI strings</h3>
                    <table style="width:100%;border-collapse:collapse">
                        <?php
                        $ui_strings  = deepl_get_ui_strings();
                        $ui_defaults = deepl_default_ui_strings();
                        $ui_labels   = [
                            'translating' => 'Translating label',
                            'error'       => 'Error message',
                            'aria_label'  => 'Aria label (accessibility)',
                        ];
                        foreach ( $ui_defaults as $key => $default ) :
                            $saved_ui = (array) get_option( 'deepl_strings', [] );
                            $current  = $saved_ui[ $key ] ?? '';
                        ?>
                        <tr>
                            <th style="padding:7px 10px 7px 0;font-weight:500;width:180px;vertical-align:top;padding-top:9px">
                                <label for="ds_<?php echo esc_attr($key); ?>"><?php echo esc_html( $ui_labels[$key] ?? $key ); ?></label>
                            </th>
                            <td style="padding:7px 0">
                                <input type="text" id="ds_<?php echo esc_attr($key); ?>"
                                       name="deepl_strings[<?php echo esc_attr($key); ?>]"
                                       value="<?php echo esc_attr( $current ); ?>"
                                       placeholder="<?php echo esc_attr( $default ); ?>"
                                       class="regular-text" style="font-size:13px" />
                                <p style="margin:2px 0 0;font-size:11px;color:#999">Default: <code><?php echo esc_html($default); ?></code></p>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </table>
                </div>

                <div style="background:#f6f7f7;border:1px solid #dde;border-radius:8px;padding:16px 20px;min-width:320px;flex:1;max-width:480px">
                    <h3 style="margin:0 0 12px;font-size:12px;font-weight:600;text-transform:uppercase;letter-spacing:.6px;color:#50575e">Language names</h3>
                    <p style="font-size:12px;color:#646970;margin:0 0 12px">These appear in the dropdown next to the language code (EN, FR…).</p>
                    <table style="width:100%;border-collapse:collapse">
                        <?php
                        $saved_strings   = (array) get_option( 'deepl_strings', [] );
                        $default_labels  = deepl_default_lang_labels();
                        $all_codes       = array_merge( [ $source_lang ], array_keys( $default_labels ) );
                        $all_codes       = array_unique( $all_codes );
                        $flags           = [ 'HU'=>'🇭🇺','EN'=>'🇬🇧','DE'=>'🇩🇪','FR'=>'🇫🇷','ES'=>'🇪🇸','IT'=>'🇮🇹','NL'=>'🇳🇱','PL'=>'🇵🇱','PT'=>'🇵🇹','RU'=>'🇷🇺','JA'=>'🇯🇵','ZH'=>'🇨🇳' ];
                        foreach ( $default_labels as $code => $default_name ) :
                            $saved_val = $saved_strings[ $code ] ?? '';
                        ?>
                        <tr>
                            <th style="padding:5px 10px 5px 0;font-weight:500;width:60px">
                                <span style="font-size:16px"><?php echo $flags[$code] ?? ''; ?></span>
                                <span style="font-size:12px;font-weight:700;margin-left:4px"><?php echo esc_html($code); ?></span>
                            </th>
                            <td style="padding:5px 0">
                                <input type="text"
                                       name="deepl_strings[<?php echo esc_attr($code); ?>]"
                                       value="<?php echo esc_attr( $saved_val ); ?>"
                                       placeholder="<?php echo esc_attr( $default_name ); ?>"
                                       style="width:180px;font-size:13px" />
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </table>
                </div>

            </div>

            <div style="margin-top:28px;padding-top:20px;border-top:1px solid #dde">
                <?php submit_button( 'Save settings', 'primary', 'submit', false ); ?>
            </div>
        </form>

        <hr style="margin:32px 0 24px">
        <h2>Usage</h2>
        <p>Shortcode: <code>[deepl_translator]</code></p>
        <p>From a PHP template: <code>&lt;?php echo do_shortcode('[deepl_translator]'); ?&gt;</code></p>
        <p style="color:#646970;font-size:13px">Optional parameters: <code>langs="EN,FR,DE"</code> &nbsp;|&nbsp; <code>selector=".my-content"</code></p>
    </div>

    <script>
    (function() {
        const defaults = <?php echo wp_json_encode( deepl_default_style() ); ?>;

        const btn  = document.getElementById('dpp-btn');
        const menu = document.getElementById('dpp-menu');

        // Preview background picker
        document.getElementById('dpp-bg-picker').addEventListener('input', e => {
            document.getElementById('deepl-preview-bg').style.background = e.target.value;
        });

        // Live preview update map
        const previewFns = {
            btn_bg:           v => btn.style.background = v,
            btn_text:         v => btn.style.color = v,
            btn_border:       v => btn.style.borderColor = v,
            btn_border_width: v => btn.style.borderWidth = v + 'px',
            btn_radius:       v => btn.style.borderRadius = v + 'px',
            btn_hover_bg:     v => {},
            dd_bg:            v => menu.style.background = v,
            dd_border:        v => menu.style.borderColor = v,
            dd_radius:        v => menu.style.borderRadius = v + 'px',
            dd_text:          v => menu.querySelectorAll('.dpp-code:not(.dpp-active-code),.dpp-label:not(.dpp-active-label)').forEach(e => e.style.color = v),
            dd_text_muted:    v => menu.querySelectorAll('.dpp-label:not(.dpp-active-label)').forEach(e => e.style.color = v),
            dd_hover_bg:      v => {},
            dd_active_bg:     v => { const a = menu.querySelector('.dpp-active'); if(a) a.style.background = v; },
            dd_active_text:   v => menu.querySelectorAll('.dpp-active-code,.dpp-active-label').forEach(e => e.style.color = v),
            dd_divider:       v => menu.querySelectorAll('.dpp-divider').forEach(e => e.style.background = v),
        };

        document.querySelectorAll('[data-preview], [name^="deepl_style"]').forEach(input => {
            const key = (input.dataset.preview || input.name.replace('deepl_style[','').replace(']','')).trim();
            input.addEventListener('input', () => {
                if (previewFns[key]) previewFns[key](input.value);
                // Sync colour swatch next to rgba fields
                const swatch = input.closest('div')?.querySelector('div[style*="background"]');
                if (swatch) swatch.style.background = input.value;
            });
            // Sync colour picker → text input
            const picker = input.previousElementSibling;
            if (picker && picker.type === 'color') {
                picker.addEventListener('input', () => {
                    input.value = picker.value;
                    if (previewFns[key]) previewFns[key](picker.value);
                });
            }
        });

        // Reset
        document.getElementById('deepl-reset-style').addEventListener('click', () => {
            if (!confirm('Reset all styles to their default values?')) return;
            document.querySelectorAll('[name^="deepl_style"]').forEach(input => {
                const key = input.name.replace('deepl_style[','').replace(']','');
                if (defaults[key] === undefined) return;
                input.value = defaults[key];
                if (previewFns[key]) previewFns[key](defaults[key]);
                const picker = input.previousElementSibling;
                if (picker && picker.type === 'color' && defaults[key].startsWith('#')) picker.value = defaults[key];
            });
        });
    })();
    </script>
    <?php
}

// ─── REST API ─────────────────────────────────────────────────────────────────

add_action( 'rest_api_init', function () {
    register_rest_route( 'deepl-translator/v1', '/translate', [
        'methods'             => 'POST',
        'callback'            => 'deepl_rest_translate',
        'permission_callback' => '__return_true',
        'args'                => [
            'texts'       => [ 'required' => true, 'type' => 'array' ],
            'target_lang' => [ 'required' => true, 'type' => 'string' ],
        ],
    ] );
} );

function deepl_rest_translate( WP_REST_Request $request ) {
    $api_key     = get_option( 'deepl_api_key', '' );
    $source_lang = strtoupper( get_option( 'deepl_source_lang', 'HU' ) );
    $cache_hours = (int) get_option( 'deepl_cache_hours', 24 );

    if ( empty( $api_key ) ) {
        return new WP_Error( 'no_api_key', 'DeepL API key is not configured.', [ 'status' => 500 ] );
    }

    $texts       = array_map( 'sanitize_text_field', (array) $request->get_param( 'texts' ) );
    $target_lang = strtoupper( sanitize_text_field( $request->get_param( 'target_lang' ) ) );
    $allowed     = array_map( 'strtoupper', (array) get_option( 'deepl_target_langs', [] ) );

    if ( ! in_array( $target_lang, $allowed, true ) ) {
        return new WP_Error( 'lang_not_allowed', 'This target language is not allowed.', [ 'status' => 403 ] );
    }

    $cache_key = 'deepl_' . md5( $target_lang . implode( '||', $texts ) );
    if ( $cache_hours > 0 ) {
        $cached = get_transient( $cache_key );
        if ( false !== $cached ) return rest_ensure_response( [ 'translations' => $cached, 'cached' => true ] );
    }

    $is_free = str_ends_with( $api_key, ':fx' );
    $api_url  = $is_free ? 'https://api-free.deepl.com/v2/translate' : 'https://api.deepl.com/v2/translate';

    $response = wp_remote_post( $api_url, [
        'timeout' => 15,
        'headers' => [ 'Authorization' => 'DeepL-Auth-Key ' . $api_key, 'Content-Type' => 'application/json' ],
        'body'    => wp_json_encode( [ 'text' => $texts, 'source_lang' => $source_lang, 'target_lang' => $target_lang, 'preserve_formatting' => true ] ),
    ] );

    if ( is_wp_error( $response ) ) {
        return new WP_Error( 'deepl_request_failed', $response->get_error_message(), [ 'status' => 502 ] );
    }

    $code = wp_remote_retrieve_response_code( $response );
    $body = json_decode( wp_remote_retrieve_body( $response ), true );

    if ( 200 !== $code ) {
        return new WP_Error( 'deepl_api_error', $body['message'] ?? 'Unknown error.', [ 'status' => $code ] );
    }

    $translations = array_column( $body['translations'] ?? [], 'text' );
    if ( $cache_hours > 0 ) set_transient( $cache_key, $translations, $cache_hours * HOUR_IN_SECONDS );

    return rest_ensure_response( [ 'translations' => $translations, 'cached' => false ] );
}

// ─── Shortcode ────────────────────────────────────────────────────────────────

add_shortcode( 'deepl_translator', 'deepl_translator_shortcode' );

function deepl_translator_shortcode( $atts ) {
    $saved_langs = (array) get_option( 'deepl_target_langs', [ 'EN', 'FR' ] );
    $atts = shortcode_atts( [
        'langs'    => implode( ',', $saved_langs ),
        'selector' => '.entry-content, .page-content, main, article, #content',
    ], $atts, 'deepl_translator' );

    $langs = array_values( array_intersect(
        array_map( 'trim', explode( ',', strtoupper( $atts['langs'] ) ) ),
        array_keys( deepl_available_languages() )
    ) );
    if ( empty( $langs ) ) return '<!-- DeepL Translator: no valid target languages configured -->';

    $source_lang = strtoupper( get_option( 'deepl_source_lang', 'HU' ) );
    $widget_id   = 'deepl-widget-' . wp_unique_id();

    $lang_labels = deepl_get_lang_labels();
    $ui_strings  = deepl_get_ui_strings();
    $lang_flags  = [ 'HU'=>'🇭🇺','EN'=>'🇬🇧','DE'=>'🇩🇪','FR'=>'🇫🇷','ES'=>'🇪🇸','IT'=>'🇮🇹','NL'=>'🇳🇱','PL'=>'🇵🇱','PT'=>'🇵🇹','RU'=>'🇷🇺','JA'=>'🇯🇵','ZH'=>'🇨🇳' ];

    ob_start();
    ?>
    <div id="<?php echo esc_attr( $widget_id ); ?>" class="deepl-translator-widget">
        <div class="deepl-dropdown-wrap">
            <button class="deepl-toggle" aria-haspopup="listbox" aria-expanded="false">
                <span class="deepl-btn-flag"><?php echo $lang_flags[ $source_lang ] ?? ''; ?></span>
                <span class="deepl-btn-code"><?php echo esc_html( $source_lang ); ?></span>
                <svg class="deepl-chevron" width="10" height="10" viewBox="0 0 10 10" fill="none"><path d="M2 3.5L5 6.5L8 3.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </button>
            <ul class="deepl-menu" role="listbox" aria-label="<?php echo esc_attr( $ui_strings['aria_label'] ); ?>" hidden>
                <li role="option" class="deepl-option deepl-active"
                    data-lang="<?php echo esc_attr( $source_lang ); ?>"
                    data-flag="<?php echo esc_attr( $lang_flags[ $source_lang ] ?? '' ); ?>">
                    <span class="deepl-opt-flag"><?php echo $lang_flags[ $source_lang ] ?? ''; ?></span>
                    <span class="deepl-opt-code"><?php echo esc_html( $source_lang ); ?></span>
                    <span class="deepl-opt-label"><?php echo esc_html( $lang_labels[ $source_lang ] ?? '' ); ?></span>
                </li>
                <li class="deepl-divider" role="separator"></li>
                <?php foreach ( $langs as $code ) : ?>
                    <li role="option" class="deepl-option"
                        data-lang="<?php echo esc_attr( $code ); ?>"
                        data-flag="<?php echo esc_attr( $lang_flags[ $code ] ?? '' ); ?>">
                        <span class="deepl-opt-flag"><?php echo $lang_flags[ $code ] ?? ''; ?></span>
                        <span class="deepl-opt-code"><?php echo esc_html( $code ); ?></span>
                        <span class="deepl-opt-label"><?php echo esc_html( $lang_labels[ $code ] ?? $code ); ?></span>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
        <span class="deepl-status" role="status" aria-live="polite"></span>
    </div>
    <script>
    (function () {
        const WID = <?php echo wp_json_encode( $widget_id ); ?>;
        const STR = <?php echo wp_json_encode( $ui_strings ); ?>;
        const API = <?php echo wp_json_encode( esc_url( rest_url( 'deepl-translator/v1/translate' ) ) ); ?>;
        const NON = <?php echo wp_json_encode( wp_create_nonce( 'wp_rest' ) ); ?>;
        const SRC = <?php echo wp_json_encode( $source_lang ); ?>;
        const SEL = <?php echo wp_json_encode( $atts['selector'] ); ?>;

        const widget   = document.getElementById(WID);
        const statusEl = widget.querySelector('.deepl-status');
        const toggle   = widget.querySelector('.deepl-toggle');
        const menu     = widget.querySelector('.deepl-menu');
        let cur = SRC, originals = null;
        const cache = {};

        function collectNodes() {
            const nodes = [];
            document.querySelectorAll(SEL).forEach(c => {
                const w = document.createTreeWalker(c, NodeFilter.SHOW_TEXT, {
                    acceptNode(n) {
                        const p = n.parentElement;
                        if (!p) return NodeFilter.FILTER_REJECT;
                        if (['script','style','noscript','code','pre'].includes(p.tagName.toLowerCase())) return NodeFilter.FILTER_REJECT;
                        if (p.closest('.deepl-translator-widget')) return NodeFilter.FILTER_REJECT;
                        return n.textContent.trim().length < 2 ? NodeFilter.FILTER_SKIP : NodeFilter.FILTER_ACCEPT;
                    }
                });
                let n; while ((n = w.nextNode())) nodes.push(n);
            });
            return nodes;
        }

        function setBtn(lang, flag) {
            toggle.querySelector('.deepl-btn-flag').textContent = flag;
            toggle.querySelector('.deepl-btn-code').textContent = lang;
        }

        function setActive(lang) {
            widget.querySelectorAll('.deepl-option').forEach(el => el.classList.toggle('deepl-active', el.dataset.lang === lang));
            const opt = menu.querySelector(`[data-lang="${lang}"]`);
            if (opt) setBtn(lang, opt.dataset.flag);
        }

        function closeMenu() { menu.hidden = true; toggle.setAttribute('aria-expanded','false'); toggle.classList.remove('deepl-open'); }

        toggle.addEventListener('click', e => { e.stopPropagation(); const o = menu.hidden; menu.hidden = !o; toggle.setAttribute('aria-expanded', o ? 'true' : 'false'); toggle.classList.toggle('deepl-open', o); });
        menu.addEventListener('click', e => { const opt = e.target.closest('[data-lang]'); if (!opt) return; closeMenu(); translate(opt.dataset.lang); });
        document.addEventListener('click', e => { if (!widget.contains(e.target)) closeMenu(); });

        async function translate(lang) {
            if (lang === cur) return;
            if (lang === SRC) {
                if (originals) { originals.forEach(({node,orig}) => node.textContent = orig); cur = SRC; setActive(SRC); statusEl.textContent = ''; }
                return;
            }
            if (!originals) { const ns = collectNodes(); originals = ns.map(n => ({node:n, orig:n.textContent})); }
            if (cache[lang]) { cache[lang].forEach(({node,text}) => node.textContent = text); cur = lang; setActive(lang); statusEl.textContent = ''; return; }
            statusEl.textContent = STR.translating;
            try {
                const res = await fetch(API, { method:'POST', headers:{'Content-Type':'application/json','X-WP-Nonce':NON}, body: JSON.stringify({texts: originals.map(o=>o.orig), target_lang: lang}) });
                if (!res.ok) { const e=await res.json().catch(()=>({})); throw new Error(e.message||`HTTP ${res.status}`); }
                const data = await res.json();
                cache[lang] = originals.map(({node},i) => ({node, text: (data.translations||[])[i] ?? originals[i].orig}));
                cache[lang].forEach(({node,text}) => node.textContent = text);
                cur = lang; setActive(lang); statusEl.textContent = '';
            } catch(e) { statusEl.textContent = STR.error + ': ' + e.message; }
        }

        setActive(SRC);
    })();
    </script>
    <?php
    return ob_get_clean();
}

// ─── CSS generation from saved style ───────────────────────────────────────────

add_action( 'wp_head', function () {
    $s = deepl_get_style();
    $bw = esc_attr( $s['btn_border_width'] );
    $br = esc_attr( $s['btn_radius'] );
    $dr = esc_attr( $s['dd_radius'] );
    ?>
    <style id="deepl-translator-styles">
    .deepl-translator-widget{display:inline-flex!important;align-items:center!important;gap:8px!important;font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif!important;font-size:13px!important;position:relative!important;z-index:9999!important;list-style:none!important;margin:0!important;padding:0!important}
    .deepl-dropdown-wrap{position:relative!important;display:inline-block!important;list-style:none!important;margin:0!important;padding:0!important}
    .deepl-toggle{display:inline-flex!important;align-items:center!important;gap:6px!important;padding:5px 9px 5px 8px!important;background:<?php echo esc_attr($s['btn_bg']); ?>!important;border:<?php echo $bw; ?>px solid <?php echo esc_attr($s['btn_border']); ?>!important;border-radius:<?php echo $br; ?>px!important;cursor:pointer!important;font-family:inherit!important;font-size:13px!important;font-weight:600!important;color:<?php echo esc_attr($s['btn_text']); ?>!important;letter-spacing:.3px!important;transition:border-color .15s,background .15s!important;white-space:nowrap!important;line-height:1!important;text-decoration:none!important;box-shadow:none!important}
    .deepl-toggle:hover,.deepl-toggle.deepl-open{background:<?php echo esc_attr($s['btn_hover_bg']); ?>!important;text-decoration:none!important}
    .deepl-btn-flag{font-size:16px!important;line-height:1!important}
    .deepl-btn-code{font-size:13px!important;font-weight:600!important;letter-spacing:.4px!important}
    .deepl-chevron{opacity:.6!important;transition:transform .2s!important;flex-shrink:0!important;display:inline-block!important}
    .deepl-toggle.deepl-open .deepl-chevron{transform:rotate(180deg)!important}
    .deepl-menu{position:absolute!important;top:calc(100% + 6px)!important;left:0!important;background:<?php echo esc_attr($s['dd_bg']); ?>!important;border:1px solid <?php echo esc_attr($s['dd_border']); ?>!important;border-radius:<?php echo $dr; ?>px!important;box-shadow:0 8px 24px rgba(0,0,0,.35)!important;min-width:158px!important;list-style:none!important;margin:0!important;padding:4px 0!important;z-index:99999!important}
    .deepl-menu .deepl-option{display:flex!important;align-items:center!important;gap:8px!important;padding:8px 13px!important;cursor:pointer!important;color:<?php echo esc_attr($s['dd_text']); ?>!important;transition:background .1s!important;list-style:none!important;margin:0!important;text-decoration:none!important;border:none!important;background:transparent!important;border-radius:0!important}
    .deepl-menu .deepl-option::before,.deepl-menu .deepl-option::after{display:none!important;content:none!important}
    .deepl-menu .deepl-option:hover{background:<?php echo esc_attr($s['dd_hover_bg']); ?>!important;color:<?php echo esc_attr($s['dd_text']); ?>!important}
    .deepl-menu .deepl-option.deepl-active{background:<?php echo esc_attr($s['dd_active_bg']); ?>!important}
    .deepl-opt-flag{font-size:17px!important;line-height:1!important;flex-shrink:0!important}
    .deepl-opt-code{font-weight:700!important;font-size:12px!important;color:<?php echo esc_attr($s['dd_text']); ?>!important;min-width:24px!important}
    .deepl-opt-label{font-size:12px!important;color:<?php echo esc_attr($s['dd_text_muted']); ?>!important}
    .deepl-menu .deepl-option.deepl-active .deepl-opt-code,.deepl-menu .deepl-option.deepl-active .deepl-opt-label{color:<?php echo esc_attr($s['dd_active_text']); ?>!important}
    .deepl-divider{height:1px!important;background:<?php echo esc_attr($s['dd_divider']); ?>!important;margin:3px 0!important;padding:0!important;list-style:none!important}
    .deepl-divider::before,.deepl-divider::after{display:none!important;content:none!important}
    .deepl-status{font-size:12px!important;color:#94a3b8!important;white-space:nowrap!important}
    </style>
    <?php
}, 20 );

// ─── Cache flush on post save ──────────────────────────────────────────────────

add_action( 'save_post', function () {
    global $wpdb;
    $wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '\_transient\_deepl\_%' OR option_name LIKE '\_transient\_timeout\_deepl\_%'" );
} );
