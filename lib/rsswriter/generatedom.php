<?php

namespace Varrcan\Yaturbo\RssWriter;

use DOMDocument;

/**
 * Class GenerateDom
 * @package Varrcan\Yaturbo\RssWriter
 */
class GenerateDom
{
    /**
     * @var string
     */
    private $encoding = 'UTF-8';
    /**
     * @var DomDocument
     */
    private $xmlDocument;

    /**
     * Создать XML
     *
     * @param       $rootName
     * @param array $arData
     *
     * @return DomDocument
     */
    public function create($rootName, array $arData)
    {
        $xml = $this->getXMLRoot();

        $xml->appendChild($this->convert($rootName, $arData));
        $this->xmlDocument = null;

        return $xml;
    }

    /**
     * Конвертация массива в XML
     *
     * @param $node_name
     * @param $arr
     *
     * @return \DOMElement
     */
    private function convert($node_name, $arr)
    {
        $xml  = $this->getXMLRoot();
        $node = $xml->createElement($node_name);

        if (\is_array($arr)) {
            if (array_key_exists('@attributes', $arr) && is_array($arr['@attributes'])) {
                foreach ($arr['@attributes'] as $key => $value) {
                    if (!$this->isValidTagName($key)) {
                        throw new \RuntimeException('Illegal character in attribute name. Attribute: ' . $key . ' in node: ' . $node_name);
                    }
                    $node->setAttribute($key, $value);
                }
                unset($arr['@attributes']);
            }

            if (array_key_exists('@value', $arr)) {
                // Хак для содержимого, чтобы не экранировались теги
                $rawXMLNode = $xml->createDocumentFragment();
                $rawXMLNode->appendXML($arr['@value']);
                $node->appendChild($rawXMLNode);
                //$node->appendChild($xml->createTextNode($arr['@value']));
                unset($arr['@value']);
            }

            if (array_key_exists('@cdata', $arr)) {
                $node->appendChild($xml->createCDATASection($arr['@cdata']));
                unset($arr['@cdata']);
            }
        }

        if (\is_array($arr)) {
            foreach ($arr as $key => $value) {
                if (!$this->isValidTagName($key)) {
                    throw new \RuntimeException('Illegal character in tag name. Tag: ' . $key . ' in node: ' . $node_name);
                }
                if (is_array($value) && is_numeric(key($value))) {
                    foreach ($value as $k => $v) {
                        $node->appendChild($this->convert($key, $v));
                    }
                } else {
                    $node->appendChild($this->convert($key, $value));
                }
                unset($arr[$key]);
            }
        } else {
            $node->appendChild($xml->createTextNode((string)$arr));
        }

        return $node;
    }

    /**
     * Получить корневой XML тег, если не существует - создать
     *
     * @return DomDocument
     */
    private function getXMLRoot()
    {
        if (!$this->xmlDocument) {
            $this->init();
        }

        return $this->xmlDocument;
    }

    /**
     * Инициализация корневого XML тега
     *
     * @param string $version
     * @param string $encoding
     * @param bool   $format_output
     */
    public function init($version = '1.0', $encoding = 'UTF-8', $format_output = true)
    {
        $this->xmlDocument               = new DomDocument($version, $encoding);
        $this->xmlDocument->formatOutput = $format_output;
        $this->encoding                  = $encoding;
    }

    /**
     * Проверка тегов на валидность
     *
     * @param string $tag
     *
     * @return bool
     */
    private function isValidTagName($tag)
    {
        $pattern = '/^[a-z_]+[a-z0-9\:\-\.\_]*[^:]*$/i';

        return preg_match($pattern, $tag, $matches) && $matches[0] === $tag;
    }
}
