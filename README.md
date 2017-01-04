# Vendi String Viewer

Used to view strings in PO files.

## Usage:
    add_filter(
                'vendi-string-view-items',
                function( $items )
                {
                    $items[ 'Sample Project' ] = 'my-slug-here';
                    return $items;
                }
            );