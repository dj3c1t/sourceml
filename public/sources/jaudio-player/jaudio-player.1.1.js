/*
  jaudioPlayer 1.1
  http://jaudio-player.dj3c1t.com/

*/
(function($){

  // -----------------------------------------------------------------------
  //                                              fonction d'appel du plugin

  $.fn.jaudioPlayer = function(param){
    if(jap_api[param]) return jap_api[param].apply(this, Array.prototype.slice.call(arguments, 1));
    if(typeof param === 'object' || !param) return jap.init.apply(this, arguments);
  };

  // -----------------------------------------------------------------------
  //                                                               attributs

  var settings        = {},
      audio_elts      = {},
      audio_elt_index = 0,
      waiting_track   = false,
      current_track   = false,
      seek_to_precent = 0;

  // -----------------------------------------------------------------------
  //                                                     fonctions publiques

  var jap_api = {

    set_option: function(option_name, value){
     settings[option_name] = value;
    },

    get_option: function(option_name){
      return settings[option_name];
    },

    resize: function(){
      jap.resize_audio_players();
    },

    play: function(audio_elt) {
      jap.play(audio_elt);
    }
  };

  // -----------------------------------------------------------------------
  //                                                      fonctions internes

  var jap = {

    init: function(options){
      settings = $.extend(
        {
          "waveform_class": "",
          "player_graphics": "jaudio-player/jap-graphics.png",
          "loading_img": "jaudio-player/jap-loading.gif",
          "auto_play_next_track": false,
          "loop": false
        },
        options
      );
      return this.each(
        function(){
          var audio_elt = $(this);
          if(audio_elt.is("audio")){
            jap.init_player(audio_elt);
          }
        }
      );
    },

    // ------------------- initialisation

    init_player: function(audio_elt){
      audio_elt_index++;
      audio_elts[audio_elt_index] = audio_elt;
      var CAN_PLAY = false;
      audio_elt.find("source").each(
        function(){
          if(audio_elt.get(0).canPlayType($(this).attr("type"))) CAN_PLAY = true;
        }
      );
      if(CAN_PLAY){
        audio_elt.wrap('<div class="audio_wrapper" id="audio_wrapper_' + audio_elt_index + '" />');
        $("#audio_wrapper_" + audio_elt_index).append(
            "<div class=\"audio_player\" id=\"audio_player_" + audio_elt_index + "\">"
          + "  <span class=\"loading glyphicon glyphicon-repeat glyphicon-rotate\"></span>"
          + "  <div class=\"player_controls\">"
          + "    <a class=\"pause\" href=\"#\"><span class=\"glyphicon glyphicon-pause\"></span></a>"
          + "    <a class=\"play\" href=\"#\"><span class=\"glyphicon glyphicon-play\"></span></a>"
          + "    <a class=\"stop\" href=\"#\"><span class=\"glyphicon glyphicon-stop\"></span></a>"
          + "  </div>"
          + "  <div class=\"player_progress\">"
          + "    <div class=\"bg\"></div>"
          + "    <div class=\"loaded\"></div>"
          + "    <div class=\"position\"></div>"
          + "  </div>"
          + "  <div class=\"time\">"
          + "    <span class=\"position\">00:00</span>"
          + "    <span class=\"duration\">00:00</span>"
          + "  </div>"
          + "</div>"
        );
        if(settings["waveform_class"].length){
          if($("#audio_wrapper_" + audio_elt_index + " ." + settings["waveform_class"]).size()){
            $("#audio_wrapper_" + audio_elt_index).addClass("with_waveform");
            $("#audio_wrapper_" + audio_elt_index + " .player_progress div").html(
              "<img src=\"" + $("#audio_wrapper_" + audio_elt_index + " ." + settings["waveform_class"]).attr("src") + "\" />"
            );
          }
        }
        audio_elt.bind('loadedmetadata', function(e){ jap.track_loadedmetadata(e); })
        audio_elt.bind('progress', function(){ jap.track_progress(); })
        audio_elt.bind('canplaythrough', function(){ jap.track_canplaythrough(); })
        audio_elt.bind('timeupdate', function(){ jap.track_timeupdate(); })
        audio_elt.bind('waiting', function(){ jap.track_waiting(); })
        audio_elt.bind('playing', function(){ jap.track_playing(); })
        audio_elt.bind('ended', function(){ jap.track_ended(); })
        $("#audio_wrapper_" + audio_elt_index + " .player_controls .play").css("display: block");
        $("#audio_wrapper_" + audio_elt_index + " .player_controls .play").click(function() { jap.play($(this)); return false; });
        $("#audio_wrapper_" + audio_elt_index + " .player_controls .pause").click(function() { jap.pause(); return false; });
        $("#audio_wrapper_" + audio_elt_index + " .player_controls .stop").click(function() { jap.stop(); return false; });
        $("#audio_wrapper_" + audio_elt_index + " .player_progress").click(function(e) { jap.seek(e); });
        jap.resize_audio_players();
      }
    },

    resize_audio_players: function(){
      $(".audio_player .player_progress").each(
        function(){
          var progress_width = $(this).width();
          $(this).find("img").css("width", progress_width);
        }
      );
    },

    // ------------------- evenements

    track_loadedmetadata: function(e){
      var current_audio = e.target;
      var minutes = "" + Math.floor(current_audio.duration / 60);
      var secondes = "" + Math.round(current_audio.duration - (minutes * 60));
      if(minutes.length == 1) minutes = "0" + minutes;
      if(secondes.length == 1) secondes = "0" + secondes;
      $(current_audio).parents(".audio_wrapper").find(".time .duration").html(minutes + ":" + secondes);
    },

    track_progress: function(){
      if(current_track != false){
        var current_audio = audio_elts[current_track].get(0);
        if(current_audio.buffered.length){
          var buffered = current_audio.buffered.end(0);
          var loaded = parseInt(((buffered / current_audio.duration) * 100), 10);
        }
        $("#audio_player_" + current_track).find(".loaded").css("width", loaded + "%");
      }
    },

    track_canplaythrough: function(){
      if(waiting_track != false){
        current_track = waiting_track;
        waiting_track = false;
        setTimeout(
          function(){
            var current_audio = audio_elts[current_track].get(0);
            current_audio.play();
            jap.gui_state("playing");
            if(seek_to_precent != 0){
              current_audio.currentTime = (current_audio.duration / 100) * seek_to_precent;
              seek_to_precent = 0;
            }
          },
          500
        );
      }
    },

    track_timeupdate: function(){
      if(current_track != false){
        var current_audio = audio_elts[current_track].get(0);
        var progress_bar = $("#audio_player_" + current_track).find(".position").get(0);
        if(current_audio && progress_bar){
          progress_bar.style.width = ((100 * current_audio.currentTime) / current_audio.duration) + "%";
          var minutes = "" + Math.floor(current_audio.currentTime / 60);
          var secondes = "" + Math.round(current_audio.currentTime - (minutes * 60));
          if(minutes.length == 1) minutes = "0" + minutes;
          if(secondes.length == 1) secondes = "0" + secondes;
          $("#audio_player_" + current_track + " .time .position").html(minutes + ":" + secondes);
          var minutes = "" + Math.floor(current_audio.duration / 60);
          var secondes = "" + Math.round(current_audio.duration - (minutes * 60));
          if(minutes.length == 1) minutes = "0" + minutes;
          if(secondes.length == 1) secondes = "0" + secondes;
          $("#audio_player_" + current_track + " .time .duration").html(minutes + ":" + secondes);
        }
      }
    },

    track_waiting: function(){
      jap.gui_state("waiting");
    },

    track_playing: function(){
      jap.gui_state("playing");
    },

    track_ended: function(){
      if(current_track != false){
        var current_audio = audio_elts[current_track].get(0);
        jap.gui_state("stoped");
        if(settings["auto_play_next_track"] || settings["loop"]) jap.play_next_track();
      }
    },

    play_next_track: function(){
      var next_track = false,
          first_track = false,
          CURRENT_FOUND = false;
      for(var track in audio_elts){
        if(!first_track) first_track = track;
        if(track == current_track) CURRENT_FOUND = true;
        else{
          if(CURRENT_FOUND){
            next_track = track;
            break;
          }
        }
      }
      if(!next_track && settings["loop"] && first_track) next_track = first_track;
      if(next_track){
        if(current_track != false) jap.stop();
        current_track = next_track;
        jap.gui_state("waiting");
        current_track = false;
        var current_audio = audio_elts[track].get(0);
        waiting_track = next_track;
        seek_to_precent = 0;
        jap.load_track(current_audio);
      }
    },

    load_track: function(audio){
      audio.preload = "auto";
      if(audio.readyState == audio.HAVE_ENOUGH_DATA) jap.track_canplaythrough();
      else audio.load();
    },

    // ------------------- boutons

    play: function(play_elt){
      var track = play_elt.parents(".audio_wrapper").attr("id").substr(14);
      if(current_track == track){
        if(audio_elts[track].get(0).paused){
          audio_elts[track].get(0).play();
        }
      }
      else{
        if(current_track != false) jap.stop();
        current_track = track;
        jap.gui_state("waiting");
        current_track = false;
        var current_audio = audio_elts[track].get(0);
        waiting_track = track;
        seek_to_precent = 0;
        jap.load_track(current_audio);
      }
    },

    seek: function(e){
      var $target = $(e.target);
      if(true || $target.is("img")){
        var audio_player_elt = $target.parents(".audio_player");
        var track = audio_player_elt.attr("id").substr(13);
        var progress_bar = audio_player_elt.find(".player_progress");
        var current_audio = audio_elts[track].get(0);
        seek_to_precent = ((e.pageX - progress_bar.offset().left) * 100) / progress_bar.width();
        if(track){
          if(current_track == false || current_track != track){
            if(current_track != false) jap.stop();
            current_track = track;
            jap.gui_state("waiting");
            current_track = false;
            waiting_track = track;
            jap.load_track(current_audio);
          }
          else{
            current_audio.currentTime = (current_audio.duration / 100) * seek_to_precent;
          }
        }
      }
    },

    pause: function(){
      if(current_track != false){
        audio_elts[current_track].get(0).pause();
        jap.gui_state("paused");
      }
    },
    
    stop: function(){
      if(current_track != false){
        var current_audio = audio_elts[current_track].get(0);
        current_audio.pause();
        if(current_audio.currentTime) current_audio.currentTime = 0;
        jap.gui_state("stoped");
        current_track = false;
      }
    },

    // ------------------- interface

    gui_state: function(state){
      jap.gui_blur();
      if(state == "waiting"){
        $("#audio_player_" + current_track).find(".play").hide();
        $("#audio_player_" + current_track).find(".pause").hide();
        $("#audio_player_" + current_track).find(".stop").hide();
        $("#audio_player_" + current_track).find(".loading").show();
      }
      if(state == "playing"){
        $("#audio_player_" + current_track).find(".loading").hide();
        $("#audio_player_" + current_track).find(".play").hide();
        $("#audio_player_" + current_track).find(".pause").show();
        $("#audio_player_" + current_track).find(".stop").show();
      }
      else if(state == "paused"){
        $("#audio_player_" + current_track).find(".loading").hide();
        $("#audio_player_" + current_track).find(".play").show();
        $("#audio_player_" + current_track).find(".pause").hide();
        $("#audio_player_" + current_track).find(".stop").show();
      }
      else if(state == "stoped"){
        $("#audio_player_" + current_track).find(".loading").hide();
        $("#audio_player_" + current_track).find(".play").show();
        $("#audio_player_" + current_track).find(".pause").hide();
        $("#audio_player_" + current_track).find(".stop").hide();
        $("#audio_player_" + current_track).find(".position").css("width", "0%");
        $("#audio_player_" + current_track).find(".time .position").html("00:00");
      }
    },
    
    gui_blur: function(){
      if(current_track != false){
        $("#audio_player_" + current_track).find(".play").get(0).blur();
        $("#audio_player_" + current_track).find(".pause").get(0).blur();
        $("#audio_player_" + current_track).find(".stop").get(0).blur();
      }
    }

  };

})(jQuery);