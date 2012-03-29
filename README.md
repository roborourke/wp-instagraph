# WP Instagraph

This is a prototype WordPress plugin for using imagemagick filters on your wordpress images.

There is currently no UI but it is planned.

## Usage

To use the filters in your templates you have to call image sizes in the following way:

```php
<?php

    the_post_thumbnail( 'medium:kelvin' );

?>
```

In the above the image size requested is 'medium' and the filter to use is 'kelvin'.

The supplied filters are 'lomo', 'nashville', 'kelvin', 'toaster', 'gotham', 'tilt_shift'.

## API

You can register new filters in the following way:

```php
<?php

register_instagraph_filter( 'custom_filter', 'custom_filter_callback' );

function custom_filter_callback( $this ) {
    $this->tempfile();

    $command = "convert $this->_tmp -channel B -level 33% -channel G -level 20% $this->_tmp";

    $this->execute($command);
    $this->vignette($this->_tmp);

    $this->output();
}

?>
```

This was a quickly made hack so it will need work, and hopefully some contributions! Go play.

## Questions

If you have any questions use the issue tracker here or get me on twitter [@sanchothefat](https://twitter.com/sanchothefat).
