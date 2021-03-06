<?php
    header('Content-Type: application/json; charset=utf-8');
	include('../conexion.php');
	$fecha = date("d-m-Y", strtotime($_GET['fecha']));
	$sql_cab_doc_gen = "select * from cab_doc_gen where cdg_cod_gen='02' and cdg_cod_emp='01' and to_char(cdg_fec_gen,'dd-mm-yyyy')='".$fecha."' order by cdg_num_doc Desc";
	$sql_parse = oci_parse($conn,$sql_cab_doc_gen);
    oci_execute($sql_parse);
    oci_fetch_all($sql_parse, $documentos, null, null, OCI_FETCHSTATEMENT_BY_ROW);

    //print_r (json_encode($documentos));
    $docs = array(); // cuando no hay ninguno es necesario
    $i=0;
    $count_index_factura=1;
    $count_index_boleta=1;
    foreach ($documentos as $documento){


        $docs[$i]['numero']=$documento['CDG_NUM_DOC'];

        /* DOC Y SERIE 01-F001
        **********************/
        if($documento['CDG_TIP_DOC'] == 'F'){
            $tipoDoc = 'Factura';
            $serie = 'F00'.$documento['CDG_SER_DOC'];
            $orden_index = 1;
            $count_index_doc =  $count_index_factura++;
        }elseif($documento['CDG_TIP_DOC'] == 'B'){
            $tipoDoc = 'Boleta';
            $serie = 'B00'.$documento['CDG_SER_DOC'];
            $orden_index = 2;
            $count_index_doc =  $count_index_boleta++;
        }elseif($documento['CDG_TIP_DOC'] == 'A'){
            $tipoDoc = 'Nota Credito';
            if($documento['CDG_TIP_REF'] == 'BR' || $documento['CDG_TIP_REF'] == 'BS'){
                $serie = 'BN0'.$documento['CDG_SER_DOC'];
                $orden_index = 2;
                $count_index_doc =  $count_index_boleta++;
            }elseif($documento['CDG_TIP_REF'] == 'FR' || $documento['CDG_TIP_REF'] == 'FS' || $documento['CDG_TIP_REF'] == 'FC'){
                $serie = 'FN0'.$documento['CDG_SER_DOC'];
                $orden_index = 1;
                $count_index_doc =  $count_index_factura++;
            }
        }
        $docs[$i]['id']=$count_index_doc;
        $docs[$i]['orden_index']=$orden_index;
        $docs[$i]['tipo_doc']=$tipoDoc;
        $docs[$i]['serie']=$serie;
        $docs[$i]['cliente']=$documento['CDG_NOM_CLI'];
        $docs[$i]['impresion']=$documento['CDG_TIP_IMP'];

        /*OT
        *******/
        ($documento['CDG_ORD_TRA'] == 0)? $docs[$i]['ot']='' : $docs[$i]['ot']=$documento['CDG_ORD_TRA'];

        /*ANULADO
        *************/
        ($documento['CDG_ANU_SN']=='S' && $documento['CDG_DOC_ANU']='S' )?  $docs[$i]['anulado']='Si' : $docs[$i]['anulado']='';

        /*SUNAT CODIGO
        ****************/
        ($documento['CDG_COD_SNT']=='0001' || $documento['CDG_COD_SNT']=='0003')? $docs[$i]['sunat_codigo']=$documento['CDG_COD_SNT'] : $docs[$i]['sunat_codigo']='';

        $docs[$i]['sunat_codigo']=$documento['CDG_COD_SNT'];
        $docs[$i]['sunat_envio']=$documento['CDG_SUN_ENV'];

        $docs[$i]['total']=number_format($documento['CDG_IMP_NETO'],0,'.',',');

        /*  MONEDA
        *********************************************/
        if($documento['CDG_TIP_CAM'] != 0){
            $moneda = '$$ ';
        }else{
            $moneda = 'S/ ';
        }
        $docs[$i]['moneda']=$moneda;

        /*PDF LINK
        *****************/
        $pdf_link = '?gen='.$documento['CDG_COD_GEN'].'&emp='.$documento['CDG_COD_EMP'].'&tip='.$documento['CDG_TIP_DOC'].'&num='.$documento['CDG_NUM_DOC'];
        $docs[$i]['pdf_link']=$pdf_link;

        /*Anticipo
        ******************/
        if($documento['CDG_EXI_ANT']=='AN'){
            $docs[$i]['anticipo']='ANT';
            $docs[$i]['franquicia'] = '';
        }

        /*Franquicia
        *****************/
        if($documento['CDG_EXI_FRA']=='S' && $documento['CDG_EXI_ANT']!='AN'){
            $docs[$i]['anticipo']='';
            $docs[$i]['franquicia'] = 'FRA';
        }

        $i++;

    }
    print_r(json_encode($docs,JSON_UNESCAPED_UNICODE));
?>