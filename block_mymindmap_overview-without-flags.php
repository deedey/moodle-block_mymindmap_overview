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
 *
 * @package    block_mymindmap_overview
 * @copyright  2018 Dey Bendifallah
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @based on jsmind  project -2014-2016 hizzgdev@163.com
 * @jsmind Project Home: https://github.com/hizzgdev/jsmind/
 * dragOn jQuery plugin Project Home : https://github.com/PretorDH/Dragon
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/blocks/mymindmap_overview/lib.php');

class block_mymindmap_overview extends block_base {

    public function init() {
        $this->title = get_string('pluginname', 'block_mymindmap_overview');
    }
 
    public function get_content() {
        global $USER, $DB, $CFG,$PAGE ;

        if ($this->content !== NULL) {
            return $this->content;
        }

        $PAGE->requires->css('/blocks/mymindmap_overview/scripts/style/jsmind.css',true);
        $PAGE->requires->jquery();
        $PAGE->requires->js('/blocks/mymindmap_overview/scripts/js/drag-on.js',true);
        $PAGE->requires->js('/blocks/mymindmap_overview/scripts/js/jsmind.js',true);
        $PAGE->requires->js('/blocks/mymindmap_overview/scripts/js/jsmind.launcher.js',true);

        $this->content = new stdClass();
        $this->content->text = '';
        $this->content->footer = '';
        $params = '';
        $passed = '';$nbpassed = 0;$totpassed=0;$is_passed = 0;
        $actual = '';$nbactual = 0;$totactual=0;$is_actual=0;
        $last_context = $DB->get_field_select('logstore_standard_log','contextinstanceid', 'action = "viewed" AND '.
                      'target = "course_module" AND userid = '.$USER->id.' order by id desc limit 1',
                      array ($params=null), $strictness=IGNORE_MISSING);
      if ($last_context > 0)
        $last_course = $DB->get_field_select('course_modules','course', 'id = '.$last_context,
                          array ($params=null), $strictness=IGNORE_MISSING);
      else
        $last_course = 0;
        $last_context_course = $DB->get_field_select('logstore_standard_log','id', 'action = "viewed" AND '.
                      'target = "course" AND userid = '.$USER->id.' order by id desc limit 1',
                      array ($params=null), $strictness=IGNORE_MISSING);
        $last_context_module = $DB->get_field_select('logstore_standard_log','id', 'action = "viewed" AND '.
                      'target = "course_module" AND userid = '.$USER->id.' order by id desc limit 1',
                      array ($params=null), $strictness=IGNORE_MISSING);
        $content = mymindmap_overview_content_base();
        $courses = enrol_get_all_users_courses($USER->id, $onlyactive = false, $fields = 'format,enddate,newsitems', $sort = 'c.category,c.fullname ASC,visible DESC,sortorder ASC');
        $nbr_courses = count($courses);
        $numcourse = 1;
        $courseskip = 0;
        $opened_past = 'false';
        if ($nbr_courses > 0)
        {
          $opened_course = ($last_context_course > 0 && $last_context_module > 0 && $last_context_course > $last_context_module) ? mymindmap_overview_last_course() : $last_course;
          foreach ($courses as $course)
          {
             $rolecourse = mymindmap_overview_my_role_course($course);
             $nbcmod = mymindmap_overview_coursemod_query($course);
             if (($course->enddate > time() || $course->enddate == 0) && $course->startdate < time() && (($course->visible == 1 && ($nbcmod > 0 || $course->format == 'social') && $rolecourse == 0) || $rolecourse == 1))
                  $totactual++;
             elseif ($course->enddate < time() && $course->enddate > 0 && $course->startdate < time() && (($course->visible == 1 && ($nbcmod > 0 || $course->format == 'social') && $rolecourse == 0) || $rolecourse == 1))
                  $totpassed++;
          }
          foreach ($courses as $course)
          {
             $categ = mymindmap_overview_categories_path($course);
             $rolecourse = mymindmap_overview_my_role_course($course);
             $nbcmod = mymindmap_overview_coursemod_query($course);
             if (($course->enddate > time() || $course->enddate == 0) && $course->startdate < time()  && (($course->visible == 1 && ($nbcmod > 0 || $course->format == 'social') && $rolecourse == 0) || $rolecourse == 1))
             {
                  $nbactual++;
                  $is_actual = 1;
                  $is_passed = 0;
             }
             elseif ($course->enddate < time() && $course->enddate != 0 && $course->startdate < time()  && (($course->visible == 1 && ($nbcmod > 0 || $course->format == 'social') && $rolecourse == 0) || $rolecourse == 1))
             {
                  $nbpassed++;
                  if ($opened_course == $course->id) $opened_past = 'true';
                  $is_actual = 0;
                  $is_passed = 1;
             }
            if ($nbcmod == 0 && $course->format != 'social' && $rolecourse == 0)
                continue;
            $numcourse++;
            if ($course->visible == 0 && $rolecourse == 0)
            {
               $courseskip++;
               continue;
            }
            if (($course->startdate < time() || $course->startdate == 0) && ($course->enddate < time() || $course->enddate > 0)){
               $to_add = ',"direction" : "left","expanded" :false';
            }else{
               $to_add = '';
            }
            if ($opened_course == $course->id)
               $to_add = ',"direction" : "left","expanded" :true';

            $idcourse = (!empty($course->idnumber)) ? $course->idnumber : $course->fullname;
            $nb_modules = (($nbcmod > 0 || $course->format == 'social') && $course->format != 'singleactivity') ? ($nbcmod + 1) : $nbcmod;
/* 
           $from_flag = get_last_content_to_display_flag($course);
            $is_flag = (strstr($from_flag,'**')) ? true : false;
            $flag = '';
            
            if ($is_flag)
            {
                $tab_flag = explode('**',$from_flag);
                $the_link_asset = $tab_flag[1];
                $the_asset =  $tab_flag[2];
                $the_flag = $tab_flag[0];
                $flag = '<span style=" white-space:nowrap;margin:0 0 0 4px;padding:0 0 10px 0;">'.
                           '<a href= "'.$the_link_asset.'" title="'.get_string('mymindmap_new_modin','block_mymindmap_overview').'">'.
                           '<img src="'.$the_flag.'" width="15" height="15"></a></span>';
            }
*/
            $warning = '<img height="22" widht="22" style="margin-right:5px;" src="'.
                        $CFG->wwwroot.'/blocks/mymindmap_overview/images/warning.png" title="'.
                        get_string('mymindmap_warning','block_mymindmap_overview').'" />';
            if ($nb_modules == 0)
               $the_course = $warning.str_replace('"',' - ',$course->fullname);
            else
               $the_course = '<span style="font-weight:bold;">('.$nb_modules.')</span>  '.
                             str_replace('"',' - ',$course->fullname);
            $content1 = '
            {"id":"'.$idcourse.'","topic":"<a href='.
            '../course/view.php?id='.$course->id.' title=\"'.
            str_replace('"',' - ',$categ.' / '. $course->fullname).'\n  '.
            $nb_modules.' '.get_string('mymindmap_contents','block_mymindmap_overview').
            '\">'.addslashes($the_course).'</a>"'.$to_add.',"children":[';
            $sql = 'SELECT * FROM {course_sections} cs WHERE ';
            $sql .= ($rolecourse == 0) ? 'cs.course = ? AND cs.visible = ? AND cs.sequence != ?' :  'cs.course = ?  AND cs.sequence != ?' ;
            $sql .= 'ORDER BY section ASC';
            $sections = ($rolecourse == 0) ? $DB->get_records_sql($sql, array( $course->id,1,'')) :  $DB->get_records_sql($sql, array( $course->id,''));
            $nbr_seq =count($sections);
            $newseq = 0;
            $modseq = array();
            if ($nbcmod > 0 || $course->format == 'social')
            {
              foreach ($sections as $section)
              {
                    /*$asset_into_seq = ($is_flag && strstr($section->sequence,$the_asset)) ?  addslashes('<span style=" '.
                                                          'white-space:nowrap;margin:0 0 0 4px;padding:0 0 10px 0;"><img src="'.$the_flag.
                                                          '" width="15" height="15" title="'.get_string('mymindmap_new_modin','block_mymindmap_overview').'"></span>'): '';
                    */
                    $asset_into_seq= '';
                    $section_name = ($section->name === NULL || trim(strip_tags($section->name)) == '') ? get_string('mymindmap_withoutname','block_mymindmap_overview') : addslashes($section->name).$asset_into_seq;
                    if (strstr($section->sequence,','))
                       $modseq = explode(',',$section->sequence);
                    else
                    {
                       unset ($modseq);
                       $modseq[] = $section->sequence;
                    }
                   $nb_modtot = count($modseq);
                   $modseq = array_values(mymindmap_overview_mod_sections($nb_modtot,$course,$modseq));
                   $nb_mod = count($modseq);
                   $newidseq = 1;
                   $counter = 0;
                   if ($nb_mod > 0)
                   {
                      if ($newidseq > 0)
                      {
                          $mynewseq = $section->section;
                          $expanded = ($last_context > 0 && $opened_course == $course->id && strstr($section->sequence,$last_context)) ? 'true' : 'false';
                          $mynewseq = ($last_context > 0 && strstr($section->sequence,$last_context)) ? $section->section : 0;
                          $sectioname = '<span style=\"font-weight:bold;\">('.$nb_mod.')</span>  '.$section_name;
                          $content1 .= '
                          {"id":"section'.$section->id.'-'.$mynewseq.'-'.$course->id.'","topic":"<a href='.
                          '../course/view.php?id='.$course->id.'&sectionid='.$section->id.'#section-'.
                          $section->section.' title=\"'.addslashes(str_replace('"','-',$section->name)).
                          '\">'.$sectioname.'</a>","expanded":'.$expanded.',"children":[';
                          $newidseq = 0;
                      }
                      for ($i=0;$i < $nb_mod; $i++)
                      {
                         $sql1 = 'SELECT * FROM {course_modules} cm WHERE ';
                         $sql1 .=  ($rolecourse == 0) ?  'cm.course = ? AND cm.id = ? AND cm.deletioninprogress = ? AND cm.visible = ? ' : 'cm.course = ? AND cm.id = ? AND cm.deletioninprogress = ? ';
                         $modules = ($rolecourse == 0) ?  $DB->get_records_sql($sql1, array( $course->id,$modseq[$i],0,1)) :   $DB->get_records_sql($sql1, array( $course->id,$modseq[$i],0));

                         foreach ($modules as $record)
                         {
                             $module = $DB->get_field('modules','name', array('id'=>$record->module), $strictness=IGNORE_MISSING);
                             $mod_name = $DB->get_field($module,'name', array('id'=>$record->instance), $strictness=IGNORE_MISSING);
                             $is_member = mymindmap_overview_ismember($rolecourse,$record);
                             $counter++;
                             $mod_name = preg_replace("#\n|\t|\r#",'',str_replace("'"," ",str_replace('"','-',$mod_name)));
                             $virgule = ($nb_mod > $counter) ? ',' : '';
                             if (file_exists($CFG->dirroot.'/mod/'.$module.'/pix/icon.gif'))
                                 $icon = '<img height="18" widht="18" style="margin-right:5px;" src="'.$CFG->wwwroot.'/mod/'.$module.'/pix/icon.gif" />';
                             else
                                 $icon = '<img height="18" width="18" style="margin-right:5px;" src="'.$CFG->wwwroot.'/mod/'.$module.'/pix/icon.png" />';
                             $activitylink = '';
                             if ($is_member == 0)
                                 $content1 .= '
                                 {"id":"module'.$record->id.'-'.$newseq.'-'.$course->id.'-'.$i.'","topic":"'.addslashes($icon).
                                 '  '.$mod_name.addslashes($activitylink).get_string('mymindmap_restriction','block_mymindmap_overview').'"}'.$virgule;
                             else
                             {
                             /*
                                 if ($is_flag && strstr($the_link_asset,'/mod/'.$module.'/view.php?id='.$record->id))
                                     $myflag = addslashes('<span style=" white-space:nowrap;margin:0 0 0 4px;padding:0 0 10px 0;"><img src="'.$the_flag.
                                                          '" width="15" height="15" title="'.get_string('mymindmap_new_module','block_mymindmap_overview').'">');
                                 else 
                             */
                                 $myflag = '';
                                 $content1 .= '
                                 {"id":"module'.$record->id.'-'.$newseq.'-'.$course->id.'-'.$i.
                                 '","topic":"'.addslashes($icon).'  <a href=../mod/'.$module.'/view.php?id='.$record->id;
                                 $link_title = ($last_context == $record->id) ? ' title=\"'.
                                                get_string('mymindmap_last_module','block_mymindmap_overview').': \n'.
                                                $mod_name.'\"' : ' title =\"'.$mod_name.'\"' ;
                                 $content1 .= $link_title.'>';
                                 $content1 .= ($last_context == $record->id) ? '<span class=\"lastcontent\">'.
                                              $mod_name.addslashes($activitylink).'</span>': $mod_name.addslashes($activitylink);
                                 $content1 .= '</a>'.'  '.$myflag.'"}'.$virgule;
                             }
                         }
                     }
                   }
                   $newidseq++;
                   $newseq++;
                   if ($nbr_seq > $newseq && $nb_mod > 0)
                      $content1 .= '
                      ]},';
                   elseif ($nbr_seq == $newseq)
                      $content1 .= '
                      ]}';
               }
            }
            if(($is_actual == 1 && $nbactual < $totactual && $totactual > 0) || ($is_passed == 1 && $nbpassed < $totpassed && $totpassed > 0))
                $content1 .= '
                   ]},';
            if ($is_passed == 1 && $nbpassed == 1 && $totactual > 0)
                  $passed .=',{"id":"passed","topic":"<span style=\"font-size:16px;\">('.
                  $totpassed.') '.get_string('mymindmap_past','block_mymindmap_overview').'</span>'.
                  '","direction":"right","expanded":provisional,"children":[';
            elseif ($is_passed == 1 && $nbpassed == 1 && $totactual == 0)
                  $passed .='{"id":"passed","topic":"<span style=\"font-size:16px;\">('.
                  $totpassed.')>'.get_string('mymindmap_past','block_mymindmap_overview').'</span>'.
                  '","direction":"right","expanded":provisional,"children":[';
            if (($course->enddate > time() || $course->enddate == 0) && $course->startdate < time())
                  $actual .= $content1;
            elseif (($course->enddate < time() &&  $course->enddate > 0) && $course->startdate < time())
                  $passed .= $content1;
          }
          if ($nbactual > 0)
            $content .= $actual.'
               ]}';
          if ($nbpassed > 0)
            $content .= $passed.'
               ]}
            ]}';
          $content .= '
          ]}
          }';
          $content = str_replace('provisional',$opened_past,$content);
          $hight = ($nbactual > 0) ? 450+($nbactual * 65) : 600;
          $this->content->text .= html_writer::div(mymindmap_overview_screenview (str_replace('',' ',$content),$hight));
        }
        else
        {
          $this->content->text = html_writer::div(mymindmap_overview_no_courses());
        }
        $this->content->footer = '';
        return $this->content;
      }
}
