<?php

namespace LoveDuckie\SilverStripe\Canonical;

use SilverStripe\View\HTML;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\TextField;
use SilverStripe\Forms\LiteralField;
use SilverStripe\SiteConfig\SiteConfig;
use SilverStripe\CMS\Model\SiteTreeExtension;

use SilverStripe\Core\Config\Config;

use SilverStripe\Control\Controller;

use Exception;

class SiteTreeCanonicalExtension extends SiteTreeExtension
{
    private static $db = [
        'CanonicalURL' => 'Text'
    ];

    /**
     * @param FieldList $fields
     * @return void
     * @throws Exception
     */
    public function updateCMSFields(FieldList $fields)
    {
        if ($MetaToggle = $fields->fieldByName('Root.Main.Metadata')) {
            if ($url = $this->getOrSetCanonicalURL()) {
                $MetaToggle->push($MetaCanonical = TextField::create('CanonicalURL', _t(__CLASS__ . '.LinkOverride', "Override canonical URL")));
                $MetaCanonical
                    ->setAttribute('placeholder', $this->getOrSetCanonicalURL())
                    ->setDescription(_t(__CLASS__ . '.LinkOverrideDesc', 'Only set this if another URL should count as the original (e.g. of reposting a blog post from another source).'));
            } else {
                $MetaToggle->push($MetaCanonical = LiteralField::create("CanonicalURL", '<p class="form__field-label">' . _t(__CLASS__ . '.LinkFieldPlaceholder', 'Canonical-URLs needs a Canoinical-Domain in <a href="/admin/settings">SiteConfig</a>') . '</p>'));
            }
            $MetaCanonical->setRightTitle(_t(__CLASS__ . '.LinkFieldRightTitle', 'Used to identify the original resource (URL) to prevent being considered as "duplicate content".'));
        }
    }

    /**
     * @param string $url
     * @return bool
     * @throws Exception
     */
    private function isUrlAbsolute(string $url)
    {
        if (!$url) {
            throw new Exception("The URL specified is invalid or null");
        }
        $urlArray = parse_url($url);
        return isset($urlArray['host']) && isset($urlArray['scheme']);
    }

    private function getOrSetCanonicalURL()
    {
        $className = static::class;
        $siteConfig = SiteConfig::current_site_config();
        $config = Config::inst()->get($className);
        if (!$config) {
            throw new Exception("Failed to find the configuration for \"$className\"");
        }
        $controller = Controller::curr();
        if (!filter_var($siteConfig->CanonicalDomain, FILTER_VALIDATE_URL)) {
            return;
        }

        $link = null;
        $canonicalBase = trim($siteConfig->CanonicalDomain, '/');

        if ($this->owner->hasMethod('CanonicalLink')) {
            $link = $this->owner->CanonicalLink();
        } else if ($this->owner->hasField('CanonicalURL') && isset($this->owner->CanonicalURL) && $this->owner->CanonicalURL != null) {
            $link = $this->owner->CanonicalURL;
        }

        if ($link && !$this->isUrlAbsolute($link)) {
            $link = $canonicalBase . $link;
        } else {
            $link = $canonicalBase . $this->owner->Link();
        }

        return $link;
    }

    public function MetaTags(&$tags)
    {
        if (!$canonLink = $this->getOrSetCanonicalURL()) {
            return;
        }

        $attributes = [
            'rel' => 'canonical',
            'href' => $canonLink
        ];
        $canonTag = HTML::createTag('link', $attributes);

        $tagsArray = explode(PHP_EOL, $tags);
        $tagPattern = 'rel="canonical"';

        $tagSearch = function ($val) use ($tagPattern) {
            return (stripos($val, $tagPattern) !== false ? true : false);
        };

        $currentTags = array_filter($tagsArray, $tagSearch);
        $cleanedTags = array_diff($tagsArray, $currentTags);

        $cleanedTags[] = $canonTag;

        $tags = implode(PHP_EOL, $cleanedTags);
    }
}
