<?php
/* <one line to give the program's name and a brief idea of what it does.>
 * Copyright (C) <2018>  jamelbaz@gmail.com <jamelbaz.com>
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

/**
 * \file    admin/setup.php
 * \ingroup sophia
 * \brief   Example module setup page.
 *
 * Put detailed description here.
 */

// Load Dolibarr environment
if (false === (@include '../../main.inc.php')) {  // From htdocs directory
	require '../../../main.inc.php'; // From "custom" directory
}

global $langs, $user;

// Libraries
require_once DOL_DOCUMENT_ROOT . "/core/lib/admin.lib.php";

if (! $user->admin) accessforbidden();

$action = GETPOST('action','alpha');

if ($action == 'setvalue' && $user->admin)
{
	$db->begin();
    $result=dolibarr_set_const($db, "SOPHIA_PRODUCT",GETPOST('SOPHIA_PRODUCT','alpha'),'int',0,'',$conf->entity);
    if (! $result > 0) $error++;
   
	
	//Activate Ask For Preferred Shipping Method

	
	if (! $error)
  	{
  		$db->commit();
  		setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
  	}
  	else
  	{
  		$db->rollback();
		dol_print_error($db);
    }
}


// Access control
if (! $user->admin) {
	accessforbidden();
}

// Parameters
$action = GETPOST('action', 'alpha');

/*
 * Actions
 */

/*
 * View
 */
$page_name = "SophiaSetup";
llxHeader('', $langs->trans($page_name));

// Subheader
$linkback = '<a href="' . DOL_URL_ROOT . '/admin/modules.php">'
	. $langs->trans("BackToModuleList") . '</a>';
print load_fiche_titre($langs->trans($page_name), $linkback);

// Configuration header
dol_fiche_head(
	null,
	'settings',
	$langs->trans("Sophia"),
	0,
	"sophia@sophia"
);

// Setup page goes here
echo $langs->trans("SophiaSetupPage");

// Test if php curl exist
if (! function_exists('curl_version'))
{
	$langs->load("errors");
	setEventMessages($langs->trans("ErrorPhpCurlNotInstalled"), null, 'errors');
}

print '<form method="post" action="'.$_SERVER["PHP_SELF"].'">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="action" value="setvalue">';

print '<br>';
print '<br>';

print '<table class="noborder" width="100%">';

// Account Parameters

print '<input type="hidden" name="element" value="produits">';
print "<td>".$langs->trans("Product")."</td>";

print '<td>';
if (! empty($conf->global->PRODUIT_MULTIPRICES))
	$form->select_produits($conf->global->SOPHIA_PRODUCT, 'SOPHIA_PRODUCT', '', $conf->product->limit_size, $soc->price_level);
else
	$form->select_produits($conf->global->SOPHIA_PRODUCT, 'SOPHIA_PRODUCT', '', $conf->product->limit_size);
print '</td>';


print '</table>';

dol_fiche_end();

print '<div class="center"><input type="submit" class="button" value="'.$langs->trans("Modify").'"></div>';

print '</form>';

llxFooter();
