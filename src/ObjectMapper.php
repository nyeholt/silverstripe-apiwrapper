<?php

namespace Symbiote\ApiWrapper;

use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\PaginatedList;
use SilverStripe\ORM\SS_List;
use SilverStripe\View\ViewableData;

class ObjectMapper
{
    /**
     * A mapping of class type => data fields.
     *
     * Ensure this is defined in most specific to least specific class;
     * it'll be looked up top to bottom for an is_a check
     */
    public $mapping = [
        DataObject::class => [
            'ID' => 'id',
            'Title' => 'title',
            'Content' => 'content'
        ]
    ];

    /**
     * @return array
     */
    public function mapObject($object)
    {
        if (is_array($object) || $object instanceof SS_List) {
            return $this->mapList($object);
        }

        $mapping = [];
        $item = [];

        foreach ($this->mapping as $class => $fieldMap) {
            if (is_a($object, $class)) {
                $mapping = $fieldMap;
                break;
            }
        }
        foreach ($mapping as $field => $name) {
            $value = $object->$field;
            if ($value instanceof ViewableData) {
                $value = $this->mapObject($value);
            } else if (is_array($value) || $value instanceof SS_List) {
                $value = $this->mapList($value);
            }
            $item[$name] = $value;
        }

        return $item;
    }

    /**
     * @return array
     */
    public function mapList($list)
    {
        $newList = [];
        foreach ($list as $item) {
            $newList[] = $item instanceof DataObject ? $this->mapObject($item) : $item;
        }

        $items = [
            'items' => $newList,
        ];

        if ($list instanceof PaginatedList) {
            $items['total'] = $list->getTotalItems();
            $items['perpage'] = $list->getPageLength();
            $items['current_page'] = $list->CurrentPage();
            $items['total_pages'] = $list->TotalPages();
        }


        return $items;
    }
}
