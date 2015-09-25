<?php

namespace Tests\TestCase\Functions;

require_once 'autoload.php';

class Pdf extends \PHPUnit_Framework_TestCase
{
    public function test()
    {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_URL, 'https://getcomposer.org/doc/01-basic-usage.md');

        $html = curl_exec($ch);

        $filePath = tempnam(sys_get_temp_dir(), 'tests') . '.html';
        file_put_contents($filePath, $html);

        curl_close($ch);

        $pdfTool = new \IFR\Main\Tools\Pdf();

        $options = [
            'load-error-handling'   => 'ignore',
            'footer-font-size'      => 10,
            'footer-center'         => '[page] / [topage]',
        ];

        $pdf = $pdfTool->getRawPdfFromHtml($html, $options);
        assertTrue(null != $pdf);

        $filePath = dirname($filePath) . DIRECTORY_SEPARATOR . basename($filePath, '.html') . '.pdf';
        file_put_contents($filePath, $pdf);

    }
}

