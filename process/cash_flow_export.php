<?php
require '../vendor/autoload.php';

use Dompdf\Dompdf;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['html_content'])) {
    $html = '
    <html>
      <head>
        <style>
          table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
          th, td { border: 1px solid #000; padding: 5px; text-align: left; }
          h2, h3 { text-align: center; }
        </style>
      </head>
      <body>' . $_POST['html_content'] . '</body>
    </html>';

    // Prevent output issues
    if (ob_get_length()) ob_end_clean();

    $dompdf = new Dompdf();
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();
    $dompdf->stream("Cash_Flow.pdf", ["Attachment" => true]);
    exit;
}
