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
 */
defined('MOODLE_INTERNAL') || die();
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
        $PAGE->requires->js('/blocks/mymindmap_overview/scripts/js/jsmind.js',true);
        $PAGE->requires->js('/blocks/mymindmap_overview/scripts/js/jsmind.launcher.js',true);
        $this->content = new stdClass();
        $this->content->text = '';
        $this->content->footer = '';
        $passed = '';$nbpassed = 0;$totpassed=0;
        $future = '';$nbfuture = 0;$totfuture=0;
        $actual = '';$nbactual = 0;$totactual=0;
        $content = '{
        "meta":{
            "name":"Mindmind Courses",
            "author":"hizzgdev@163.com",
            "version":"0.2"
        },
        "format":"node_tree",
        "data":{"id":"root","topic":"'.get_string('mymindmap_mine','block_mymindmap_overview').'","children":[';
        $courses = enrol_get_all_users_courses($USER->id, $onlyactive = false, $fields = 'format,enddate,newsitems', $sort = 'c.fullname ASC,visible DESC,sortorder ASC');
        $nbr_courses = count($courses);
        $newidcourse = 0;
        $numcourse = 1;
        $courseskip = 0;
        if ($nbr_courses > 0)
        {
          $opened_course = 0;
          foreach ($courses as $course)
          {
             $nbcmod = $DB->count_records_select('course_modules','module != 9 AND course = '.$course->id, array ('course'=>$course->id));
             if (($course->enddate > time() || $course->enddate == 0) && $course->startdate < time() && $course->visible == 1 && ($nbcmod > 0 || $course->format == 'social'))
             {
                  $totactual++;
                  if ($totactual > 0 && $opened_course == 0 && $nbcmod > 0)
                     $opened_course = $course->id;
             }
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
                  $is_actual = 0;
                  $is_passed = 1;
             }
            if ($nbcmod == 0 && $course->format != 'social')
                continue;
            if (user_has_role_assignment($USER->id, 1) ||
                user_has_role_assignment($USER->id, 3) ||
                user_has_role_assignment($USER->id, 4))
                $rolecourse = 1;
            else
                $rolecourse = 0;
            $course_visible = $course->visible;
            $numcourse++;
            if ($course->visible == 0)
            {
               $courseskip++;
               continue;
            }
            if (($course->startdate < time() || $course->startdate == 0) && ($course->enddate < time() || $course->enddate > 0)){
               $ajout = ',"direction" : "left","expanded" :false';
            }else{
               $ajout = '';
            }
            if ($opened_course == $course->id)
               $ajout = ',"direction" : "left","expanded" :true';

            $idcourse = (!empty($course->idnumber)) ? $course->idnumber : $course->fullname;
            $nb_modules = (($nbcmod > 0 || $course->format == 'social') && $course->format != 'singleactivity') ? ($nbcmod + 1) : $nbcmod;
            $suffixe = (strstr($course->fullname,'2018-2019 - ') && !strstr($course->fullname,'- phase')) ? strstr(str_replace('2018-2019 - ','',$course->fullname), ' - ') : '----';
            $lecours = '<span style="font-weight:bold;">('.$nb_modules.')</span>  '.str_replace($suffixe,'',str_replace('2018-2019 - ',' ',str_replace('"',' - ',$course->fullname)));
            $content1 = '
            {"id":"'.$idcourse.'","topic":"<a href='.
            '../course/view.php?id='.$course->id.' title=\"'.
            addslashes($course->fullname).'\n  '.$nb_modules.' ressources\">'.addslashes($lecours).'</a>"'.$ajout.',"children":[';
           $sql = 'SELECT * FROM {course_sections} cs WHERE ';
           $sql .= ($rolecourse == 0) ? 'cs.course = ? AND cs.visible = ? AND cs.sequence != ?' :  'cs.course = ?  AND cs.sequence != ?' ;
           $sql .= 'ORDER BY section ASC';
           $sections = ($rolecourse == 0) ? $DB->get_records_sql($sql, array( $course->id,1,'')) :  $DB->get_records_sql($sql, array( $course->id,''));
           $nbr_seq =count($sections);
           $newseq = 0;
           $virgulesection = '';
           $modseq = array();
           if ($nbcmod > 0 || $course->format == 'social')
           {
              foreach ($sections as $section)
              {
                    $nomsection = ($section->name === NULL || trim(strip_tags($section->name)) == '') ? get_string('mymindmap_withoutname','block_mymindmap_overview') : addslashes($section->name);
                    if (strstr($section->sequence,','))
                       $modseq = explode(',',$section->sequence);
                    else
                    {
                       unset ($modseq);
                       $modseq[] = $section->sequence;
                    }
                   $nb_modtot = count($modseq);
                   for ($a=0;$a < $nb_modtot; $a++)
                   {
                      $modvalable[$a] = $DB->get_field('course_modules', 'deletioninprogress', array('id' => $modseq[$a]));
                      $modvisible[$a] = $DB->get_field('course_modules', 'visible', array('id' => $modseq[$a]));
                      if ($modvalable[$a] == 1 || $modvisible[$a] == 0)
                      {
                         unset($modseq[$a]);
                      }
                   }
                   $modseq = array_values($modseq);
                   $nb_mod = count($modseq);
                   $newidseq = 1;
                   $compteur = 0;
                   if ($nb_mod > 0)
                   {
                      for ($i=0;$i < $nb_mod; $i++)
                      {
                         if ($newidseq > 0)
                         {
                           $expanded = ($opened_course == $course->id && $newseq == 0) ? 'true' : 'false';
                           $sectioname = '<span style=\"font-weight:bold;\">('.$nb_mod.')</span>  '.str_replace('"',"-",$nomsection);
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
                             $modnom = $DB->get_field($module,'name', array('id'=>$record->instance), $strictness=IGNORE_MISSING);
                             $context = context_module::instance($record->id);
                             $valable = ($record->availability != '') ? json_decode($record->availability): NULL;
                             $is_membre = 2;
                             if ($valable != NULL && $rolecourse == 0)
                             {
                                $is_membre = 0;
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
                                        $is_membre = 1;
                                }
                             }
                             else
                               $is_membre = 1;
                             $compteur++;
                             $modnom = str_replace('"','-',$modnom);
                             $virgule = ($nb_mod > $compteur) ? ',' : '';
                              if ($is_membre == 0)
                                 $content1 .= '
                                 {"id":"module'.$record->id.'-'.$newseq.'-'.$course->id.'-'.$i.'","topic":"'.
                                 addslashes(preg_replace("#\n|\t|\r#",'',$modnom)).
                                 get_string('mymindmap_restriction','block_mymindmap_overview').'"}'.$virgule;
                             else
                                 $content1 .= '
                                 {"id":"module'.$record->id.'-'.$newseq.'-'.$course->id.'-'.$i.
                                 '","topic":"<a href=../mod/'.$module.'/view.php?id='.$record->id.' title=\''.
                                 addslashes(preg_replace("#\n|\t|\r#",'',$modnom)).'\'>'.
                                 preg_replace("#\n|\t|\r#",'',$modnom).'</a>"}'.$virgule;
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
            if ($nbactual == $totactual && $totactual > 0 && (($nbpassed == $totpassed && $nbpassed > 0) ||  $totpassed == 0))
                $content1 .= '
                ]}';
            elseif(($is_actual == 1 && $nbactual < $totactual && $totactual > 0) || ($is_passed == 1 && $nbpassed < $totpassed && $totpassed > 0))
                $content1 .= '
                ]},';
            elseif ($is_passed == 1 && $nbpassed == $totpassed && $totpassed > 0 && (($nbactual == $totactual && $totactual > 0) || $totactual == 0))
                $content1 .= '
                ]}';
            if ($is_passed == 1 && $nbpassed == 1 && $totactual > 0)
                  $passed .=',{"id":"passed","topic":"<span style=\"font-weight:bold;font-size:14px;\">('.
                  $totpassed.') '.get_string('mymindmap_past','block_mymindmap_overview').'</span>'.
                  '","direction":"right","expanded":false,"children":[';
            elseif ($is_passed == 1 && $nbpassed == 1 && $totactual == 0)
                  $passed .='{"id":"passed","topic":"<span style=\"font-weight:bold;font-size:14px;\">('.
                  $totpassed.')>'.get_string('mymindmap_past','block_mymindmap_overview').'</span>'.
                  '","direction":"right","expanded":true,"children":[';
            if (($course->enddate > time() || $course->enddate == 0) && $course->startdate < time())
                  $actual .= $content1;
            elseif (($course->enddate < time() &&  $course->enddate > 0) && $course->startdate < time())
                  $passed .= $content1;
         }
         if ($nbactual > 0 && $nbpassed > 0)
            $content .= $actual.'
            ]}';;
         if ($nbpassed > 0)
            $content .= $passed.'
            ]}';
         $content .= '
         ]}
         }';
       }
       $hauteur = 450+($nbactual * 65);
       $this->content->text .= html_writer::div('<div style="height:35px;"><div id="mindmap" class="btn btn-default" style="clear:both;float:left;" '.
                                                 'onclick="$(document).ready(function(){'.
                                                 '$(\'#jsmind_container\').toggle();'.
                                                 '$(\'#jsmind_container\').html(\'\');'.
                                                 '$(\'#jsmind_container\').css(\'height\',\''.$hauteur.'px\');'.
                                                 'load_jsmind('.htmlentities($content).');})">'.
                                                  get_string('mymindmap_openit','block_mymindmap_overview').'</div></div>'.
                                                 '<div id="jsmind_container" style="display:none;"></div>');
       $this->content->footer = '';
       return $this->content;
    }
}
