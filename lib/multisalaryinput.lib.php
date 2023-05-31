<?php
/* Copyright (C) 2023 SuperAdmin
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
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file    multisalaryinput/lib/multisalaryinput.lib.php
 * \ingroup multisalaryinput
 * \brief   Library files with common functions for MultiSalaryInput
 */

require_once DOL_DOCUMENT_ROOT . '/core/lib/functions.lib.php';

if (!function_exists('isModEnabled')) {
	/**
	 * Is Dolibarr module enabled
	 *
	 * @param string $module Module name to check
	 * @return    boolean                True if module is enabled
	 */
	function isModEnabled($module)
	{
		global $conf;

		// Fix special cases
		$arrayconv = array(
			'project' => 'projet',
			'contract' => 'contrat'
		);
		if (empty(getDolGlobalString('MAIN_USE_NEW_SUPPLIERMOD'))) {
			$arrayconv['supplier_order'] = 'fournisseur';
			$arrayconv['supplier_invoice'] = 'fournisseur';
		}
		if (!empty($arrayconv[$module])) {
			$module = $arrayconv[$module];
		}

		//return !empty($conf->modules[$module]);
		return !empty($conf->$module->enabled);
	}
}

/**
 * Prepare admin pages header
 *
 * @return array
 */
function multisalaryinputAdminPrepareHead()
{
	global $langs, $conf;

	$langs->load("multisalaryinput@multisalaryinput");

	$h = 0;
	$head = array();

	$head[$h][0] = dol_buildpath("/multisalaryinput/admin/setup.php", 1);
	$head[$h][1] = $langs->trans("Settings");
	$head[$h][2] = 'settings';
	$h++;

	/*
	$head[$h][0] = dol_buildpath("/multisalaryinput/admin/myobject_extrafields.php", 1);
	$head[$h][1] = $langs->trans("ExtraFields");
	$head[$h][2] = 'myobject_extrafields';
	$h++;
	*/

	$head[$h][0] = dol_buildpath("/multisalaryinput/admin/about.php", 1);
	$head[$h][1] = $langs->trans("About");
	$head[$h][2] = 'about';
	$h++;

	// Show more tabs from modules
	// Entries must be declared in modules descriptor with line
	//$this->tabs = array(
	//	'entity:+tabname:Title:@multisalaryinput:/multisalaryinput/mypage.php?id=__ID__'
	//); // to add new tab
	//$this->tabs = array(
	//	'entity:-tabname:Title:@multisalaryinput:/multisalaryinput/mypage.php?id=__ID__'
	//); // to remove a tab
	complete_head_from_modules($conf, $langs, null, $head, $h, 'multisalaryinput@multisalaryinput');

	complete_head_from_modules($conf, $langs, null, $head, $h, 'multisalaryinput@multisalaryinput', 'remove');

	return $head;
}


function getEmployeeArray(&$employeesArray, &$errors)
{
	global $db, $user, $conf, $hookmanager;
	// Forge request to select users
	$sql = "SELECT DISTINCT u.rowid, u.lastname as lastname, u.firstname";

	if (!empty(isModEnabled('multicompany')) && $conf->entity == 1 && $user->admin && !$user->entity) {
		$sql .= ", e.label";
	}

	$sql .= " FROM " . MAIN_DB_PREFIX . "user as u";

	if (!empty(isModEnabled('multicompany')) && $conf->entity == 1 && $user->admin && !$user->entity) {
		$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "entity as e ON e.rowid = u.entity";
		$sql .= " WHERE u.entity IS NOT NULL";
	} else {
		if (!empty(isModEnabled('multicompany')) && !empty(getDolGlobalString('MULTICOMPANY_TRANSVERSE_MODE'))) {
			$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "usergroup_user as ug";
			$sql .= " ON ug.fk_user = u.rowid";
			$sql .= " WHERE ug.entity = " . $conf->entity;
		} else {
			$sql .= " WHERE u.entity IN (0, " . $conf->entity . ")";
		}
	}

	$sql .= " AND COALESCE(u.employee,0) <> 0";

	if (!empty(getDolGlobalString('USER_HIDE_INACTIVE_IN_COMBOBOX'))) {
		$sql .= " AND COALESCE(u.statut,0) <> 0";
	}

	//Add hook to filter on user (for exemple on usergroup define in custom modules)
	$reshook = $hookmanager->executeHooks('addSQLWhereFilterOnSelectUsers', array());

	if (!empty($reshook)) {
		$sql .= $hookmanager->resPrint;
	}

	// MAIN_FIRSTNAME_NAME_POSITION is 0 means firstname+lastname
	if (empty(getDolGlobalString('MAIN_FIRSTNAME_NAME_POSITION'))) {
		$sql .= " ORDER BY u.statut DESC, u.firstname ASC, u.lastname ASC";
	} else {
		$sql .= " ORDER BY u.statut DESC, u.lastname ASC, u.firstname ASC";
	}

	$resql = $db->query($sql);

	if (!$resql) {
		$errors = $db->lasterror();
		return -1;
	}


	$num = $db->num_rows($resql);

	for ($i = 0; $i < $num; $i++) {
		$obj = $db->fetch_object($resql);
		$employeesArray[$obj->rowid] = $obj->firstname . ' ' . $obj->lastname;
	}

	return 1;
}
