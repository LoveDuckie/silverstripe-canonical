<?php

namespace LoveDuckie\SilverStripe\Canonical;

use SilverStripe\ORM\DataExtension;
use SilverStripe\Forms\TextField;
use SilverStripe\Forms\FieldList;


class ConfigCanonicalExtension extends DataExtension
{
    private static $db = [
        'CanonicalDomain' => 'Varchar(255)'
    ];

    /**
     * @param FieldList $fields
     * @return FieldList
     */
    public function updateCMSFields(FieldList $fields): FieldList
    {
        $CanonicalDomainField = TextField::create('CanonicalDomain')
            ->setDescription(_t(__CLASS__ . '.InfoField', 'The canonical domain will be added to the HTML head. It can be overriden per Page in ') . _t('SilverStripe\CMS\Model\SiteTree.MetadataToggle', 'Metadata') . '.')
            ->setAttribute('placeholder', _t(__CLASS__ . '.CanonicalDomainDescription', 'https://www.example.com'));

        $fields->addFieldToTab('Root.Canonical', $CanonicalDomainField);

        return $fields;
    }
}
