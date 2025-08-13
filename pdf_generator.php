<?php
require_once 'vendor/tecnickcom/tcpdf/tcpdf.php';

class PostPDF extends TCPDF {
    public function Header() {
        $this->SetFont('helvetica', 'B', 16);
        $this->Cell(0, 15, 'Study Material', 0, true, 'C');
        $this->Ln(10);
    }
    
    public function Footer() {
        $this->SetY(-15);
        $this->SetFont('helvetica', 'I', 8);
        $this->Cell(0, 10, 'Page '.$this->getAliasNumPage().'/'.$this->getAliasNbPages(), 0, false, 'C');
    }
}

function generatePostPDF($questions, $postID) {
    // Create new PDF document
    $pdf = new PostPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
    
    // Set document information
    $pdf->SetCreator('Your Website Name');
    $pdf->SetAuthor('Study Platform');
    $pdf->SetTitle('Study Material - Post #' . $postID);
    
    // Set default header data
    $pdf->SetHeaderData(PDF_HEADER_LOGO, PDF_HEADER_LOGO_WIDTH, PDF_HEADER_TITLE, PDF_HEADER_STRING);
    
    // Set margins
    $pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
    $pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
    $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);
    
    // Set auto page breaks
    $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
    
    // Add a page
    $pdf->AddPage();
    
    foreach($questions as $index => $question) {
        $questionNumber = $index + 1;
        
        // Question
        $pdf->SetFont('helvetica', 'B', 14);
        $pdf->Cell(0, 10, "Question $questionNumber", 0, 1);
        $pdf->SetFont('helvetica', '', 12);
        $pdf->writeHTML($question['QuestionContent'], true, false, true, false, '');
        
        // Question Image
        if(!empty($question['QuestionImageURL'])) {
            if(file_exists($question['QuestionImageURL'])) {
                $pdf->Image($question['QuestionImageURL'], null, null, 150);
            }
        }
        
        $pdf->Ln(10);
        
        // Answer
        $pdf->SetFont('helvetica', 'B', 14);
        $pdf->Cell(0, 10, 'Answer:', 0, 1);
        $pdf->SetFont('helvetica', '', 12);
        $pdf->writeHTML($question['AnswerContent'], true, false, true, false, '');
        
        // Answer Image
        if(!empty($question['AnswerImageURL'])) {
            if(file_exists($question['AnswerImageURL'])) {
                $pdf->Image($question['AnswerImageURL'], null, null, 150);
            }
        }
        
        $pdf->Ln(10);
        
        // Explanation
        $pdf->SetFont('helvetica', 'B', 14);
        $pdf->Cell(0, 10, 'Explanation:', 0, 1);
        $pdf->SetFont('helvetica', '', 12);
        $pdf->writeHTML($question['Explanation'], true, false, true, false, '');
        
        $pdf->Ln(15);
        
        // Add a page break between questions (except for the last question)
        if($index < count($questions) - 1) {
            $pdf->AddPage();
        }
    }
    
    return $pdf;
}
?>