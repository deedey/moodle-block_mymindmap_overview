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
        $PAGE->requires->js('/blocks/mymindmap_overview/scripts/js/jquery-3.2.1.min.js',true);
        $PAGE->requires->js('/blocks/mymindmap_overview/scripts/js/drag-on.js',true);
        $PAGE->requires->js('/blocks/mymindmap_overview/scripts/js/jsmind.js',true);
        $PAGE->requires->js('/blocks/mymindmap_overview/scripts/js/jsmind.launcher.js',true);

        $this->content = new stdClass();
        $this->content->text = '';
        $this->content->footer = '';
        $passed = '';$nbpassed = 0;$totpassed=0;
        $actual = '';$nbactual = 0;$totactual=0;
        $content = mymindmap_overview_content_base();
        $courses = enrol_get_all_users_courses($USER->id, $onlyactive = false, $fields = 'format,enddate,newsitems', $sort = 'c.fullname ASC,visible DESC,sortorder ASC');
        $nbr_courses = count($courses);
        $numcourse = 1;
        $courseskip = 0;
        $opened_past = 'false';
        if ($nbr_courses > 0)
        {
          $opened_course = mymindmap_overview_last_course();
          foreach ($courses as $course)
          {
             $nbcmod = $DB->count_records_select('course_modules','module != 9 AND course = '.$course->id, array ('course'=>$course->id));
             if (($course->enddate > time() || $course->enddate == 0) && $course->startdate < time() && $course->visible == 1 && ($nbcmod > 0 || $course->format == 'social'))
                  $totactual++;
             elseif ($course->enddate < time() && $course->enddate > 0 && $course->startdate < time() && $course->visible == 1 && ($nbcmod > 0 || $course->format == 'social'))
                  $totpassed++;
          }
          foreach ($courses as $course)
          {
            $nbcmod = $DB->count_records_select('course_modules','module != 9 AND course = '.$course->id, array ('course'=>$course->id));
             if (($course->enddate > time() || $course->enddate == 0) && $course->startdate < time() && $course->visible == 1 && ($nbcmod > 0 || $course->format == 'social'))
             {
                  $nbactual++;
                  $is_actual = 1;
                  $is_passed = 0;
             }
             elseif ($course->enddate < time() && $course->enddate != 0 && $course->startdate < time() && $course->visible == 1 && ($nbcmod > 0 || $course->format == 'social'))
             {
                  $nbpassed++;
                  if ($opened_course == $course->id) $opened_past = 'true';
                  $is_actual = 0;
                  $is_passed = 1;
             }
            if ($nbcmod == 0 && $course->format != 'social')
                continue;
            $rolecourse = mymindmap_overview_my_role_course($course);
            $numcourse++;
            if ($course->visible == 0)
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
            $the_course = '<span style="font-weight:bold;">('.$nb_modules.')</span>  '.str_replace('"',' - ',$course->fullname);
            $content1 = '
            {"id":"'.$idcourse.'","topic":"<a href='.
            '../course/view.php?id='.$course->id.' title=\"'.
            addslashes($course->fullname).'\n  '.$nb_modules.' ressources\">'.addslashes($the_course).'</a>"'.$to_add.',"children":[';
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
                    $section_name = ($section->name === NULL || trim(strip_tags($section->name)) == '') ? get_string('mymindmap_withoutname','block_mymindmap_overview') : addslashes($section->name);
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
                      for ($i=0;$i < $nb_mod; $i++)
                      {
                         if ($newidseq > 0)
                         {
                           $expanded = ($opened_course == $course->id && $newseq == 0) ? 'true' : 'false';
                           $sectioname = '<span style=\"font-weight:bold;\">('.$nb_mod.')</span>  '.str_replace('"',"-",$section_name);
                           $content1 .= '
                           {"id":"section'.$section->id.'-'.$newseq.'-'.$course->id.'","topic":"<a href='.
                                           '../course/view.php?id='.$course->id.'&sectionid='.$section->id.'#section-'.
                                           $section->section.'>'.$sectioname.'</a>","expanded":'.$expanded.',"children":[';
                           $newidseq = 0;
                         }
                         $sql1 = 'SELECT * FROM {course_modules} cm WHERE ';
                         $sql1 .= 'cm.course = ? AND cm.visible = ?  AND cm.id = ? AND cm.deletioninprogress = ?';
                         $modules = $DB->get_records_sql($sql1, array( $course->id,1,$modseq[$i],0));

                         foreach ($modules as $record)
                         {
                             $module = $DB->get_field('modules','name', array('id'=>$record->module), $strictness=IGNORE_MISSING);
                             $mod_name = $DB->get_field($module,'name', array('id'=>$record->instance), $strictness=IGNORE_MISSING);
                             $is_member = mymindmap_overview_ismember($rolecourse,$record);
                             $counter++;
                             $mod_name = addslashes(str_replace('"','-',$mod_name));
                             $virgule = ($nb_mod > $counter) ? ',' : '';
                             if (file_exists($CFG->dirroot.'/mod/'.$module.'/pix/icon.gif'))
                                 $icon = '<img height="18" widht="18" style="margin-right:5px;" src="'.$CFG->wwwroot.'/mod/'.$module.'/pix/icon.gif" />';
                             else
                                 $icon = '<img height="18" width="18" style="margin-right:5px;" src="'.$CFG->wwwroot.'/mod/'.$module.'/pix/icon.png" />';
                              if ($is_member == 0)
                                 $content1 .= '
                                 {"id":"module'.$record->id.'-'.$newseq.'-'.$course->id.'-'.$i.'","topic":"'.addslashes($icon).'  '.
                                 preg_replace("#\n|\t|\r#",'',$mod_name).
                                 get_string('mymindmap_restriction','block_mymindmap_overview').'"}'.$virgule;
                             else
                                 $content1 .= '
                                 {"id":"module'.$record->id.'-'.$newseq.'-'.$course->id.'-'.$i.
                                 '","topic":"'.addslashes($icon).'  <a href=../mod/'.$module.'/view.php?id='.$record->id.' title=\''.
                                 preg_replace("#\n|\t|\r#",'',str_replace("'"," ",$mod_name)).'\'>'.
                                 preg_replace("#\n|\t|\r#",'',$mod_name).'</a>"}'.$virgule;
                         }
                     }
                   }
                   $newidseq++;
                   $newseq++;
                   if ($nbr_seq > $newseq)
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
         $this->content->text .= html_writer::div(mymindmap_overview_screenview ($content,$hight));
       }
       else
       {
         $this->content->text = html_writer::div(mymindmap_overview_no_courses);
       }
       $this->content->footer = '';
       return $this->content;
    }
}
