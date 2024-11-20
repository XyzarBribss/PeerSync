<?php
require_once('config.php');
require_once('tcpdf/tcpdf.php');  

if (isset($_POST['notebook_id'])) {
    $notebook_id = $_POST['notebook_id'];

    
    $queryNotebook = "SELECT * FROM notebooks WHERE id = ?";
    $stmtNotebook = $conn->prepare($queryNotebook);
    
    if ($stmtNotebook === false) {
        die("Error preparing query: " . $conn->error);
    }

    $stmtNotebook->bind_param("i", $notebook_id);

    $stmtNotebook->execute();
    $resultNotebook = $stmtNotebook->get_result();
    
    $notebook = $resultNotebook->fetch_assoc();

    if ($notebook) {
        $pdf = new TCPDF();

        $pdf->SetCreator('PeerSync');
        $pdf->SetAuthor('PeerSync');
        $pdf->SetTitle('Notebook Export');
        $pdf->SetSubject('Export of Notebook');

        $pdf->AddPage();

        $pdf->SetFont('helvetica', 'B', 16);
        $pdf->Cell(0, 10, 'Notebook: ' . $notebook['name'], 0, 1, 'C');
        $pdf->Ln(10);

        $queryNotes = "SELECT * FROM notes WHERE NotebookID = ?";
        $stmtNotes = $conn->prepare($queryNotes);
        
        if ($stmtNotes === false) {
            die("Error preparing query: " . $conn->error);
        }

        $stmtNotes->bind_param("i", $notebook_id);

        $stmtNotes->execute();
        $resultNotes = $stmtNotes->get_result();

        if ($resultNotes->num_rows > 0) {
            $pdf->SetFont('helvetica', '', 12);
            while ($note = $resultNotes->fetch_assoc()) {
                $pdf->SetFont('helvetica', 'B', 12);
                $pdf->Cell(0, 10, 'Title: ' . $note['Title'], 0, 1);

                $pdf->SetFont('helvetica', '', 12);
                $content = $note['Content'];

                $pdf->writeHTML($content, true, false, true, false, '');

                $pdf->Ln(5);
            }
        } else {
            $pdf->Cell(0, 10, 'No notes available for this notebook.', 0, 1, 'C');
        }

        $pdf->Output('notebook_' . $notebook['id'] . '.pdf', 'D');
    } else {
        echo "No notebook found with the given ID.";
    }
} else {
    echo "No notebook selected.";
}

$conn->close();
?>
