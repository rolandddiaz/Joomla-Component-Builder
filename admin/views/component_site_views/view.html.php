<?php
/**
 * @package    Joomla.Component.Builder
 *
 * @created    30th April, 2015
 * @author     Llewellyn van der Merwe <http://www.joomlacomponentbuilder.com>
 * @github     Joomla Component Builder <https://github.com/vdm-io/Joomla-Component-Builder>
 * @copyright  Copyright (C) 2015 - 2018 Vast Development Method. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access to this file
defined('_JEXEC') or die('Restricted access');

// import Joomla view library
jimport('joomla.application.component.view');

/**
 * Component_site_views View class
 */
class ComponentbuilderViewComponent_site_views extends JViewLegacy
{
	/**
	 * display method of View
	 * @return void
	 */
	public function display($tpl = null)
	{
		// Assign the variables
		$this->form = $this->get('Form');
		$this->item = $this->get('Item');
		$this->script = $this->get('Script');
		$this->state = $this->get('State');
		// get action permissions
		$this->canDo = ComponentbuilderHelper::getActions('component_site_views',$this->item);
		// get input
		$jinput = JFactory::getApplication()->input;
		$this->ref = $jinput->get('ref', 0, 'word');
		$this->refid = $jinput->get('refid', 0, 'int');
		$this->referral = '';
		if ($this->refid)
		{
			// return to the item that refered to this item
			$this->referral = '&ref='.(string)$this->ref.'&refid='.(int)$this->refid;
		}
		elseif($this->ref)
		{
			// return to the list view that refered to this item
			$this->referral = '&ref='.(string)$this->ref;
		}

		// Set the toolbar
		$this->addToolBar();
		
		// Check for errors.
		if (count($errors = $this->get('Errors')))
		{
			throw new Exception(implode("\n", $errors), 500);
		}

		// Display the template
		parent::display($tpl);

		// Set the document
		$this->setDocument();
	}


	/**
	 * Setting the toolbar
	 */
	protected function addToolBar()
	{
		JFactory::getApplication()->input->set('hidemainmenu', true);
		$user = JFactory::getUser();
		$userId	= $user->id;
		$isNew = $this->item->id == 0;

		JToolbarHelper::title( JText::_($isNew ? 'COM_COMPONENTBUILDER_COMPONENT_SITE_VIEWS_NEW' : 'COM_COMPONENTBUILDER_COMPONENT_SITE_VIEWS_EDIT'), 'pencil-2 article-add');
		// Built the actions for new and existing records.
		if ($this->refid || $this->ref)
		{
			if ($this->canDo->get('component_site_views.create') && $isNew)
			{
				// We can create the record.
				JToolBarHelper::save('component_site_views.save', 'JTOOLBAR_SAVE');
			}
			elseif ($this->canDo->get('component_site_views.edit'))
			{
				// We can save the record.
				JToolBarHelper::save('component_site_views.save', 'JTOOLBAR_SAVE');
			}
			if ($isNew)
			{
				// Do not creat but cancel.
				JToolBarHelper::cancel('component_site_views.cancel', 'JTOOLBAR_CANCEL');
			}
			else
			{
				// We can close it.
				JToolBarHelper::cancel('component_site_views.cancel', 'JTOOLBAR_CLOSE');
			}
		}
		else
		{
			if ($isNew)
			{
				// For new records, check the create permission.
				if ($this->canDo->get('component_site_views.create'))
				{
					JToolBarHelper::apply('component_site_views.apply', 'JTOOLBAR_APPLY');
					JToolBarHelper::save('component_site_views.save', 'JTOOLBAR_SAVE');
					JToolBarHelper::custom('component_site_views.save2new', 'save-new.png', 'save-new_f2.png', 'JTOOLBAR_SAVE_AND_NEW', false);
				};
				JToolBarHelper::cancel('component_site_views.cancel', 'JTOOLBAR_CANCEL');
			}
			else
			{
				if ($this->canDo->get('component_site_views.edit'))
				{
					// We can save the new record
					JToolBarHelper::apply('component_site_views.apply', 'JTOOLBAR_APPLY');
					JToolBarHelper::save('component_site_views.save', 'JTOOLBAR_SAVE');
					// We can save this record, but check the create permission to see
					// if we can return to make a new one.
					if ($this->canDo->get('component_site_views.create'))
					{
						JToolBarHelper::custom('component_site_views.save2new', 'save-new.png', 'save-new_f2.png', 'JTOOLBAR_SAVE_AND_NEW', false);
					}
				}
				$canVersion = ($this->canDo->get('core.version') && $this->canDo->get('component_site_views.version'));
				if ($this->state->params->get('save_history', 1) && $this->canDo->get('component_site_views.edit') && $canVersion)
				{
					JToolbarHelper::versions('com_componentbuilder.component_site_views', $this->item->id);
				}
				if ($this->canDo->get('component_site_views.create'))
				{
					JToolBarHelper::custom('component_site_views.save2copy', 'save-copy.png', 'save-copy_f2.png', 'JTOOLBAR_SAVE_AS_COPY', false);
				}
				JToolBarHelper::cancel('component_site_views.cancel', 'JTOOLBAR_CLOSE');
			}
		}
		JToolbarHelper::divider();
		// set help url for this view if found
		$help_url = ComponentbuilderHelper::getHelpUrl('component_site_views');
		if (ComponentbuilderHelper::checkString($help_url))
		{
			JToolbarHelper::help('COM_COMPONENTBUILDER_HELP_MANAGER', false, $help_url);
		}
	}

	/**
	 * Escapes a value for output in a view script.
	 *
	 * @param   mixed  $var  The output to escape.
	 *
	 * @return  mixed  The escaped value.
	 */
	public function escape($var)
	{
		if(strlen($var) > 30)
		{
    		// use the helper htmlEscape method instead and shorten the string
			return ComponentbuilderHelper::htmlEscape($var, $this->_charset, true, 30);
		}
		// use the helper htmlEscape method instead.
		return ComponentbuilderHelper::htmlEscape($var, $this->_charset);
	}

	/**
	 * Method to set up the document properties
	 *
	 * @return void
	 */
	protected function setDocument()
	{
		$isNew = ($this->item->id < 1);
		if (!isset($this->document))
		{
			$this->document = JFactory::getDocument();
		}
		$this->document->setTitle(JText::_($isNew ? 'COM_COMPONENTBUILDER_COMPONENT_SITE_VIEWS_NEW' : 'COM_COMPONENTBUILDER_COMPONENT_SITE_VIEWS_EDIT'));
		$this->document->addStyleSheet(JURI::root() . "administrator/components/com_componentbuilder/assets/css/component_site_views.css", (ComponentbuilderHelper::jVersion()->isCompatible('3.8.0')) ? array('version' => 'auto') : 'text/css'); 
		$this->document->addScript(JURI::root() . $this->script, (ComponentbuilderHelper::jVersion()->isCompatible('3.8.0')) ? array('version' => 'auto') : 'text/javascript');
		$this->document->addScript(JURI::root() . "administrator/components/com_componentbuilder/views/component_site_views/submitbutton.js", (ComponentbuilderHelper::jVersion()->isCompatible('3.8.0')) ? array('version' => 'auto') : 'text/javascript'); 
		JText::script('view not acceptable. Error');
	}
}
