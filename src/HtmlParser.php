<?php

namespace Durlecode\EJSParser;

use DOMDocument;
use DomXPath;

class HtmlParser
{
    /**
     * @var Config
     */
    private $config;

    /**
     * @var StdClass
     */
    private $html;

    /**
     * @var DOMDocument
     */
    private $dom;

    /**
     * @var EditorJS blocks
     */
    private $blocks = [];

    /**
     * @var string
     */
    private $prefix;

    /**
     * @var string
     */
    private $time;

    /**
     * @var string
     */
    private $version;

    public function __construct(string $html)
    {
    	$this->config = new Config();

    	if (empty($html)) throw new ParserException('Empty HTML !');

    	$this->prefix = $this->config->getPrefix();

        libxml_use_internal_errors(true);

        $this->html = $html;

        $this->dom = new DOMDocument(1.0, 'UTF-8');

        $this->dom->loadHTML(mb_encode_numericentity($this->html, [0x80, 0x10FFFF, 0, ~0], 'UTF-8'), LIBXML_NOERROR);
    }

    static function parse(string $html)
    {
        return new self($html);
    }

    /**
     * @return string
     */
    public function getPrefix(): string
    {
        return !empty($this->prefix) ? $this->prefix : $this->config->getPrefix();
    }

    /**
     * @param string $prefix
     */
    public function setPrefix(string $prefix): void
    {
        $this->prefix = $prefix;
    }

    public function getTime()
    {
        return isset($this->time) ? $this->time : round(microtime(true) * 1000);
    }

    /**
     * @param string $time
     */
    public function setTime(string $time): void
    {
        $this->time = $time;
    }

    public function getVersion()
    {
        return isset($this->version) ? $this->version : $this->config->getVersion();
    }

    /**
     * @param string $version
     */
    public function setVersion(string $version): void
    {
        $this->version = $version;
    }

    public function getHtml()
    {
        return isset($this->html) ? $this->html : null;
    }

    public function toBlocks()
    {
        $this->init();

        $this->blocks = [
			'time' => $this->getTime(),
			'blocks' => $this->blocks,
			'version' => $this->getVersion()
		];

        return json_encode($this->blocks, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT);
    }

    /**
     * @throws ParserException
     */
    private function init()
    {
        if (!$this->hasHtml()) throw new ParserException('No HTML to parse !');

        $finder = new DomXPath($this->dom);

		$nodes = $finder->query("//*[contains(@class, '$this->prefix')]");

		foreach ($nodes as $node) {

			$nodeAttr = [];

			if (isset($node->attributes)) {
				foreach ($node->attributes as $attribute) {
					$nodeAttr[$attribute->name] = $attribute->value;
				}
			}

			$classList = array_key_exists('class', $nodeAttr) ? explode(' ', $nodeAttr['class']) : [];

			$prefixClass = $this->getPrefix();
			
			// Get block type from class name
			$blockType = array_values(array_filter($classList, function($class) use($prefixClass) {
				if (0 === strpos($class, $prefixClass.'-')) {
					return true;
				}
			}));
			
			$blockType = isset($blockType[0]) ? explode('-', $blockType[0])[1] : null;

			// Styles / Tunes list from class name
			$styles = array_values(array_filter($classList, function($class) use($prefixClass) {
				if (0 === strpos($class, $prefixClass.'_')) {
					return true;
				}
			}));

			foreach ($styles as $k => $style) {
				$styles[$k] = ltrim(strstr($style, '_'), '_');
			}

			// Call block parse function
			$method = isset($blockType) ? 'parse'.ucfirst($blockType) : '';
			if (method_exists($this, $method)) {
				$data = $this->{$method}($node, $styles);				
				array_push($this->blocks, $data);
			}
			else if (!empty($method)) {
                throw new ParserException('Unknow block '.$blockType.' !');
            }
		}
    }

    private function hasHtml()
    {
        return (get_object_vars($this->dom)) !== FALSE;
    }

    /**
	 * Nodes by class name
	 * 
	 * @param object $parentNode where find elements
	 * @param string $tagName
	 * @param string $className
	 * @return array
	 */
	private function getElementsByClass(&$parentNode, $tagName, $className) {
		
		$nodes = [];

		$childNodeList = $parentNode->getElementsByTagName($tagName);

		for ($i = 0; $i < $childNodeList->length; $i++) {
			$tmp = $childNodeList->item($i);
			if (stripos($tmp->getAttribute('class'), $className) !== false) {
				$nodes[] = $tmp;
			}
		}

		return $nodes;
	}

    /**
	 * Prepare block value
	 * 
	 * @param object $node
	 * @return string
	 */
	private function setInnerHtml($node) {

		$innerHTML = '';

		// Check child elements exist
		if (isset($node->childNodes)) {
			foreach ($node->childNodes as $childNode) {
				$innerHTML .= $childNode->ownerDocument->saveHTML($childNode);
			}
		} else {
			$innerHTML .= (isset($node->nodeValue)) ? $node->nodeValue : '';
		}
		
		return $innerHTML;
	}

	/**
	 * Get alignment from class name
	 * 
	 * @param array $styles
	 * @return string
	 */
	private function setAlignment($styles) {

		$filter = ['center', 'right', 'justify', 'left'];
		$alignment = array_values(array_intersect($styles, $filter));
		$alignment = !empty($alignment) ? $alignment[0] : 'left';

		return $alignment;
	}

    /**
	 * Header Parser
	 * 
	 * @param object $node
	 * @param array $styles
	 * @return array
	 */
	private function parseHeader($node, $styles) {
		
		$block['type'] = 'header';
		$block['data']['text'] = $this->setInnerHtml($node);
		$block['data']['level'] = ltrim($node->tagName, $node->tagName[0]);
		$block['data']['alignment'] = $this->setAlignment($styles);
		
		return $block;
	}

	/**
	 * Paragraph Parser
	 * 
	 * @param object $node
	 * @param array $styles
	 * @return array
	 */
	private function parseParagraph($node, $styles) {
		
		$block['type'] = 'paragraph';
		$block['data']['text'] = $this->setInnerHtml($node);
		$block['data']['alignment'] = $this->setAlignment($styles);
		
		return $block;
	}

	/**
	 * List Parser
	 * 
	 * @param object $node
	 * @param array $styles
	 * @return array
	 */
	private function parseList($node, $styles) {
		
		$style = in_array('ordered', $styles) ? 'ordered' : 'unordered';

		foreach ($node->childNodes as $childNode) {
			if ($childNode->nodeType === XML_ELEMENT_NODE) {
				$items[] = $childNode->nodeValue;
			}
		}

		$block['type'] = 'list';
		$block['data']['style'] = $style;
		$block['data']['items'] = $items;
		
		return $block;
	}

	/**
	 * Raw HTML Parser
	 * 
	 * @param object $node
	 * @param array $styles
	 * @return array
	 */
	private function parseRaw($node, $styles) {

		$block['type'] = 'raw';
		$block['data']['html'] = $this->setInnerHtml($node);
		
		return $block;
	}

	/**
	 * LinkTool Parser
	 * 
	 * @param object $node
	 * @param array $styles
	 * @return array
	 */
	private function parseLinkTool($node, $styles) {

		$title = $this->getElementsByClass($node, 'p', $this->prefix.'_title');
		$title = $title[0]->textContent;

		$description = $this->getElementsByClass($node, 'p', $this->prefix.'_description');
		$description = $description[0]->textContent;

		$sitename = $this->getElementsByClass($node, 'p', $this->prefix.'_sitename');
		$sitename = $sitename[0]->textContent;

		$block['type'] = 'linkTool';
		$block['data']['link'] = $node->getElementsByTagName('a')->item(0)->getAttribute('href');
		$block['data']['meta']['site_name'] = $sitename;
		$block['data']['meta']['image']['url'] = $node->getElementsByTagName('img')->item(0)->getAttribute('src');
		$block['data']['meta']['title'] = $title;
		$block['data']['meta']['description'] = $description;
		
		return $block;
	}

	/**
	 * Delimiter Parser
	 * 
	 * @param object $node
	 * @param array $styles
	 * @return array
	 */
	private function parseDelimiter($node, $styles) {

		$block['type'] = 'delimiter';
		$block['data'] = [];
		
		return $block;
	}

	/**
	 * Alert Parser
	 * 
	 * @param object $node
	 * @param array $styles
	 * @return array
	 */
	private function parseAlert($node, $styles) {

		$types = [
			'primary',
			'secondary',
			'info',
			'success',
			'warning',
			'danger',
			'light',
			'dark'
		];
		$dataType = array_values(array_intersect($styles, $types));
		$dataType = !empty($dataType) ? $dataType[0] : 'primary';

		$block['type'] = 'alert';
		$block['data']['type'] = $dataType;
		$block['data']['align'] = $this->setAlignment($styles);
		$block['data']['message'] = $this->setInnerHtml($node);
		
		return $block;
	}

	/**
	 * Table Parser
	 * 
	 * @param object $node
	 * @param array $styles
	 * @return array
	 */
	private function parseTable($node, $styles) {

		$withHeadings = in_array('withheadings', $styles) ? true : false;

		$trs = [];

		// Parse thead
		$thead = $node->getElementsByTagName('thead');
		if ($thead->length > 0) {
			$tr = $thead->item(0)->getElementsByTagName('tr')->item(0);
			foreach ($tr->childNodes as $childNode) {
				if ($childNode->nodeType === XML_ELEMENT_NODE) {
					$theadTds[] = $childNode->nodeValue;
				}
			}
			array_push($trs, $theadTds);
		}

		// Parse tbody
		$tbody = $node->getElementsByTagName('tbody');
		if ($tbody->length > 0) {
			$rows = $tbody->item(0)->getElementsByTagName('tr');
			foreach ($rows as $tr) {
				foreach ($tr->childNodes as $childNode) {
					if ($childNode->nodeType === XML_ELEMENT_NODE) {
						$tbodyTds[] = $childNode->nodeValue;
					}
				}
				array_push($trs, $tbodyTds);
				unset($tbodyTds);
			}
		}

		$block['type'] = 'table';
		$block['data']['withHeadings'] = $withHeadings;
		$block['data']['content'] = $trs;    
		
		return $block;
	}

	/**
	 * Code Parser
	 * 
	 * @param object $node
	 * @param array $styles
	 * @return array
	 */
	private function parseCode($node, $styles) {

		$block['type'] = 'code';
		$block['data']['code'] = $node->getElementsByTagName('code')->item(0)->nodeValue;
		
		return $block;
	}

	/**
	 * Quote Parser
	 * 
	 * @param object $node
	 * @param array $styles
	 * @return array
	 */
	private function parseQuote($node, $styles) {

		$block['type'] = 'quote';
		$block['data']['text'] = $this->setInnerHtml($node->getElementsByTagName('blockquote')->item(0));
		$block['data']['caption'] = $this->setInnerHtml($node->getElementsByTagName('figcaption')->item(0));
		$block['data']['alignment'] = $this->setAlignment($styles);
		
		return $block;
	}

         /**
	 * Video Parser
	 * 
	 * @param object $node
	 * @param array $styles
	 * @return array
	 */
	private function parseVideo($node, $styles) {

		$block['type'] = 'video';
		$block['data']['url'] = $this->setInnerHtml($node);
		
		return $block;
	}
	
	/**
	 * Embed Parser
	 * 
	 * @param object $node
	 * @param array $styles
	 * @return array
	 */
	private function parseEmbed($node, $styles) {

		$services = [
			'facebook',
			'instagram',
			'youtube',
			'twitter',
			'twitch-video',
			'miro',
			'vimeo',
			'gfycat',
			'imgur',
			'vine',
			'aparat',
			'yandex-music-track',
			'yandex-music-album',
			'yandex-music-playlist',
			'coub',
			'codepen',
			'pinterest',
			'github'
		];
		$dataServices = array_values(array_intersect($styles, $services));
		$dataServices = !empty($dataServices) ? $dataServices[0] : '';

		$block['type'] = 'embed';
		$block['data']['service'] = $dataServices;
		$block['data']['source'] = $node->getElementsByTagName('iframe')->item(0)->getAttribute('src');
		$block['data']['embed'] = $node->getElementsByTagName('iframe')->item(0)->getAttribute('src');
		$block['data']['width'] = $node->getElementsByTagName('iframe')->item(0)->getAttribute('width');
		$block['data']['height'] = $node->getElementsByTagName('iframe')->item(0)->getAttribute('height');
		$block['data']['caption'] = $this->setInnerHtml($node->getElementsByTagName('figcaption')->item(0));
		
		return $block;
	}

	/**
	 * Image Parser
	 * 
	 * @param object $node
	 * @param array $styles
	 * @return array
	 */
	private function parseImage($node, $styles) {

		$withBorder = in_array('withborder', $styles) ? true : false;
		$withBackground = in_array('withbackground', $styles) ? true : false;
		$stretched = in_array('stretched', $styles) ? true : false;

		$block['type'] = 'image';
		$block['data']['url'] = $node->getElementsByTagName('img')->item(0)->getAttribute('src');
		$block['data']['caption'] = $this->setInnerHtml($node->getElementsByTagName('figcaption')->item(0));
		$block['data']['withBorder'] = $withBorder;
		$block['data']['withBackground'] = $withBackground;
		$block['data']['stretched'] = $stretched;
		
		return $block;
	}

	/**
	 * Warning Parser
	 * 
	 * @param object $node
	 * @param array $styles
	 * @return array
	 */
	private function parseWarning($node, $styles) {

		$block['type'] = 'warning';
		$block['data']['title'] = $node->getElementsByTagName('h4')->item(0)->nodeValue;
		$block['data']['message'] = $node->getElementsByTagName('p')->item(0)->nodeValue;
		
		return $block;
	}

} 
