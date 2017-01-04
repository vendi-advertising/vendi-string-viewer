<?php
/*
Plugin Name: Vendi String Viewer
Description: Used to view strings in PO files.
Version: 1.0.0
Author: Vendi Advertising (Chris Haas)
*/

define( 'VENDI_STRING_VIEWER_PLUGIN_FILE', __FILE__ );
define( 'VENDI_STRING_VIEWER_PLUGIN_PATH', dirname( __FILE__ ) );
define( 'VENDI_STRING_VIEWER_PLUGIN_URL',  plugin_dir_url( __FILE__ ) );

require_once VENDI_STRING_VIEWER_PLUGIN_PATH . '/vendor/autoload.php';

//Custom icon - Requires Vendi WP Admin Cleanup plugin
add_filter(
            'vendi-wp-admin-cleanup-root-svg',
            function( $default, $name, $class )
            {
                if( 'String Viewer' === $name )
                {
                    return '<svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 288.949 383.461"><g fill="#fff"><path d="M159.979,162.94c20.46-23.38,56.931,3.38,76.99,16c18.18,12.48,43.64,17.68,50.99,40.99c-1.12,25.24,1.53,46.67,0,68 c-4.08,57.01-52.07,99.28-111.99,94.98c-46.64-3.341-70.65-35.65-97.98-62.99c-14.02-14.021-27.1-28.09-40-40.99 c-13.96-13.96-30.15-25.57-37.99-42c7.99-24.77,50.8-24.46,71.99-12c10.59,6.23,16.33,15.31,23.99,21c0-18.36,0-37.54,0-56.99 c0-18.7-3.77-40.32,0-55.99c2.98-12.37,25.79-24.22,32-34c10.92,10.75,22.74,20.58,32,33 C159.979,142.28,159.979,152.61,159.979,162.94z"/><path d="M205.22,64.95c-12.752-3.751-48.94-2-71.99-2c-25.86,0-50.13,0-73.99,0c-12.05,2.17-22.3,12.23-25,19 c-10.47,26.26,13.53,53.42,42,43c23.2-8.5,30.2-35.2,52.99-48c20.68,14.69,28.15,39.89,51.99,48 C223.689,139.38,240.178,75.234,205.22,64.95z M94.23,86.95c-6.28,6.28-17.78,23.49-28.99,24c-11.62,0.52-17-8.5-17-16 c0-22.03,29.06-14.92,51.99-16C102.28,82.16,96.74,84.45,94.23,86.95z M192.22,110.95c-17.19,0-26.33-26.3-37.99-31 c15.54-0.87,42.53-4.54,50.99,5C212.819,98.74,203.17,110.95,192.22,110.95z"/><path d="M160.229,46.96c-21.33,0-42.67,0-64,0C81.17-15.32,175.29-15.32,160.229,46.96z"/></g></svg>';
                }

                return $default;
            },
            10,
            3
        );

add_action(
            'admin_menu',
            function()
            {
                $menus = apply_filters( 'vendi-string-view-items', array() );

                add_menu_page(
                                'String Viewer',
                                'String Viewer',
                                'manage_options',
                                'vendi-string-viewer',
                                function() use( $menus )
                                {
                                    echo '<div class="wrap">';
                                    if( ! $menus || ! is_array( $menus ) || 0 === count( $menus ) )
                                    {
                                        echo '<p>No plugins or themes are exposing their menus. Please see the code for how to do this.</p>';
                                    }
                                    else
                                    {
                                        echo '<p>Please select one of the menus:</p>';
                                        echo '<ul>';
                                        foreach( $menus as $name => $slug )
                                        {
                                            echo sprintf( '<li><a href="%1$s">%2$s</a></li>', esc_url( admin_url( 'admin.php?page=' . $slug ) ), esc_html( $name ) );
                                        }
                                        echo '</ul>';
                                    }


                                    echo '</div>';
                                }
                            );

                foreach( $menus as $name => $slug )
                {
                    add_submenu_page (
                                        'vendi-string-viewer',
                                        $name,
                                        $name,
                                        'manage_options',
                                        $slug,
                                        function() use ( $name, $slug )
                                        {
                                            echo '<div class="wrap">';
                                            echo sprintf( '<h1>Strings from <em>%1$s</em></h1>', esc_html( $name ) );

                                            echo sprintf( '<a href="%1$s">View by context</a>', esc_url( add_query_arg( array( 'sort' => 'context' ) ) ) );
                                            echo ' | ';
                                            echo sprintf( '<a href="%1$s">View by file</a>', esc_url( add_query_arg( array( 'sort' => 'file' ) ) ) );

                                            echo '<p>Please note, the values <code>%s</code> and <code>%d</code> will dynamically be replaced with a text or numeric value when shown on the screen.</p>';

                                            $sort = \Vendi\Shared\utils::get_get_value( 'sort' );

                                            $language_file = trailingslashit( WP_PLUGIN_DIR ) . $slug . '/languages/' . $slug . '.pot';

                                            if( is_file( $language_file ) )
                                            {
                                                $fileHandler  = new Sepia\FileHandler( $language_file );
                                                $poParser = new Sepia\PoParser( $fileHandler );
                                                $entries  = $poParser->parse();

                                                $roots = [];

                                                switch( $sort )
                                                {
                                                    case 'file':
                                                        foreach( $entries as $text => $other )
                                                        {
                                                            $text = str_replace( '<##EOL##>', "\n", $text );

                                                            $parts = explode( '<##EOC##>', $text );

                                                            if( 2 === count( $parts ) )
                                                            {
                                                                $text = $parts[ 1 ];
                                                            }

                                                            if( array_key_exists( 'reference', $other ) )
                                                            {
                                                                $files = explode( ' ', $other[ 'reference' ][ 0 ] );
                                                                foreach( $files as $file )
                                                                {
                                                                    $parts = explode( ':', $file );
                                                                    if( 2 === count( $parts ) )
                                                                    {
                                                                        $roots[ $parts[ 0 ] ][] = array( 'text' => $text, 'other' => $other );
                                                                    }
                                                                }
                                                                // dump( $roots );
                                                            }
                                                        }

                                                        break;

                                                    default:

                                                        foreach( $entries as $text => $other )
                                                        {
                                                            $text = str_replace( '<##EOL##>', "\n", $text );

                                                            $parts = explode( '<##EOC##>', $text );

                                                            if( 2 === count( $parts ) )
                                                            {
                                                                $roots[ $parts[ 0 ] ][] = array( 'text' => $parts[ 1 ], 'other' => $other );
                                                            }
                                                            else
                                                            {
                                                                $roots[ '_Not Specified' ][] = array( 'text' => $text, 'other' => $other );
                                                            }
                                                        }
                                                }

                                               ksort( $roots );

                                                foreach( $roots as $context => $items )
                                                {
                                                    echo sprintf( '<h2>%1$s</h2>', esc_html( $context ) );

                                                    echo '<ul>';

                                                    foreach( $items as $item )
                                                    {

                                                        if( array_key_exists( 'reference', $item[ 'other' ] ) )
                                                        {
                                                            echo '<li>';

                                                            echo str_replace( '%d', '<code>%d</code>', str_replace( '%s', '<code>%s</code>', esc_html( $item[ 'text' ] ) ) );
                                                            echo '<br />';
                                                            echo '<pre style="margin-left: 20px; font-size: 90%;">' . esc_html( implode( "\n", explode( ' ', $item[ 'other' ][ 'reference' ][ 0 ] ) ) ) . '</pre>';

                                                            echo '</li>';
                                                        }
                                                    }

                                                    echo '</ul>';
                                                }

                                            }

                                            echo '</div>';
                                        }
                                    );
                }
            }
        );
