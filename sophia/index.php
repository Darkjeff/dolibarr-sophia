<?php
/* <one line to give the program's name and a brief idea of what it does.>
 * Copyright (C) <2017> SaaSprov.ma <saasprov@gmail.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */


// Load Dolibarr environment
$res = @include ("../main.inc.php"); // For root directory
if (! $res)
	$res = @include ("../../main.inc.php"); // For "custom" directory
if (! $res)
	die("Include of main fails");

require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';

require_once 'class/sophia.class.php';

$action=GETPOST('action', 'alpha');
$newFile=GETPOST('newFile', 'int');

$obj = new Sophia($db);




// var_dump($conf->global->SOPHIA_PRODUCT);die();

function csv_to_array($filename, $delimiter=';')
{
	if(!file_exists($filename) || !is_readable($filename))
		return FALSE;
	
	$header = NULL;
	$data = array();
	if (($handle = fopen($filename, 'r')) !== FALSE)
	{
		while (($row = fgetcsv($handle, 1000, $delimiter)) !== FALSE)
		{
			if(!$header)
				$header = $row;
			else
				$data[] = array_combine($header, $row);
		}
		fclose($handle);
	}
	return $data;
}

/*
 * Actions
 */


  if ($action == 'uploadcsv')
    {
		
		
		$prodstatic = new Product($db);
		$prodstatic->fetch($conf->global->SOPHIA_PRODUCT);
		$prd_desc = $prodstatic->label;
		
		$file = $_FILES['csvfile']['tmp_name'];
		$filename = $_FILES['csvfile']['name'];
		$ext = pathinfo($filename, PATHINFO_EXTENSION);
		
		if($ext != 'csv'){
			$error = "le type du fichier est incompatible";
			die($error);
		}
		
		$lines = csv_to_array($_FILES['csvfile']['tmp_name']);
		$listerror = array();
		$data = array();
		$dataFac = array();
		$error = false;
		$code_company_error = array();
		$code_prd_error = array();
		$code_inv_error = array();
		$success = null;
		$i=1;

		//var_dump($lines);
		foreach($lines as $key => $value){
			// var_dump($line);
			
			// var_dump($value['Facturé le']);die;
			if(empty($value['Référence client'])){
				continue;
			}
			// var_dump($line);
			$societe_id = $obj->check_code_client($value['Référence client']);
			
			if(empty($societe_id)){
				$code_company_error[$value['Référence client']] = $value['Référence client'];
				$error = true;
				continue;
			}
			
			// $product_id = $obj->check_code_product($line[4]);
			
			// if(empty($product_id)){
				// $code_prd_error[$line[4]] = $line[4];
				// $error = true;
				// continue;
			// }
			
			$inv = $obj->check_code_invoice($value['Numéro de facture']);
			
			if(!empty($inv)){
				$code_inv_error[$value['Numéro de facture']] = $value['Numéro de facture'];
				$error = true;
				continue;
			}
						
			$data[$value['Numéro de facture']] = array( 
								'societe_id' => $societe_id,
								'n_fact' => $value['Numéro de facture'],
								'd_fact' => $value['Facturé le']
								);
								
			$data[$value['Numéro de facture']]['products'][] = array(
											'prod_id' => $conf->global->SOPHIA_PRODUCT,
											'desc' => $prd_desc,
											'qte' => 1,
											't_ht' => $value['Total HT'],
											't_ttc' => $value['Total TTC'],
											'txtva' => 20
										);
		}
		// var_dump($code_company_error);
		// var_dump($code_prd_error);
		// var_dump($code_inv_error);
		
		if(!empty($data)){ // j'enregistre les factures
		
			$obj->save_invoice($data, $user);
			$success = "Factures enregistrées";
		}
		
		if($error){
			$errcomp = implode(", ", $code_company_error);
			$errprd = implode(", ", $code_prd_error);
			$errinv = implode(", ", $code_inv_error);

			if($errcomp) setEventMessages($langs->trans("Sociétés avec codes ($errcomp)  indisponibles!"), null, 'errors');
			if($errprd) setEventMessages($langs->trans("Produits avec codes ($errprd)  indisponibles!"), null, 'errors');
			if($errinv) setEventMessages($langs->trans("Numéros de factures ($errinv) existent déjà!"), null, 'errors');
			
		}
		
    }

	$form = new Form($db);

	$morejs=array();
	$title = $langs->trans('Import Sophia');
	llxHeader('',$title,'','','','',$morejs,'',0,0);
	
	if(empty($conf->global->SOPHIA_PRODUCT)){
		print "Il faut définir un produit en passant par le menu 'configuration'!";
	}else{
		print '<form method="POST" action="" enctype="multipart/form-data">';
		print '<input type="hidden" name="action" value="uploadcsv">';
			dol_fiche_head();
			print load_fiche_titre($langs->trans("Import fichier Sophia"));
			
				print ' <table style="text-align: center;" id="tb1" class="liste" width="100%">
					  <tr>
						<td style="text-align:right;">Importer votre fichier!</td>
						<td><input class="flat" type="file" size="33" name="csvfile"/></td>
					  </tr>
					  <tr>
						<td colspan="2"><input type="submit" class="button" name="add" value="Import "/></td>
					  </tr>
				</table>';
			dol_fiche_end();
		print '</form>';
	}

	
	
	if(!empty($data)){
		echo '<pre><ul>';
		foreach($data as $field=>$success){
			echo '<li style="color:green;"> Facture enregistrée : '.$success['n_fact'].'</li>';
		}
		echo '</ul></pre>';
	}
	
	if(!empty($error)){
		if(!empty($code_company_error)){
			echo '<pre><ul>';
			foreach($code_company_error as $field=>$err){
				echo '<li style="color:red;"> Société avec code : '.$err.' indisponible!</li>';
			}
			echo '</ul></pre>';
		}
		
		
		if(!empty($code_prd_error)){
			echo '<pre><ul>';
			foreach($code_prd_error as $field=>$err){
				echo '<li style="color:red;"> Produits avec code : '.$err.' indisponible!</li>';
			}
			echo '</ul></pre>';
		}
		
		
		if(!empty($code_inv_error)){
			echo '<pre><ul>';
			foreach($code_inv_error as $field=>$err){
				echo '<li style="color:red;"> Numéros de factures '.$err.' existe déjà dans la base de données!</li>';
			}
			echo '</ul></pre>';
		}
		
	}
		
	
		
	

llxFooter();
$db->close();