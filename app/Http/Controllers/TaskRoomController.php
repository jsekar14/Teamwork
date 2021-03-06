<?php

namespace Teamwork\Http\Controllers;

use Illuminate\Http\Request;
use Teamwork\GroupTask;
use Teamwork\Response;
use \Teamwork\Tasks as Task;
use \Teamwork\Time;
use \Teamwork\Progress;

class TaskRoomController extends Controller
{

    public function taskRoom(Request $request){
      if ($request->clear)
        $clear=true;
      else
        $clear = false;
    	$user = \Teamwork\User::find(\Auth::user()->id);
    	$currentTask = GroupTask::where('group_id',$user->group_id)
                              ->where('completed',0)
                              ->orderBy('order','ASC')
                              ->first();
   		//$currentTask = \Teamwork\GroupTask::where('group_id',$user->group_id)->where('name',$task)->orderBy('created_at','DESC')->first();

   		$request->session()->put('currentGroupTask',$currentTask->id);

   		if($currentTask->name != "Cryptography" && $currentTask->name != "Memory"){
   			return redirect('get-group-task');
   		}

      return view('layouts.participants.task-room')
      	->with('user', $user)
      	->with('task',$currentTask)
        ->with('clear',$clear);
    }
}