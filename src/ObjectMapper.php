<?php

namespace Symbiote\ApiWrapper;

use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\PaginatedList;
use SilverStripe\ORM\SS_List;
use SilverStripe\View\ArrayData;
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

        // if we're given array data, we take the assumption
        // that the mapping is going to be all keys go to values,
        // so we map accordingly; this allows the subsequent
        // value mapping to happen too
        if ($object instanceof ArrayData) {
            $data = $object->toMap();
            $mapping = array_combine(array_keys($data), array_keys($data));
        } else {
            foreach ($this->mapping as $class => $fieldMap) {
                if (is_a($object, $class)) {
                    $mapping = $fieldMap;
                    break;
                }
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
        $isObject = false;
        foreach ($list as $key => $item) {
            if (is_string($key)) {
                $isObject = true;
            }
            $newList[$key] = ($item instanceof DataObject || $item instanceof ArrayData) ? $this->mapObject($item) : $item;
        }

        if ($isObject) {
            return $newList;
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
