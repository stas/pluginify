#!/usr/bin/php -R
<?php
    class Pluginify {
        // Version
        static $version = '0.1';
        
        /**
         * Constructor
         *
         * @param Mixed, CLI $argv
         * @return Bool, true on success and false on failure
         */
        function Pluginify( $argv ) {
            // Show help if asked
            if ( in_array( '-h', $argv ) || in_array( '--help', $argv ) )
                self::help();
            // Cleanup any defined arguments
            foreach ( $argv as $k => $v )
               in_array( $v, array( '-h', '--help', $argv[0] ) ) ? $argv[$k] = false : true;
            $argv = array_filter( $argv );
            // If no errors find the plugin name
            if ( count( $argv ) > 1 )
                self::error( 0, $argv ); // Too many args
            elseif ( count( $argv ) == 1 )
                self::process( reset( $argv ) ); // Do the magic
            else{
                self::help();
                return true; // End successfuly
            }
        }
        
        /**
         * Outputs the help block
         */
        function help() {
            printf( "`pluginify` v%s, is a tool to generate a WordPress plugin skeleton.\n", Pluginify::$version );
            echo "\tSyntax: `pluginify` -h <plugin_name>\n";
            echo "\t-h \t\t\t Prints this help.\n";
            echo "\t <plugin_name> \t\t Creates a plugin using <plugin_name>.\n";
        }
        
        /**
         * Error messages wrapper
         *
         * @param Int $code, the error code to be used
         * @param Mixed $args, the arguments list
         */
        function error( $code, $args ) {
            switch ( intval( $code ) ) {
                case 0 :
                    printf( "Error #%d: unknown arguments passed. Arguments: %s.\n", $code, json_encode( $args ) );
                    break;
                case 1 :
                    printf( "Error #%d: there was an error while creating plugin directories. Plugin name: %s.\n", $code, $args );
                    break;
                case 2 :
                    printf( "Error #%d: there was an error while creating readme file. Plugin name: %s.\n", $code, $args );
                    break;
                case 3 :
                    printf( "Error #%d: there was an error while creating default php files. Plugin name: %s.\n", $code, $args );
                    break;
                default:
                    printf( "Unknown error code %d with arguments: %s.\n", $code, json_encode( $args ) );
            }
            self::help();
        }
        
        /**
         * Try to guess some user details: wp-svn id, email, last WordPress version
         *
         * @return Mixed, the wp-svn username or current system username and guessed email
         */
        function whoami() {
            $uid = false;
            $email = false;
            $version = false;
            
            // Find any dot files
            $dotfile = shell_exec( "grep -siR 'wordpress' ~/.subversion/ | cut -d ':' -f 1" );
            // Get the last one
            $dotfile = end( array_filter( explode( "\n", $dotfile ) ) );
            // Try to get the WordPress.org username
            if ( !empty( $dotfile ) ) {
                $contents = file( $dotfile );
                if ( !empty( $contents ) )
                    foreach ( $contents as $k => $l )
                        if ( trim( $l ) == 'username' && ( $k + 2 <= count( $contents ) ) ) {
                            $uid = trim( $contents[$k + 2] );
                            break;
                        }
            }
            if ( !$uid )
                $uid = get_current_user();
            
            // Try to get the email from ~/.gitconfig file
            $email = trim( shell_exec( "grep -siR 'email' ~/.gitconfig | cut -d '=' -f 2" ) );
            if ( empty( $email ) )
                $email = $uid . '@' . php_uname( 'n' );
            
            // Try to guess the WordPress version if we are inside a WordPress codebase
            $cwd_path = explode( DIRECTORY_SEPARATOR, getcwd() );
            foreach ( $cwd_path as $k => $d )
                if ( $d == 'wp-content' ) {
                    $cwd_path = array_slice( $cwd_path, 0, $k );
                    $wp_path = implode( DIRECTORY_SEPARATOR, $cwd_path );
                    include $wp_path . '/wp-includes/version.php';
                    $version = $wp_version;
                }
            
            return compact( 'uid', 'email', 'version' );
        }
        
        /**
         * Pluginify Processor
         *
         * @param String $plugin, the new plugin name
         * @return Bool, true on success or false on failure
         */
        function process( $plugin ) {
            $whoami = self::whoami();
            
            if ( !self::make_dirs( $plugin ) )
                self::error( 1, $plugin ); // Failure on mkdir
            
            if ( !self::readme( $plugin, $whoami ) )
                self::error( 2, $plugin ); // Failure on readme creation
            
            if ( !self::files( $plugin, $whoami ) )
                self::error( 3, $plugin ); // Failure on php files creation
            
            return true;
        }
        
        /**
         * Generate default php files using current informations
         *
         * @param String $plugin, plugin name
         * @param Mixed $whoami, collected informations from self::whoami()
         * @return Bool, true on success or false on failure
         */
        function files( $plugin, $whoami ) {
            extract( $whoami );
            
            $fail = false;
            $plugin_files = array();
            
            $plugin_upper = strtoupper( $plugin );
            $plugin_camel = ucwords( $plugin );
            
            $plugin_files[ $plugin . '.php' ] =
"<?php
/*
Plugin Name: $plugin_upper
Plugin URI: http://wordpress.org/extend/plugins/$plugin/
Description: $plugin_camel Description
Version: 0.1
Author: $uid
Author URI: http://wordpress.org/extend/plugins/$plugin/
*/
?>
<?php
/*  Copyright 2011  $uid <$email>

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

define( '$plugin_upper', '0.1' );

/**
 * Main $plugin_camel Class
 */
class $plugin_camel {
    /**
     * Static constructor
     */
    function init() {
        if ( !get_transient( '{$plugin}_loaded' ) )
            add_action( 'admin_notices', array( __CLASS__, 'notifications' ) );
        set_transient( '{$plugin}_loaded', true );
    }
    
    /**
     * i18n
     */
    function localization() {
        load_plugin_textdomain( '$plugin', false, basename( dirname( __FILE__ ) ) . '/languages' );
    }
    
    /**
     * Start with a successful notifications is always a win
     */
    function notifications() {
        self::render( 'notification', array( 'status' => 'successfuly' ) );
    }
    
    /**
     * render( \$name, \$vars = null, \$echo = true )
     *
     * Helper to load and render templates easily
     * @param String \$name, the name of the template
     * @param Mixed \$vars, some variables you want to pass to the template
     * @param Boolean \$echo, to echo the results or return as data
     * @return String \$data, the resulted data if \$echo is `false`
     */
    function render( \$name, \$vars = null, \$echo = true ) {
        ob_start();
        if( !empty( \$vars ) )
            extract( \$vars );
        
        include dirname( __FILE__ ) . '/templates/' . \$name . '.php';
        
        \$data = ob_get_clean();
        
        if( \$echo )
            echo \$data;
        else
            return \$data;
    }
}
$plugin_camel::init();
?>";
            $plugin_files[ 'templates/notification.php' ] =
"<div id=\"message\" class=\"updated fade\">
    <p><?php _e( \"$plugin_camel loaded: \$status\", '$plugin' ) ?></p>
</div>
";
            foreach ( $plugin_files as $path => $file )
                if ( !file_put_contents( getcwd() . '/' . $plugin . '/' . $path, $file, LOCK_EX ) )
                    $fail = true;
            
            return !$fail;
        }
        
        /**
         * Generate a readme.txt file with current informations
         *
         * @param String $plugin, plugin name
         * @param Mixed $whoami, collected informations from self::whoami()
         * @return Bool, true on success or false on failure
         */
        function readme( $plugin, $whoami ) {
            extract( $whoami );
            
            $plugin_camel = ucwords( $plugin );
            
            $readme =
"=== Fancy $plugin_camel Name ===
Contributors: $uid
Tags: $plugin tags
Requires at least: WordPress $version
Tested up to: WordPress $version
Stable tag: 0.1
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=$email

$plugin_camel short description of the plugin.
This should be no more than 150 characters.  No markup here.

== Description ==

$plugin_camel long description.
No limit, and you can use Markdown (as well as in the following sections).

== Installation ==

Please follow the [standard installation procedure for WordPress plugins](http://codex.wordpress.org/Managing_Plugins#Installing_Plugins).

== Frequently Asked Questions ==

For questions, please email me mailto:$email

== Changelog ==

= 0.1 =
* First stable release.

== Screenshots ==

";          if ( file_put_contents( getcwd() . '/' . $plugin . '/readme.txt', $readme, LOCK_EX ) )
                return true;
            return false;
        }
        
        /**
         * Creates plugin directories
         *
         * @param String $plugin, the plugin name
         * @return Bool, true on success or false on failure
         */
        function make_dirs( $plugin ) {
            $fail = false;
            $cwd = getcwd();
            $plugin_paths[] = $cwd . '/' . $plugin . '/templates';
            $plugin_paths[] = $cwd . '/' . $plugin . '/languages';
            foreach( $plugin_paths as $p )
                if ( !mkdir( $p, 0755, true ) )
                    $fail = true;
            
            return !$fail;
        }
    }
    
    if ( php_sapi_name() === 'cli' )
        new Pluginify( $argv );
    else
        echo "`pluginify` must be run from command line!\n";
?>