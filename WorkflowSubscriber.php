<?php

namespace Klik\SimpleProcess;

use Symfony\Component\Workflow\Event\Event;
use Symfony\Component\Workflow\Event\GuardEvent as SymfonyGuardEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;




class WorkflowSubscriber implements EventSubscriberInterface
{
    private $process;

    public function __construct($process)
    {
        $this->process = $process;
    }

    public function guardEvent(SymfonyGuardEvent $event)
    {
        // dd($event);
        $workflowName   = $event->getWorkflowName();
        $transitionName = $event->getTransition()->getName();
        
        try {
            $this->process->call('guard_'.$transitionName, $event);
        } catch (\Throwable $th) {
            throw $th;
        }

        
    }

    public function leaveEvent(Event $event)
    {
        // $places       = $event->getTransition()->getFroms();
        // $workflowName = $event->getWorkflowName();
        // $transitionName = $event->getTransition()->getName();

        // try {
        //     $this->process->call('leave_'.$transitionName, $event);

        //     $this->process->call('leave', $event);
        // } catch (\Throwable $th) {
        //     throw $th;
        // }
 
    }

    public function transitionEvent(Event $event)
    {
        $workflowName   = $event->getWorkflowName();
        $transitionName = $event->getTransition()->getName();

       

        try{
            $this->process->call('transition_'.$transitionName, $event);

            $this->process->call('transition', $event);
            
        }catch(Throwable $th){
            throw $th;
        }
    }

    public function enterEvent(Event $event)
    {
        // $places       = $event->getTransition()->getTos();
        // $workflowName = $event->getWorkflowName();
    }

    public function enteredEvent(Event $event)
    {
        // $workflowName = $event->getWorkflowName();

        // $transition   = $event->getTransition();
     
    }

    public function completedEvent(Event $event)
    {
        $workflowName   = $event->getWorkflowName();
        
        $transitionName = $event->getTransition()->getName();
        
        try{
            $this->process->call('completed_'.$transitionName, $event);

            $this->process->call('completed', $event);

        }catch(Throwable $th){
            throw $th;
        }
        
    }

    public function announceEvent($event)
    {
    }

    public static function getSubscribedEvents()
    {
        return [
            'workflow.guard' => ['guardEvent'],
            'workflow.leave' => ['leaveEvent'],
            'workflow.transition' => ['transitionEvent'],
            'workflow.enter' => ['enterEvent'],
            'workflow.entered' => ['enteredEvent'],
            'workflow.completed' => ['completedEvent'],
            'workflow.announce' => ['announceEvent'],
        ];
    }
}