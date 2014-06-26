# Wordpress-CustomPostType

Helper to create custom post types quickly

## Setup

Add the following lines to your functions.php

include_once("lib/CustomPostType.php");

## Example Usage

Setting up a new custom post type with custom meta boxes. The below example is to add a 'studios'
custom post type with 2 custom meta boxes for the studios address, and telephone number

```php

  $studio_args = array(
      'public'        => true,
      'menu_position' => 5,
      'supports'      => array( 'title', 'editor', 'thumbnail', 'comments' ),
      'has_archive'   => true,
      'menu_icon'     => 'dashicons-location',
  );
  $studio = new CustomPostType('Studio', $studio_args);

    $addressMetaBox = new MetaBox('Address');
    $studio->addMetaBoxObject($addressMetaBox);
    $addressMetaBox->addFields(array(
      'Address' =>  'text',
    ));

    $telephoneMetaBox = new MetaBox('Telephone');
    $studio->addMetaBoxObject($telephoneMetaBox);
    $telephoneMetaBox->addFields(array(
      'Number' =>  'text',
    ));
```