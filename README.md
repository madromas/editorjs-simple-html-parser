 [![codecov](https://codecov.io/gh/Durlecode/editorjs-simple-html-parser/branch/master/graph/badge.svg?token=OKG54EX9C3)](https://codecov.io/gh/Durlecode/editorjs-simple-html-parser)
 [![License: GPL v3](https://img.shields.io/badge/License-GPLv3-blue.svg)](https://www.gnu.org/licenses/gpl-3.0)
# Simple PHP Parser for Editor.js

Parse data for [Editor.js](https://editorjs.io/ "Editor.js Homepage") with 2 way:
1. Parse JSON data to HTML
2. Parse HTML to JSON data
## Supported Tools

Package | Key | Main CSS Class<br>(with default prefix) | Additional / modificator CSS classes<br>(with default prefix)
--- | --- | --- | ---
`@editorjs/header`<br>`editorjs-header-with-alignment` | `header` | `.prs-header` | alignment:<br>`.prs_left`<br>`.prs_right`<br>`.prs_center`<br>`.prs_justify`
`@editorjs/paragraph`<br>`editorjs-paragraph-with-alignment` | `paragraph` | `.prs-paragraph` | alignment:<br>`.prs_left`<br>`.prs_right`<br>`.prs_center`<br>`.prs_justify`
`@editorjs/inline-code` |  |  |
`@editorjs/marker` |  |  |
`@editorjs/underline` |  |  |
`@editorjs/list` | `list` | `.prs-list` | additional:<br>`.prs_ordered`
`@editorjs/raw` | `raw` |  |
`@editorjs/simple-image` | `simpleImage` | `.prs-image` | additional:<br>`.prs_withborder`<br>`.prs_withbackground`<br>`.prs_stretched`
`@editorjs/embed` | `embed` | `.prs-embed` | additional:<br>`.prs_youtube`<br>`.prs_codepen`<br>`.prs_vimeo`
`@editorjs/link` | `linkTool` | `.prs-linktool` | additional:<br>`.prs_title`<br>`.prs_description`<br>`.prs_sitename`
`@editorjs/simple-video-editorjs` | `SimpleVideo` | `.prs-video` | additional:<br>`stretched`<br>`autoplay`<br>`muted`<br>`controls`
`@editorjs/delimiter` | `delimiter` | `.prs-delimiter` |
`editorjs-alert` | `alert` | `.prs-alert` | alignment:<br>`.prs_left`<br>`.prs_right`<br>`.prs_center`<br>additional:<br>`.prs_primary`<br>`.prs_secondary`<br>`.prs_info`<br>`.prs_success`<br>`.prs_warning`<br>`.prs_danger`<br>`.prs_light`<br>`.prs_dark`
`@editorjs/warning` | `warning` | `.prs-warning` |
`@editorjs/table` | `table` | `.prs-table` | additional:<br>`.prs_withheadings`
`@editorjs/quote` | `quote` | `.prs-quote` | alignment:<br>`.prs_left`<br>`.prs_center`
`@editorjs/code` | `code` | `.prs-code`

## Installation

```
composer require edd-g/editorjs-simple-html-parser
```
## 1. JSON to HTML Parser

### Usage

```php
use Durlecode\EJSParser\Parser;

$html = Parser::parse($data)->toHtml();
```

Where `$data` is the clean JSON data coming from Editor.js *See `$data` example below*

```json
{
    "time" : 1583848289745,
    "blocks" : [
        {
            "type" : "header",
            "data" : {
                "text" : "Hello World",
                "level" : 2
            }
        }
    ],
    "version" : "2.16.1"
}
```

By default this will generate html with css classes with `prs` prefix, so if you want to change it, follow example below

```php
use Durlecode\EJSParser\Parser;

$parser = new Parser($data);

$parser->setPrefix("cat");

$parsed = $parser->toHtml();
```
### Methods 

#### `toHtml()`
Return generated HTML

#### `setPrefix(string $prefix)`
Set CSS classes Prefix

#### `getPrefix()`
Return current prefix

#### `getVersion()`
Return Editor.js content version

#### `getTime()`
Return Editor.js content timestamp

#### `getBlocks()`
Return Editor.js content blocks

### Generated HTML

##### Header

```html
<h2 class="prs-header prs_center">Lorem</h2>
```

##### Paragraph

```html
<p class="prs-paragraph prs_center">
    <code class="inline-code">Pellentesque</code> 
    <i>malesuada fames</i> 
    <mark class="cdx-marker">tempus</mark>
</p>
```

##### Ordered List

```html
<ol class="prs-list prs_ordered">
    <li></li>
</ol>
```

##### Unordered List

```html
<ul class="prs-list">
    <li></li>
</ul>
```

##### Table

```html
<table class="prs-table prs_withheadings">
    <thead>
        <tr>
            <th>1</th><th>2</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td>a</td><td>b</td>
        </tr>
    </tbody>
</table>
```

##### Video

```html
<div class="prs-video">
    <video src="" controls muted autoplay stretched></video>
</div>
```

##### Code

```html
<pre class="prs-code">
    <code></code>
</pre>
```

##### Embed 
###### *(Actually working with Youtube, Codepen & Gfycat)*

```html
<figure class="prs-embed prs_youtube">
    <iframe width="580" height="320" src="https://www.youtube.com/embed/CwXOrWvPBPk" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" allowfullscreen="1"></iframe>
    <figcaption>Shrek (2001) Trailer</figcaption>
</figure>
```

##### Delimiter

```html
<hr class="prs-delimiter">
```

##### LinkTool

```html
<figure class="prs-linkTool">
    <a href="https://github.com/" target="_blank">
       <img src="https://example.com/cat.png" alt="">
       <p class="prs_title">Title</p>
       <p class="prs_description">Description</p>
       <p class="prs_sitename">example.com</p>
    </a>
</figure>
```

##### Image

```html
<figure class="prs-image prs_withborder prs_withbackground prs_stretched">
    <img src="" alt="">
    <figcaption></figcaption>
</figure>
```

##### Quote

```html
<figure class="prs-quote prs_center">
    <blockquote></blockquote>
    <figcaption></figcaption>
</figure>
```

##### Warning

```html
<div class="prs-warning">
    <i></i>
    <h4>Title</h4>
    <p>Message</p>
</div>
```

##### Alert

```html
<p class="prs-alert prs_center prs_success">
    Alert!
</p>
```

##### Raw

```html
<div class="prs-raw">
    Raw HTML ...
</div>
```

## 2. HTML to JSON Parser

### Usage

```php
use Durlecode\EJSParser\HtmlParser;

$parser = new HtmlParser($html);

$blocks = $parser->toBlocks();

header("Content-Type: application/json");
echo $blocks;
```

Where `$html` is the HTML specially tagged with CSS classes *See examples of the generated HTML code above*

By default this will parse html with css classes with `prs` prefix, so if you want to change it, follow example below

```php
use Durlecode\EJSParser\HtmlParser;

$parser = new HtmlParser($html);

$parser->setPrefix("cat");

$blocks = $parser->toBlocks();
```

You may set time and version EditorJS generated blocks *By default: time generate auto, EditorJS version pass from config.php*:

```php
use Durlecode\EJSParser\HtmlParser;

$parser = new HtmlParser($html);

$parser->setTime("1703787424242");
$parser->setVersion("2.28.8");

$blocks = $parser->toBlocks();
```

### Methods 

#### `toBlocks()`
Return generated EditorJS Blocks

#### `setPrefix(string $prefix)`
Set CSS classes Prefix

#### `getPrefix()`
Return current prefix

#### `setVersion(string $version)`
Set Editor.js content version

#### `getVersion()`
Return Editor.js content version

#### `getTime()`
Return Editor.js content timestamp

#### `setTime(string $time)`
Set Editor.js content timestamp
