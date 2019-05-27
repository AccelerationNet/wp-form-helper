if(typeof(WPFH) == 'undefined') WPFH = {};
(function($){
  "use strict";
  WPFH.uploading =0;
  WPFH.onDocumentUpload = null;
  WPFH.ajaxUploadReplacer = function(el){
    el =$(el);
    var btn = el.find('.upload-btn');
    var name = btn.data('name');
    var upload = new ss.SimpleUpload({
      button: btn,
      name:'file',
      data: $(btn).data(),
      url: WPFH_CFG.ajax_url+'?action=wpfh_file_upload',
      autoSubmit:true,
      onComplete: function (file, res, btn, size){
        res = JSON.parse(res);
        var nonce = res.nonce;
        $(btn).data('nonce', nonce);
        var url = res.file_url;
        WPFH.uploading--;
        if(WPFH.onDocumentUpload){
          var cont = WPFH.onDocumentUpload(url);
          if(!cont) return;
        }
        //console.log('uploaded file', res, url);
        var hidden = $('<input type=hidden />').attr('name', name).val(url);
        var img = $('<img />').attr('src', url);
        var newcontent = $('<div>').addClass('uploaded-file')
            .append([hidden, img]);
        el.find('.uploaded-file').detach();
        el.append(newcontent);
      }
    });
    //console.log(upload);
  };
  $(function(){
    //console.log('starting');
    $("#loading").ajaxStart(function(){
      $(this).show();
    }).ajaxComplete(function(){
      $(this).hide();
    });
    $('.wpfh-ajax-upload').each(function(k, v) {
      WPFH.ajaxUploadReplacer(v);
    });
  });
  //console.log('defined');
})(jQuery);
