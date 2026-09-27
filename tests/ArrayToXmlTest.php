<?php
declare(strict_types=1);

namespace gijsbos\Http\Utils;

use PHPUnit\Framework\TestCase;

final class ArrayToXmlTest extends TestCase
{
    public function testRootIsRoot()
    {
        $this->assertSame("root", ArrayToXml::convert(["a" => "1"])->getName());
    }

    public function testScalarsBecomeChildElements()
    {
        $xml = ArrayToXml::convert(["name" => "alice", "age" => 30, "active" => true, "deleted" => false]);

        $this->assertSame("alice", (string) $xml->name);
        $this->assertSame("30", (string) $xml->age);
        $this->assertSame("true", (string) $xml->active);
        $this->assertSame("false", (string) $xml->deleted);
    }

    public function testNestedAssociativeArraysBecomeNestedElements()
    {
        $xml = ArrayToXml::convert(["user" => ["name" => "alice", "address" => ["city" => "Utrecht"]]]);

        $this->assertSame("alice", (string) $xml->user->name);
        $this->assertSame("Utrecht", (string) $xml->user->address->city);
    }

    public function testListsOfRecordsBecomeRepeatedItemElements()
    {
        $xml = ArrayToXml::convert(["users" => [["name" => "a"], ["name" => "b"]]]);

        $this->assertCount(2, $xml->users->item);
        $this->assertSame("a", (string) $xml->users->item[0]->name);
        $this->assertSame("b", (string) $xml->users->item[1]->name);
    }

    public function testSpecialCharactersRoundTrip()
    {
        $reparsed = simplexml_load_string(ArrayToXml::convert(["text" => 'a < b & "c"'])->asXML());

        $this->assertNotFalse($reparsed, "output must be well-formed XML");
        $this->assertSame('a < b & "c"', (string) $reparsed->text);
    }

    public function testIntoExistingElementAppendsToIt()
    {
        $existing = new \SimpleXMLElement("<envelope/>");

        $result = ArrayToXml::convert(["a" => "1"], $existing);

        $this->assertSame($existing, $result);
        $this->assertSame("1", (string) $existing->a);
    }

    public function testEmptyArrayGivesEmptyRoot()
    {
        $this->assertCount(0, ArrayToXml::convert([])->children());
    }

    public function testListsOfScalarsAreSupported()
    {
        $xml = ArrayToXml::convert(["tags" => ["a", "b"]]);

        $this->assertCount(2, $xml->tags->item);
        $this->assertSame("a", (string) $xml->tags->item[0]);
        $this->assertSame("b", (string) $xml->tags->item[1]);
    }

    public function testScalarListItemsAreEscapedAndCastToStrings()
    {
        $reparsed = simplexml_load_string(ArrayToXml::convert(["values" => [1, 2.5, true, 'a & b']])->asXML());

        $this->assertNotFalse($reparsed);
        $this->assertSame(["1", "2.5", "true", "a & b"], array_map('strval', iterator_to_array($reparsed->values->item, false)));
    }
}
