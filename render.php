<?php

require_once(__DIR__ . '/libs/twig/lib/Twig/Autoloader.php');
require_once(__DIR__ . '/libs/twig-extensions/lib/Twig/Extensions/Autoloader.php');
//require_once('libs/twig-extensions/lib/Twig/Extensions/Autoloader.php');
require_once(__DIR__ . '/libs/simpledom/SimpleDOM.php');

$_setlocale_locale = 'en';
$_setlocale_data = require(__DIR__ . '/locale.php');

function _setlocale($locale) {
    global $_setlocale_locale;
    $_setlocale_locale = $locale;
}

function _gettext($text) {
    global $_setlocale_locale, $_setlocale_data;
    $ref = &$_setlocale_data[$text][$_setlocale_locale];
    return isset($ref) ? $ref : $text;
}

function getLangText($xml, $base, $defaultLocale) {
	$locales = array_unique(array($defaultLocale, 'en', 'es', 'ja'));
	foreach ($locales as $locale) {
		$elements = $xml->xpath("{$base}[@lang='{$locale}']");
		if (count($elements)) break;
	}
	if (!count($elements)) return new SimpleXmlElement('<e></e>');
	return $elements[0];
}

function getTranslatedLevel($level, $defaultLocale) {
	$locales = array_unique(array($defaultLocale, 'en', 'es', 'ja'));
	static $trans = array(
		'es' => array(
			'expert' => 'Experto',
			'advanced' => 'Avanzado',
			'medium' => 'Medio',
			'basic' => 'Básico',
			'native' => 'Nativo',
			'elemental' => 'Elemental',
		),
		'en' => array(
			'expert' => 'Expert',
		),
		'ja' => array(
		),
	);
	foreach ($locales as $locale) {
		if (isset($trans[$locale][$level])) return $trans[$locale][$level];
	}
	return $level;
}

function normalizeId($id) {
	return strtolower(trim($id));
}

function parseDate($date) {
	if (empty($date)) return array();
	return array_map('trim', explode(',', $date));
}

class LabelType {
	public $id;
	public $title;
	
	public $labels = array();
}

class Root {
	public $title;
	
	public $header;

	// array<Section>
	public $sections = array();
	
	public $labelTypes = array();
}

class Level {
	public $title;
}

class Image {
	public $alt;
	public $url;
	public $width;
	public $height;
}

class Section {
    public $id;
	public $title;
	public $logos = array();
	public $entries = array();
}

class Entry {
	public $dates = array();
	public $labels = array();
	public $images = array();
    public $imageSet = array();
    public $imageSetName = '';
	public $links = array();
    public $videos = array();
	public $summary;
}

class Link {
    public $index;
    public $url;
    public $title;
}

class Video {
    public $uid;
    public $url;
    public $thumb;
    public $title;
}

class Label {
	public $name;
	public $type;
	public $title;
	public $usageCount;
}

function minitemplate($text, $vars = array()) {
	return preg_replace_callback('@{{\\s*(\\w+)\\s*}}@', function($matches) use (&$vars) {
		$key = strtolower(trim($matches[1]));
		if (isset($vars[$key])) {
			return $vars[$key];
		}
		return $matches[0];
	}, $text);
}

function render($locale = 'es') {
	$xml = simpledom_load_string(file_get_contents(__DIR__ . '/info.xml'));

	$root = new Root();
	
	$now = new DateTime('now');
	$start = new DateTime('1986-09-06 00:00:00');

    $infoHeader = $xml->xpath('/info/header');
	$root->header = minitemplate(getLangText($infoHeader[0], 'summary', $locale)->innerXML(), array(
		'age' => $now->diff($start)->format('%y'),
	));
    $infoTitle = $xml->xpath('/info/title');
	$root->title = $infoTitle[0]->innerXML();
	
	$types = array();
	$labels = array();
	$entries = array();

	foreach ($xml->xpath('/info/labels/category') as $xmlCategory) {
		$categoryType = normalizeId((string)$xmlCategory->attributes()->id);
		$labelType = $root->labelTypes[] = new LabelType();
		$labelType->id = $categoryType;
		$labelType->title = getLangText($xmlCategory, 'summary', $locale);
		
		foreach ($xmlCategory->xpath('label') as $xmlLabel) {
			$label = $labelType->labels[] = new Label();
			$label->name = normalizeId((string)$xmlLabel->attributes()->id);
			$label->type = $categoryType;
			$label->title = (string)getLangText($xmlLabel, 'summary', $locale);
			$label->usageCount = 0;

			$labels[$label->name] = $label;
		}
	}

	//print_r($labels);
	//exit;
	
	foreach ($xml->xpath('/info/types/type') as $xmlType) {
		$types[(string)$xmlType->attributes()->id] = $xmlType;
	}

	foreach ($xml->xpath('/info/entries/category') as $xmlCategory) {
		$categoryType = (string)$xmlCategory->attributes()->type;
		foreach ($xmlCategory->xpath('entry') as $xmlEntry) {
			$entries[$categoryType][] = $xmlEntry;
		}
	}
	
	//print_r($entries); exit;
	
	foreach ($xml->xpath('/info/render/type') as $xmlTypeToRender) {
		$typeToRenderAttributes = $xmlTypeToRender->attributes();
		$type_id = (string)$typeToRenderAttributes->id;
		
		$title = getLangText($types[$type_id], 'summary', $locale);
		
		//print_r((string)$title);
		
		$section = $root->sections[] = new Section();
		$section->title = $title;
        $section->id = $type_id;
		
		foreach ($entries[$type_id] as $xmlEntry) {
			$entry = new Entry();
            $section->entries[] = $entry;

			$entry->title = getLangText($xmlEntry, 'title', $locale);
			
			foreach (parseDate($xmlEntry->attributes()->date) as $date) {
				$entry->dates[] = $date;
			}
			
			foreach ($xmlEntry->xpath('logo') as $xmlLogo) {
				$logo = new Image();
                $entry->logos[] = $logo;
				$logo->alt = $entry->title;
				$logo->url = 'i/' . (string)$xmlLogo;
				list($logo->width, $logo->height) = getimagesize(__DIR__ . '/' . $logo->url);
			}

			foreach ($xmlEntry->xpath('level') as $xmlLevel) {
				$level = new Level();
                $entry->levels[] = $level;
                $level->title = getTranslatedLevel((string)$xmlLevel, $locale);
			}

			foreach ($xmlEntry->xpath('image') as $xmlImage) {
				$image = new Image();
                $entry->images[] = $image;
                $image->alt = $entry->title;
				$image->url = 'i/' . (string)$xmlImage;
				list($image->width, $image->height) = getimagesize(__DIR__ . '/' . $image->url);
			}
            
			foreach ($xmlEntry->xpath('image_set') as $xmlImageSet) {
                $entry->imageSetName = basename((string)$xmlImageSet);
                $baseUrl = 'i/sets/' . basename((string)$xmlImageSet);
                $imageSetFolder = __DIR__ . '/' . $baseUrl;
                foreach (glob($imageSetFolder . '/*') as $imageName) {
                    $baseImageName = basename($imageName);
                    $fullImageUrl = $baseUrl . '/' . $baseImageName;
                    $fullImageLocalPath = $imageSetFolder . '/' . $baseImageName;
                    
                    $image_icon_base_path = 'iicon/' . basename((string)$xmlImageSet) . '/' . $baseImageName;
                    $iconLocalPath = __DIR__ . '/' . $image_icon_base_path;
                    if (!is_file($iconLocalPath)) {
                        $size = '512x96';
                        @mkdir(dirname($iconLocalPath), 0777, true);
                        //`convert -strip -thumbnail {$size}^ -gravity center -extent {$size} -quality 85 "{$fullImageLocalPath}"[0] "jpg:{$iconLocalPath}"`;
                        `convert -strip -thumbnail "{$size}" quality "90" "{$fullImageLocalPath}"[0] "jpg:{$iconLocalPath}"`;
                    }
                    
				    $image = $entry->imageSet[] = new Image();
				    $image->alt = $entry->title;
				    $image->url = $fullImageUrl;
                    $image->iconUrl = '/' . $image_icon_base_path;
                    list($image->iconWidth, $image->iconHeight) = getimagesize($iconLocalPath);
				    list($image->width, $image->height) = getimagesize($fullImageLocalPath);
                }
                //echo $imageSetFolder;
                //exit;
			}

			$entry->summary = getLangText($xmlEntry, 'summary', $locale)->innerXML();
			
			$entryLabels = explode(',', (string)$xmlEntry->attributes()->labels);
			
			foreach ($entryLabels as $labelName) {
				$labelName = normalizeId($labelName);
				if ($labelName == '') continue;

				if (!isset($labels[$labelName])) {
					print_r(array_keys($labels));
					throw(new Exception("Label '{$labelName}' not defined"));
				}
				$label = $labels[$labelName];
				$label->usageCount++;
				$entry->labels[] = $label;
			}

            foreach ($xmlEntry->xpath('youtube') as $k => $xmlLink) {
                $video = new Video();
                $entry->videos[] = $video;
                $video->uid = (string)$xmlLink;
                $video->url = "http://www.youtube.com/watch?v=" . $video->uid;
                $video->thumb = "//i1.ytimg.com/vi/" . $video->uid . "/mqdefault.jpg";
            }

			foreach ($xmlEntry->xpath('link') as $k => $xmlLink) {
				$link = new Link();
                $entry->links[] = $link;
                $link->url = (string)$xmlLink;
				$link->index = $k + 1;
				$link->title = '[' . ($link->index) . ']';
			}
			//print_r($xmlEntry);
		}
		//print_r($entries[$type_id]);
	}
	
    // Sort labels
    foreach ($root->labelTypes as &$labelType) {
        usort($labelType->labels, function(Label $a, Label $b) {
            return $b->usageCount - $a->usageCount;
        });
    }

	$languages = array(
		array('id' => 'es', 'url' => '/es', 'title' => 'Español'),
		array('id' => 'en', 'url' => '/en', 'title' => 'English'),
		//array('id' => 'ja', 'url' => '/ja', 'title' => '日本語'),
	);

	// TWIG
	Twig_Autoloader::register();
    Twig_Extensions_Autoloader::register();
	$loader = new Twig_Loader_Filesystem(__DIR__ . '/templates');
    //$loader->
    //Twig_Extensions_Extension_I18n
	$twig = new Twig_Environment($loader, array(
		//'cache' => __DIR__ . '/cache/twig',
	));
    _setlocale($locale);
    $twig->addFilter('trans', new Twig_Filter_Function('_gettext'));
	$out = $twig->loadTemplate('index.html')->render(array(
		'root' => $root,
        'language' => $locale,
		'languages' => $languages,
	));

	return
		"<!DOCTYPE html>\n" .
		tidy_parse_string(
			$out,
			array(
				'indent' => true,
				'output-xhtml' => true,
				'wrap' => 200
			), 'UTF8'
		)->html()->value
	;
}