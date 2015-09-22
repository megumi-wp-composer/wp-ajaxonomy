# megumi/wp-ajaxonomy

[![Build Status](https://travis-ci.org/megumi-wp-composer/wp-ajaxonomy.svg?branch=master)](https://travis-ci.org/megumi-wp-composer/wp-ajaxonomy)
[![Latest Stable Version](https://poser.pugx.org/megumi/wp-ajaxonomy/v/stable.svg)](https://packagist.org/packages/megumi/wp-ajaxonomy)
[![Total Downloads](https://poser.pugx.org/megumi/wp-ajaxonomy/downloads.svg)](https://packagist.org/packages/megumi/wp-ajaxonomy)
[![Latest Unstable Version](https://poser.pugx.org/megumi/wp-ajaxonomy/v/unstable.svg)](https://packagist.org/packages/megumi/wp-ajaxonomy)
[![License](https://poser.pugx.org/megumi/wp-ajaxonomy/license.svg)](https://packagist.org/packages/megumi/wp-ajaxonomy)

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

`Megumi\WP\Ajaxonomy` has same parameters with `register_taxonomy()`.

```
<?php
/*
Plugin Name: My Music Library
Version: 0.1-alpha
Description: PLUGIN DESCRIPTION HERE
Author: YOUR NAME HERE
Author URI: YOUR SITE HERE
Plugin URI: PLUGIN SITE HERE
Text Domain: ajax-taxonomy
Domain Path: /languages
*/

require_once dirname( __FILE__ ) . '/vendor/autoload.php';

add_action( 'plugins_loaded', function(){
	$args = array(
		'label'            => 'Music',
	);
	$tax = new Megumi\WP\Ajaxonomy( 'music', 'post', $args );
	$tax->register();
} );

```

Then:

