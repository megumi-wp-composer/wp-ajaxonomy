# megumi/wp-ajaxonomy

## Installation

Create a composer.json in your plugin root or mu-plugins

```
{
    "require": {
        "megumi/wp-ajaxonomy": "*"
    }
}
```

Place the following code into your plugin.

```
require_once dirname( __FILE__ ) . '/vendor/autoload.php';
```

Then:

```
$ composer install
```

## How to use

`Megumi\WP\Ajaxonomy` has a same parameters with `register_taxonomy()`.

```
<?php

add_action( 'plugins_loaded', function(){
    $labels = array(
        'name'              => _x( 'Genres', 'taxonomy general name' ),
        'singular_name'     => _x( 'Genre', 'taxonomy singular name' ),
        'search_items'      => __( 'Search Genres' ),
        'all_items'         => __( 'All Genres' ),
        'parent_item'       => __( 'Parent Genre' ),
        'parent_item_colon' => __( 'Parent Genre:' ),
        'edit_item'         => __( 'Edit Genre' ),
        'update_item'       => __( 'Update Genre' ),
        'add_new_item'      => __( 'Add New Genre' ),
        'new_item_name'     => __( 'New Genre Name' ),
        'menu_name'         => __( 'Genre' ),
    );

    $args = array(
        'labels'            => $labels,
    );

    $tax = new Megumi\WP\Ajaxonomy( 'genre', 'post', $args );
    $tax->register();
} );
```
