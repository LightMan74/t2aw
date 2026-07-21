<?php
// header('Content-Type: image/png');
    include('php_qrcode/lib/full/qrlib.php');
    $data = $_GET['data'] ?? '';
    $dataText   = $data;
    $svgTagId   = 'id-of-svg';
    $saveToFile = false;
    $imageWidth = 100; // px
    
    // SVG file format support
    $svgCode = QRcode::svg($dataText, $svgTagId, $saveToFile, QR_ECLEVEL_H, $imageWidth);
    // $svgCode = QRcode::png($dataText);
    
    echo $svgCode;
    
    