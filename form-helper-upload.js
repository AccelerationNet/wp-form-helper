if(window.WPFH == undefined) window.WPFH={};
(function($){
  "use strict";
  WPFH.uploading =0;
  WPFH.submitHandler = function(evt){
    if(evt.preventDefault)evt.preventDefault();
    var form = $(evt.target);
    form.find('.wpfh-ajax-upload').each(function(k, v) {
      WPFH.uploading++;
      WPFH.ajaxUploadReplace(form, v);
    });
    return false;
  };
  WPFH.ajaxUploadReplace = function(form, el){
    el =$(el);form = $(form);
    var name = el.attr('name');
    var file_data = el.find("input[type=file]").val();
    var form_data = {action:'wpfh_file_upload', file:file_data};
    //var fd = new FormData(form_data);
    console.log(form_data);
    return $.post(WPFH_CFG.ajax_url, form_data)
      .success(function (res){
        console.log('uploaded file', res);
        var hidden = $('<input type=hidden />').attr('name', name).val(res.url);
        var img = $('<img />').attr('src', res.url);
        var newcontent = $('<div>').addClass('uploaded-file')
            .append([hidden, img]);
        el.empty().append(newcontent);
        WPFH.uploading--;
        if(WPFH.uploading==0){
          form.off('submit', WPFH.submitHandler);
          form.submit();
        }
      });
  };
  jQuery.ready(function(){
    console.log('starting');
    $("#loading").ajaxStart(function(){
      $(this).show();
    }).ajaxComplete(function(){
      $(this).hide();
    });
    $('form.wp-form-helper').on('submit', WPFH.submitHandler);
  });
})(jQuery);
