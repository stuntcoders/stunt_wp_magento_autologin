<?php
/**
 * Plugin Name: Mageto admin autologin
 * Plugin URI: https://github.com/stuntcoders/stunt_wp_magento_autologin
 * Description: Automatically log in Magento administrators to WordPress.
 * Version: 1.0
 * Author: StuntCoders
 * License: The MIT License (MIT)
 */

class Stuntcoders_WP_Magento_Autologin
{
    function init_magento_admin_session() {
        $magePath = realpath(ABSPATH . '/../app/Mage.php');
        require_once($magePath);
        umask(0);

        Mage::app();
        Mage::getSingleton('core/session', array('name'=>'adminhtml'));
    }

    function is_magento_admin_logged_in() {
        return Mage::getSingleton('admin/session')->isLoggedIn();
    }

    function get_magento_admin() {
        return Mage::getSingleton('admin/session')->getUser();
    }

    function login_if_magento_admin() {
        self::init_magento_admin_session();

        if (self::is_magento_admin_logged_in()) {
            $magento_admin = self::get_magento_admin();

            $user = get_user_by ( 'email', $magento_admin->getEmail() );

            // Create if user doesn't exist
            if ( ! $user ) {
                $user_id = wp_create_user ( $magento_admin->getUsername() , wp_generate_password(), $magento_admin->getEmail() );

                $user = get_user_by ( 'id', $user_id );
                $user_data = array (
                    'ID' => $user_id,
                    'display_name' => trim ( $magento_admin->getFirstName() . " " . $magento_admin->getLasttName() ),
                    'first_name' => $magento_admin->getFirstName(),
                    'last_name' => $magento_admin->getLasttName(),
                    'role' => 'administrator'
                );
                wp_update_user ( $user_data );
            }

            // Login user
            if (! is_user_logged_in ()) {
                wp_set_current_user ( $user->ID );
                wp_set_auth_cookie ( $user->ID, true );
                do_action ( 'wp_login', $user->user_login );
                wp_redirect ( get_admin_url() );
                die;
            }
        }
    }

    function logout_wp_admin_if_not_magento_admin() {
        self::init_magento_admin_session();
        if (!self::is_magento_admin_logged_in()) {
            wp_logout();
        }
    }

    function logout_magento_admin() {
        self::init_magento_admin_session();
        $adminSession = Mage::getSingleton('admin/session');
        $adminSession->unsetAll();
        $adminSession->getCookie()->delete($adminSession->getSessionName());
    }
}

add_action ( 'login_init', 'Stuntcoders_WP_Magento_Autologin::login_if_magento_admin' );
add_action ( 'admin_init', 'Stuntcoders_WP_Magento_Autologin::logout_wp_admin_if_not_magento_admin' );
add_action ( 'wp_logout', 'Stuntcoders_WP_Magento_Autologin::logout_magento_admin' );
