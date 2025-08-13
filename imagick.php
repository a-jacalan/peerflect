<?php
function debugConversion($inputFile, $outputDir) {
    // Detailed logging of conversion process
    $commands = [
        'PDF Conversion' => sprintf(
            'soffice --headless --convert-to pdf:writer_pdf_Export --outdir %s %s',
            escapeshellarg($outputDir),
            escapeshellarg($inputFile)
        ),
        'Image Conversion' => sprintf(
            'convert -verbose -density 300 %s -quality 90 %s',
            escapeshellarg($outputDir . basename($inputFile, '.pptx') . '.pdf'),
            escapeshellarg($outputDir . 'slide_%03d.jpg')
        )
    ];

    $logFile = $outputDir . 'conversion_debug.log';
    
    foreach ($commands as $stage => $command) {
        $fullCommand = $command . " 2>&1 >> " . escapeshellarg($logFile);
        exec($fullCommand, $output, $returnVar);
        
        echo "{$stage} Command: {$command}\n";
        echo "Return Code: {$returnVar}\n";
        echo "Output: " . print_r($output, true) . "\n\n";
    }

    // Read and display log contents
    if (file_exists($logFile)) {
        echo "Conversion Log Contents:\n";
        echo file_get_contents($logFile);
    }
}
?>