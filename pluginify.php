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
            else
                return true; // End successfuly
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
                default:
                    printf( "Unknown error code %d with arguments: %s.\n", $code, json_encode( $args ) );
            }
            self::help();
        }
        
        /**
         * Gets the WordPress SVN username from your ~/.subversion folder
         *
         * @return String, the wp-svn username or current system username
         */
        function wp_id() {
            $wp_id = false;
            
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
                            var_dump( $k );
                            $wp_id = trim( $contents[$k + 2] );
                            break;
                        }
            }
            
            if ( $wp_id )
                return $wp_id;
            return get_current_user();
        }
        
        /**
         * Pluginify Processor
         *
         * @param String $plugin, the new plugin name
         * @return Bool, true on success or false on failure
         */
        function process( $plugin ) {
            $author = self::wp_id();
            
            if ( !make_dirs( $plugin ) )
                self::error( 1, $plugin ); // Failure on mkdir
            
        }
        
        function readme( $plugin, $author ) {
            
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
            $plugin_paths[] = $cwd . '/' . $plugin . '/includes/templates';
            $plugin_paths[] = $cwd . '/' . $plugin . '/languages';
            foreach( $plugin_paths as $p )
                if ( !mkdir( $p, '0755', true ) )
                    $fail = true;
            
            if ( $fail )
                return false;
            return true;
        }
    }
    
    if ( php_sapi_name() === 'cli' ) {
        new Pluginify( $argv );
    } else
        echo "`pluginify` must be run from command line!\n";
?>