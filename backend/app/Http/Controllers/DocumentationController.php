<?php

namespace App\Http\Controllers;

use TCPDF;

class DocumentationController extends Controller
{
    public function exportPdf()
    {
        $jsonPath = storage_path('api-docs/api-docs.json');
        if (!file_exists($jsonPath)) {
            abort(500, 'API documentation JSON not found.');
        }
        $spec = json_decode(file_get_contents($jsonPath), true);

        $title = $spec['info']['title'] ?? 'API Documentation';
        $version = $spec['info']['version'] ?? '1.0.0';

        $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);

        $pdf->SetCreator('CAS Backend');
        $pdf->SetAuthor('Team');
        $pdf->SetTitle($title);

        $pdf->setHeaderData('', 0, $title . ' v' . $version, '');
        $pdf->setFooterData(array(0,0,0), array(0,0,0));

        $pdf->setHeaderFont(array('dejavusans', '', 9));
        $pdf->setFooterFont(array('dejavusans', '', 9));

        $pdf->SetMargins(20, 20, 20);
        $pdf->SetHeaderMargin(10);
        $pdf->SetFooterMargin(10);

        $pdf->SetPrintHeader(true);
        $pdf->SetPrintFooter(true);

        $pdf->SetFont('dejavusans', '', 10);
        $pdf->AddPage();

        $html = '<h1>' . $title . '</h1>';
        $html .= '<p><strong>Verzia:</strong> ' . $version . '</p>';
        $html .= '<p>' . ($spec['info']['description'] ?? '') . '</p>';
        $html .= '<hr>';

        foreach ($spec['paths'] as $path => $methods) {
            foreach ($methods as $method => $details) {
                $html .= '<h2>' . strtoupper($method) . ' ' . $path . '</h2>';
                $html .= '<p><strong>' . ($details['summary'] ?? '') . '</strong></p>';
                $html .= '<p>' . ($details['description'] ?? '') . '</p>';

                if (isset($details['requestBody']['content']['application/json']['schema']['properties'])) {
                    $props = $details['requestBody']['content']['application/json']['schema']['properties'];
                    $required = $details['requestBody']['content']['application/json']['schema']['required'] ?? [];
                    $html .= '<table border="1" cellpadding="5">';
                    $html .= '<tr><th>Parameter</th><th>Typ</th><th>Povinný</th><th>Predvolená</th><th>Popis</th></tr>';
                    foreach ($props as $name => $prop) {
                        $req = in_array($name, $required) ? 'Áno' : 'Nie';
                        $default = $prop['default'] ?? '-';
                        $html .= '<tr>';
                        $html .= '<td><code>' . $name . '</code></td>';
                        $html .= '<td>' . ($prop['type'] ?? '') . '</td>';
                        $html .= '<td>' . $req . '</td>';
                        $html .= '<td>' . $default . '</td>';
                        $html .= '<td>' . ($prop['description'] ?? '') . '</td>';
                        $html .= '</tr>';
                    }
                    $html .= '</table>';
                }

                if (!empty($details['responses'])) {
                    $html .= '<p><strong>Odpovede:</strong></p>';
                    $html .= '<table border="1" cellpadding="5">';
                    $html .= '<tr><th>Kód</th><th>Popis</th></tr>';
                    foreach ($details['responses'] as $code => $resp) {
                        $html .= '<tr>';
                        $html .= '<td>' . $code . '</td>';
                        $html .= '<td>' . ($resp['description'] ?? '') . '</td>';
                        $html .= '</tr>';
                    }
                    $html .= '</table>';
                }

                $html .= '<br><br>';
            }
        }

        $pdf->writeHTML($html, true, false, true, false, '');

        return response($pdf->Output('api-documentation.pdf', 'S'), 200)
            ->header('Content-Type', 'application/pdf');
    }
}