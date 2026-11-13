<?php

declare(strict_types=1);

namespace Devoda\BlockDeveloper\BlockDeveloper\Block\Generator;

class XmlSchema
{
    /**
     * @var string
     */
    protected $xmlVersion = '1.0';

    /**
     * @var string
     */
    protected $schemaVersion = '0.3';

    /**
     * @param array $tables
     *
     * @return string
     */
    public function generate($tables = [])
    {
        $xml = sprintf('<?xml version="%s"?>' . PHP_EOL . '<schema version="%s">', $this->xmlVersion, $this->schemaVersion);
        foreach ($tables as $k => $v) {
            $fields = $this->generateFields($v);
            if (!empty($fields)) {
                $xml .= PHP_EOL . PHP_EOL;
                $xml .= $this->getTabs(1);
                $xml .= vsprintf('<table name="%s">%s</table>', [
                    $k,
                    PHP_EOL . $this->getTabs(2) . implode(PHP_EOL . $this->getTabs(2), $fields) . PHP_EOL . $this->getTabs(1),
                ]);
            }
        }
        $xml .= PHP_EOL . PHP_EOL . '</schema>' . PHP_EOL;

        return $xml;
    }

    /**
     * @param $v
     *
     * @return array
     */
    public function generateFields($v)
    {
        $fields = [];
        if (isset($v['fields']) && is_array($v['fields']) && !empty($v['fields'])) {
            foreach ($v['fields'] as $field) {
                if (is_array($field) && isset($field['name'], $field['type'])) {
                    $inside = [];
                    if (isset($field['attributes']) && is_array($field['attributes'])) {
                        foreach ($field['attributes'] as $attributeKey => $attributeValue) {
                            switch (strtoupper($attributeKey)) {
                                case 'DEFDATE':
                                case 'DEFTIMESTAMP':
                                case 'NOQUOTE':
                                case 'CONSTRAINTS':
                                case 'AUTOINCREMENT':
                                case 'KEY':
                                case 'PRIMARY':
                                    if ((bool) $attributeValue === true) {
                                        $inside[] = sprintf('<%s/>', strtolower($attributeKey));
                                    }
                                    break;
                                case 'DEF':
                                case 'DEFAULT':
                                    $inside[] = sprintf('<default value="%s"/>', $attributeValue);
                                    break;
                                case 'UNSIGNED':
                                case 'NOTNULL':
                                    if ((bool) $attributeValue === true) {
                                        $inside[] = '<unsigned/>';
                                    }
                                    break;
                                default:
                                    break;
                            }
                        }
                    }
                    $size = isset($field['size']) && trim($field['size']) != '' ? sprintf(' size="%s"', $field['size']) : null;
                    $inside = !empty($inside) ? PHP_EOL . $this->getTabs(3) . implode(PHP_EOL . $this->getTabs(3), $inside)
                        . PHP_EOL . $this->getTabs(2) : null;
                    $fields[] = vsprintf(
                        '<field name="%s" type="%s"%s>%s</field>',
                        [
                            $field['name'], $field['type'], $size, $inside,
                        ]
                    );
                }
            }
        }

        return $fields;
    }

    /**
     * @return string
     */
    public function getXmlVersion()
    {
        return $this->xmlVersion;
    }

    /**
     * @param string $xmlVersion
     */
    public function setXmlVersion($xmlVersion)
    {
        $this->xmlVersion = $xmlVersion;
    }

    /**
     * @return string
     */
    public function getSchemaVersion()
    {
        return $this->schemaVersion;
    }

    /**
     * @param string $schemaVersion
     */
    public function setSchemaVersion($schemaVersion)
    {
        $this->schemaVersion = $schemaVersion;
    }

    /**
     * @param int $count
     *
     * @return string
     */
    protected function getTabs($count = 1)
    {
        $return = '';
        if ($count >= 1) {
            for ($i = 1; $i <= $count; ++$i) {
                $return .= '    ';
            }
        }

        return $return;
    }
}
