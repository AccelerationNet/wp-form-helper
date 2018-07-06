if(typeof(WPFH) == 'undefined') WPFH = {};
console.log('Loading wpfh');
WPFH.bind  = function bind(el, o){
  jQuery.each(o, function(k, v){
    // console.log('Binding ', k, v, o, el);
    var inps = jQuery('[name='+k+']', el).each(function(i, inp){
      inp = jQuery(inp);
      if (inp.is('[type=radio],[type=checkbox]')){
        inp.prop('checked', inp.val() == v);
      }
      else if (inp.is(':input')) inp.val(v);
      else if (inp.is('a') && k == "email") inp.attr('href', 'mailto:'+v);
      else inp.text(v);
    });
  });
};

WPFH.addObjectVal = function (o, k, v){
  if(o[k]){
    if(Array.isArray(o[k])){ o[k].push(v); }
    else{ o[k]= [o[k], v]; }
  }
  else{ o[k] = v; }
};

WPFH.setObjectVals = function setObjectVals(el, o){
  // console.log('Binding ', k, v, o, el);
  var inps = jQuery(':input[name]', el).each(function(i, inp){
    inp = jQuery(inp);
    var name = inp.attr('name').trim();
    if(inp.is('[type=checkbox],[type=radio]')){
      if(inp.is(':checked')){
        WPFH.addObjectVal(o, name, inp.val());
      }
    }else{
      WPFH.addObjectVal(o, name, inp.val());
    }
  });
};

WPFH.serializeForm = function(el){
  var rtn = {};
  WPFH.setObjectVals(el, rtn);
  return rtn;
};
