<?php

namespace Klik\SimpleProcess;


use Symfony\Component\Workflow\MarkingStore\MethodMarkingStore;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Workflow\Definition;
use Symfony\Component\Workflow\DefinitionBuilder;
use Symfony\Component\Workflow\MarkingStore\MarkingStoreInterface;
use Symfony\Component\Workflow\MarkingStore\MultipleStateMarkingStore;
use Symfony\Component\Workflow\MarkingStore\SingleStateMarkingStore;
use Symfony\Component\Workflow\Registry;
use Symfony\Component\Workflow\StateMachine;
use Symfony\Component\Workflow\SupportStrategy\InstanceOfSupportStrategy;
use Symfony\Component\Workflow\Transition;
use Symfony\Component\Workflow\Workflow;
use Klik\User\Models\User;


abstract class Process{


    private $type = 'state_machine';

    protected $workflow_name;

    protected $property = 'stage';

    protected $initial_stage = 'start';

    protected $supports;

    private $singleState = true;

    private $definition;

    private $workflow;

    protected $subject;

    protected $stage;

    protected $next_user;

    protected $transition_description;

    protected $in_debug_mode = false;

    protected $admin_email = 'zz_klik@orange.com';

    /**
     * varible: $stages;
     * 
     * Array definiujący stany w jakich może znajdować się proces oraz nazwę wyświetlaną.
     * [
     * 'start' => 'Klient do podjęcia jako Lead IoT',
     * 'in_am' => 'Ankieta w uzupełnieniu',
     * ]
     */
    protected $stages = [];

    /**
     * varible: select_next_user_stages
     * 
     * Definiuje stages w których user może ręcznie wybrać następnego użytkownika w procesie
     * 
     */
    protected $select_next_user_stages = [];

        /**
     * varible: transitions
     * 
     * Array of arrays. Definiuje tranzycje 
     * 'transition_name' => [
     *      'from' => 'stage_name_from',  
     *       'to' => 'stage_name_to',
     *      'name' =>'Wyświetlana nazwa tranzycji'
     *   ],
     * 
     * 'from' może również być tablicą przyjującą listę tranzycji
     */
    protected $transition;


    /**
     * function: completed 
     * 
     * Woływana po każdej tranzycji.
     * 
     * W tej metodzie należy wykonać wszystkie czynności na $this->subject
     * 
     * tj. zmiany na jakiś parametrach oraz jego zapis $this->subject->save();
     */
    abstract public function completed($event);



    public function __construct($subject = null)
    {
        
        $dispatcher = new EventDispatcher();

        $subscriber = new WorkflowSubscriber($this);

        $dispatcher->addSubscriber($subscriber);

        $this->builidDefinition();
    
        $marking = new MethodMarkingStore($this->singleState, $this->property);

        $this->workflow = new StateMachine($this->definition, $marking, $dispatcher, $this->workflow_name);
        
        $this->subject = $subject ? $subject : $this->getEmptyMotion();

    }


    private function builidDefinition()
    {
        $definitionBuilder = new DefinitionBuilder();
 
        $definitionBuilder->addPlaces(array_keys($this->stages));
 
        $definitionBuilder = $this->addTransitions($definitionBuilder);
        
        $this->definition = $definitionBuilder->build();
    }

    public function getProcessStages(){
        return $this->stages;
    }

    public function getStageName($stage_name = null)
    {
        if($stage_name != null){
            $stage = $stage_name;
        }else{
            $stage = $this->subject->stage;
        }

        return $this->stages[$stage];
    }

    public function getTransitionName($transition)
    {
      if(  \key_exists($transition , $this->transitions))
      {
        return $this->transitions[$transition]['name'];
      }
      return $transition;
    }

    public function getTransitionClass($transition)
    {
      if(  \key_exists($transition , $this->transitions))
      {
          if(isset($this->transitions[$transition]['class'])){
              
              return $this->transitions[$transition]['class'];
          }
      }
      return null;
    }

    private function addTransitions($definitionBuilder)
    {

        
        foreach ($this->transitions  as $transitionName => $transition) {
            if (!is_string($transitionName)) {
                $transitionName = $transition['name'];
            }

            foreach ((array)$transition['from'] as $form) {
                $definitionBuilder->addTransition(new Transition($transitionName, $form, $transition['to']));
            }
        }

        return $definitionBuilder;
    }


    public function apply($transition, array $context = null )
    {        

        try{
            $this->workflow->apply($this->subject, $transition, $context);
        }catch(Throwable $th){
            throw $th;
        }        
    }

  



    public function can($transition)
    {
        return $this->workflow->can($this->subject, $transition);
    }


    public function getEnabledTransitions()
    {
        return $this->workflow->getEnabledTransitions($this->subject);
    }
    public function getDefinition()
    {
        return $this->workflow->getDefinition($this->subject);
    }


    /**
     * Function: call
     * 
     * Funkcja wywoływana przez WorkflowSubscriber po każdym evencie. 
     * wywołuje metody transition_* guard_* i completed_* w klasie dziedziczącej z BaseProcess
     * 
     * Nie używać :) 
     */
    public function call($event_name, $event)
    {
        if (\method_exists($this, $event_name)) {
            //Sprawdzenie przy wywołaniu eventu GUARD
            if($event instanceof \Symfony\Component\Workflow\Event\GuardEvent){
               
                // Domyślnie ustawiam GUARD blocked na true
                $event->setBlocked(true);
            }
          
            call_user_func([$this, $event_name], $event);

        }
        
    }


    

    protected function getEmptyMotion()
    {
        $property = $this->property;
    
        $to_return =  new $this->supports;

        $to_return->$property = $this->initial_stage;

        return $to_return;


    }


    public function notify($user, $how = 'email', $template = null, $priority = 3)
    {
        if( is_array($how)){
            $how = \implode('|', $how );
        }

        if(strpos($how, 'email') !== false){
            $this->sendEmail($user, $template, $priority);
        }
   
    }

    private function sendEmail($user, $template, $priority = 3)
    {

        if( !$user instanceof \Klik\User\Models\User)
        {
            $user_query = \Klik\User\Models\User::query();

            if(is_numeric($user)){
                $user_query->where('id', $user);
            }else{
                $user_query->where('mail', $user);
            }

            $user = $user_query->first();
        }

        $to = $user->mail;

        if($this->in_debug_mode){
            $to = $this->admin_email;
        }

        if(!$user->hasReplacer()){
            \Mail::to($to)->priority($priority)->send($template);
        }else{
            \Mail::to($to)->priority($priority)->cc($user->getReplacer()->mail)->send($template);
        }
        //\Mail::to($user->mail)->send($template);
    }

    

    public function __get($name)
    {
        switch ($name) {
            case 'stage':
                return $this->subject->stage;
                break;
            case 'history':
                return $this->subject->process_logs;
                break;
            
            default:
                # code...
                break;
        }
    }


}