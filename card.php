<?php

require 'config.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
dol_include_once('/order2bill/class/order2bill.class.php');
dol_include_once('/order2bill/lib/order2bill.lib.php');

if(empty($user->rights->order2bill->read)) accessforbidden();

$langs->load('order2bill@order2bill');

$action = GETPOST('action');
$id = GETPOST('id', 'int');
$ref = GETPOST('ref');

$mode = 'view';
if (empty($user->rights->order2bill->write)) $mode = 'view'; // Force 'view' mode if can't edit object
else if ($action == 'create' || $action == 'edit') $mode = 'edit';

$object = new Order2Bill($db);

if (!empty($id)) $object->load($id);
elseif (!empty($ref)) $object->loadBy($ref, 'ref');

$hookmanager->initHooks(array('order2billcard', 'globalcard'));

/*
 * Actions
 */

$parameters = array('id' => $id, 'ref' => $ref, 'mode' => $mode);
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

// Si vide alors le comportement n'est pas remplacé
if (empty($reshook))
{
	$error = 0;
	switch ($action) {
		case 'save':
			$object->set_values($_REQUEST); // Set standard attributes
			
//			$object->date_other = dol_mktime(GETPOST('starthour'), GETPOST('startmin'), 0, GETPOST('startmonth'), GETPOST('startday'), GETPOST('startyear'));

			// Check parameters
//			if (empty($object->date_other))
//			{
//				$error++;
//				setEventMessages($langs->trans('warning_date_must_be_fill'), array(), 'warnings');
//			}
			
			// ... 
			
			if ($error > 0)
			{
				$mode = 'edit';
				break;
			}
			
			$object->save(empty($object->ref));
			
			header('Location: '.dol_buildpath('/order2bill/card.php', 1).'?id='.$object->getId());
			exit;
			
			break;
		case 'confirm_clone':
			$object->cloneObject();
			
			header('Location: '.dol_buildpath('/order2bill/card.php', 1).'?id='.$object->getId());
			exit;
			break;
		case 'modif':
			if (!empty($user->rights->order2bill->write)) $object->setDraft();
				
			break;
		case 'confirm_validate':
			if (!empty($user->rights->order2bill->write)) $object->setValid();
			
			header('Location: '.dol_buildpath('/order2bill/card.php', 1).'?id='.$object->getId());
			exit;
			break;
		case 'confirm_delete':
			if (!empty($user->rights->order2bill->write)) $object->delete();
			
			header('Location: '.dol_buildpath('/order2bill/list.php', 1));
			exit;
			break;
		// link from llx_element_element
		case 'dellink':
			$object->generic->deleteObjectLinked(null, '', null, '', GETPOST('dellinkid'));
			header('Location: '.dol_buildpath('/order2bill/card.php', 1).'?id='.$object->getId());
			exit;
			break;
	}
}


/**
 * View
 */

$title=$langs->trans("Order2Bill");
llxHeader('',$title);

if ($action == 'create' && $mode == 'edit')
{
	load_fiche_titre($langs->trans("NewOrder2Bill"));
	dol_fiche_head();
}
else
{
	$head = order2bill_prepare_head($object);
	$picto = 'generic';
	dol_fiche_head($head, 'card', $langs->trans("Order2Bill"), 0, $picto);
}

$formcore = new TFormCore;
$formcore->Set_typeaff($mode);

$form = new Form($db);

$formconfirm = getFormConfirmOrder2Bill($PDOdb, $form, $object, $action);
if (!empty($formconfirm)) echo $formconfirm;

$TBS=new TTemplateTBS();
$TBS->TBS->protect=false;
$TBS->TBS->noerr=true;

if ($mode == 'edit') echo $formcore->begin_form($_SERVER['PHP_SELF'], 'form_order2bill');

$linkback = '<a href="'.dol_buildpath('/order2bill/list.php', 1).'">' . $langs->trans("BackToList") . '</a>';
print $TBS->render('tpl/card.tpl.php'
	,array() // Block
	,array(
		'object'=>$object
		,'view' => array(
			'mode' => $mode
			,'action' => 'save'
			,'urlcard' => dol_buildpath('/order2bill/card.php', 1)
			,'urllist' => dol_buildpath('/order2bill/list.php', 1)
			,'showRef' => ($action == 'create') ? $langs->trans('Draft') : $form->showrefnav($object->generic, 'ref', $linkback, 1, 'ref', 'ref', '')
			,'showLabel' => $formcore->texte('', 'label', $object->label, 80, 255)
//			,'showNote' => $formcore->zonetexte('', 'note', $object->note, 80, 8)
			,'showStatus' => $object->getLibStatut(1)
		)
		,'langs' => $langs
		,'user' => $user
		,'conf' => $conf
		,'TOrder2Bill' => array(
			'STATUS_DRAFT' => TOrder2Bill::STATUS_DRAFT
			,'STATUS_VALIDATED' => TOrder2Bill::STATUS_VALIDATED
			,'STATUS_REFUSED' => TOrder2Bill::STATUS_REFUSED
			,'STATUS_ACCEPTED' => TOrder2Bill::STATUS_ACCEPTED
		)
	)
);

if ($mode == 'edit') echo $formcore->end_form();

if ($mode == 'view' && $object->getId()) $somethingshown = $form->showLinkedObjectBlock($object->generic);

llxFooter();