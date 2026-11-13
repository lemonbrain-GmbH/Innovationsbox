# WebP Images for all your Thumbnails

The WebP image format provides [small filesize](https://developers.google.com/speed/webp/) while still retaining quality and transparency - just what we want for a fast web. With all the major browsers supporting it and Safari [from v14 on macOS 11 Big Sur and later](https://caniuse.com/webp) on board, there is no reason to not use it!

This Add-on will serve WebP images from your uploads in jpg/jpeg/png/gif format with the help of PHP's [imagewebp](https://www.php.net/manual/en/function.imagewebp.php) function.

## Installation

On package installation/start the following will happen:

- a total of five ThumbnailTypes will be installed, corresponding in size to the default Bootstrap breakpoints
- a new listener is introduced on 'ThumbnailGenerate' that will add a WebP version
- a second listener is introduced on 'ThumbnailDelete' that will remove the WebP version
- adds a template for the block "Image" called "Webp"
- adds a job for recreating all WebP Thumbnails

The core functionality of image uploads and thumbnail generation is not touched, only expanded. Ideally the user wont even know about WebP formats and gets much lighter images displayed.

WebP versions now get created on thumbnail creation and deleted on Thumbnail deletion. That means that existing thumbnails prior to package installation won't have a WebP sibling until the are recreated by the installed automated job.


#### What this package won't do:

The upload of WebP Images (even if allowed on the FileManager) will not get recognized as an image and won't have thumbnail versions. The goal of this package is not to enable WebP uploads but to present WebP in the frontend. It also don't creates WebP Images for the source files, just for the thumbnail.

## Usage (quick)

install Package > upload image > add Block "Image" with uploaded Image > select template "Webp" for Block "Image"

If you wanna use the already uploaded Images you need to recreate the WebP Image siblings for them by running an automated job manually (/dashboard/system/optimization/jobs - "Create webp Thumbnails")

Get served the smallest-possible, as-big-as-required WebP thumbnail on all different screen sizes

## Usage (custom, developer)

As in the template for the Block "Image" you can ```use LemonbrainWebp\ImageBrewer``` in your template. This provides ```ImageBrewer::get(), ImageBrewer::getFromFile()``` that returns a wrapped ```<picture>``` element with a ```<source>``` for both webp and the original file format.

__Parameters:__

- image/imageId _(required)_: Concrete File (ID) of your Media Item
- alt _(required)_: alt Text for your Image
- displaySizes _(required)_: string of sizes for the image, ex: ```'(max-width: 600px) 100vw, 50vw'```
- ratio _(default: auto)_: define a fixed ratio as ```1/2``` or use ```auto``` to use the original ratio. All css values for ```aspect-ratio``` are supported.
- objectFit _(default: contain)_: use ```['fill', 'cover', 'contain', 'none', 'scale-down']``` according to CSS properties
- miscFields: optional array of attributes you want on your element. ex: ```['data-yourcustomstuff' => 'veryCustomStuff']```
- additionalClasses: string of additional classes
- width: fixed width
- height: fixed height
- lazyLoading: boolean to enable lazy loading (default: true)

So it would be easy to create a new BlockType using an Image and have some ratios matching your design as a user selectable value and pass that to the ```ImageBrewer::get()``` function.

#### Real Usage:

```php
use Concrete\Core\Support\Facade\Application as App;
use LemonbrainWebp\ImageBrewer;

$imageBrewer = App::make(ImageBrewer::class);

// in your template:

// full example
echo $imageBrewer->getFromFile(
    image: $myFileEntity,
    alt: 'accurate Description',
    displaySizes: '(max-width: 600px) 100vw, 50vw'
    ratio: '4/3',
    objectFit: 'cover',
    miscFields: ['data-custom' => 'value'],
    additionalClasses: 'additional-class and-another',
    width: 800,
    height: 600,
    lazyLoading: true,
);

// much more realistic example:
echo $imageBrewer->getFromFile(
    image: $myFileEntity,
    displaySizes: '(max-width: 600px) 100vw, 50vw'
    alt: 'accurate Description',
    objectFit: 'cover',
);

// minimal example
echo $imageBrewer->getFromFile(
    image: $myFileEntity,
    displaySizes: '100vw'
    alt: 'accurate Description',
);
```

### JSON

There is also a function for a json response ```$imageBrewer->getJson($fileID, $enableAddContent)```

__Parameters:__
- $fileID _(required)_: Concrete File ID of your Media Item
- $enableAddContent: If possible includes the content of the main image. Handy for SVG-Images.

__Output:__
The urls are just showing up, if a file is really existing on the filesystem. The last entry is always the source file
```
[
    {
        'name' => 'X-Large',
        'handler' => 'webp_xlarge',
        'width' => 2000,
        'url' => ...,
        'url_webp' => ...,
        'url_2x' => ...,
        'url_webp_2x' => ...,
    },
    {
        'name' => 'Large',
        'handler' => 'webp_large',
        'width' => 1200,
        'url' => ...,
        'url_webp' => ...,
        'url_2x' => ...,
        'url_webp_2x' => ...,
    },
    {
        'name' => 'Medium',
        'handler' => 'webp_medium',
        'width' => 1000,
        'url' => ...,
        'url_webp' => ...,
        'url_2x' => ...,
        'url_webp_2x' => ...,
    },
    {
        'name' => 'Small',
        'handler' => 'webp_small',
        'width' => 800,
        'url' => ...,
        'url_webp' => ...,
        'url_2x' => ...,
        'url_webp_2x' => ...,
    },
    {
        'name' => 'X-Small',
        'handler' => 'webp_xsmall',
        'width' => 300,
        'url' => ...,
        'url_webp' => ...,
        'url_2x' => ...,
        'url_webp_2x' => ...,
    },
    {
        'name' => 'Source',
        'handler' => 'source',
        'width' => $image->getWidth(),
        'url' => $image->getURL(),
        'content'  => $content
    }
]
```

### SVG inline

There is apparently also this: ```$imageBrewer->getInlineSvgFromFile(FileEntity $image)``` to output the contents of a SVG file directly in the template.


## Feedback

We love to hear your thoughts via [Mail](mailto:hallo@lemonbrain.ch).

Have🥳Fun
