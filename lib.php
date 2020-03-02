<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 *
 * mymindmap_overview block common functions 
 * @package    block_mymindmap_overview
 * @copyright  2018 Dey Bendifallah
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @based on jsmind  project -2014-2016 hizzgdev@163.com
 * @jsmind Project Home: https://github.com/hizzgdev/jsmind/
 * dragOn jQuery plugin Project Home : https://github.com/PretorDH/Dragon
 */
defined('MOODLE_INTERNAL') || die();


    function mymindmap_overview_ismember($rolecourse,$record) {
          global $USER, $DB, $CFG ;
          $context = context_module::instance($record->id);
          $valable = ($record->availability != '') ? json_decode($record->availability): NULL;
          $is_member = 2;
          if ($valable != NULL && $rolecourse == 0)
          {
              $is_member = 0;
              $is_group = 0; $is_grouping = 0; $is_date1 = 1; $is_date2 = 1; $is_complete = 1;
              $nbr_groups = count($valable->{'c'});
              for ($group = 0;$group < $nbr_groups;$group++)
              {
                  if (isset($valable->{'c'}[$group]->{'type'}) && $valable->{'c'}[$group]->{'type'} == 'group')
                  {
                      $user_group = count($DB->get_records_sql("SELECT * FROM {groups_members} WHERE ".
                                   "groupid= ?  AND userid= ? ", array($valable->{'c'}[$group]->{'id'},$USER->id )));
                      if ($user_group > 0) $is_group = 1;
                  }
                  if (isset($valable->{'c'}[$group]->{'type'}) && $valable->{'c'}[$group]->{'type'} == 'grouping')
                  {
                      $user_group = count($DB->get_records_sql("SELECT * FROM {groupings_groups} as gg, {groups_members} as gm WHERE ".
                                  "gm.groupid = gg.groupid AND gg.groupingid = ? AND gm.userid= ? ", array($valable->{'c'}[$group]->{'id'},$USER->id )));
                      if ($user_group > 0) $is_grouping = 1;
                  }
                  if (isset($valable->{'c'}[$group]->{'type'}) && $valable->{'c'}[$group]->{'type'} == 'completion')
                  {
                      $completion = count($DB->get_records_sql("SELECT * FROM {course_modules_completion} WHERE ".
                                 "coursemoduleid= ?  AND completionstate= ?   AND userid= ? "
                                 ,array($valable->{'c'}[$group]->{'cm'},$valable->{'c'}[$group]->{'e'},$USER->id )));
                      if ($completion == 0) $is_complete = 0;
                  }
                  if (isset($valable->{'c'}[$group]->{'type'}) && $valable->{'c'}[$group]->{'type'} == 'date')
                  {
                     if (isset($valable->{'c'}[$group]->{'d'}) && $valable->{'c'}[$group]->{'d'} == '<'){
                           if (time() > $valable->{'c'}[$group]->{'t'} && $rolecourse == 0) {$is_date1 = 0;break;};
                     }
                     if (isset($valable->{'c'}[$group]->{'d'}) && $valable->{'c'}[$group]->{'d'} == '>='){
                           if (time() < $valable->{'c'}[$group]->{'t'} && $rolecourse == 0) {$is_date2 = 0;break;};
                     }
                  }
                  if ((($is_group == 1 || $is_grouping == 1) && $is_complete == 1 &&
                     ($is_date1 == 1 && $is_date2 == 1)) || $rolecourse == 1)
                          $is_member = 1;
              }
          }
          else
             $is_member = 1;
        return $is_member;
    }

    function mymindmap_overview_content_base() {
        $base_content = '{
        "meta":{
            "name":"Mindmap Courses",
            "author":"hizzgdev@163.com",
            "version":"0.2"
        },
        "format":"node_tree",
        "data":{"id":"root","topic":"'.get_string('mymindmap_mine','block_mymindmap_overview').'","children":[';

        return $base_content;
   }

   function mymindmap_overview_my_role_course ($course) {
           global $CFG,$USER ;
           $coursecontext = context_course::instance($course->id);
           if (user_has_role_assignment($USER->id, 1,$coursecontext->id) ||
              user_has_role_assignment($USER->id, 3,$coursecontext->id) ||
              user_has_role_assignment($USER->id, 4,$coursecontext->id))
                $rolecourse = 1;
            else
                $rolecourse = 0;
            return $rolecourse;
   }

  function mymindmap_overview_screenview ($content,$hight) {
         $my_buttons = '<div style="height:35px;">'.
         '<div id="mindmap" class="btn btn-default" '.
         'style="clear:both;float:left;cursor:pointer !important;" '.
         'onclick="$(document).ready(function(){'.
         '$(\'#jsmind_container\').toggle();'.
         '$(\'#jsmind_container\').html(\'\');'.
         '$(\'#jsmind_container\').css(\'height\',\''.$hight.'px\');'.
         'load_jsmind('.htmlentities($content).');});'.
         '$(\'#jsmind_container\').dragOn;"  title="'.
         get_string('mymindmap_howto','block_mymindmap_overview').'">'.
         get_string('mymindmap_openit','block_mymindmap_overview').'</div>'.
         '<div id="expander" class="btn btn-default" '.
         'style="float:left;margin-left:20px;cursor:pointer !important;" '.
         'onclick="$(document).ready(function(){'.
         '$(\'#jsmind_container\').show();'.
         '$(\'#jsmind_container\').html(\'\');'.
         '$(\'#jsmind_container\').css(\'height\',\'1080px\');'.
         'expander('.htmlentities($content).');});'.
         '$(\'#jsmind_container\').dragOn;" title= "'.
         get_string('mymindmap_expand','block_mymindmap_overview').'">'.
         get_string('mymindmap_expand_all','block_mymindmap_overview').'</div>'.
         '<div id="collapser" class="btn btn-default" '.
         'style="float:left;margin-left:20px;cursor:pointer !important;" '.
         'onclick="$(document).ready(function(){'.
         '$(\'#jsmind_container\').show();'.
         '$(\'#jsmind_container\').html(\'\');'.
         '$(\'#jsmind_container\').css(\'height\',\''.$hight.'px\');'.
         'collapse('.htmlentities($content).');});'.
         '$(\'#jsmind_container\').dragOn;" title= "'.
         get_string('mymindmap_collapse','block_mymindmap_overview').'">'.
         get_string('mymindmap_collapse_all','block_mymindmap_overview').'</div></div>'.
         '<div id="jsmind_container" style="display:none;" class="dragon"></div>';
       return $my_buttons;
   }

   function mymindmap_overview_no_courses () {
      $no_courses = '<div style="height:35px;color:#FF0000;font-weight: bold;">'.
                  get_string('mymindmap_nocourse', 'block_mymindmap_overview').'</div>';
      return $no_courses;
   }

   function mymindmap_overview_last_course(){
          global $USER, $DB;
          $opened_course = 0;
          $sql = 'SELECT * FROM {logstore_standard_log} WHERE ';
          $sql .= 'action = ?  AND target = ? AND userid = ? ';
          $sql .= 'order by timecreated desc';
          $lastcourse = $DB->get_records_sql($sql, array('viewed', 'course', $USER->id));
          $cx = 0;
          foreach ($lastcourse as $mycourse) {
            if ($cx > 0)
                    break;
            $opened_course = $mycourse->courseid;
            $cx++;
          }
      return $opened_course;
   }

   function mymindmap_overview_categories_path($course){
          global $DB;
          $moncours = $course->id;
          $sql = 'SELECT cm.id,cg.path FROM mdl_course as cm , mdl_course_categories as cg WHERE '.
                 'cm.id = '.$course->id.' and cm.category = cg.id';
          $cats = $DB->get_records_sql($sql, array ($params = null));
          if (strstr($cats[$moncours]->{'path'},'/'))
          {
            $Tab = explode('/',$cats[$moncours]->{'path'});
            $Nbr = count($Tab);
            $categ='';
            for($i=1;$i<$Nbr;$i++)
            {
                $categ .= $DB->get_field('course_categories', 'name', array('id' => $Tab[$i]));
                if (($i+1) < $Nbr) $categ .= ' / ';
            }
          }
          else
          {
              $categ = $DB->get_field('course_categories', 'name', array('id' => $cats[$moncours]->{'path'})).' / ';
          }
      return $categ;
   }


   function mymindmap_overview_coursemod_query($course){
          global $DB;
          $nbr_items = $DB->count_records_select('course_modules','module != 9 AND deletioninprogress = 0 AND course = '.$course->id , array ('course'=>$course->id));
      return $nbr_items;
   }

   function mymindmap_overview_mod_sections ($nb_modtot,$course,$modseq) {
       global  $DB;
       for ($a=0;$a < $nb_modtot; $a++)
       {
          $modvalable[$a] = $DB->get_field('course_modules', 'deletioninprogress', array('id' => $modseq[$a]));
          $modvisible[$a] = $DB->get_field('course_modules', 'visible', array('id' => $modseq[$a]));
          if (mymindmap_overview_my_role_course ($course) == 0 && ($modvalable[$a] == 1 || $modvisible[$a] == 0))
             unset($modseq[$a]);
          elseif (mymindmap_overview_my_role_course ($course) == 1 && $modvalable[$a] == 1)
             unset($modseq[$a]);
       }
      return $modseq;
   }
   function get_this_content_to_display_flag($record,$course) {
       global $DB,$USER;
       $output = '';
       $is_viewed_new_module = $DB->get_field_select('logstore_standard_log','id', 'action = "viewed" AND '.
           'contextinstanceid = '.$record->id.
           ' AND userid = '.$USER->id.' order by id desc limit 1',
           array ($params=null), $strictness=IGNORE_MISSING);
       if ($is_viewed_new_module == FALSE)
       {
           $output = new moodle_url('/blocks/mymindmap_overview/images/flag.gif');
           return  $output;
       }
       return $output;
   }
   function get_last_content_to_display_flag($course) {
       global $DB,$USER;
       $last_new_module = $DB->get_field_select('logstore_standard_log','contextinstanceid','(action = "created"'.
           ' OR action = "updated") AND target = "course_module"'.
           ' AND courseid = '.$course->id.' order by id desc limit 1',
           array ($params=null), $strictness=IGNORE_MISSING);
       $last_user_create = $DB->get_field_select('logstore_standard_log','userid','(action = "created"'.
           ' OR action = "updated") AND target = "course_module"'.
           ' AND courseid = '.$course->id.' order by id desc limit 1',
           array ($params=null), $strictness=IGNORE_MISSING);
       if (isset($last_new_module) && $last_new_module > 0)
       {
           $is_viewed_new_module = $DB->get_field_select('logstore_standard_log','id', 'action = "viewed" AND '.
               'contextinstanceid = '.$last_new_module.' AND courseid = '.$course->id.
               ' AND userid = '.$USER->id.' order by id desc limit 1',
               array ($params=null), $strictness=IGNORE_MISSING);
           if ($is_viewed_new_module == FALSE)
           {
               $objecttable = $DB->get_field_select('logstore_standard_log','objecttable',
                   'contextinstanceid = '.$last_new_module.' order by id desc limit 1',
                   array ($params=null), $strictness=IGNORE_MISSING);
               $objectmodule = $DB->get_field_select('course_modules','module',
                   'id = '.$last_new_module,array ($params=null), $strictness=IGNORE_MISSING);
               if (!empty($objectmodule))
                  $object = $DB->get_field_select('modules','name',
                      'id = '.$objectmodule,array ($params=null), $strictness=IGNORE_MISSING);
               else
                   return '';
               if ($last_user_create != $USER->id)
                   $sortie = new moodle_url('/blocks/mymindmap_overview/images/flag.gif').'**'.
                             new moodle_url('/mod/'.$object.'/view.php?id='.$last_new_module).'**'.$last_new_module;
               else
                   $sortie = '';
            return  $sortie;
           }
       }
       return '';
   }
   
    
