<?php

declare(strict_types=1);

namespace Helpers;

/*
echo App::make(\Helpers\HTML::class)->allow_only('This is <br> and so on', array("br"));

echo App::make(\Helpers\HTML::class)->allow_only('This is <b>bold</b> and <a href="http://www.#links">this</a> is <i>italic</i>.', array("b","a"));
*/

class HTML
{
    public function allow_only($str, $allowed)
    {
        $options = [
            [
                'HTML.Allowed',
                implode(',', $allowed),
            ],
            // Format end output with Tidy
            [
                'Output.TidyFormat',
                true,
            ],
            // Assume XHTML 1.0 Strict Doctype
            [
                'HTML.Doctype',
                'XHTML 1.0 Strict',
            ],
            // Disable cache, but see note after the example
            [
                'Cache.DefinitionImpl',
                null,
            ],
        ];

        $config = \HTMLPurifier_Config::createDefault();
        foreach ($options as $option) {
            $config->set($option[0], $option[1]);
        }

        $purifier = new \HTMLPurifier($config);

        return $purifier->purify($str);
    }
}
