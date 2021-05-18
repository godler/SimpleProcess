<?php

namespace Klik\SimpleProcess;


class TestProcess  extends Process{

    private $supports = \Moduless\Survey\Entities\SurveyMotion::class;


    protected $stages = [
        'start' => 'Start',
        'reviewed' => 'Przejrzane',
        'interview' => 'Prawie przejrzane',
        'rejected' => 'Odrzucone',
        'published'=> 'Obublikowane',
    ];


    protected $transitions = [
        'to_review' => [
            'from' => 'start',
            'to' => 'reviewed',
            'name' => 'Do przejrzenia'
        ],
        'publish' => [
            'from' => 'reviewed',
            'to' => 'published',
            'name' => 'Opublikuj'
        ],
      
    ];

    protected function checkIfSubjectExists()
    {
        
        $subject_query = \Modules\Survey\Entities\SurveyMotion::query();
        $subject_query->where('questionnaire_id', (int)$this->survey_id);
        $subject_query->where('entity_id', (int)$this->entity_id);
        if($this->filter_id !== null){
            $subject_query->where('filter_id', (int)$this->filter_id);
        }else{
            $subject_query->whereNull('filter_id');
        }

        $subject =  $subject_query->first();


        if($subject){
            return $subject;
        }

        return false;
    }

    protected function getNotSavetSubject(){

        $subject =  new \Modules\Survey\Entities\SurveyMotion();
 
        $subject->stage = 'start';
 
        return $subject;
     }


    public function nextUser()
    {
        return null;
    }


    public function guard_to_intereview($event)
    {
       if(!auth()->user()->hasRole('MSA')){
        $event->setBlocked(true);
       }
    }

    public function completed_to_intereview($event)
    {
        //Send email
    }




}