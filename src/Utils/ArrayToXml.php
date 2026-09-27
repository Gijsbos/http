<?php
declare(strict_types=1);

namespace gijsbos\Http\Utils;

use SimpleXMLElement;

/**
 * ArrayToXml
 *  Keys become elements, list entries become repeated <item> elements. Values are escaped, booleans become
 *  true/false (as in JSON).
 */
class ArrayToXml
{
    /**
     * convert
     *  Into $xml when given, otherwise into a new <root> element
     */
    public static function convert(array $data, null|SimpleXMLElement $xml = null) : SimpleXMLElement
    {
        $xml ??= new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><root/>');

        foreach($data as $key => $value)
        {
            if(is_array($value))
            {
                $child = $xml->addChild((string) $key);

                if(array_is_list($value))
                {
                    foreach($value as $item)
                    {
                        if(is_array($item))
                            self::convert($item, $child->addChild("item"));
                        else
                            $child->addChild("item", htmlspecialchars(self::scalarToString($item)));
                    }
                }
                else
                {
                    self::convert($value, $child);
                }
            }
            else
            {
                $xml->addChild((string) $key, htmlspecialchars(self::scalarToString($value)));
            }
        }

        return $xml;
    }

    /**
     * scalarToString
     */
    private static function scalarToString(mixed $value) : string
    {
        return is_bool($value) ? ($value ? "true" : "false") : (string) $value;
    }
}
