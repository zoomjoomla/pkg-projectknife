<?php
/**
 * @package      pkg_projectknife
 * @subpackage   com_pktasks
 *
 * @author       Tobias Kuhn (eaxs)
 * @copyright    Copyright (C) 2015-2017 Tobias Kuhn. All rights reserved.
 * @license      GNU General Public License version 2 or later.
 */

defined('_JEXEC') or die;



class PKtasksControllerTasks extends JControllerAdmin
{
    /**
     * Proxy for getModel.
     *
     * @param     string    $name      The model name. Optional.
     * @param     string    $prefix    The class prefix. Optional.
     * @param     array     $config    The array of possible config values. Optional.
     *
     * @return    jmodel
     */
    public function getModel($name = 'Task', $prefix = 'PKtasksModel', $config = array('ignore_request' => true))
    {
        $model = parent::getModel($name, $prefix, $config);

        return $model;
    }


    /**
     * Method to copy a list of items.
     *
     * @return    void
     */
    public function copy()
    {
        // Check for request forgeries
        JSession::checkToken() or die(JText::_('JINVALID_TOKEN'));

        $this->setRedirect(JRoute::_('index.php?option=' . $this->option . '&view=' . $this->view_list, false));

        // Get user input and model
        $pks     = JFactory::getApplication()->input->get('cid', array(), 'array');
        $options = JFactory::getApplication()->input->get('copy', array(), 'array');
        $model   = $this->getModel();

        // Make sure the item ids are integers
        JArrayHelper::toInteger($pks);

        // Remove any values of zero.
        $k = array_search(0, $pks, true);

        while ($k !== false)
        {
            unset($pks[$k]);
            $k = array_search(0, $pks, true);
        }

        if (empty($pks)) {
            JLog::add(JText::_('JGLOBAL_NO_ITEM_SELECTED'), JLog::WARNING, 'jerror');
            return;
        }


        // Set default options
        if (!isset($options['project_id'])) {
            $options['project_id'] = '';
        }

        if (!isset($options['milestone_id'])) {
            $options['milestone_id'] = '';
        }

        if (!isset($options['access'])) {
            $options['access'] = '';
        }


        // Validate options
        if (is_numeric($options['project_id']) && !is_numeric($options['milestone_id'])) {
            // Do not keep current milestone. Reset to 0
            $options['milestone_id'] = 0;
        }

        if (is_numeric($options['milestone_id']) && $options['milestone_id'] > 0) {
            $db    = JFactory::getDbo();
            $query = $db->getQuery(true);

            $query->select('project_id')
                  ->from('#__pk_milestones')
                  ->where('id = ' . (int) $options['milestone_id']);

            $db->setQuery($query);
            $options['project_id'] = (int) $db->loadResult();
        }

        // Check access
        if (!PKUserHelper::isSuperAdmin()) {
            $count  = count($pks);
            $user   = JFactory::getUser();
            $levels = PKUserHelper::getAccesslevels();
            $db     = JFactory::getDbo();
            $query  = $db->getQuery(true);

            // Check if the selected access level is allowed
            if (is_numeric($options['access'])) {
                if (!in_array($options['access'], $levels)) {
                    JLog::add(JText::_('PKGLOBAL_ACCESS_LEVEL_NOT_ALLOWED'), JLog::WARNING, 'jerror');
                    return;
                }
            }


            if (is_numeric($options['milestone_id']) && $options['milestone_id'] > 0) {
                // Check milestone access
                $options['milestone_id'] = (int) $options['milestone_id'];

                $query->clear()
                      ->select('access')
                      ->from('#__pk_milestones')
                      ->where('id = ' . $options['milestone_id']);

                $db->setQuery($query);
                $milestone_access = (int) $db->loadResult();

                // Check viewing access
                if (!in_array($milestone_access, $levels)) {
                    JLog::add(JText::_('COM_PKMILESTONES_MILESTONE_VIEW_ACCESS_DENIED'), JLog::WARNING, 'jerror');
                    return;
                }
            }
            else if (is_numeric($options['project_id'])) {
                // Check project access if no milestone is selected
                $options['project_id'] = (int) $options['project_id'];

                $query->clear()
                      ->select('access')
                      ->from('#__pk_projects')
                      ->where('id = ' . $options['project_id']);

                $db->setQuery($query);
                $project_access = (int) $db->loadResult();

                // Check viewing access
                if (!in_array($project_access, $levels)) {
                    JLog::add(JText::_('COM_PKPROJECTS_PROJECT_VIEW_ACCESS_DENIED'), JLog::WARNING, 'jerror');
                    return;
                }
            }

            // Check create task permission
            if (is_numeric($options['project_id'])) {
                if (!PKUserHelper::authorise('core.create.task', $options['project_id'])) {
                    JLog::add(JText::_($this->text_prefix . '_CREATE_PERMISSION_DENIED'), JLog::WARNING, 'jerror');
                    return;
                }
            }


            // Load project, access and author from selected tasks
            $query->select('a.id, a.project_id, a.access, a.created_by, p.access AS project_access, m.access AS milestone_access')
                  ->from('#__pk_tasks AS a')
                  ->leftJoin('#__pk_projects AS p ON p.id = a.project_id')
                  ->leftJoin('#__pk_milestones AS m ON m.id = a.milestone_id')
                  ->where('a.id IN(' . implode(',', $pks) . ')');

            $db->setQuery($query);
            $items = $db->loadObjectList('id');

            $can = array();

            for($i = 0; $i != $count; $i++)
            {
                $id  = $pks[$i];
                $pid = $items[$id]->project_id;

                // Cache permissions
                if (!isset($can[$pid])) {
                    $can[$pid]             = array();
                    $can[$pid]['edit']     = PKUserHelper::authProject('task.edit', $pid);
                    $can[$pid]['edit_own'] = PKUserHelper::authProject('task.edit.own', $pid);
                }

                // Check project viewing access
                if (!is_numeric($options['project_id'])) {
                    if (!in_array($items[$id]->project_access, $levels)) {
                        unset($pks[$i]);
                        continue;
                    }
                }

                if (!is_numeric($options['milestone_id']) || $options['milestone_id'] <= 0) {
                    if (!in_array($items[$id]->milestone_access, $levels) && $items[$id]->milestone_access > 0) {
                        unset($pks[$i]);
                        continue;
                    }
                }

                // Check edit, edit.own and viewing access
                if ((!$can[$pid]['edit'] && !($can[$pid]['edit_own'] && $items[$i]->created_by == $user->id)) || !in_array($items[$id]->access, $levels)) {
                    unset($pks[$i]);
                }
            }

            if (!$count) {
                JLog::add(JText::_('JGLOBAL_NO_ITEM_SELECTED'), JLog::WARNING, 'jerror');
                return;
            }
        }


        // Copy the items.
        try {
            $model->copy($pks, $options);
            $this->setMessage(JText::plural($this->text_prefix . '_N_ITEMS_COPIED', count($pks)));
        }
        catch (Exception $e) {
            $this->setMessage($e->getMessage(), 'error');
        }
    }
}
