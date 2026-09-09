<?php
if (!defined('ABSPATH')) exit;

/**
 * Registers tfp_week_video_url / meeting fields as REST-visible post meta
 * so the block editor's own data store (core/editor) can read and save
 * them — this is what powers the Gutenberg sidebar panel added in
 * assets/js/admin-week-meta-panel.js.
 *
 * We deliberately do NOT use the classic add_meta_box() mechanism here:
 * LearnDash strips third-party meta boxes from its Lesson edit screen,
 * so a classic meta box never renders there. The Gutenberg sidebar-panel
 * approach hooks into core editor data instead, which LearnDash doesn't
 * (and can't reasonably) block.
 */
add_action('init', function () {
    // IMPORTANT: Gutenberg meta fields via REST API ONLY work if the post type supports 'custom-fields'.
    // Since LearnDash disables this by default, we must re-enable it for our Sidebar Panel to save data.
    add_post_type_support('sfwd-lessons', 'custom-fields');

    $fields = [
        'tfp_week_video_url'          => 'esc_url_raw',
        'tfp_week_meeting_date'       => 'sanitize_text_field',
        'tfp_week_meeting_time'       => 'sanitize_text_field',
        'tfp_week_facilitator_name'   => 'sanitize_text_field',
        'tfp_week_meeting_description' => 'wp_kses_post',
        'tfp_week_meeting_discord_url' => 'esc_url_raw',
        'tfp_week_meeting_exercise_title' => 'sanitize_text_field',
        'tfp_week_meeting_exercise_format' => 'sanitize_text_field',
        'tfp_week_meeting_exercise_description' => 'wp_kses_post',
        'tfp_week_meeting_exercise_purpose' => 'wp_kses_post',
        'tfp_week_meeting_facilitator_notes' => 'wp_kses_post',
    ];

    foreach ($fields as $key => $sanitizer) {
        register_post_meta('sfwd-lessons', $key, [
            'show_in_rest'      => true,
            'single'            => true,
            'type'              => 'string',
            'sanitize_callback' => $sanitizer,
            'auth_callback'     => function () {
                return current_user_can('edit_posts');
            },
        ]);
    }

    // Homework Questions (JSON)
    //
    // The Gutenberg sidebar editor (assets/js/admin-week-meta-panel.js)
    // writes this meta as a serialized JSON array and is the only intended
    // writer. The sanitizer below must never convert a bad write into a full
    // deletion: on invalid JSON it keeps the raw (sanitized) value instead of
    // returning '' — the structured editor guarantees valid JSON, and the
    // consumers (homework-helpers.php / page-week.php) already treat an
    // unparseable value as "no questions".
    register_post_meta('sfwd-lessons', 'tfp_week_homework_questions', [
        'show_in_rest'      => true,
        'single'            => true,
        'type'              => 'string',
        'sanitize_callback' => function ($value) {
            if (empty($value)) {
                return ''; // Intentional clear (e.g. "no homework").
            }

            if (is_string($value)) {
                $decoded = json_decode($value, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    // Re-encode to ensure safe, compact canonical storage.
                    return wp_json_encode($decoded);
                }
            }

        // Invalid JSON — never silently wipe previously saved questions.
        return is_string($value) ? sanitize_textarea_field($value) : '';
        },
        'auth_callback'     => function () {
            return current_user_can('edit_posts');
        },
    ]);

    // Quiz Questions (JSON) — multiple-choice, each with a REQUIRED correct
    // answer (`correct_index`). Same safe-sanitize contract as homework:
    // invalid JSON is kept, never silently wiped.
    register_post_meta('sfwd-lessons', 'tfp_week_quiz_questions', [
        'show_in_rest'      => true,
        'single'            => true,
        'type'              => 'string',
        'sanitize_callback' => function ($value) {
            if (empty($value)) {
                return ''; // Intentional clear (e.g. "no quiz").
            }

            if (is_string($value)) {
                $decoded = json_decode($value, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    return wp_json_encode($decoded);
                }
            }

            return is_string($value) ? sanitize_textarea_field($value) : '';
        },
        'auth_callback'     => function () {
            return current_user_can('edit_posts');
        },
    ]);

    // Quiz pass percentage (1-100). Empty = use the 70% default (quiz-helpers.php).
    // Stored as a string so clearing the field removes the meta cleanly.
    register_post_meta('sfwd-lessons', 'tfp_week_quiz_pass_percentage', [
        'show_in_rest'      => true,
        'single'            => true,
        'type'              => 'string',
        'sanitize_callback' => function ($value) {
            if ($value === '' || $value === null) {
                return '';
            }
            $v = absint($value);
            if ($v < 1) {
                return '';
            }
            return (string) min(100, $v);
        },
        'auth_callback'     => function () {
            return current_user_can('edit_posts');
        },
    ]);

    // Test Questions and pass percentage (JSON / 1-100).
    // Test Questions (JSON) — multiple-choice, each with a REQUIRED correct
    // answer (`correct_index`). Same safe-sanitize contract as homework:
    // invalid JSON is kept, never silently wiped.
    register_post_meta('sfwd-lessons', 'tfp_week_test_questions', [
        'show_in_rest'      => true,
        'single'            => true,
        'type'              => 'string',
        'sanitize_callback' => function ($value) {
            if (empty($value)) {
                return ''; // Intentional clear (e.g. "no test").
            }

            if (is_string($value)) {
                $decoded = json_decode($value, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    return wp_json_encode($decoded);
                }
            }

            return is_string($value) ? sanitize_textarea_field($value) : '';
        },
        'auth_callback'     => function () {
            return current_user_can('edit_posts');
        },
    ]);


    // Test pass percentage (1-100). Empty = use the 70% default (test-helpers.php).
    // Stored as a string so clearing the field removes the meta cleanly.
    register_post_meta('sfwd-lessons', 'tfp_week_test_pass_percentage', [
        'show_in_rest'      => true,
        'single'            => true,
        'type'              => 'string',
        'sanitize_callback' => function ($value) {
            if ($value === '' || $value === null) {
                return '';
            }
            $v = absint($value);
            if ($v < 1) {
                return '';
            }
            return (string) min(100, $v);
        },
        'auth_callback'     => function () {
            return current_user_can('edit_posts');
        },
    ]);
}, 20);

/**
 * Load the custom Week editor panels in the Gutenberg document sidebar.
 *
 * Keep this check deliberately tolerant: LearnDash/Gutenberg can initialize
 * the editor before get_current_screen() is fully populated, so relying only
 * on one screen property can silently prevent all custom panels from loading.
 * The post type is resolved from the screen first, then the editor request.
 *
 * File modification timestamps are used as versions so restoring/redeploying
 * the plugin cannot leave the browser using stale admin JavaScript.
 */
add_action('enqueue_block_editor_assets', function () {
    $post_type = '';

    if (function_exists('get_current_screen')) {
        $screen = get_current_screen();
        if ($screen && !empty($screen->post_type)) {
            $post_type = (string) $screen->post_type;
        }
    }

    if ($post_type === '' && isset($_GET['post_type'])) {
        $post_type = sanitize_key(wp_unslash($_GET['post_type']));
    }

    if ($post_type === '' && isset($_GET['post'])) {
        $post_type = get_post_type(absint($_GET['post']));
    }

    if ($post_type !== 'sfwd-lessons') {
        return;
    }

    $deps = ['wp-plugins', 'wp-edit-post', 'wp-components', 'wp-data', 'wp-element', 'wp-i18n', 'wp-compose'];

    $meta_js = TFP_DASH_PATH . 'assets/js/admin-week-meta-panel.js';
    $quiz_js = TFP_DASH_PATH . 'assets/js/admin-week-quiz-panel.js';
    $test_js = TFP_DASH_PATH . 'assets/js/admin-week-test-panel.js';
    $panel_css = TFP_DASH_PATH . 'assets/css/admin-week-meta-panel.css';

    wp_enqueue_script(
        'tfp-week-meta-panel',
        TFP_DASH_URL . 'assets/js/admin-week-meta-panel.js',
        $deps,
        file_exists($meta_js) ? (string) filemtime($meta_js) : TFP_DASH_VERSION,
        true
    );

    wp_enqueue_script(
        'tfp-week-quiz-panel',
        TFP_DASH_URL . 'assets/js/admin-week-quiz-panel.js',
        $deps,
        file_exists($quiz_js) ? (string) filemtime($quiz_js) : TFP_DASH_VERSION,
        true
    );

    wp_enqueue_script(
        'tfp-week-test-panel',
        TFP_DASH_URL . 'assets/js/admin-week-test-panel.js',
        $deps,
        file_exists($test_js) ? (string) filemtime($test_js) : TFP_DASH_VERSION,
        true
    );

    wp_enqueue_style(
        'tfp-week-meta-panel-css',
        TFP_DASH_URL . 'assets/css/admin-week-meta-panel.css',
        [],
        file_exists($panel_css) ? (string) filemtime($panel_css) : TFP_DASH_VERSION
    );
}, 100);
