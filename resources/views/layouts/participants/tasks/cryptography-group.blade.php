@extends('layouts.bare')

@section('js')
  <script src="{{ URL::asset('js/cryptography.js') }}"></script>
  <script src="{{ URL::asset('js/timer.js') }}"></script>
@stop

@section('css')
  <link rel="stylesheet" href="{{ URL::asset('css/tasks.css') }}">
@stop

@section('content')
<script>

var mapping = <?php echo  $mapping; ?>;
var maxResponses = {{ $maxResponses }};
var whose_turn = {{ $whose_turn }};
var task_id = {{ $task_id }};
var group_id = {{ $user->group_id }};
var local_guess = [];
var responses = '';
var time_remaining;


var trialStage = 1;
var trials = 1;
var isReady = true;
var equations = [];
var hypotheses = [];
var mapping_guess = '';
var payment = 8.00;
var guesses = [];
var page = 1;


$( document ).ready(function() {
  time_remaining = parseInt('{{ $time_remaining }}');
  responses = $('<div>').html('{{ $responses }}')[0].textContent;
  console.log(responses);
  responses = JSON.parse(responses);
  console.log(responses);
  if (responses.length  > 0){
    for(var i = 0; i < responses.length; i++){
      if(responses[i]['prompt'].includes('Guess Full Mapping')){
        console.log(responses[i]['response'].split(', Correct: ')[0]);
        if(responses[i]['response'].split(', Correct: ')[0].includes(','))
          guesses = responses[i]['response'].split(', Correct: ')[0].split(',');
        else
          guesses = [responses[i]['response'].split(', Correct: ')[0]];
        console.log(guesses);
        $(".full-mapping").each(function(i, el){
          
          $(el).val(guesses[i].split('=')[1]);
        });
        //mapping_guess = '['+responses[i]['response'].split(', Correct: ')[0]+']';
        //mapping_guess = JSON.parse(mapping_guess);
        console.log(mapping_guess);
        trials++;
        payment -= 0.50;
      }
      if(responses[i]['prompt'].includes('Propose Hypothesis')){
        $("#hypothesis-result").append('<h5>' + responses[i]['response'].replace(':','is').replace('=',' = ') + '</h5>');
      }
      if(responses[i]['prompt'].includes('Propose Equation')){
        $("#answers").append('<h5 class="answer">' + responses[i]['response'].replace('=',' = ') + '</h5>');
        //equations.push(response[i]['response']);
      }
      if(responses[i]['prompt'].includes('Rule Broken')){
         payment -= 2.00;
      }
    }
    $('#payment').text(payment.toFixed(2));
    $("#trial-counter").html(trials);
    
    //$('#mapping-list').html(localStorage.getItem('mapping'));
    //local_guess = JSON.parse(localStorage.getItem('mapping'));
    //$(".full-mapping").each(function(i, el){
        //$(el).val(local_guess[i]);
      //});
  }
  else{
    localStorage.setItem('group_id',group_id);
    localStorage.setItem('trials',trials); 
    localStorage.setItem('equations',$('#answers').html());
    localStorage.setItem('hypotheses',$('#hypothesis-result').html());
    localStorage.setItem('mapping','[]');
    localStorage.setItem('payment',$('#payment').text());

  }

  whose_turn = parseInt(whose_turn);
  task_id = parseInt(task_id);

  switch(whose_turn){
    case 0:
      $('#submit-mapping').attr('disabled',true);
      $('#submit-mapping').text('Waiting for Team');
      $('#submit-hypothesis').attr('disabled',true);
      $('#submit-hypothesis').text('Waiting for Team');
      $('#order-instructions').modal('toggle');
      break;
    case 1:
      $('#submit-mapping').attr('disabled',true);
      $('#submit-mapping').text('Waiting\nFor Teammates...');
      $('#submit-equation').attr('disabled',true);
      $('#submit-equation').text('Waiting\nFor Teammates...');
      $('#order-instructions').modal('toggle');
      break;
    case 2:
      $('#submit-hypothesis').attr('disabled',true);
      $('#submit-hypothesis').text('Waiting\nFor Teammates...');
      $('#submit-equation').attr('disabled',true);
      $('#submit-equation').text('Waiting\nFor Teammates...');
      $('#order-instructions').modal('toggle');
      break;
    default:
      break;

  }
  Pusher.logToConsole = true;

    var pusher = new Pusher('{{ config("app.PUSHER_APP_KEY") }}', {
      cluster: 'us2'
    });

  $("#alert").hide();
  //$("#hypothesis").hide();
  //$("#guess-full-mapping").hide();
  $("#task-end").hide();

  rules = {
    1:[1,4],
    2:[1,7],
    3:[1,8],
    4:[1,9],
    5:[2,10],
    6:[2,11],
    7:[2,12],
    8:[2,15],
    9:[3,4],
    10:[3,5],
    11:[3,6],
    12:[3,7],
    13:[4,8],
    14:[5,9],
    15:[6,10],
    16:[7,11]
  };

  rule_desc = [
    'The first equation must not contain more than 4 letters',
    'The first equation must contain at least 3 letters',
    'The first equation must contain a minus sign',
    'The second equation must contain the letter F',
    'The second equation must contain the letter G',
    'The second equation must contain the letter H',
    'The second equation must contain the letter I',
    'The third equation must NOT contain the letter A',
    'The third equation must NOT contain the letter B',
    'The third equation must NOT contain the letter C',
    'The third equation must NOT contain the letter D',
    'The fourth equation must contain a minus sign',
    'The fourth equation must NOT contain a minus sign',
    'The fifth equation must contain a minus sign',
    'The fifth equation must NOT contain a minus sign',
  ]

  //create task_id to pass in
  //task_id = Math.floor(Math.random() * 15) + 1;
  console.log(rules[task_id]);
  var crypto = new Cryptography(mapping,rules[task_id]);

  $('#rule_1').text(rule_desc[rules[task_id][0] - 1]);
  $('#rule_2').text(rule_desc[rules[task_id][1] - 1])


  initializeTimer(time_remaining, function() {
    $.post('/task-complete', {_token: "{{ csrf_token() }}"});
    $("#crypto-header").hide();
    $("#crypto-ui").hide();
    $("#task-end").show();
    $('#time-up').modal();
  });

  setTimeout(function() {
    $("#timer-warning").modal();
  }, 540 * 1000);

  var channel = pusher.subscribe('task-channel');
    channel.bind('action-submitted',function(data){
      console.log(data.group_task.whose_turn);
      switch(data.group_task.whose_turn){
        case 0:
          $('#submit-mapping').attr('disabled',true);
          $('#submit-mapping').text('Waiting for Team');
          $('#submit-hypothesis').attr('disabled',true);
          $('#submit-hypothesis').text('Waiting for Team');
          $('#submit-equation').attr('disabled',false);
          $('#submit-equation').text('Submit');
          //$('#order-instructions').modal('toggle');
          trials++;
          $("#trial-counter").html(trials);
          localStorage.setItem('trials',trials);
          if(trials == maxResponses)
            $('#last-trial').modal();
          $('#payment').text((((parseFloat($('#payment').text()) - 0.50) > 0.00) ? (parseFloat($('#payment').text()) - 0.50) : 0.00).toFixed(2));
          localStorage.setItem('payment',$('#payment').text());
          break;
        case 1:
          $('#submit-mapping').attr('disabled',true);
          $('#submit-mapping').text('Waiting for Team');
          $('#submit-equation').attr('disabled',true);
          $('#submit-equation').text('Waiting for Team');
          $('#submit-hypothesis').attr('disabled',false);
          $('#submit-hypothesis').text('Submit');
          //$('#order-instructions').modal('toggle');
          break;
        case 2:
          $('#submit-hypothesis').attr('disabled',true);
          $('#submit-hypothesis').text('Waiting for Team');
          $('#submit-equation').attr('disabled',true);
          $('#submit-equation').text('Waiting for Team');
          $('#submit-mapping').attr('disabled',false);
          $('#submit-mapping').text('Submit');
          //$('#order-instructions').modal('toggle');
          break;
        default:
          break;

      }
    });
    /*
    channel.bind('all-ready', function(data) {

        trials++;
        $("#trial-counter").html(trials);

        if(trials == maxResponses) {
          $('#last-trial').modal();
        }
      $('.sub-btn').attr('disabled',false);

      $('.sub-btn').text('Submit');
      //isReady = true;
      $('#payment').text((((parseInt($('#payment').text()) - 0.50) > 0.00) ? (parseInt($('#payment').text()) - 0.50) : 0.00).toFixed(2));

    });*/
    channel.bind('task-complete', function(data){
      localStorage.clear();
      $("#task-result").val(1);
      $("#crypto-header").hide();
      $("#crypto-ui").hide();
      $("#task-end").show();
    });
    channel.bind('rule-broken', function(data){
      $("#rule_broken").modal('toggle');
      $('#payment').text((((parseFloat($('#payment').text()) - 2.00) > 0.00) ? (parseFloat($('#payment').text()) - 2.00) : 0.00).toFixed(2));
      localStorage.setItem('payment',$('#payment').text());
    });
    channel.bind('clear-storage', function(data){
      console.log('freedom!');
      localStorage.clear();
      window.location.href='/participant-login';
    });

  $("#ok-time-up").on('click', function(event) {
    localStorage.clear();
    $("#task-result").val(0);
    $("#crypto-header").hide();
    $("#crypto-ui").hide();
    $("#task-end").show();
    $('#time-up').modal('toggle');
    event.preventDefault();
  });

  $("#submit-equation").on("click", function(event) {
      event.preventDefault();
      $("#alert").hide();

      var equation = $("#equation").val().toUpperCase().replace(/=/g, '');

      if(equation == '') {
        event.preventDefault();
        $('#invalid_equation').modal('toggle');
        return;
      };

      try {
        var res = crypto.parseEquation(equation,trials);
        var answer = res[0];
        var rule_broken = res[1];
        console.log('??????');
        console.log(res);
        console.log(rule_broken);
        if(rule_broken){
          $.post("/rule-broken", {
            _token: "{{ csrf_token() }}",
            rule_broken: rule_broken
          });
        }

        $("#answers").append('<h5 class="answer">' + equation + ' = ' + answer + '</h5>');
        localStorage.setItem('equations',$('#answers').html());
        $("#equation").val('');

        $.post("/cryptography", {
            _token: "{{ csrf_token() }}",
            prompt: "Propose Equation",
            guess: equation + '=' + answer
          }, function(data) {
            console.log(data);
              
            if(data == 'WAIT'){

              $('#submit-equation').text('Waiting for Team');
              $('#submit-equation').attr('disabled',true);
              //Ready = false;
            }
           //sReady = false;
            
          } );
      }
      catch(e) {
        //var res = crypto.parseEquation(equation,trials);
        console.log(e);
        $('#alert-text-equation').text(e);
        $('#invalid_equation').modal('toggle');
        //$("#alert").html(e);
        //$("#alert").show();
      }
    event.preventDefault();
      
      
  });

  $("#submit-hypothesis").on("click", function(event){
      event.preventDefault();
      if ($("#hypothesis-left").val() === '---' || $("#hypothesis-right").val() === '---'){
        $('#invalid-hypothesis').modal('toggle');
        return false;
      }

      var result = crypto.testHypothesis($("#hypothesis-left").val(), $("#hypothesis-right").val());
      var output = (result) ? "true" : "false";
      $("#hypothesis-result").append('<h5>' + $("#hypothesis-left").val() + " = " + $("#hypothesis-right").val() + " is " + output + '</h5>');
      localStorage.setItem('hypotheses',$('#hypothesis-result').html());

      $.post("/cryptography", {
          _token: "{{ csrf_token() }}",
          prompt: "Propose Hypothesis",
          guess: $("#hypothesis-left").val() + '=' + $("#hypothesis-right").val() + ' : ' + output
        }, function(data) {
          console.log(data);
          if(data == 'WAIT'){
            $('#submit-hypothesis').text('Waiting for Team');
            $('#submit-hypothesis').attr('disabled',true);
            //isReady = false;
          }
          isReady = false;
          
        });
      event.preventDefault();
  });

  $("#submit-mapping").on("click", function(event){
      event.preventDefault();
      var result = true;
      var guessStr = '';
      var mappingList = '';
      var mappingArr = [];

      $(".full-mapping").each(function(i, el){
        mappingArr.push($(el).val());
        mappingList += '<span>' + $(el).attr('name') + ' = ' + $(el).val() + '</span>';
        guessStr += $(el).attr('name') + '=' + $(el).val() + ',';
        if(crypto.testHypothesis($(el).attr('name'), $(el).val()) == false) result = false;
      });

      localStorage.setItem('mapping',JSON.stringify(mappingArr));

      //$("#mapping-list").html(mappingList);
      $.post("/cryptography", {
          _token: "{{ csrf_token() }}",
          prompt: "Guess Full Mapping",
          mapping: JSON.stringify(mapping),
          guess: guessStr
        }, function(data) {
          console.log(data);
          if(data=='WAIT'){
            $('#submit-mapping').text('Waiting for Team');
            $('#submit-mapping').attr('disabled',true);
            isReady = false;
          }
          //isReady = false;
          
        } );

      if(result) {
        $.post('/task-complete', {_token: "{{ csrf_token() }}"});
      }

      else if (trials == maxResponses) {
        $.post('/task-complete', {_token: "{{ csrf_token() }}"});
      }
      event.preventDefault();
  });

  $('#next-button').on('click',function(event){
    if (page == 1 ){
      $('#back-button').css('display','block');
    }
    $('#page'+page.toString()).css('display','none');

    page += 1;

    if (page == 4){
      $('#next-button').css('display','none');
    }
    $('#page'+page.toString()).css('display','block');
    
  });
  $('#back-button').on('click',function(event){
    if (page == 4 ){
      $('#next-button').css('display','block');
    }
    $('#page'+page.toString()).css('display','none');

    page -= 1;

    if (page == 1){
      $('#back-button').css('display','none');
    }
    $('#page'+page.toString()).css('display','block');
    
  });


});

</script>

<div class="container" >
  <div class="row" id="crypto-ui">
      <div class="col-sm-12 text-center">
        @if ($user->group_role == "leader")
          <form name="cryptography" id="crypto-form">
            <div class='row'>
              <div class="col-sm-3 " style="border-right:1px solid #DCDCDC">
                <h4 class="text-guess">Current Guesses</h4>
                <div class='col-sm-11' style='float:center;margin:auto' id="mapping-list" >
                  @foreach($sorted as $key => $el)
                    <div style='display:flex'>
                      <span>{{ $el }} = </span>
                      <select data-stop-refresh="true" style='width:55%;margin-left:auto;' class="form-control full-mapping" name="{{ $el }}">
                          <option value='---'>---</option>
                          @for($i = 0; $i < count($sorted); $i++)
                            <option value="{{ $i }}">{{ $i }}</option>
                          @endfor
                      </select>
                    </div>
                  @endforeach
                </div>
              </div>
              <div id='leader-dashboard' class='col-sm-9'>
                <div class='row'>
                  <div class='col-sm-12'>
                    <h4 style='color:#595959; background:#f2f2f2;margin-right:auto;text-align:left'>LEADER Dashboard</h4>
                  </div>
                </div>
                <div class='row'>
                  <div class='col-sm-9'>
                    <p style='text-align:left'><strong>Equation rules</strong><br />
                    1. <span id='rule_1'>The first equation must contain at least 3 letters</span><br />
                    2. <span id='rule_2'>The fifth equation must NOT contain a minus sign</span><br />
                    <span style='color:red'><i>Breaking a rule costs $2</i></span>
                    </p>
                  </div>
                  <div class='col-sm-3'>
                    <h4 style='text-align:right'><b id='timer'></b></h4>
                  </div>
                </div>

                <p style='text-align:left'><b style='font-size:20px'>Current payment: $<span id='payment'>8.00</span></b><br>
                <span style='color:red;text-align:left'><i>Each 'trial' costs $0.50</i></span>
                </p><br/>
                <div class='row'>
                  <div class='col-sm-5'>
                    <button style='float:left;' type='button' class="sub-btn btn btn-lg btn-primary" id="submit-mapping" >Submit</button><br />
                    <button type="button" class="btn btn-sm btn-warning" data-toggle="modal" data-target="#review-instructions" style='float:left;margin-top:20px'>Review Instructions</button>
                    <button type="button" class="btn btn-sm btn-warning" data-toggle="modal" data-target="#device-instructions" style='float:left;margin-top:20px'>Mic/Camera Guide</button>
                  </div>
                  <div class='col-sm-7'>
                    <p style='text-align:left;justify-content: left'><i>Click 'submit' <b>after each trial.</b> You do NOT need to guess all the letters to click submit. If you have all the letters correct, the task is complete! You will be able to submit once both of your teammates have finished their turns.</i></p>
                  </div>
                </div>


              </div>
            </div>
          </form>
        @elseif ($user->group_role == 'follower1')
          <form name="cryptography" id="crypto-form">
            <div class='row'>
              <div class="col-sm-7 " style="border-right:1px solid #DCDCDC;min-width:">
                <div class='col-sm-9' style='margin:auto;' id="propose-equation">
                  <h5>Trial <span style='width:7px !important' id="trial-counter">1</span></h5>
                  <h4 class="text-equation">Enter an equation</h4>
                  <h5>Enter the left-hand side of an equation, using letters, addition and
                    subtraction: e.g. “A+B”. Please only use the letters A-J plus '+' and '-'.
                  </h5>
                  <div id="alert" class="alert alert-danger" role="alert"></div>
                  <div class="form-group">
                    <input type="text" class="form-control" name="equation" id="equation">
                  </div>
                </div>
                <div class="text-center">
                  <button type='button' class="sub-btn btn btn-lg btn-primary" id="submit-equation" >Submit</button>
                </div>
                <div class="text-center">
                  <button type="button" class="btn btn-sm btn-warning" data-toggle="modal" data-target="#review-instructions" style='margin-top:10px'>Review Instructions</button>

                </div>
                <div class="text-center">
                  <button type="button" class="btn btn-sm btn-warning" data-toggle="modal" data-target="#device-instructions" style='margin-top:10px'>Mic/Camera Guide</button>
                </div>
              </div>
              <div class="col-md-5">
                <h4 class="text-equation">Equation History</h4>
                <div id="answers"></div>
              </div>
            </div>
          </form>

        @elseif ($user->group_role == 'follower2')
          <form name="cryptography" id="crypto-form">
            <div class='row'>
              <div class="col-sm-7 " style="border-right:1px solid #DCDCDC;min-width:">
                <div class='col-sm-9' style='margin:auto;' id="hypothesis">
                    <h5>Trial <span style='width:7px !important' id="trial-counter">1</span></h5>
                    <h4 class="text-hypothesis">Make a hypothesis</h4>
                    <h5>
                      Hypothesize the value of a single letter (e.g. F = 7)
                    </h5>
                    <select class="form-control " id="hypothesis-left">
                        <option>---</option>
                        @foreach($sorted as $key => $el)
                          <option>{{ $el }}</option>
                        @endforeach
                    </select>
                    <span>
                      =
                    </span>
                    <select class="form-control " id="hypothesis-right">
                        <option>---</option>
                        @for($i = 0; $i < count($sorted); $i++)
                          <option>{{ $i }}</option>
                        @endfor
                    </select>
                    <div class="text-center">
                      <button type='button' class="sub-btn btn btn-lg btn-primary" id="submit-hypothesis" >Submit</button>
                    </div>
                    <div class="text-center">
                      <button type="button" class="btn btn-sm btn-warning" data-toggle="modal" data-target="#review-instructions" style='margin-top:10px'>Review Instructions</button>
                    </div>
                    <div class="text-center">
                      <button type="button" class="btn btn-sm btn-warning" data-toggle="modal" data-target="#device-instructions" style='margin-top:10px'>Mic/Camera Guide</button>
                    </div>
                </div>
                
              </div>
              <div class="col-md-5">
                <h4 class="text-hypothesis">Hypotheses</h4>
                <div id="hypothesis-result"></div>
              </div>
            </div>
          </form>

        @else
    @endif
    
</div></div>

<div class="row vertical-center" id="task-end">
      <div class="col-md-8 offset-md-2">
        @if($isReporter)
          <form action="/cryptography-end" id="cryptography-end-form" method="post">
        @else
          <form action="/cryptography-end" id="cryptography-end-form" method="post">
        @endif
          {{ csrf_field() }}
          <input type="hidden" name="task_result" id="task-result" value="0">
          <h3 class="text-center">
            You have completed the Cryptography Task.<br>
            Press the button below to continue
          </h3>
          <div class="text-center">
            <button class="btn btn-lg btn-primary" id="continue" type="submit">Continue</button>
          </div>
        </form>
      </div>
  </div>

  <div class="modal fade" id="last-trial">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h4 class="modal-title text-center">
          This is your last trial. The guesses you submit at the end of the
          trial will be your final answer. Remember, you get points for all
          the letter values you correctly identify
          </h4>
        </div>
        <div class="modal-body text-center">
          <button class="btn btn-lg btn-primary pull-right" id="ok-last-trial" data-dismiss="modal" type="button">Ok</button>
        </div>
      </div><!-- modal-content -->
    </div><!-- modal-dialog -->
  </div><!-- modal -->

  <div class="modal fade" id="review-instructions">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-body text-center">
          <h5>
          Each letter from A to J has a value from 0 to 9. Each letter has a
          different value. Your goal is to uncover the value of each letter by
          using “trials”. A trial has three steps. First you <span class="text-equation">enter an equation</span>
          (e.g. “A+B”). You can only use addition and subtraction. Second, you
          <span class="text-hypothesis">make a hypothesis</span> (e.g. “D=4”) and the computer will tell you if this
          hypothesis is TRUE or FALSE. Third, you can <span class="text-guess">guess</span> the values of each
          letter. You don’t have to make guesses for all the letters.
          </h5>
          <h5>
            Try to find out the value of each letter WITH AS FEW TRIALS AS
            POSSIBLE. You have {{ $maxResponses }} trials and 10 minutes. If you run out of
            trials, or time, you will get some points for any of the letters
            you have correctly identified.
          </h5>
        </div>
        <div class="modal-body text-center">
          <button class="btn btn-lg btn-primary pull-right" data-dismiss="modal" type="button">Ok</button>
        </div>
      </div><!-- modal-content -->
    </div><!-- modal-dialog -->
  </div><!-- modal -->

  <div class="modal fade" id="order-instructions">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-body text-center">
          <h5>
            In each round of this task, you and your teammates will complete your assigned steps in this order: submitting an equation, submitting an hypothesis, and submitting a guess at the final answer. <b>If at any point you see that your submit button is disabled and says 'Waiting for Team', this means that someone else on your team is taking their turn. Check in with your teammates if you are ever unsure of whose turn it is.
          </h5>
        </div>
        <div class="modal-body text-center">
          <button class="btn btn-lg btn-primary pull-right" data-dismiss="modal" type="button">Ok</button>
        </div>
      </div><!-- modal-content -->
    </div><!-- modal-dialog -->
  </div><!-- modal -->

  <div class="modal fade" id="timer-warning">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h4 class="modal-title text-center">
          You have one minute remaining.
          </h4>
        </div>
        <div class="modal-body text-center">
          <button class="btn btn-lg btn-primary pull-right" id="ok-timer-warning" data-dismiss="modal" type="button">Ok</button>
        </div>
      </div><!-- modal-content -->
    </div><!-- modal-dialog -->
  </div><!-- modal -->

  <div class="modal fade" id="time-up">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h4 class="modal-title text-center">
          Your time is up. You will get points for your current guesses
          that are correct.
          </h4>
        </div>
        <div class="modal-body text-center">
          <button class="btn btn-lg btn-primary pull-right" id="ok-time-up" data-dismiss="modal" type="button">Ok</button>
        </div>
      </div><!-- modal-content -->
    </div><!-- modal-dialog -->
  </div><!-- modal -->

  <div class="modal fade" id="invalid_equation">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h4 id='alert-text-equation' class="modal-title text-center">
          The equation you submitted is not valid. Please only use the letters A-J plus '+' and '-'.
          </h4>
        </div>
        <div class="modal-body text-center">
          <button class="btn btn-lg btn-primary pull-right"  data-dismiss="modal" type="button">Ok</button>
        </div>
      </div><!-- modal-content -->
    </div><!-- modal-dialog -->
  </div><!-- modal -->

  <div class="modal fade" id="device-instructions">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header" style="display:block">
          <h4 id='page1'>
          If your teammates can't hear or see you, your browser may be blocking the site from accessing your microphone/camera. </h4>
          <h4 id='page2' style='display:none;'>Check the URL bar at the top of your web browser for a small camera/microphone icon at the far right. Click it and select 'allow' or 'always allow'. If you don't see the icon, that's okay. </h4>
          <h4 id='page3' style='display:none;'>
          Refresh your page. Wait and see if the web browser asks you to allow access to the microphone. Select "allow" and proceed with the task.</h4><br/>
          <h4 id='page4' style='display:none;'>
          If your issue persists, you may need to go into your system prefences / control panel and confirm that you have a working microphone and camera available.
          </h4>
          <div style='display:flex;'>
            <button id='back-button' class='btn btn-lg btn-primary' style='display:none;margin:auto'>Back</button>
            <button id='next-button' class='btn btn-lg btn-primary' style='margin:auto'>Next</button>
          </div>
        </div>
        <div class="modal-body text-center">
          <button class="btn btn-lg btn-primary pull-right" data-dismiss="modal" type="button">Ok</button>
        </div>
      </div><!-- modal-content -->
    </div><!-- modal-dialog -->
  </div><!-- modal -->

    <div class="modal fade" id="invalid-hypothesis">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h4 id='alert-text' class="modal-title text-center">
            The hypothesis you submitted is not valid. Please make sure you have selected a letter and a value from the dropdowns.
          </h4>
        </div>
        <div class="modal-body text-center">
          <button class="btn btn-lg btn-primary pull-right"  data-dismiss="modal" type="button">Ok</button>
        </div>
      </div><!-- modal-content -->
    </div><!-- modal-dialog -->
  </div><!-- modal -->

  <div class="modal fade" id="rule_broken">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h4 class="modal-title text-center">
          Your team has submitted an equation which violates one of your equation rules. Your payment has decreased by $2. Check with the "leader" of your team to see what these rules are. 
          </h4>
        </div>
        <div class="modal-body text-center">
          <button class="btn btn-lg btn-primary pull-right" id="ok-rule-broken" data-dismiss="modal" type="button">Ok</button>
        </div>
      </div><!-- modal-content -->
    </div><!-- modal-dialog -->
  </div><!-- modal -->
@stop
