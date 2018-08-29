<?php
    define('sys_checksession', false);
	require_once '../../sys.php';
	require_once '../../lib/tcpdf/tcpdf.php';
	
	// params
	$rid = Sys::GetP('id', '');
	
	$q = new PgQuery("
	SELECT r.*, d.*,
	c.cliente_desc,
	top.tipope_desc,
	m.moneda_desc
	FROM public.registro r
	JOIN public.registro_det d ON d.registro_id = r.registro_id
	LEFT JOIN public.cliente c ON c.cliente_id = r.cliente_id
	JOIN public.tipope top ON top.tipope_id = r.tipope_id
	JOIN public.moneda m ON m.moneda_id = d.moneda_id
	WHERE r.registro_id LIKE '$id'
	ORDER BY d.registro_det_id 
	", null, true, true);
	
	$r = $q->row;
	
	//var_dump($r);
	//exit;
	
	/*class PDF extends FPDF
	{
		// Cabecera de página
		function Header()
		{
			global $r;
		    // Logo
		    $this->Image('logo.png',15,2,38);
		    // Arial bold 15
		    $this->SetFont('Arial','B',8);
		    // Título
		    $this->Cell(50,8,"CASA DE CAMBIO",0,0,'C');
		    // Salto de línea
		    $this->Ln(6);
		}
		
		// Pie de página
		/*function Footer()
		{
		    // Posición: a 1,5 cm del final
		    $this->SetY(-7);
		    /// Arial italic 8
		    $this->SetFont('Arial','I',6);
			//$this->SetTextColor(255,255,255);
		    // Número de página
		    $this->Cell(0,5,'Gracias por su preferencia',0,0,'C');
		}*/
		/*function AcceptPageBreak()
		{
			return false;
		}*/
	}*/
	$ph = 50;
	if ($r['cliente_id']!='00-0000000') { // dif. de publico general
		$ph +=5;
	}
	if ($r['registro_tcfactor']>0) { // contipo de cambio
		$ph +=5;
	}
	if ($r['registro_interes']>0) { // contipo de cambio
		$ph +=5;
	}
	$ph = $ph + ($q->recordCount*5);
	$pt = 'P';
	if ($ph <= 70)
		$pt = 'L';
    $tipope_desc = substr($r['tipope_desc'], 0,6);
	// Creación del objeto de la clase heredada
	//$pdf = new PDF('P','mm', array(70,80)); //array(70,100)
	$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, array(70,100), true, 'UTF-8', false);
    $pdf->SetCreator(PDF_CREATOR);
    $pdf->SetAuthor('Nicola Asuni');
    $pdf->SetTitle('TCPDF Example 028');
    $pdf->SetSubject('TCPDF Tutorial');
    $pdf->SetKeywords('TCPDF, PDF, example, test, guide');
    
    // remove default header/footer
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);
    
    // set default monospaced font
    $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
    
    // set margins
    $pdf->SetMargins(10, PDF_MARGIN_TOP, 10);
    
    // set auto page breaks
    $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
    
    // set image scale factor
    $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
	$pdf->AddPage();
	$pdf->SetMargins(4,1,4);
	$pdf->SetX(4);
	$pdf->SetFont('Arial','',7);
	$pdf->Cell(0,4,"Tipo: $tipope_desc",0,1,'L');
	$pdf->Cell(0,4,utf8_decode("N° Ope.: {$r['registro_id']}"),0,1,'L');
	$pdf->Cell(0,4,"Fecha: {$r['registro_fecha']}",0,1,'L');
	if ($r['cliente_id']!='00-0000000' && !is_null($r['cliente_id'])) { // dif. de publico general
		$pdf->Cell(0,4,"Cliente: {$r['cliente_desc']}",0,1,'L');
	}
	if ($r['registro_tcfactor']>0) { // contipo de cambio
		$pdf->Cell(0,4,"Tipo Cambio: {$r['registro_tcfactor']}",0,1,'L');
	}
	if ($r['registro_interes']>0) { // contipo de cambio
		$pdf->Cell(0,4,"Interes: {$r['registro_interes']}",0,1,'L');
	}
	$y = $pdf->GetY(); 
	$pdf->Line(4, $y+1, 66, $y+1);
	$pdf->SetY($pdf->GetY()+3);
	
	$pdf->Cell(30, 2,"Moneda",0,0,'L');
	$pdf->Cell(32, 2,"Importe",0,0,'R');
	$pdf->SetY($pdf->GetY()+3);
	
	$y = $pdf->GetY(); 
	$pdf->Line(4, $y+1, 66, $y+1);
	$pdf->SetY($pdf->GetY()+2);
	
	while ($d = $q->Read()) {
		$pdf->Cell(30,4,"{$d['moneda_desc']}".$i,0,0,'L');
		$pdf->Cell(32,4,Sys::NFormat(abs($d['registro_det_importe'])),0,1,'R');	
	}
	
	$y = $pdf->GetY(); 
	$pdf->Line(4, $y+1, 66, $y+1);
	$pdf->SetY($pdf->GetY()+2);
	$pdf->SetFont('Arial','',6);
	$pdf->Cell(0,4,strtoupper("[{$r['usuario']}]"),0,1,'L');
	
	$pdf->SetFont('Arial','I',6);
	//$this->SetTextColor(255,255,255);
    // Número de página
    $pdf->Cell(62,5,'Gracias por su preferencia',0,0,'C');
	
	$pdf->Output('doc.pdf', 'I');
?>