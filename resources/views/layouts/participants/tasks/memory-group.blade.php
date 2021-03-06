@extends('layouts.master')

@section('js')
  <script src="{{ URL::asset('js/memory.js') }}"></script>
  <script src="{{ URL::asset('js/image-preloader.js') }}"></script>
@stop

@section('css')
  <link rel="stylesheet" href="{{ URL::asset('css/tasks.css') }}">
@stop

@section('content')

<script>
  var itv;
  function leaderAnswered(){
    $.post('leader-answered', {
            _token : "{{ csrf_token() }}",
            key : '1'
    });
  }

    var tests = <?php echo  $enc_tests; ?>;
    $( document ).ready(function() {
      //console.log("{{ $scores }}");
      scores = JSON.parse("{{ $scores }}".replace(/&quot;/g,'"'));
      console.log(scores);
      count = 1;
      jQuery.each(scores, function(key, val) {
        $('#pid_'+count.toString()).text(key);
        $('#faces_'+count.toString()).text(val['faces']);
        $('#words_'+count.toString()).text(val['words']);
        $('#stories_'+count.toString()).text(val['story']);
        count++;
      });
      for (var i = 0; i < localStorage.length; i++){
        var key = localStorage.key(i);
        console.log('$%$%$%$%$%$%');
        console.log(key);
        var val = localStorage.getItem(key);
        console.log(val)

        if (key.includes('response')){
          if (key.includes('checkbox')){
            console.log('im in');
            key = key.split('#')[0];
            var vals = val.split(',');
            console.log(vals);
            vals.forEach(function(e){
              $('input[name="'+key.toString()+'"][value="'+e+'"]').attr('checked',true);
              console.log('input[name="'+key.toString()+'"][value="'+e+'"]');
              console.log($('input[name="'+key.toString()+'"][value="'+e+'"]'));
            });
            console.log('im in');
          }
          else{
            $('input[name="'+key.toString()+'"]').val(val);
          }
        }
      }

      Pusher.logToConsole = true;

      var pusher = new Pusher('{{ config("app.PUSHER_APP_KEY") }}', {
        cluster: 'us2'
      });
      var channel = pusher.subscribe('memory-channel');

      console.log("{{ $user }}");
      console.log('##############');

      userId = {{ \Auth::user()->id }};
      groupId = {{ \Auth::user()->group_id }};
      taskId = {{ $taskId }};
      isReporter = {{ $isReporter }};
      var preloadImages = <?= json_encode($imgsToPreload); ?>
      // Preload all images
      preload(preloadImages);

      var callback = function() {
        localStorage.clear();
        clearInterval(itv);
        $('#memory-form').submit();
        
        /*console.log("BINGOOOOOO");
          $.ajax({url:'memory-group',
            type:'post',
            data:$('#memory-form').serialize(),
            success:function(data){
              console.log(data);
              console.log('1');
              $.ajax({url:'/check-task',
                type:'get',
                _token: "{{ csrf_token() }}",
                success: function(task_name){
                  console.log(2);
                  console.log('###');
                  console.log(task_name);
                  if (task_name != "Cryptography" && task_name !='Memory')
                    window.location.href='/end-group-task';
                  else
                    $('#content').html(data);
                }
              });
            }

          }).fail(function(){console.log('fail')});*/
      };
      var memory = new Memory(tests, isReporter, callback);
      memory.begin();

      channel.bind('leader-answered', function(data){
        if(data['user']['group_id'].toString() === "{{ $user->group_id }}" && "{{ $user->group_role }}" != "leader")
          memory.advance();
      });

      $('.not-reporter').on('click', function(event) {
        $("#role").val('not-reporter');
        $("#memory-form").submit();
        event.preventDefault();
        event.stopImmediatePropagation();
      });

      $('.reporter').on('click', function(event) {
        $("#role").val('reporter');
      });

      $('.memory-nav').on('click', function(event) {
        if(memory.hasPopup()) {
          event.stopImmediatePropagation();
          return;
        }
        if(memory.hasWait()) {
          memory.markMemoryChoice(userId, groupId, taskId, "{{ csrf_token() }}", "#waiting");
          event.stopImmediatePropagation();
          return;
        }
        if(memory.hasLeaderWait()) {
          memory.markMemoryChoice(userId, groupId, taskId, "{{ csrf_token() }}", "#leader_waiting");
          event.stopImmediatePropagation();
          return;
        }

        memory.advance();

        event.preventDefault();
      });

      $('.choose-mem-review-type').on('click', function(event) {
        memory.setGroupTestReviewChoice($(this).data('type'));
        $('.mem-btn-' + $(this).data('type')).addClass('btn-lg');
        $.post( "/record-mem-review-selection", { user_id: userId, group_tasks_id: taskId, type: $(this).data('type'), _token: "{{ csrf_token() }}" } );
        memory.markMemoryChoice(userId, groupId, taskId, "{{ csrf_token() }}", "#waiting");
        event.preventDefault();
      });

      $('.switch-mem-review-type').on('click', function(event) {
        memory.switchMemReviewType($(this).data('type'));
        $('.switch-mem-review-type').removeClass('btn-lg');
        $.post( "/record-mem-review-selection", { user_id: userId, group_tasks_id: taskId, type: $(this).data('type'), _token: "{{ csrf_token() }}" } );
        $('.mem-btn-' + $(this).data('type')).addClass('btn-lg');
        event.preventDefault();
      });

      $(document).on("timerComplete", function() {
        $.post( "/record-mem-review-selection", { user_id: userId, group_tasks_id: taskId, type: 'Review is complete', _token: "{{ csrf_token() }}" } );
      });

      $('.select-all').on('change', function(event) {
        var response = $(this).attr('name');
        $('.no-selection[name="'+response+'"]').attr('checked', false);
      });

      // Target images
      $('.target-nav').on('click', function(event) {
        memory.navImgTarget($(this).attr('name'));
        event.preventDefault();
      });

      $('.word-nav').on('click', function(event) {
        memory.navWordTarget($(this).attr('name'));
        event.preventDefault();
      });

      $(document).keydown(function(event) {
        console.log("{{ $user }}");
        console.log('##############');

        var key = event.key;
        if(((key == 1 || key == 2 || key == 3) && ($(".memory-img").is(":visible") || $(".story-choices").is(":visible"))) && "{{ $user->group_role }}" === "leader" ) {
          memory.advanceImageTest(key);
          $.post('leader-answered', {
            _token : "{{ csrf_token() }}",
            key : key
          });

        }

      });
      $('input[name*="response"]').on('change',function(event){
        console.log('input changed');
        if($(event.target).attr('type') === "checkbox"){
          var radios = $('input[name*="'+$(event.target).attr('name')+'"][type="checkbox"]');
          var checked_vals = [];
          $('input[name*="'+$(event.target).attr('name')+'"][type="checkbox"]').each(function(i){
            if($(this).is(':checked')){
              checked_vals.push($(this).val());
            }
          });
          localStorage.setItem($(event.target).attr('name')+'#checkbox',checked_vals.join(','));
        }
        else
          localStorage.setItem($(event.target).attr('name'),$(event.target).val());
      });

      $("#popup-continue").on('click', function(event) {
        $("#popup").modal('toggle');
        //memory.advance();
      })
    });

</script>

<div class="container">
  <div class="row vertical-center">
    <div class="col-md-12 text-center">
      <form id="memory-form" action="/memory-group" method="post">
        {{ csrf_field() }}
        @foreach($tests as $key => $test)

          @if($test['task_type'] == 'intro')

          @endif {{-- End if type = intro --}}

          @if($test['task_type'] == 'mixed')

            @foreach($test['blocks'] as $b_key => $block)

              @if($block['type'] == 'text_intro')
                <div class="memory memory-text text" id="memory_{{ $key }}_{{ $b_key }}">
                  @if(isset($block['header']))
                    <h2 class="text-primary">{{ $block['header'] }}</h2>
                    <h3 class="text-success">
                      Task {{ \Session::get('completedTasks') + 1 }} of {{ \Session::get('totalTasks') }}
                    </h3>
                  @endif
                  @foreach($block['text'] as $text)
                    <h4>{!! $text !!}</h4>
                  @endforeach
                  <div class="text-center">
                    <input class="btn btn-primary memory-nav btn-lg"
                           type="button" name="next"
                           id="continue_{{ $key }}_{{ $b_key }}"
                           value="Next">
                  </div>
                </div>
              @endif {{-- End if blocktype = text_intro --}}

              @if($block['type'] == 'text')
                @if(isset($block['role']))
                    <div class="memory memory-text text" id="memory_{{ $key }}_{{ $b_key }}">
                      @if(isset($block['header']))
                        <h2 class="text-primary">{{ $block['header'] }}</h2>
                      @endif
                      @foreach($block[$user['group_role']] as $text)

                        <h4>{!! $text !!}</h4>
                      @endforeach
                      <div class="text-center">
                        <input class="btn btn-primary memory-nav btn-lg"
                               type="button" name="next"
                               id="continue_{{ $key }}_{{ $b_key }}"
                               value="Next">
                      </div>
                    </div>
                @else
                  <div class="memory memory-text text" id="memory_{{ $key }}_{{ $b_key }}">
                      @if(isset($block['header']))
                        <h2 class="text-primary">{{ $block['header'] }}</h2>
                      @endif
                      @foreach($block['text'] as $text)

                        <h4>{!! $text !!}</h4>
                      @endforeach
                      <div class="text-center">
                        <input class="btn btn-primary memory-nav btn-lg"
                               type="button" name="next"
                               id="continue_{{ $key }}_{{ $b_key }}"
                               value="Next">
                      </div>
                    </div>
                @endif
              @endif {{-- End if blocktype = text --}}

              @if($block['type'] == 'review_choice')
                <div class="memory memory-text text" id="memory_{{ $key }}_{{ $b_key }}">
                  @if(isset($block['header']))
                    <h2 class="text-primary">{{ $block['header'] }}</h2>
                  @endif
                  @foreach($block['text'] as $text)
                    <h4>{!! $text !!}</h4>
                  @endforeach
                  <div class="row">
                    @foreach($block['choices'] as $choice)
                    <div class="col-md-4">
                      <input class="btn btn-block btn-{{$choice['color']}} choose-mem-review-type" 
                             type="button" name="next"
                             data-type="{{ $choice['type'] }}"
                             value="{{ ucfirst($choice['type']) }}">
                    </div>
                    @endforeach
                  </div>
                </div>
              @endif {{-- End if blocktype = review_choice --}}

              @if($block['type'] == 'mixed_review')
                <div class="memory memory-text text" id="memory_{{ $key }}_{{ $b_key }}">
                  <div class="float-right text-primary text-secondary"><h4>Time remaining:</h4><span class="timer text-primary" id="timer_{{ $key }}_{{ $b_key }}"></span></div><br>
                  @if(isset($block['header']))
                    <h2 class="text-primary">{{ $block['header'] }}</h2>
                  @endif
                  @foreach($block['text'] as $text)
                    <h4>{!! $text !!}</h4>
                  @endforeach
                  <div class="row">
                    <div class="col-md-12">
                      <p class="float-left text-left">
                        Use these buttons to switch <br>between different stimuli types:
                      </p>
                    </div>
                  </div>
                  <div class="row">
                    <div class="col-md-12">
                      <ul class="nav nav-pills float-left">
                        @foreach($block['choices'] as $choice)
                          <li class="nav-item ml-md-4">
                            <button class="btn btn-{{$choice['color']}} switch-mem-review-type mem-btn-{{ $choice['type'] }}" data-type="{{ $choice['type'] }}">{{ ucfirst($choice['type']) }}</button>
                          </li>
                        @endforeach
                      </ul>
                    </div>
                  </div>

                  <div class="row">
                    <div class="col-md-12" style="min-height:480px;">
                      @foreach($block['types'] as $memType)
                        <div id="{{ $memType['type'] }}_{{ $key }}_{{ $b_key }}" class="memory-review mixed-mem-targets">

                          @if($memType['type'] == 'images')
                            @foreach($memType['targets'] as $img_key => $img)
                              <img class="target-img target img-target-{{ $img_key }} target-img-{{ $testName }}" src="{{ $memType['directory'].$img }}">
                            @endforeach
                            <div class="text-center mt-lg-2">
                              <input class="btn btn-primary target-nav btn-lg" type="button" name="back" id="back" value="Change Perspective">
                            </div>
                          @endif {{-- end target images --}}

                          @if($memType['type'] == 'words')
                            @foreach($memType['targets'] as $word_key => $word)
                              <div class="target-word target word-target-{{ $word_key }}">
                                <h1 class="text-center">{{ $word }}</h1>
                              </div>
                            @endforeach
                            <div id="instr_nav" class="text-center">
                              <input class="btn btn-primary word-nav back btn-lg" type="button" name="back" id="back" disabled="true", value="Previous Word">
                              <input class="btn btn-primary word-nav next btn-lg" type="button" name="next" id="next" value="Next Word"><br />
                            </div>
                          @endif {{-- end target words --}}

                          @if($memType['type'] == 'stories')
                            @foreach($memType['targets'] as $num => $target)
                              <h4 class="text-left mt-lg-4">{{ $num + 1 }}: {{ $target }}</h4>
                              <hr>
                            @endforeach
                          @endif {{-- end target stories --}}

                        </div>
                      @endforeach
                    </div>
                  </div>
                </div>
              @endif {{-- End if blocktype = mixed_review --}}

              @if($block['type'] == 'practice_test_stories')
                <div class="memory test practice-test" id="memory_{{ $key }}_{{ $b_key }}">
                  <h4>{{ $block['prompt'] }}</h4>
                  @if($user->group_role == 'leader')
                    <h4>As the leader, you can <strong>type the number '1', '2', or '3' on your keyboard</strong> to enter the group's answer</h4>
                  @endif
                  <div class="row">
                    <div class="col-md-6 offset-md-3 text-left story-choices">
                      @foreach($block['choices'] as $c_key => $choice)
                          <h4>
                            {{ $c_key + 1 }}) {{ $choice }}
                          </h4>
                      @endforeach
                      <input type="hidden" 
                      name="response_{{ $key }}_{{ $b_key }}"
                             id="response_{{ $key }}_{{ $b_key }}">
                    </div>
                  </div>
                </div>
              @endif {{-- End if blocktype = practice_test_stories --}}

              @if($block['type'] == 'test_stories')
              <div class="memory test practice-test" id="memory_{{ $key }}_{{ $b_key }}">
                <h4>{{ $block['prompt'] }}</h4>
                @if($user->group_role == 'leader')
                    <h4>As the leader, you can <strong>type the number '1', '2', or '3' on your keyboard</strong> to enter the group's answer</h4>
                  @endif
                <div class="row">
                  <div class="col-md-6 offset-md-3 text-left story-choices">
                    @foreach($block['choices'] as $c_key => $choice)
                        <h4>
                          {{ $c_key + 1 }}) {{ $choice }}
                        </h4>
                    @endforeach
                    <input type="hidden" 
                    name="response_{{ $key }}_{{ $b_key }}"
                           id="response_{{ $key }}_{{ $b_key }}">
                 </div>
               </div>
              </div>
              @endif {{-- End if blocktype = test_stories --}}

              @if($block['type'] == 'practice_test_words' || $block['type'] == 'test_words')
                <div class="memory test" id="memory_{{ $key }}_{{ $b_key }}">
                  <h4>{{ $block['prompt'] }}</h4>
                  @if($user->group_role == 'leader')
                    <h4>As the leader, <strong>select all that apply,</strong> then click "Next"</h4>
                  @endif
                  <div class="row justify-content-md-center word-choices">
                    @foreach($block['choices'] as $c_key => $choice)
                      <div class="col-md-3 form-group">
                        <h2>
                        <label
                               for="response_{{ $key }}_{{ $b_key }}">
                               {{ $choice }}
                        </label><br>
                        
                        @if($user->group_role == 'leader')
                          <input type="checkbox" style='pointer-events: auto !important;position:auto !important; opacity:1 !important;'  
                        
                               name="response_{{ $key }}_{{ $b_key }}[]"
                               value="{{ $c_key + 1 }}">
                        @endif
                        </h2>
                      </div>
                    @endforeach
                  </div>
                  <div class="text-center">
                    
                    @if($user->group_role == 'leader')
                      <input class="btn btn-primary memory-nav btn-lg" onclick='leaderAnswered()'  type="button" name="next"
                           id="continue_{{ $key }}_{{ $b_key }}"
                           value="Next">
                    @endif
                           
                  </div>
                </div>
              @endif {{-- End if blocktype = practice_text_words --}}

              @if($block['type'] == 'practice_test_images')
                <div class="memory test practice-test" id="memory_{{ $key }}_{{ $b_key }}">
                  <h4>{{ $block['prompt'] }}</h4>
                  @if($user->group_role == 'leader')
                    <h4>As the leader, you can <strong>type the number '1', '2', or '3' on your keyboard</strong> to enter the group's answer</h4>
                  @endif
                  <img class="memory-img mt-lg-4 target-img-{{ $testName }}" src="{{ $test['directory'].$block['img'] }}">
                  @if($block['show_numbers'] == 'true')
                    <div class="row text-center justify-content-center">
                      <div class="col-md-3"><h2>1</h2></div>
                      <div class="col-md-3"><h2>2</h2></div>
                      <div class="col-md-3"><h2>3</h2></div>
                    </div>
                  @endif
                  <input type="hidden" 
                  name="response_{{ $key }}_{{ $b_key }}"
                         id="response_{{ $key }}_{{ $b_key }}">
                </div>
              @endif {{-- End if blocktype = practice_test_images --}}

              @if($block['type'] == 'test_images')
                <div class="memory test" id="memory_{{ $key }}_{{ $b_key }}">
                  <h4>{{ $block['prompt'] }}</h4>
                  @if($user->group_role == 'leader')
                    <h4>As the leader, you can <strong>type the number '1', '2', or '3' on your keyboard</strong> to enter the group's answer</h4>
                  @endif
                  <img class="memory-img mt-lg-4 target-img-{{ $testName }}" src="{{ $test['directory'].$block['img'] }}">
                  @if($block['show_numbers'] == 'true')
                    <div class="row text-center justify-content-center">
                      <div class="col-md-3"><h2>1</h2></div>
                      <div class="col-md-3"><h2>2</h2></div>
                      <div class="col-md-3"><h2>3</h2></div>
                    </div>
                  @endif
                  <input type="hidden" 
                  @if($user->group_role != 'leader')
                    disabled 
                  @endif
                  name="response_{{ $key }}_{{ $b_key }}"
                         id="response_{{ $key }}_{{ $b_key }}">
                </div>
              @endif {{-- End if blocktype = test_images --}}

            @endforeach {{-- End foreach block --}}
          @endif {{-- End if type = mixed --}}

        @endforeach {{-- End foreach test --}}
        <input type="hidden" name="role" id="role">
      </form>
    </div>
  </div>
</div>

<div class="modal fade" id="popup" data-backdrop="static">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h4 class="modal-title text-center" id="popup-text"></h4>
      </div>
      <div class="modal-body text-center">
          <button class="btn btn-lg btn-primary" id="popup-continue" type="button">Continue</button>
      </div>
    </div><!-- /.modal-content -->
  </div><!-- /.modal-dialog -->
</div><!-- /.modal -->

<div class="modal fade" id="waiting" data-backdrop="static">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h4 class="modal-title text-center" id="popup-text">
          Please wait for the other members of your group.
        </h4>
      </div>
    </div><!-- /.modal-content -->
  </div><!-- /.modal-dialog -->
</div><!-- /.modal -->

<div class="modal fade" id="leader_waiting" data-backdrop="static">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h4 class="modal-title text-center" id="popup-text">
          Please wait for the leader to make a selection
        </h4>
      </div>
    </div><!-- /.modal-content -->
  </div><!-- /.modal-dialog -->
</div><!-- /.modal -->

@include('layouts.includes.gather-reporter-modal')

@stop
