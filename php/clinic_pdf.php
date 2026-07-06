<?php

function clinic_mpdf_available() {
  return is_file(__DIR__ . '/../vendor/autoload.php');
}

function clinic_send_pdf_html($html, $filename) {
  if (!clinic_mpdf_available()) {
    header('Content-Type: text/plain; charset=UTF-8');
    echo 'Run composer install in the project root to enable PDF export.';
    exit;
  }
  require_once __DIR__ . '/../vendor/autoload.php';
  $tmp = __DIR__ . '/../storage/mpdf_tmp';
  if (!is_dir($tmp)) {
    mkdir($tmp, 0775, true);
  }
  $mpdf = new \Mpdf\Mpdf([
    'format' => 'A4',
    'tempDir' => $tmp,
    'margin_left' => 12,
    'margin_right' => 12,
    'margin_top' => 14,
    'margin_bottom' => 14,
  ]);
  $mpdf->WriteHTML($html);
  $safe = preg_replace('/[^a-zA-Z0-9_.-]+/', '_', $filename);
  if ($safe === '') {
    $safe = 'document.pdf';
  }
  if (substr($safe, -4) !== '.pdf') {
    $safe .= '.pdf';
  }
  $mpdf->Output($safe, \Mpdf\Output\Destination::INLINE);
  exit;
}
