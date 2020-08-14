<?php
/*
 * Manage Post Type
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('CWG_Instock_Post_Type')) {

    class CWG_Instock_Post_Type {

        /**
         * Construct the Class
         */
        public function __construct() {
            add_action('init', array($this, 'register_post_type'));
            add_action('init', array($this, 'register_post_status'));
            // manage columns
            add_filter('manage_cwginstocknotifier_posts_columns', array($this, 'add_columns'));
            add_action('manage_cwginstocknotifier_posts_custom_column', array($this, 'manage_columns'), 10, 2);

            add_filter('list_table_primary_column', array($this, 'list_table_primary_column'), 10, 2);

            // manage sortable columns
            add_filter('manage_edit-cwginstocknotifier_sortable_columns', array($this, 'sortable_columns'));
            // custom css
            add_action('admin_head', array($this, 'custom_css'));

            add_filter('post_row_actions', array($this, 'manage_row_actions'), 10, 2);
            // perform admin action
            add_action('admin_action_cwginstock-sendmail', array($this, 'send_manual_mail'));

            // Bulk action unset edit
            add_filter('bulk_actions-edit-cwginstocknotifier', array($this, 'remove_from_bulk_actions'));
            // handle bulk actions
            add_filter('handle_bulk_actions-edit-cwginstocknotifier', array($this, 'handle_bulk_actions'), 10, 3);

            //mark status to mail sent
            add_action('cwginstocknotifier_handle_action_mark_status_sent', array($this, 'bulk_mark_status_sent'));
            //mark status to subscribed
            add_action('cwginstocknotifier_handle_action_mark_status_subscribed', array($this, 'bulk_mark_status_subscribed'));
            //mark status to unsubscribed
            add_action('cwginstocknotifier_handle_action_mark_status_unsubscribed', array($this, 'bulk_mark_status_unsubscribed'));
            //send mail in bulk
            add_action('cwginstocknotifier_handle_action_send_mail', array($this, 'bulk_send_manual_email'));

            add_action('admin_menu', array($this, 'display_subscribers_count'), 999);

            add_filter('plugin_action_links_' . CWGSTOCKPLUGINBASENAME, array($this, 'plugin_action_links'));

            // add filter option in custom post type
            add_action('restrict_manage_posts', array($this, 'filter_by_subscribed_products'));

            add_filter('parse_query', array($this, 'parse_query'));

            // add columns in product list table
            // manage columns
            add_filter('manage_product_posts_columns', array($this, 'add_subscribers_count_column'), 999);
            add_action('manage_product_posts_custom_column', array($this, 'show_subscribers_count'), 999, 2);
            add_filter('manage_edit-product_sortable_columns', array($this, 'subscribers_sortable_columns'), 999);
            add_action('pre_get_posts', array($this, 'sort_total_subscribers'), 999);
        }

        /**
         * Register Post Type
         */
        public function register_post_type() {
            $labels = array(
                'name' => _x('Subscribers', 'All Subscribers', 'cwginstocknotifier'),
                'singular_name' => _x('All Subscribers', 'All Subscribers', 'cwginstocknotifier'),
                'menu_name' => _x('Instock Notifier', 'Instock Notifier', 'cwginstocknotifier'),
                'name_admin_bar' => _x('Instock Notifier', 'Name in Admin Bar', 'cwginstocknotifier'),
                'add_new' => _x('Add New Subscriber', 'add new in menu', 'cwginstocknotifier'),
                'add_new_item' => __('Add New Subscriber', 'cwginstocknotifier'),
                'new_item' => __('New Subscriber', 'cwginstocknotifier'),
                'edit_item' => __('Edit Subscriber', 'cwginstocknotifier'),
                'view_item' => __('View Subscriber', 'cwginstocknotifier'),
                'all_items' => __('All Subscribers', 'cwginstocknotifier'),
                'search_items' => __('Search Subscribers', 'cwginstocknotifier'),
                'parent_item_colon' => __('Parent:', 'cwginstocknotifier'),
                'not_found' => __('No Subscriber Found', 'cwginstocknotifier'),
                'not_found_in_trash' => __('No Subscriber found in Trash', 'cwginstocknotifier'),
            );

            $args = array(
                'labels' => $labels,
                'show_ui' => true,
                'show_in_menu' => true,
                'menu_icon' => 'data:image/svg+xml;base64,PD94bWwgdmVyc2lvbj0iMS4wIiBlbmNvZGluZz0iaXNvLTg4NTktMSI/PjxzdmcgdmVyc2lvbj0iMS4xIiBpZD0iTGF5ZXJfMSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIiB4bWxuczp4bGluaz0iaHR0cDovL3d3dy53My5vcmcvMTk5OS94bGluayIgeD0iMHB4IiB5PSIwcHgiIHZpZXdCb3g9IjAgMCA0OTMuNzczIDQ5My43NzMiIHN0eWxlPSJlbmFibGUtYmFja2dyb3VuZDpuZXcgMCAwIDQ5My43NzMgNDkzLjc3MzsiIHhtbDpzcGFjZT0icHJlc2VydmUiPjxwYXRoIHN0eWxlPSJmaWxsOiM4RDk2OUU7IiBkPSJNNDc5LjI1Niw0ODUuODk2YzAsNC4zNTYtMy41MjEsNy44NzctNy44NzcsNy44NzdIMjIuMzk0Yy00LjM1NiwwLTcuODc3LTMuNTIxLTcuODc3LTcuODc3di0xMy43ODVjMC00LjM1NiwzLjUyMS03Ljg3Nyw3Ljg3Ny03Ljg3N2g0NDguOTg1YzQuMzU2LDAsNy44NzcsMy41MjEsNy44NzcsNy44NzdWNDg1Ljg5NnoiLz48cGF0aCBzdHlsZT0iZmlsbDojQUNCNUJDOyIgZD0iTTIyLjM5NCw0NjQuMjM0Yy00LjAwMSwwLTcuMTY4LDMuMDMzLTcuNjgsNi44OTJjMC41MTIsMy44NiwzLjY3OSw2Ljg5Miw3LjY4LDYuODkyaDQ0OC45ODVjNC4wMDEsMCw3LjE2OC0zLjAzMyw3LjY4LTYuODkyYy0wLjUwNC0zLjg2LTMuNjc4LTYuODkyLTcuNjgtNi44OTJIMjIuMzk0eiIvPjxwYXRoIHN0eWxlPSJmaWxsOiNENjA5NDk7IiBkPSJNNDI0LjEyNSwxNjkuMDMxYy00Ljc1OCwwLTkuNTc4LTEuMzA4LTEzLjkwMy00LjA0OUwyNDYuODg2LDYxLjY0NUw4My41NTEsMTY0Ljk4MmMtMTIuMTcsNy42OTYtMjguMjQ3LDQuMDgtMzUuOTM1LTguMDc0Yy03LjY5Ni0xMi4xNy00LjA4LTI4LjI0Nyw4LjA4Mi0zNS45MzVMMjQ2Ljg4NiwwbDE5MS4xODEsMTIwLjk2NmMxMi4xNjIsNy42OTYsMTUuNzg1LDIzLjc3Myw4LjA4MiwzNS45MzVDNDQxLjIwMiwxNjQuNzM4LDQzMi43NjYsMTY5LjAxNSw0MjQuMTI1LDE2OS4wMzF6Ii8+PHBhdGggc3R5bGU9ImZpbGw6I0I3MDI0MzsiIGQ9Ik04My41NTEsMTY0Ljk4MkwyNDYuODg2LDYxLjY0NWwxNjMuMzM2LDEwMy4zMzdjNC4zMjQsMi43NDEsOS4xMzcsNC4wNDksMTMuOTAzLDQuMDQ5YzcuNzEyLTAuMDE2LDE1LjExNi0zLjU4NCwyMC4xODEtOS44OTNjLTEuNzU3LTIuMTUtMy43NjUtNC4xMi02LjIzMS01LjY3OUwyNDYuODg2LDMyLjQ5Mkw1NS42OTgsMTUzLjQ1OGMtMi40ODEsMS41NjctNC41MjksMy41NjgtNi4yODYsNS43NDJDNTcuNjA0LDE2OS40MDEsNzIuMjI0LDE3Mi4xNSw4My41NTEsMTY0Ljk4MnoiLz48cGF0aCBzdHlsZT0iZmlsbDojQzE3RTUyOyIgZD0iTTIyOS4xNjMsNDMyLjI3YzAsMy41MTMtMi44NDQsNi4zNzItNi4zNzIsNi4zNzJIMTE3LjM4MmMtMy41MjEsMC02LjM3Mi0yLjg1MS02LjM3Mi02LjM3MlYzMjYuODUzYzAtMy41MTMsMi44NDQtNi4zNzIsNi4zNzItNi4zNzJoMTA1LjQxN2MzLjUyMSwwLDYuMzcyLDIuODUxLDYuMzcyLDYuMzcyVjQzMi4yN0gyMjkuMTYzeiIvPjxwYXRoIHN0eWxlPSJmaWxsOiNCNTY4NDE7IiBkPSJNMjI5LjE2MywzMjYuODUzVjQzMi4yN2MwLDMuNTEzLTIuODQ0LDYuMzcyLTYuMzcyLDYuMzcySDExNy4zODIiLz48cGF0aCBzdHlsZT0iZmlsbDojQTg2NzQzOyIgZD0iTTExMS4wMDksMzI2Ljg1M2MwLTMuNTEzLDIuODQ0LTYuMzcyLDYuMzcyLTYuMzcyaDEwNS40MTdjMy41MjEsMCw2LjM3MiwyLjg1MSw2LjM3Miw2LjM3MlY0MzIuMjdjMCwzLjUxMy0yLjg0NCw2LjM3Mi02LjM3Miw2LjM3MiIvPjxwYXRoIHN0eWxlPSJmaWxsOiNDMTdFNTI7IiBkPSJNMzgyLjc2Myw0MzIuMjdjMCwzLjUxMy0yLjg0NCw2LjM3Mi02LjM3Miw2LjM3MkgyNzAuOTgyYy0zLjUyMSwwLTYuMzcyLTIuODUxLTYuMzcyLTYuMzcyVjMyNi44NTNjMC0zLjUxMywyLjg0NC02LjM3Miw2LjM3Mi02LjM3MmgxMDUuNDE3YzMuNTIxLDAsNi4zNzIsMi44NTEsNi4zNzIsNi4zNzJWNDMyLjI3SDM4Mi43NjN6Ii8+PHBhdGggc3R5bGU9ImZpbGw6I0I1Njg0MTsiIGQ9Ik0zODIuNzYzLDMyNi44NTNWNDMyLjI3YzAsMy41MTMtMi44NDQsNi4zNzItNi4zNzIsNi4zNzJIMjcwLjk4MiIvPjxwYXRoIHN0eWxlPSJmaWxsOiNBODY3NDM7IiBkPSJNMjY0LjYwOSwzMjYuODUzYzAtMy41MTMsMi44NDQtNi4zNzIsNi4zNzItNi4zNzJoMTA1LjQxN2MzLjUyMSwwLDYuMzcyLDIuODUxLDYuMzcyLDYuMzcyVjQzMi4yN2MwLDMuNTEzLTIuODQ0LDYuMzcyLTYuMzcyLDYuMzcyIi8+PHBhdGggc3R5bGU9ImZpbGw6I0MxN0U1MjsiIGQ9Ik0zMDUuOTYzLDI4My45MjRjMCwzLjUxMy0yLjg0NCw2LjM3Mi02LjM3Miw2LjM3MkgxOTQuMTgyYy0zLjUyMSwwLTYuMzcyLTIuODUxLTYuMzcyLTYuMzcyVjE3OC41MDdjMC0zLjUxMywyLjg0NC02LjM3Miw2LjM3Mi02LjM3MmgxMDUuNDE3YzMuNTIxLDAsNi4zNzIsMi44NTEsNi4zNzIsNi4zNzJ2MTA1LjQxN0gzMDUuOTYzeiIvPjxwYXRoIHN0eWxlPSJmaWxsOiNCNTY4NDE7IiBkPSJNMzA1Ljk2MywxNzguNTA3djEwNS40MTdjMCwzLjUxMy0yLjg0NCw2LjM3Mi02LjM3Miw2LjM3MkgxOTQuMTgyIi8+PHBhdGggc3R5bGU9ImZpbGw6I0E4Njc0MzsiIGQ9Ik0xODcuODA5LDE3OC41MDdjMC0zLjUxMywyLjg0NC02LjM3Miw2LjM3Mi02LjM3MmgxMDUuNDE3YzMuNTIxLDAsNi4zNzIsMi44NTEsNi4zNzIsNi4zNzJ2MTA1LjQxN2MwLDMuNTEzLTIuODQ0LDYuMzcyLTYuMzcyLDYuMzcyIi8+PGc+PC9nPjxnPjwvZz48Zz48L2c+PGc+PC9nPjxnPjwvZz48Zz48L2c+PGc+PC9nPjxnPjwvZz48Zz48L2c+PGc+PC9nPjxnPjwvZz48Zz48L2c+PGc+PC9nPjxnPjwvZz48Zz48L2c+PC9zdmc+',
                'capability_type' => 'post',
                'capabilities' => array(
                    'create_posts' => 'do_not_allow',
                ),
                'map_meta_cap' => true,
            );

            do_action('cwginstocknotifier_register_post_type');
            register_post_type('cwginstocknotifier', $args);
        }

        public function register_post_status() {

            register_post_status('cwg_mailsent', array(
                'label' => _x('Mail Sent', 'post', 'cwginstocknotifier'),
                'public' => true,
                'exclude_from_search' => false,
                'show_in_admin_all_list' => true,
                'show_in_admin_status_list' => true,
                'label_count' => _n_noop('Mail Sent <span class="count">(%s)</span>', 'Mail Sent <span class="count">(%s)</span>'),
            ));

            register_post_status('cwg_mailnotsent', array(
                'label' => _x('Failed', 'post', 'cwginstocknotifier'),
                'public' => true,
                'exclude_from_search' => false,
                'show_in_admin_all_list' => false,
                'show_in_admin_status_list' => true,
                'label_count' => _n_noop('Failed <span class="count">(%s)</span>', 'Failed <span class="count">(%s)</span>'),
            ));

            register_post_status('cwg_subscribed', array(
                'label' => _x('Subscribed', 'post', 'cwginstocknotifier'),
                'public' => true,
                'exclude_from_search' => false,
                'show_in_admin_all_list' => true,
                'show_in_admin_status_list' => true,
                'label_count' => _n_noop('Subscribed <span class="count">(%s)</span>', 'Subscribed <span class="count">(%s)</span>'),
            ));

            register_post_status('cwg_unsubscribed', array(
                'label' => _x('Unsubscribed', 'post', 'cwginstocknotifier'),
                'public' => true,
                'exclude_from_search' => false,
                'show_in_admin_all_list' => false,
                'show_in_admin_status_list' => true,
                'label_count' => _n_noop('Unsubscribed <span class="count">(%s)</span>', 'Unsubscribed <span class="count">(%s)</span>'),
            ));

            register_post_status('cwg_converted', array(
                'label' => _x('Purchased', 'post', 'cwginstocknotifier'),
                'public' => true,
                'exclude_from_search' => false,
                'show_in_admin_all_list' => true,
                'show_in_admin_status_list' => true,
                'label_count' => _n_noop('Purchased <span class="count">(%s)</span>', 'Purchased <span class="count">(%s)</span>'),
            ));
        }

        public function display_subscribers_count() {
            global $menu;
            $count_posts = wp_count_posts('cwginstocknotifier');
            $count = $count_posts->cwg_subscribed;
            if ($count >= 1) {
                foreach ($menu as $key => $value) {

                    if ($menu[$key][2] == 'edit.php?post_type=cwginstocknotifier') {

                        $menu[$key][0] .= ' <span class="update-plugins"><span class="plugin-count">' . $count . '</span></span>';

                        return;
                    }
                }
            }
        }

        public function add_columns($columns) {
            $newcolumns['cb'] = $columns['cb'];
            $newcolumns['cwgimage'] = "<img width='16' height='16' src='data:image/svg+xml;base64,PD94bWwgdmVyc2lvbj0iMS4wIiBlbmNvZGluZz0iaXNvLTg4NTktMSI/PjxzdmcgdmVyc2lvbj0iMS4xIiBpZD0iTGF5ZXJfMSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIiB4bWxuczp4bGluaz0iaHR0cDovL3d3dy53My5vcmcvMTk5OS94bGluayIgeD0iMHB4IiB5PSIwcHgiIHZpZXdCb3g9IjAgMCA1MTIuMDAxIDUxMi4wMDEiIHN0eWxlPSJlbmFibGUtYmFja2dyb3VuZDpuZXcgMCAwIDUxMi4wMDEgNTEyLjAwMTsiIHhtbDpzcGFjZT0icHJlc2VydmUiPjxwYXRoIHN0eWxlPSJmaWxsOiM2OTRCNEI7IiBkPSJNODguMjIyLDE1OS42MzJjLTgyLjE0MiwwLTczLjAzNCw5OS44OS03Mi42NTksMTcyLjI3N2MwLjAxNCwyLjczNCwxLjQ0Niw1LjE5OCwzLjc4OCw2LjYxYzkuMTAyLDUuNDg3LDM0LjEsMTguMzE2LDY4Ljg3MSwxOC4zMTZzNTkuNzY5LTEyLjgyOSw2OC44NzEtMTguMzE2YzIuMzQxLTEuNDEyLDMuNzc0LTMuODc2LDMuNzg4LTYuNjFDMTYxLjI1NiwyNTkuNTIyLDE3MC4zNjQsMTU5LjYzMiw4OC4yMjIsMTU5LjYzMnoiLz48cGF0aCBzdHlsZT0iZmlsbDojNUE0MTQ2OyIgZD0iTTgyLjkzLDE1OS44MzhjLTc2LjM5NSw0LjI4My02Ny43MzMsMTAxLjI5NS02Ny4zNjcsMTcyLjA3MWMwLjAxNCwyLjczNCwxLjQ0Niw1LjE5OSwzLjc4OCw2LjYxYzkuMTAyLDUuNDg3LDM0LjEsMTguMzE2LDY4Ljg3MSwxOC4zMTZsMjAuNzU5LTE0NS4zMDgiLz48cGF0aCBzdHlsZT0iZmlsbDojNjk0QjRCOyIgZD0iTTg4LjIyMiwxNTkuNjMyYy00Ni43MDYsMC00OS45NDksNTYuNDM2LTQ5Ljk0OSw1Ni40MzZjMjYuOTIxLTQuNTQxLDQzLjQ2Myw2LjgxMSw3MC43MDgtNC41NDFMODguMjIyLDM1Ni44MzVjMzQuNzcyLDAsNTkuNzY5LTEyLjgyOSw2OC44NzEtMTguMzE2YzIuMzQxLTEuNDEyLDMuNzc0LTMuODc2LDMuNzg4LTYuNjFDMTYxLjI1NiwyNTkuNTIyLDE3MC4zNjQsMTU5LjYzMiw4OC4yMjIsMTU5LjYzMnoiLz48cGF0aCBzdHlsZT0iZmlsbDojNzg1NTUwOyIgZD0iTTE2MC44ODEsMzMxLjkwOWMwLjMyMi02Mi4yMDEsNy4wMzctMTQ0LjY1MS00My4xODMtMTY2LjYzOWMtMC44NzYsNC41NS0yLjQ2MywxNy4yNTMsMS42NjEsMjUuNDk4YzEwLjM3OSwyMC43NTksMjAuNzU5LDIwLjc1OSwzMS4xMzcsMzYuMzI3YzEwLjM3OSw1Ny4wODUtMTkuODMxLDExNy42NzQtMjkuODY1LDEyNS45MjNsMCwwYzE3Ljc3OC00LjIyOCwzMC41MjItMTAuOTE5LDM2LjQ2Mi0xNC40OTlDMTU5LjQzNCwzMzcuMTA4LDE2MC44NjcsMzM0LjY0MywxNjAuODgxLDMzMS45MDl6Ii8+PHBhdGggc3R5bGU9ImZpbGw6I0U2QUY3ODsiIGQ9Ik0xNjYuODY0LDM0Ny42NTNsLTQ2LjMwNy0xOS4yOTRjLTMuODY3LTEuNjExLTYuMzg3LTUuMzktNi4zODctOS41ODF2LTEyLjU0MUg2Mi4yNzV2MTIuNTQxYzAsNC4xOTEtMi41Miw3Ljk3LTYuMzg3LDkuNTgxTDkuNTgxLDM0Ny42NTNDMy43NzksMzUwLjA3MSwwLDM1NS43NCwwLDM2Mi4wMjV2MjAuNzU5YzAsNS43MzIsNC42NDcsMTAuMzc5LDEwLjM3OSwxMC4zNzloMTU1LjY4NmM1LjczMiwwLDEwLjM3OS00LjY0NywxMC4zNzktMTAuMzc5di0yMC43NTlDMTc2LjQ0NSwzNTUuNzQsMTcyLjY2NiwzNTAuMDcxLDE2Ni44NjQsMzQ3LjY1M3oiLz48cGF0aCBzdHlsZT0iZmlsbDojRUZGMkZBOyIgZD0iTTE2Ni44NjQsMzQ3LjY1M2wtNDQuNzczLTE4LjY1NmwtMzMuODY5LDE0LjYxOGwtMzMuODY5LTE0LjYxN0w5LjU4MSwzNDcuNjU0QzMuNzc5LDM1MC4wNzEsMCwzNTUuNzQsMCwzNjIuMDI1djIwLjc1OWMwLDUuNzMyLDQuNjQ3LDEwLjM3OSwxMC4zNzksMTAuMzc5aDE1NS42ODZjNS43MzIsMCwxMC4zNzktNC42NDcsMTAuMzc5LTEwLjM3OXYtMjAuNzU5QzE3Ni40NDUsMzU1Ljc0LDE3Mi42NjYsMzUwLjA3MSwxNjYuODY0LDM0Ny42NTN6Ii8+PHBhdGggc3R5bGU9ImZpbGw6I0QyOUI2RTsiIGQ9Ik0xMTQuMTcsMzA2LjIzN0g2Mi4yNzV2MTIuNTQxYzAsNC4xOTEtMi41Miw3Ljk3LTYuMzg3LDkuNTgxbC03LjIzMiwzLjAxNEMxMDQuMDAxLDM0NS40ODUsMTE0LjE3LDMwNi4yMzcsMTE0LjE3LDMwNi4yMzd6Ii8+PGc+PHBhdGggc3R5bGU9ImZpbGw6I0U0RUFGNjsiIGQ9Ik0yNS4yOTQsMzY3LjcyOUw0LjQ2NCwzNTEuMTZDMS42NzMsMzU0LjAxNCwwLDM1Ny44NzQsMCwzNjIuMDI1djIwLjc1OWMwLDUuNzMyLDQuNjQ3LDEwLjM3OSwxMC4zNzksMTAuMzc5aDIwLjc1OXYtMTMuMjc2QzMxLjEzNywzNzUuMTU3LDI4Ljk4OCwzNzAuNjg0LDI1LjI5NCwzNjcuNzI5eiIvPjxwYXRoIHN0eWxlPSJmaWxsOiNFNEVBRjY7IiBkPSJNMTc2LjQ0NSwzODIuNzgzdi0yMC43NTljMC00LjExOS0xLjY0NS03Ljk1Mi00LjM5Ny0xMC44bC0yMC44OTgsMTYuNTA0Yy0zLjY5MywyLjk1NC01Ljg0Myw3LjQyNy01Ljg0MywxMi4xNTd2MTMuMjc1aDIwLjc1OUMxNzEuNzk4LDM5My4xNjIsMTc2LjQ0NSwzODguNTE1LDE3Ni40NDUsMzgyLjc4M3oiLz48cGF0aCBzdHlsZT0iZmlsbDojRTRFQUY2OyIgZD0iTTg5LjAwMSwzODIuNzgzaC0xLjU1N2MtMi40MzYsMC00LjQxMS0xLjk3NS00LjQxMS00LjQxMXYtMzQuNzQ1aDEwLjM3OXYzNC43NDRDOTMuNDEyLDM4MC44MDgsOTEuNDM3LDM4Mi43ODMsODkuMDAxLDM4Mi43ODN6Ii8+PHBhdGggc3R5bGU9ImZpbGw6I0U0RUFGNjsiIGQ9Ik04OC4yMjIsMzQzLjYxNkw1OC41MiwzMjEuMzRjLTEuOTA3LTEuNDMtNC42MjMtMC45NjUtNS45NDUsMS4wMThsLTUuODY5LDguODAzbDE4LjIxLDI1LjQ5NGMxLjM4NCwxLjkzOCw0LjExMiwyLjMxNyw1Ljk3MiwwLjgyOUw4OC4yMjIsMzQzLjYxNnoiLz48cGF0aCBzdHlsZT0iZmlsbDojRTRFQUY2OyIgZD0iTTg4LjIyMiwzNDMuNjE2bDI5LjcwMi0yMi4yNzZjMS45MDctMS40Myw0LjYyMy0wLjk2NSw1Ljk0NSwxLjAxOGw1Ljg2OSw4LjgwM2wtMTguMjEsMjUuNDk0Yy0xLjM4NCwxLjkzOC00LjExMiwyLjMxNy01Ljk3MiwwLjgyOUw4OC4yMjIsMzQzLjYxNnoiLz48L2c+PHBhdGggc3R5bGU9ImZpbGw6I0YwQzA4NzsiIGQ9Ik0xMTQuMTcsMjA2LjMzOUM4OC4yMjIsMjMyLjI4NiwzNy4zLDIxMS41MjcsMzcuMywyNTMuMDQzbDMuMjIyLDMwLjU4MWMwLjYyMiw2Ljg0MSw0LjU4OCwxMi45MzEsMTAuNTkxLDE2LjI2N2wyOS41NDgsMTYuNDE1YzQuNzAyLDIuNjEyLDEwLjQxOSwyLjYxMiwxNS4xMjEsMGwyOS41NDgtMTYuNDE1YzYuMDA1LTMuMzM2LDkuOTctOS40MjcsMTAuNTkxLTE2LjI2N2wzLjcyLTM2LjMyYzAuMjY1LTIuNTgyLDAuMzIxLTUuMTc5LDAuMjM0LTcuNzY5QzEzNy45MzUsMjI2LjMyNSwxMTkuMzU5LDIxNi43MTcsMTE0LjE3LDIwNi4zMzl6Ii8+PHBhdGggc3R5bGU9ImZpbGw6I0U2QUY3ODsiIGQ9Ik0zNy4zLDI1My4wNDNsMy4yMjIsMzAuNTgxYzAuNjIyLDYuODQxLDQuNTg4LDEyLjkzMSwxMC41OTEsMTYuMjY3bDI5LjU0OCwxNi40MTVjMy4yNjMsMS44MTMsNy4wMDUsMi4yODIsMTAuNTY3LDEuNTc5bDAsMGMwLDAtMTQuODk3LTQuNTE1LTIzLjc2NC0yOC41MTZjLTIuNzE2LTcuMzUyLTYuNDg3LTQ2LjI3NC0zLjI0My01MS4wMzFjNi40MTItOS40MDIsNDIuMTY1LTEwLjM3OSw1Mi43MjktMjcuODIyYy0wLjUyMi0wLjY0Ny0xLjAwNC0xLjI5Ni0xLjQ0NC0xLjk0NWMtMC4wMzUtMC4wNTItMC4wNzgtMC4xMDUtMC4xMTQtMC4xNThjLTAuNDU5LTAuNjktMC44NzUtMS4zODItMS4yMjMtMi4wNzdDODguMjIyLDIzMi4yODYsMzcuMywyMTEuNTI3LDM3LjMsMjUzLjA0M3oiLz48cGF0aCBzdHlsZT0iZmlsbDojRTRFQUY2OyIgZD0iTTEyNi4xMDYsMzgyLjc4M2gtMTMuNDkzYy00Ljg3MiwwLTguODIyLDMuOTUtOC44MjIsOC44MjJ2MS41NTdoMzEuMTM3di0xLjU1N0MxMzQuOTI4LDM4Ni43MzIsMTMwLjk3OCwzODIuNzgzLDEyNi4xMDYsMzgyLjc4M3oiLz48cG9seWdvbiBzdHlsZT0iZmlsbDojRTZBRjc4OyIgcG9pbnRzPSI0NDIuNzgsMzIzLjUyNCA0NDIuNzgsMjk1LjAyMiAzNjkuNDg3LDI5NS4wMjIgMzY5LjQ4NywzMjMuNTI0IDQwNi4xMzMsMzU2LjA5OSAiLz48cGF0aCBzdHlsZT0iZmlsbDojRUZGMkZBOyIgZD0iTTQ5OS40NDMsMzM2Ljg1N2wtNTAuMzg1LTExLjg1NWMtMC43NjEtMC4xNzktMS40NjEtMC40ODMtMi4xMTUtMC44NTFsLTQwLjgxLDIzLjgwNmwtMzkuMjE4LTI0Ljk1N2MtMS4wMTYsMC45NTYtMi4yNzUsMS42NjYtMy43MDYsMi4wMDNsLTUwLjM4NSwxMS44NTVjLTcuMzU4LDEuNzMxLTEyLjU1Nyw4LjI5Ni0xMi41NTcsMTUuODU1djMxLjg5YzAsNC40OTcsMy42NDYsOC4xNDQsOC4xNDQsOC4xNDRoMTk1LjQ0N2M0LjQ5NywwLDguMTQ0LTMuNjQ2LDguMTQ0LTguMTQ0di0zMS44OUM1MTIsMzQ1LjE1Myw1MDYuOCwzMzguNTg4LDQ5OS40NDMsMzM2Ljg1N3oiLz48cGF0aCBzdHlsZT0iZmlsbDojRDI5QjZFOyIgZD0iTTM2OS40ODcsMjk1LjAyMnYzMC4wNzNjNDYuMTY1LDE2LjYzMiw3My4yOTMtMjQuOTk1LDczLjI5My0yNC45OTV2LTUuMDc4TDM2OS40ODcsMjk1LjAyMkwzNjkuNDg3LDI5NS4wMjJ6Ii8+PHBhdGggc3R5bGU9ImZpbGw6I0YwQzA4NzsiIGQ9Ik0zNTMuMiwyMDEuMzdsMy41NDksNzguMDYzYzAuMzMyLDcuMjkzLDMuOTA3LDE0LjA1NSw5Ljc0NywxOC40MzVsMTYuODM2LDEyLjYyN2M0LjIyOSwzLjE3Miw5LjM3Myw0Ljg4NiwxNC42NTgsNC44ODZoMTYuMjg3YzUuMjg2LDAsMTAuNDMtMS43MTUsMTQuNjU4LTQuODg2bDE2LjgzNi0xMi42MjdjNS44NC00LjM4LDkuNDE2LTExLjE0Myw5Ljc0Ny0xOC40MzVsMy41NDktNzguMDYzTDM1My4yLDIwMS4zN0wzNTMuMiwyMDEuMzd6Ii8+PHBhdGggc3R5bGU9ImZpbGw6I0U2QUY3ODsiIGQ9Ik0zOTMuOTE4LDIxNy42NTdjMTYuMjg3LDAsNDAuNzE4LTQuMDcxLDQ2LjUxMy0xNi4yODdIMzUzLjJsMy41NDgsNzguMDYzYzAuMzMyLDcuMjkzLDMuOTA3LDE0LjA1NSw5Ljc0NywxOC40MzVsMTYuODM2LDEyLjYyN2M0LjIyOSwzLjE3Miw5LjM3Myw0Ljg4NiwxNC42NTgsNC44ODZoOC4xNDRjLTguMTQ0LDAtMjQuNDMxLTE2LjI4Ny0yNC40MzEtMzYuNjQ3YzAtOS45NTcsMC0zNi42NDYsMC00OC44NjJDMzgxLjcwMiwyMjUuODAxLDM4NS43NzQsMjE3LjY1NywzOTMuOTE4LDIxNy42NTd6Ii8+PHBhdGggc3R5bGU9ImZpbGw6I0U0RUFGNjsiIGQ9Ik00ODIuMTA3LDM2Ni4zMTlsMjYuMzg1LTIzLjcwMmMyLjIxMSwyLjgsMy41MDgsNi4zMjIsMy41MDgsMTAuMDk0djMxLjg5YzAsNC40OTctMy42NDYsOC4xNDQtOC4xNDQsOC4xNDRoLTI4LjUwMnYtMTEuMjhDNDc1LjM1NCwzNzUuNjg3LDQ3Ny44MDgsMzcwLjE4MSw0ODIuMTA3LDM2Ni4zMTl6Ii8+PHBvbHlnb24gc3R5bGU9ImZpbGw6IzVCNUQ2RTsiIHBvaW50cz0iNDE2LjMxMywzOTIuNzQ1IDM5NS45NTMsMzkyLjc0NSAzOTguNDk5LDM1Ni4wOTkgNDEzLjc2OCwzNTYuMDk5ICIvPjxwYXRoIHN0eWxlPSJmaWxsOiM1MTUyNjI7IiBkPSJNNDE2LjMxMywzNDcuOTU1aC0yMC4zNnYyLjM1MmMwLDUuNDQ4LDQuNDE2LDkuODYzLDkuODYzLDkuODYzaDAuNjMyYzUuNDQ4LDAsOS44NjMtNC40MTYsOS44NjMtOS44NjN2LTIuMzUySDQxNi4zMTN6Ii8+PGc+PHBhdGggc3R5bGU9ImZpbGw6I0U0RUFGNjsiIGQ9Ik0zNjguOTE0LDMxNC40NmwzNy4yMTgsMzMuNDk2YzAsMC0xMC40MjMsNS4yNjEtMjMuMjg2LDE1Ljg2N2MtMi42NTMsMi4xODgtNi42NjcsMS4zMDEtOC4wOS0xLjgyOWwtMTcuNDg2LTM4LjQ2OGw1LjUzMi04LjI5N0MzNjQuMTgsMzEzLjE2MiwzNjcuMDY5LDMxMi43OTksMzY4LjkxNCwzMTQuNDZ6Ii8+PHBhdGggc3R5bGU9ImZpbGw6I0U0RUFGNjsiIGQ9Ik00NDMuMzUxLDMxNC40NmwtMzcuMjE4LDMzLjQ5NmMwLDAsMTAuNDIzLDUuMjYxLDIzLjI4NiwxNS44NjdjMi42NTMsMi4xODgsNi42NjcsMS4zMDEsOC4wOS0xLjgyOWwxNy40ODYtMzguNDY4bC01LjUzMi04LjI5N0M0NDguMDg2LDMxMy4xNjIsNDQ1LjE5NywzMTIuNzk5LDQ0My4zNTEsMzE0LjQ2eiIvPjwvZz48cGF0aCBzdHlsZT0iZmlsbDojNzg1NTUwOyIgZD0iTTQzMC44MTgsMTc0LjM5NGwzLjgxOCwyNi45NzZjMTcuNDI1LDMuNDg1LDE5LjkzNiwzMC44MywyMC4yOTgsMzguNjQxYzAuMDY2LDEuNDE3LDAuNTE4LDIuNzg3LDEuMjg3LDMuOTc5bDYuNjQ5LDEwLjMxM2MwLDAtMi4yNzYtMTcuMDUxLDguMTQ0LTI4LjUwMkM0NzEuMDEzLDIyNS44MDEsNDc0LjU5LDE1OC4xMDcsNDMwLjgxOCwxNzQuMzk0eiIvPjxwYXRoIHN0eWxlPSJmaWxsOiNGMEMwODc7IiBkPSJNNDY2Ljk1NCwyNTIuNTYybC00LjU5OSwxOC4zOTNjLTAuNTQ1LDIuMTc5LTIuNTAzLDMuNzA4LTQuNzQ5LDMuNzA4bDAsMGMtMi40NjksMC00LjU1MS0xLjgzOC00Ljg1OC00LjI4OGwtMi4zNTEtMTguODEyYy0wLjYyOS01LjAzMSwzLjI5NC05LjQ3NCw4LjM2My05LjQ3NGgwLjAxN0M0NjQuMjYxLDI0Mi4wODgsNDY4LjI4NCwyNDcuMjQyLDQ2Ni45NTQsMjUyLjU2MnoiLz48cGF0aCBzdHlsZT0iZmlsbDojNjk0QjRCOyIgZD0iTTM1Ny4xMDEsMTY3LjA5OWw2LjAyMyw0LjExNGMtMjcuMjMsMjAuMTA1LTIyLjE0MSw1NC41ODgtMjIuMTQxLDU0LjU4OGM4LjE0NCw4LjE0NCw4LjE0NCwyOC41MDIsOC4xNDQsMjguNTAybDguMTQ0LTguMTQ0YzAsMC0zLjE0My0yMy41MjUsMTIuMjE2LTMyLjU3NGMxNC4yNTEtOC4zOTgsMjYuNzIxLTQuMDcxLDM5LjQ0NS00LjA3MWMzNC4xMDEsMCw0My42NDUtMTIuODUyLDQxLjk5MS0yOC41MDJjLTAuODU2LTguMDk5LTEzLjIzOC0yNC45NjctNDQuNzg5LTI0LjQzMUMzOTMuMzk5LDE1Ni43OTcsMzY5LjQ4NywxNjAuNjUzLDM1Ny4xMDEsMTY3LjA5OXoiLz48cGF0aCBzdHlsZT0iZmlsbDojNUE0MTQ2OyIgZD0iTTM1NC43MjYsMjA1Ljk1MWMwLDAtNC4zMjYtMTcuMDUxLDguMzk4LTM0LjczOGMtMjcuMjMsMjAuMTA1LTIyLjE0MSw1NC41ODgtMjIuMTQxLDU0LjU4OGM4LjE0NCw4LjE0NCw4LjE0NCwyOC41MDIsOC4xNDQsMjguNTAybDguMTQ0LTguMTQ0YzAsMC0zLjE0My0yMy41MjUsMTIuMjE1LTMyLjU3NGMxNC4yNTEtOC4zOTgsMjYuNzIxLTQuMDcxLDM5LjQ0NS00LjA3MWM1LjU0NiwwLDEwLjM3OS0wLjM3MiwxNC42ODQtMS4wMDhDMzk3LjM1MywyMDkuMzg3LDM4MS44NzIsMTg5LjQ5NSwzNTQuNzI2LDIwNS45NTF6Ii8+PHBhdGggc3R5bGU9ImZpbGw6I0U2QUY3ODsiIGQ9Ik0zNDUuMzExLDI1Mi41NjJsNC41OTksMTguMzkzYzAuNTQ1LDIuMTc5LDIuNTAzLDMuNzA4LDQuNzUsMy43MDhsMCwwYzIuNDY5LDAsNC41NTEtMS44MzgsNC44NTgtNC4yODhsMi4zNTEtMTguODEyYzAuNjI5LTUuMDMxLTMuMjk0LTkuNDc0LTguMzY0LTkuNDc0aC0wLjAxN0MzNDguMDA2LDI0Mi4wODgsMzQzLjk4MSwyNDcuMjQyLDM0NS4zMTEsMjUyLjU2MnoiLz48cGF0aCBzdHlsZT0iZmlsbDojRTRFQUY2OyIgZD0iTTQ1NC45OTUsMzg0LjYwMmgtMjAuMzZjLTQuNDk3LDAtOC4xNDQsMy42NDYtOC4xNDQsOC4xNDRsMCwwaDM2LjY0NmwwLDBDNDYzLjEzOCwzODguMjQ4LDQ1OS40OTIsMzg0LjYwMiw0NTQuOTk1LDM4NC42MDJ6Ii8+PHBhdGggc3R5bGU9ImZpbGw6IzY5NEI0QjsiIGQ9Ik0yNTYuMTU1LDU3LjVjLTEzOS42NDIsMC0xMjQuMTU4LDE2OS44MTQtMTIzLjUyLDI5Mi44NzFjMC4wMjQsNC42NDcsMi40NTgsOC44MzgsNi40MzksMTEuMjM3YzE1LjQ3NCw5LjMyOCw1Ny45NywzMS4xMzcsMTE3LjA4MSwzMS4xMzdzMTAxLjYwNy0yMS44MDksMTE3LjA4MS0zMS4xMzdjMy45OC0yLjQsNi40MTUtNi41OSw2LjQzOS0xMS4yMzdDMzgwLjMxMywyMjcuMzEzLDM5NS43OTcsNTcuNSwyNTYuMTU1LDU3LjV6Ii8+PHBhdGggc3R5bGU9ImZpbGw6IzVBNDE0NjsiIGQ9Ik0yNDcuMTU3LDU3Ljg0OUMxMTcuMjg2LDY1LjEzLDEzMi4wMTEsMjMwLjA1MSwxMzIuNjM1LDM1MC4zN2MwLjAyNCw0LjY0NywyLjQ1OCw4LjgzOCw2LjQzOSwxMS4yMzdjMTUuNDc0LDkuMzI4LDU3Ljk3LDMxLjEzNywxMTcuMDgxLDMxLjEzN2wzNS4yODktMjQ3LjAyMyIvPjxwYXRoIHN0eWxlPSJmaWxsOiM2OTRCNEI7IiBkPSJNMjU2LjE1NSw1Ny41Yy03OS40LDAtODQuOTE0LDk1Ljk0Mi04NC45MTQsOTUuOTQyYzQ1Ljc2NS03LjcxOSw3My44ODYsMTEuNTc5LDEyMC4yMDMtNy43MTlsLTM1LjI4OSwyNDcuMDIzYzU5LjExMSwwLDEwMS42MDctMjEuODA5LDExNy4wODEtMzEuMTM3YzMuOTgtMi40LDYuNDE1LTYuNTksNi40MzktMTEuMjM3QzM4MC4zMTMsMjI3LjMxMywzOTUuNzk3LDU3LjUsMjU2LjE1NSw1Ny41eiIvPjxwYXRoIHN0eWxlPSJmaWxsOiM3ODU1NTA7IiBkPSJNMzc5LjY3NSwzNTAuMzcyYzAuNTQ4LTEwNS43NDIsMTEuOTYyLTI0NS45MDYtNzMuNDEtMjgzLjI4NmMtMS40ODksNy43MzYtNC4xODUsMjkuMzMxLDIuODIzLDQzLjM0OGMxNy42NDUsMzUuMjg5LDM1LjI4OSwzNS4yODksNTIuOTMzLDYxLjc1NmMxNy42NDQsOTcuMDQ1LTMzLjcxNCwyMDAuMDQ3LTUwLjc3MSwyMTQuMDY4bDAsMGMzMC4yMjItNy4xODgsNTEuODg3LTE4LjU2Miw2MS45ODUtMjQuNjQ5QzM3Ny4yMTYsMzU5LjIwOSwzNzkuNjUxLDM1NS4wMTksMzc5LjY3NSwzNTAuMzcyeiIvPjxwYXRoIHN0eWxlPSJmaWxsOiNFNkFGNzg7IiBkPSJNMzg5Ljg0NiwzNzcuMTM3bC03OC43MjItMzIuODAxYy02LjU3NS0yLjczOS0xMC44NTgtOS4xNjQtMTAuODU4LTE2LjI4OHYtMjEuMzJoLTg4LjIyMnYyMS4zMmMwLDcuMTIzLTQuMjgzLDEzLjU0OC0xMC44NTgsMTYuMjg4bC03OC43MjIsMzIuODAxYy05Ljg2Miw0LjEwOS0xNi4yODcsMTMuNzQ2LTE2LjI4NywyNC40MzF2MzUuMjg5YzAsOS43NDQsNy45LDE3LjY0NSwxNy42NDQsMTcuNjQ1aDI2NC42NjdjOS43NDQsMCwxNy42NDQtNy45LDE3LjY0NC0xNy42NDV2LTM1LjI4OUM0MDYuMTMzLDM5MC44ODIsMzk5LjcwOCwzODEuMjQ2LDM4OS44NDYsMzc3LjEzN3oiLz48cGF0aCBzdHlsZT0iZmlsbDojQjRFMUZBOyIgZD0iTTM4OS44NDYsMzc3LjEzN2wtNzYuMTE0LTMxLjcxNGwtNTcuNTc3LDI0Ljg0OWwtNTcuNTc3LTI0Ljg0OWwtNzYuMTE0LDMxLjcxNGMtOS44NjIsNC4xMDktMTYuMjg3LDEzLjc0Ni0xNi4yODcsMjQuNDMxdjM1LjI4OWMwLDkuNzQ0LDcuOSwxNy42NDUsMTcuNjQ0LDE3LjY0NWgyNjQuNjY3YzkuNzQ0LDAsMTcuNjQ0LTcuOSwxNy42NDQtMTcuNjQ1di0zNS4yODlDNDA2LjEzMywzOTAuODgyLDM5OS43MDgsMzgxLjI0NiwzODkuODQ2LDM3Ny4xMzd6Ii8+PHBhdGggc3R5bGU9ImZpbGw6I0QyOUI2RTsiIGQ9Ik0zMDAuMjY2LDMwNi43MjhoLTg4LjIyMnYyMS4zMmMwLDcuMTIzLTQuMjgzLDEzLjU0OC0xMC44NTgsMTYuMjg4bC0xMi4yOTUsNS4xMjRDMjgyLjk3OCwzNzMuNDQ5LDMwMC4yNjYsMzA2LjcyOCwzMDAuMjY2LDMwNi43Mjh6Ii8+PGc+PHBhdGggc3R5bGU9ImZpbGw6I0EwRDJGMDsiIGQ9Ik0xNDkuMTc4LDQxMS4yNjVsLTM1LjQxMS0yOC4xNjdjLTQuNzQ0LDQuODUyLTcuNTg4LDExLjQxMy03LjU4OCwxOC40Njl2MzUuMjg5YzAsOS43NDQsNy45LDE3LjY0NCwxNy42NDQsMTcuNjQ0aDM1LjI4OXYtMjIuNTY4QzE1OS4xMSw0MjMuODkyLDE1NS40NTYsNDE2LjI4OCwxNDkuMTc4LDQxMS4yNjV6Ii8+PHBhdGggc3R5bGU9ImZpbGw6I0EwRDJGMDsiIGQ9Ik00MDYuMTMzLDQzNi44NTZ2LTM1LjI4OWMwLTcuMDAyLTIuNzk3LTEzLjUxOC03LjQ3NS0xOC4zNTlsLTM1LjUyNiwyOC4wNTdjLTYuMjc4LDUuMDIzLTkuOTMzLDEyLjYyNy05LjkzMywyMC42NjdWNDU0LjVoMzUuMjg5QzM5OC4yMzMsNDU0LjUwMSw0MDYuMTMzLDQ0Ni42MDEsNDA2LjEzMyw0MzYuODU2eiIvPjxwYXRoIHN0eWxlPSJmaWxsOiNBMEQyRjA7IiBkPSJNMjU2LjE1NSw0MzYuODU2TDI1Ni4xNTUsNDM2Ljg1NmMtNC44NzIsMC04LjgyMi0zLjk1LTguODIyLTguODIydi01Ny43NDJoMTcuNjQ0djU3Ljc0M0MyNjQuOTc3LDQzMi45MDcsMjYxLjAyNyw0MzYuODU2LDI1Ni4xNTUsNDM2Ljg1NnoiLz48cGF0aCBzdHlsZT0iZmlsbDojQTBEMkYwOyIgZD0iTTI1Ni4xNTUsMzcwLjI3MmwtNTAuNDk0LTM3Ljg3MWMtMy4yNDEtMi40MzEtNy44Ni0xLjY0LTEwLjEwNywxLjczMWwtOS45NzcsMTQuOTY2bDMwLjk1Nyw0My4zMzljMi4zNTMsMy4yOTUsNi45OTEsMy45MzgsMTAuMTUyLDEuNDA5TDI1Ni4xNTUsMzcwLjI3MnoiLz48cGF0aCBzdHlsZT0iZmlsbDojQTBEMkYwOyIgZD0iTTI1Ni4xNTUsMzcwLjI3Mmw1MC40OTQtMzcuODcxYzMuMjQxLTIuNDMxLDcuODYtMS42NCwxMC4xMDcsMS43MzFsOS45NzcsMTQuOTY2bC0zMC45NTcsNDMuMzM5Yy0yLjM1MywzLjI5NS02Ljk5MSwzLjkzOC0xMC4xNTIsMS40MDlMMjU2LjE1NSwzNzAuMjcyeiIvPjwvZz48cGF0aCBzdHlsZT0iZmlsbDojRjBDMDg3OyIgZD0iTTMwMC4yNjYsMTM2LjljLTQ0LjExMSw0NC4xMTEtMTMwLjY3OSw4LjgyMi0xMzAuNjc5LDc5LjRsNS40NzksNTEuOTg5YzEuMDU2LDExLjYyOSw3Ljc5OSwyMS45ODMsMTguMDA2LDI3LjY1M2w1MC4yMzEsMjcuOTA2YzcuOTk0LDQuNDQxLDE3LjcxMyw0LjQ0MSwyNS43MDcsMGw1MC4yMzEtMjcuOTA2YzEwLjIwNy01LjY3MiwxNi45NDktMTYuMDI1LDE4LjAwNi0yNy42NTNsNi4zMjQtNjEuNzQ1YzAuNDUtNC4zODgsMC41NDYtOC44MDMsMC4zOTctMTMuMjA4QzM0MC42NjUsMTcwLjg3OSwzMDkuMDg4LDE1NC41NDQsMzAwLjI2NiwxMzYuOXoiLz48cGF0aCBzdHlsZT0iZmlsbDojRTZBRjc4OyIgZD0iTTE2OS41ODcsMjE2LjNsNS40NzksNTEuOTg5YzEuMDU2LDExLjYyOSw3Ljc5OSwyMS45ODMsMTguMDA2LDI3LjY1M2w1MC4yMzEsMjcuOTA2YzUuNTQ3LDMuMDgxLDExLjkwOCwzLjg4LDE3Ljk2MywyLjY4NWwwLDBjMCwwLTI1LjMyNi03LjY3NC00MC4zOTgtNDguNDc3Yy00LjYxNy0xMi41LTExLjAyOC03OC42NjUtNS41MTQtODYuNzUyYzEwLjg5OS0xNS45ODUsNzEuNjgxLTE3LjY0NSw4OS42NDEtNDcuMjk4Yy0wLjg4OC0xLjEwMS0xLjcwNy0yLjIwMy0yLjQ1NS0zLjMwOGMtMC4wNjEtMC4wODktMC4xMzMtMC4xNzktMC4xOTMtMC4yNjhjLTAuNzgtMS4xNzItMS40ODgtMi4zNDgtMi4wNzktMy41M0MyNTYuMTU1LDE4MS4wMTEsMTY5LjU4NywxNDUuNzIyLDE2OS41ODcsMjE2LjN6Ii8+PHBhdGggc3R5bGU9ImZpbGw6I0EwRDJGMDsiIGQ9Ik0zMjYuNzMzLDQzNi44NTZoLTM1LjI4OWMtNC44NzIsMC04LjgyMiwzLjk1LTguODIyLDguODIydjguODIyaDUyLjkzM3YtOC44MjJDMzM1LjU1NSw0NDAuODA2LDMzMS42MDUsNDM2Ljg1NiwzMjYuNzMzLDQzNi44NTZ6Ii8+PGc+PC9nPjxnPjwvZz48Zz48L2c+PGc+PC9nPjxnPjwvZz48Zz48L2c+PGc+PC9nPjxnPjwvZz48Zz48L2c+PGc+PC9nPjxnPjwvZz48Zz48L2c+PGc+PC9nPjxnPjwvZz48Zz48L2c+PC9zdmc+'/><p>" . __('Avatar', 'cwginstocknotifier') . "</p>";
            $newcolumns['email'] = __('Email', 'cwginstocknotifier');
            $newcolumns['status'] = __('Status', 'cwginstocknotifier');
            $newcolumns['product'] = __('Product', 'cwginstocknotifier');
            $newcolumns['userisexists'] = __('Registered User', 'cwginstocknotifier');
            $newcolumns['date'] = __('Subscribed on', 'cwginstocknotifier');
            return apply_filters('cwginstocknotifier_columns', $newcolumns);
        }

        public function manage_columns($column, $post_id) {
            return $this->_manage_columns($column, $post_id);
        }

        protected function _manage_columns($columns, $post_id) {

            $email = get_post_meta($post_id, 'cwginstock_subscriber_email', true);

            $obj = new CWG_Instock_API(0, 0, $email);

            switch ($columns) {
                case 'email':
                    echo $email;
                    break;
                case 'cwgimage':
                    echo get_avatar($email, '32');
                    break;
                case 'status':
                    $this->display_status($post_id);
                    break;
                case 'product':
                    $obj = new CWG_Instock_API();
                    $product_name = $obj->display_product_name($post_id);
                    $product_id = get_post_meta($post_id, 'cwginstock_product_id', true);
                    $variation_id = get_post_meta($post_id, 'cwginstock_variation_id', true);
                    $pid = get_post_meta($post_id, 'cwginstock_pid', true);
                    $intvariation = intval($variation_id);

                    $image = '';
                    if ($intvariation > 0) {
                        $var_obj = wc_get_product($intvariation);
                        // $image = $var_obj->get_image(array(40, 40));
                        $pid = $product_id;
                    } else {
                        $product_obj = wc_get_product($product_id);
                    }
                    if ($product_id) {
                        $permalink = esc_url_raw(admin_url("post.php?post=$product_id&action=edit"));
                        $permalink = " <a href='$permalink'>#{$pid } {$product_name}</a>";
                        echo $permalink;
                    }
                    break;
                case 'userisexists':
                    $exists = $obj->is_user_exists();
                    if ($exists) {
                        echo "<mark class='cwgmark_exists'>" . __('Yes', 'cwginstocknotifier') . "</mark>";
                    } else {
                        echo "<mark class='cwgmark_notexists'>" . __('No', 'cwginstocknotifier') . "</mark>";
                    }
                    break;
                case 'date':
                    echo date('y-m-d h:i:s');
                    break;
            }
        }

        public function display_status($id) {
            $get_post_status = get_post_status($id);
            switch ($get_post_status) {
                case 'cwg_subscribed':
                    $subscribed = __('Subscribed', 'cwginstocknotifier');
                    echo "<mark class='cwgmark cwgsubscribed'>$subscribed</mark>";
                    break;
                case 'cwg_mailsent':
                    $mailsent = __('Mail Sent', 'cwginstocknotifier');
                    echo "<mark class='cwgmark cwgmailsent'>$mailsent</mark>";
                    break;
                case 'cwg_unsubscribed':
                    $unsubscribed = __('Unsubscribed', 'cwginstocknotifier');
                    echo "<mark class='cwgmark cwgunsubscribed'>$unsubscribed</mark>";
                    break;
                case 'cwg_converted':
                    $converted = __('Purchased', 'cwginstocknotifier');
                    echo "<mark class='cwgmark cwgpurchased'>$converted</mark>";
                    break;
                case 'cwg_mailnotsent':
                    $notsent = __('Failed', 'cwginstocknotifier');
                    echo "<mark class='cwgmark cwgfailed'>$notsent</mark>";
                    break;
                default:
                    $otherstatus = $get_post_status;
                    echo "<mark class='cwgmark'>$otherstatus</mark>";
                    break;
            }
        }

        public function sortable_columns($columns) {
            $columns['email'] = 'title';
            $columns['product'] = 'product';
            return $columns;
        }

        public function manage_row_actions($actions, $post) {
            $post_status = get_post_status($post->ID);
            $newactions = array();
            $check_gdpr_eraser = get_post_meta($post->ID, 'cwginstock_subscriber_personal_data_deleted', true);
            $post_id = intval($post->ID);
            if ($post->post_type == 'cwginstocknotifier' && $post_status != 'trash' && $check_gdpr_eraser != 'yes') {


                $newactions['id'] = "<span class='id' style='color:#a0a0a0;'>" . __('ID:', 'cwginstocknotifier') . " $post_id" . "</span>";
                /*
                 * ****************************************************
                 */
                $edit_list = admin_url('edit.php?post_type=cwginstocknotifier');
                $action = 'cwginstock-sendmail';
                $nonce = wp_create_nonce('cwginstock_sendmail-' . $post_id);
                $query_arg = esc_url_raw(add_query_arg(array('action' => $action, 'post_id' => $post_id, 'nonce' => $nonce), $edit_list));
                $caption = __('Send Instock Mail', 'cwginstocknotifier');
                $sendmail = "<a href='$query_arg'>$caption</a>";
                /*
                 * ******************************************************
                 */

                $newactions['sendmail'] = $sendmail;

                $newactions['trash'] = $actions['trash'];
                return apply_filters('cwginstocknotifier_row_actions', $newactions);
            } elseif ($post->post_type == 'cwginstocknotifier' && $post_status != 'trash') {
                $newactions['trash'] = $actions['trash'];
                $actions = $newactions;
            } elseif ($post->post_type == 'product') {
                $edit_list = admin_url('edit.php?post_type=cwginstocknotifier&cwg_filter_by_products[0]=' . $post_id . '&filter_action=Filter');
                $query_arg = esc_url_raw($edit_list);
                $api = new CWG_Instock_API();
                $subscribers_count = $api->get_subscribers_count($post_id, 'any');
                $actions['cwg_subscribers_count'] = "<a href='$query_arg'>" . __('View Subscribers', 'cwginstocknotifier') . '(' . $subscribers_count . ')' . "</a>";
            }

            return $actions;
        }

        public function list_table_primary_column($default, $screen) {
            if ('edit-cwginstocknotifier' === $screen) {
                $default = 'email';
            }
            return $default;
        }

        public function send_manual_mail() {
            $nonce = $_REQUEST['nonce'];
            $post_id = intval($_REQUEST['post_id']);
            if (wp_verify_nonce($nonce, 'cwginstock_sendmail-' . $post_id)) {
                //send mail
                $get_email = get_post_meta($post_id, 'cwginstock_subscriber_email', true);
                $option = get_option('cwginstocksettings');
                $mailer = new CWG_Trigger_Instock_Mail($post_id);
                $pid = get_post_meta($post_id, 'cwginstock_pid', true);
                $api = new CWG_Instock_API();
                $product_exists = wc_get_product($pid);
                if ($product_exists) {
                    $send_mail = $mailer->send();
                    if ($send_mail) {
                        $message = __("Instock mail sent to {email_id} successfully", 'cwginstocknotifier');
                        $replace = str_replace('{email_id}', $get_email, $message);
                        $mail_status = $api->mail_sent_status($post_id);
                        $logger = new CWG_Instock_Logger('success', "Manual Instock Mail sent to #$get_email - #$post_id");
                        $logger->record_log();
                        add_persistent_notice(array(
                            'type' => 'success',
                            'message' => $replace,
                        ));
                    } else {
                        $error_msg = __('Unable to send Instock mail to this {email_id}', 'cwginstocknotifier');
                        $error_replace = str_replace('{email_id}', $get_email, $error_msg);
                        $mail_status = $api->mail_not_sent_status($post_id);
                        $logger = new CWG_Instock_Logger('error', "$error_replace" . " #$post_id");
                        $logger->record_log();
                        add_persistent_notice(array(
                            'type' => 'error',
                            'message' => $error_replace,
                        ));
                    }
                } else {
                    $error_msg = __('Unable to send Instock mail to this {email_id} as stock product does not exists/deleted !!!', 'cwginstocknotifier');
                    $error_replace = str_replace('{email_id}', $get_email, $error_msg);
                    $logger = new CWG_Instock_Logger('error', "$error_replace" . " #$post_id");
                    $logger->record_log();
                    add_persistent_notice(array(
                        'type' => 'error',
                        'message' => $error_replace,
                    ));
                }
            } else {
                add_persistent_notice(array(
                    'type' => 'error',
                    'message' => __("Security Check Failed, Please try later", 'cwginstocknotifier'),
                ));
            }
            wp_redirect($_SERVER['HTTP_REFERER']);
            exit();
        }

        public function custom_css() {
            ?>
            <style type="text/css">
                #cwgimage {
                    width:32px;
                    text-align: center;
                }

                table.wp-list-table .column-total_subscribers {
                    width:10%;
                }

                #cwgimage p {
                    display:none;
                }

                .column-cwgimage p {
                    display:none;
                }
            </style>
            <?php
        }

        public function remove_from_bulk_actions($actions) {
            unset($actions['edit']);
            $newactions = array();
            $list_of_actions = array('mark_status_sent' => __('Change status to Mail Sent', 'cwginstocknotifier'), 'mark_status_subscribed' => __('Change status to Subscribed', 'cwginstocknotifier'), 'mark_status_unsubscribed' => __('Change status to Unsubscribed', 'cwginstocknotifier'), 'send_mail' => __('Send Email', 'cwginstocknotifier'));
            foreach ($list_of_actions as $key => $each_action) {
                $newactions[$key] = $each_action;
            }
            $merge_actions = array_merge($newactions, $actions);
            return apply_filters('cwginstocknotifier_bulk_actions', $merge_actions);
        }

        public function handle_bulk_actions($redirect_to, $action, $post_ids) {
            do_action('cwginstocknotifier_handle_action_' . $action, $post_ids);
            return $redirect_to;
        }

        public function plugin_action_links($links) {
            $action_links = array(
                'settings' => '<a href="' . esc_url(get_admin_url(null, 'edit.php?post_type=cwginstocknotifier&page=cwg-instock-mailer')) . '">Settings</a>',
                'add-ons' => '<a href="' . esc_url(get_admin_url(null, 'edit.php?post_type=cwginstocknotifier&page=cwg-instock-extensions')) . '">Add-ons</a>',
                'support' => '<a href="https://codewoogeek.online" target="_blank">Custom Development and Support</a>',
            );
            return array_merge($action_links, $links);
        }

        /**
         * Add Filter Option based on subscribed product ids
         */
        public function filter_by_subscribed_products() {
            //$type = 'cwginstocknotifier';
            if (isset($_GET['post_type'])) {
                $type = $_GET['post_type'];
                if ('cwginstocknotifier' == $type) {
                    ?>
                    <select style="width:320px;" data-placeholder="<?php _e("Filter by products", 'cwginstocknotifier'); ?>" data-allow_clear="true" tabindex="-1" aria-hidden="true" name="cwg_filter_by_products[]" multiple="multiple" class="wc-product-search">
                        <?php
                        $current_v = isset($_GET['cwg_filter_by_products']) ? $_GET['cwg_filter_by_products'] : array();
                        if (is_array($current_v) && !empty($current_v)) {
                            foreach ($current_v as $each_id) {
                                $product = wc_get_product($each_id);
                                if ($product) {
                                    printf('<option value="%s"%s>%s</option>', $each_id, ' selected="selected"', wp_kses_post($product->get_formatted_name()));
                                }
                            }
                        }
                        ?>
                    </select>
                    <?php
                }
            }
        }

        // for parse query based on filter by product selection

        public function parse_query($query) {
            global $pagenow;
            if (!is_admin())
                return;

            $orderby = $query->get('orderby');
            if (isset($_GET['post_type'])) {
                $type = $_GET['post_type'];
                if ('cwginstocknotifier' == $type && is_admin() && $pagenow == 'edit.php' && isset($_GET['cwg_filter_by_products']) && !empty($_GET['cwg_filter_by_products']) && is_array($_GET['cwg_filter_by_products'])) {

                    $meta_query = array(
                        'relation' => 'OR',
                        array(
                            'key' => 'cwginstock_pid',
                            'value' => $_GET['cwg_filter_by_products'],
                            'compare' => 'IN',
                        ),
                        array(
                            'key' => 'cwginstock_product_id',
                            'value' => $_GET['cwg_filter_by_products'],
                            'compare' => 'IN',
                    ));
                    $query->query_vars['meta_query'] = $meta_query;
                }

                if ('cwginstocknotifier' == $type && is_admin() && $pagenow == 'edit.php' && 'product' == $orderby) {
                    // for orderby just order based on product id
                    $query->set('meta_key', 'cwginstock_pid');
                    $query->set('orderby', 'meta_value_num');
                }
            }
        }

        public function bulk_mark_status_sent($post_ids) {
            $count = count($post_ids);
            $stock_api = new CWG_Instock_API();
            if (is_array($post_ids) && !empty($post_ids)) {
                foreach ($post_ids as $each_id) {
                    $stock_api->mail_sent_status($each_id);
                    do_action('cwg_instock_bulk_status_action', $each_id, 'cwg_mailsent');
                    $logger = new CWG_Instock_Logger('success', "Manual changed status to Mail Sent - $each_id");
                    $logger->record_log();
                }

                add_persistent_notice(array(
                    'type' => 'success',
                    'message' => __("$count - Data(s) Manually marked status to Mail Sent", 'cwginstocknotifier'),
                ));
            }
        }

        public function bulk_mark_status_subscribed($post_ids) {
            $count = count($post_ids);
            $stock_api = new CWG_Instock_API();
            if (is_array($post_ids) && !empty($post_ids)) {
                foreach ($post_ids as $each_id) {
                    $stock_api->subscriber_subscribed($each_id);
                    do_action('cwg_instock_bulk_status_action', $each_id, 'cwg_subscribed');
                    $logger = new CWG_Instock_Logger('success', "Manual changed status to Subscribe - $each_id");
                    $logger->record_log();
                }

                add_persistent_notice(array(
                    'type' => 'success',
                    'message' => __("$count - Data(s) Manually marked status to Subscribe", 'cwginstocknotifier'),
                ));
            }
        }

        public function bulk_mark_status_unsubscribed($post_ids) {
            $count = count($post_ids);
            $stock_api = new CWG_Instock_API();
            if (is_array($post_ids) && !empty($post_ids)) {
                foreach ($post_ids as $each_id) {
                    $stock_api->subscriber_unsubscribed($each_id);
                    do_action('cwg_instock_bulk_status_action', $each_id, 'cwg_unsubscribed');
                    $logger = new CWG_Instock_Logger('success', "Manual changed status to Unsubscribe - $each_id");
                    $logger->record_log();
                }

                add_persistent_notice(array(
                    'type' => 'success',
                    'message' => __("$count - Data(s) Manually marked status to Unsubscribe", 'cwginstocknotifier'),
                ));
            }
        }

        public function bulk_send_manual_email($post_ids) {
            $sent = 0;
            $failed = 0;
            $not_exists = 0;
            $count = count($post_ids);
            $stock_api = new CWG_Instock_API();
            if (is_array($post_ids) && !empty($post_ids)) {
                $logger = new CWG_Instock_Logger('success', "Bulk Email process started for data #$count");
                $logger->record_log();
                foreach ($post_ids as $post_id) {
                    $get_email = get_post_meta($post_id, 'cwginstock_subscriber_email', true);
                    $option = get_option('cwginstocksettings');
                    $mailer = new CWG_Trigger_Instock_Mail($post_id);
                    $pid = get_post_meta($post_id, 'cwginstock_pid', true);
                    $api = new CWG_Instock_API();
                    $product_exists = wc_get_product($pid);
                    if ($product_exists) {
                        $send_mail = $mailer->send();
                        if ($send_mail) {
                            $message = __("Instock mail sent to {email_id} successfully", 'cwginstocknotifier');
                            $replace = str_replace('{email_id}', $get_email, $message);
                            $mail_status = $api->mail_sent_status($post_id);
                            $logger = new CWG_Instock_Logger('success', "Bulk mail sent to #$get_email - #$post_id");
                            $logger->record_log();
                            $sent++;
                        } else {
                            $error_msg = __('Unable to send Instock mail to this {email_id}', 'cwginstocknotifier');
                            $error_replace = str_replace('{email_id}', $get_email, $error_msg);
                            $mail_status = $api->mail_not_sent_status($post_id);
                            $logger = new CWG_Instock_Logger('error', "$error_replace" . " #$post_id");
                            $logger->record_log();
                            $failed++;
                        }
                    } else {
                        $error_msg = __('Unable to send Instock mail to this {email_id} as stock product does not exists/deleted !!!', 'cwginstocknotifier');
                        $error_replace = str_replace('{email_id}', $get_email, $error_msg);
                        $logger = new CWG_Instock_Logger('error', "$error_replace" . " #$post_id");
                        $logger->record_log();
                        $not_exists++;
                    }
                }

                $final_notice = __("Bulk Email: ", 'cwginstocknotifier');
                $final_notice .= $count > 0 ? "Total = $count" : '';
                $final_notice .= $sent > 0 ? " Sent = $sent" : '';
                $final_notice .= $failed > 0 ? " Failed = $failed" : '';
                $final_notice .= $not_exists > 0 ? " Product not Exists = $not_exists" : '';

                $logger = new CWG_Instock_Logger('info', $final_notice);
                $logger->record_log();

                add_persistent_notice(array(
                    'type' => 'success',
                    'message' => $final_notice,
                ));
            }
        }

        public function add_subscribers_count_column($columns) {
            $date_columns = $columns['date'];
            unset($columns['date']);
            $columns['total_subscribers'] = __('Subscribers Count', 'cwginstocknotifier');
            $columns['date'] = $date_columns;
            return $columns;
        }

        public function show_subscribers_count($columns, $post_id) {
            if ($columns == 'total_subscribers') {
                $edit_list = admin_url('edit.php?post_type=cwginstocknotifier&cwg_filter_by_products[0]=' . $post_id . '&post_status=cwg_subscribed&filter_action=Filter');
                $query_arg = esc_url_raw($edit_list);
                $api = new CWG_Instock_API();
                $subscribers_count = intval($api->get_subscribers_count($post_id, 'cwg_subscribed'));
                $get_data = intval(get_post_meta($post_id, 'cwg_total_subscribers', true));
                if ($get_data != $subscribers_count) {
                    update_post_meta($post_id, 'cwg_total_subscribers', $subscribers_count);
                } else {
                    add_post_meta($post_id, 'cwg_total_subscribers', $subscribers_count, true);
                }
                if ($subscribers_count > 0) {
                    echo "<a href='$query_arg'> $subscribers_count </a>";
                } else {
                    echo $subscribers_count;
                }
            }
        }

        public function subscribers_sortable_columns($columns) {
            $columns['total_subscribers'] = 'total_subscribers';
            return $columns;
        }

        public function sort_total_subscribers($query) {
            if (!is_admin())
                return;

            $orderby = $query->get('orderby');
            if ('total_subscribers' == $orderby) {
                $query->set('meta_key', 'cwg_total_subscribers');
                $query->set('orderby', 'meta_value_num');
            }
        }

    }

    new CWG_Instock_Post_Type();
}