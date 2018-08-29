<?php
    define('sys_checksession', false);
	require_once '../../sys.php';
	require_once '../../lib/fpdf/fpdf.php';
	
	// params
	$rid = Sys::GetP('id', '');
	
	$q = new PgQuery("
	SELECT r.*, d.*,
	c.cliente_desc,
	top.tipope_desc,
	m.moneda_desc, m.moneda_simbolo
	FROM public.registro r
	JOIN public.registro_det d ON d.registro_id = r.registro_id
	LEFT JOIN public.cliente c ON c.cliente_id = r.cliente_id
	JOIN public.tipope top ON top.tipope_id = r.tipope_id
	JOIN public.moneda m ON m.moneda_id = d.moneda_id
	WHERE r.registro_id LIKE '$id'
	AND NOT d.clasemov_id IN ('17') 
	ORDER BY d.registro_det_id 
	", null, true, true);
	
	$r = $q->row;
	
	//var_dump($r);
	//exit;
	
	class PDF extends FPDF
	{
		// Cabecera de página
		function Header()
		{
			global $r;
		    // Logo
		    $this->Image('logo.png',20,0,30);
		    // Arial bold 
		    $this->SetY(7);
		    $this->SetFont('helvetica','B',8);
		    // Título
		    $this->Cell(50,6,"CASA DE CAMBIO",0,1,'C');
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
	}
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
    $tipope_desc = $r['tipope_desc'];
	// Creación del objeto de la clase heredada
	$pdf = new PDF('P','mm', array(70,3276)); //array(70,100)
    $pdf->SetDisplayMode('real', 'single');
	$pdf->SetAutoPageBreak(true, 10);
	$pdf->AliasNbPages();
	$pdf->AddPage();
	$pdf->SetMargins(4,0,4);
	$pdf->SetX(4);
	$pdf->SetFont('helvetica','B',7);
	if ($r['tipope_id']=='03' || $r['tipope_id']=='04') {
		$pdf->Cell(35,3,"Operacion: $tipope_desc",0,0,'L');
		$pdf->Cell(27,3,utf8_decode("Nª: {$r['registro_id']}"),0,1,'R');
	} else {
		$pdf->Cell(0,3,"Operacion: $tipope_desc",0,1,'L');
		$pdf->Cell(0,3,utf8_decode("Nª: {$r['registro_id']}"),0,1,'L');
	}
	$pdf->Cell(35,3,"Fecha: {$r['registro_fecha']}",0,0,'L');
    $pdf->Cell(27,3,strtoupper("[{$r['usuario']}]"),0,1,'R');
    
	if ($r['cliente_id']!='00-0000000' && !is_null($r['cliente_id'])) { // dif. de publico general
		$pdf->Cell(0,3,"Cliente: {$r['cliente_desc']}",0,1,'L');
	}
	
	if ($r['registro_interes']>0) { // interes
		$pdf->Cell(32,3,"Interes: {$r['registro_interes']} %",0,0,'L');
        $pdf->Cell(30,3,"I.Mora: {$r['registro_imora']} %",0,1,'L');
	}
    if ($r['registro_estado']=='N') {
        $pdf->Cell(0,3,"-- ANULADO --",0,1,'C');
    }
	$y = $pdf->GetY(); 
	$pdf->Line(3, $y+1, 66, $y+1);
	$pdf->SetY($pdf->GetY()+2);
    if ($r['tipope_id']=='03' || $r['tipope_id']=='04') {
        $pdf->Cell(20, 2,"Recibido",0,0,'L');
        $pdf->Cell(20, 2,"T.C.",0,0,'C');
        $pdf->Cell(22, 2,"Entregado",0,0,'R');
        $pdf->SetY($pdf->GetY()+2);
        
        $y = $pdf->GetY(); 
        $pdf->Line(3, $y+1, 66, $y+1);
        $pdf->SetY($pdf->GetY()+2);
        $d1 = $q->Read();
        $pdf->Cell(20,2,"{$d1['moneda_simbolo']} ".Sys::NFormat(abs($d1['registro_det_importe'])),0,0,'L');
        $tcop = $r['registro_tcoperador']=='*'?'X':utf8_decode('÷');
        $pdf->SetFont('helvetica','B',8);
        $pdf->Cell(5,2,"$tcop",0,0,'L');
        $pdf->SetFont('helvetica','B',7);
        $pdf->Cell(15,2,Sys::NFormat($r['registro_tcfactor'], 3),0,0,'C');
        $pdf->Cell(2,2,"=",0,0,'C');
        $d2 = $q->Read();
        $pdf->Cell(20,2,"{$d2['moneda_simbolo']} ".Sys::NFormat(abs($d2['registro_det_importe'])),0,1,'R'); 
        
    } else {
        $pdf->Cell(30, 2,"Moneda",0,0,'L');
        $pdf->Cell(32, 2,"Importe",0,0,'R');
        $pdf->SetY($pdf->GetY()+2);
        
        $y = $pdf->GetY(); 
        $pdf->Line(3, $y+1, 66, $y+1);
        $pdf->SetY($pdf->GetY()+2);
        while ($d = $q->Read()) {
            $pdf->Cell(30,3,"{$d['moneda_desc']}",0,0,'L');
            if ($d['clasemov_id']=='18') {
                $pdf->Cell(32,3,'-FALTANTE-',0,1,'R');    
            } else {
                $pdf->Cell(32,3,Sys::NFormat(abs($d['registro_det_importe'])),0,1,'R');    
            }
        }    
    }
	
	
	$y = $pdf->GetY(); 
	$pdf->Line(3, $y+1, 66, $y+1);
	$pdf->SetY($pdf->GetY()+2);
	//$pdf->SetFont('Arial','',6);
	
	$pdf->SetFont('Arial','I',6);
	//$this->SetTextColor(255,255,255);
    if ($r['tipope_id']=='03' || $r['tipope_id']=='04') {
        $pdf->Cell(62,5,'Gracias por su preferencia',0,0,'C');
    }
	$pdf->Output("ticket_{$r['registro_id']}.pdf", 'I');
?>