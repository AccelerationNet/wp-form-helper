if(typeof(WPFH) == 'undefined') WPFH = {};
console.log('Loading wpfh');

WPFH.trimAndNullify = function trimAndNullify(it) {
  if(!it) return null;
  it = it.toString().trim();
  if(it.length == 0) return null;
  return it;
};


WPFH.debounce = function(func, wait, immediate) {
    var timeout;
    return function() {
      var context = this, args = arguments;
      var later = function() {
        timeout = null;
        if (!immediate) func.apply(context, args);
      };
      if (immediate && !timeout) func.apply(context, args);
      clearTimeout(timeout);
      timeout = setTimeout(later, wait);
    };
};

WPFH.throttle = function throttle (func, wait) {
    var context, args, timeout, throttling, more, result;
    var whenDone = WPFH.debounce(function(){ more = throttling = false; }, wait);
    return function() {
      context = this; args = arguments;
      var later = function() {
        timeout = null;
        if (more) func.apply(context, args);
        whenDone();
      };
      if (!timeout) timeout = setTimeout(later, wait);
      if (throttling) {
        more = true;
      } else {
        result = func.apply(context, args);
      }
      whenDone();
      throttling = true;
      return result;
    };
};

WPFH.toCurrency = function toCurrency(it){
  return Number(it).toLocaleString('en-US', {style: 'currency', currency: 'USD'});
}

WPFH.fromCurrency = function fromCurrency(it){
  if(typeof(it) == "number") return it;
  return Number(it.toString().replace(/,|\$|\s/ig, ''));
}

WPFH.prefixRemover = function prefixRemover(prefix){
  var prefixRe = new RegExp('^'+prefix);
  return function deprefixed(k){
    return k.replace(prefixRe, '');
  }
}

WPFH.bind  = function bind(el, o, keyMod){
  var el = jQuery(el);
  jQuery.each(o, function(k, v){

    if(keyMod){k = keyMod(k); }
    // console.log('Binding ', k, v, o, el);
    if(!k || k.indexOf('$')==0) return true;
    var inps = jQuery('[name='+k+'],.'+k, el).each(function(i, inp){
      inp = jQuery(inp);
      if (inp.is('[type=radio]')){
        inp.prop('checked', inp.val() == v);
      }
      else if (inp.is(':input')){
        if(inp.hasClass('currency')) inp.val(Number(v).toFixed(2));
        else inp.val(v);
      }
      else if (inp.is('a') && k == "email") inp.attr('href', 'mailto:'+v);
      else if (inp.is('span')){
        if(inp.hasClass('currency'))inp.text(WPFH.toCurrency(v));
        else if (inp.hasClass('as-html')) inp.html(v);
        else inp.text(v);
      }
    });
  });
};

WPFH.addObjectVal = function (o, k, v){
  v = WPFH.trimAndNullify(v);
  if(WPFH._serializePrefix) k = WPFH._serializePrefix + k;
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
    if(inp.is('[type=checkbox]')){
      var val = inp.is(':checked') ? inp.val() : null;
      WPFH.addObjectVal(o, name, val);
    }
    else if(inp.is('[type=radio]')){
      if(inp.is(':checked')){
        WPFH.addObjectVal(o, name, inp.val());
      }
    }
    else{
      WPFH.addObjectVal(o, name, inp.val());
    }
  });
};

WPFH._serializePrefix = null;
WPFH.serialize = WPFH.serializeForm = function(el, prefix){
  var rtn = {}, before = WPFH._serializePrefix;
  el = jQuery(el);
  try{
    WPFH._serializePrefix = prefix;
    WPFH.setObjectVals(el, rtn);
    return rtn;
  }finally{
    WPFH._serializePrefix = before;
  }
};

WPFH._plus = new RegExp('\\+','g');
WPFH.parseQuery = function(query) {
  if(!query) query = window.location.search.substring(1);
  var obj = {};
  var vars = query.split('&');
  for (var i = 0; i < vars.length; i++) {
    var pair = vars[i].split('=');
    var k = decodeURIComponent(pair[0]).replace(WPFH._plus, " ");
    var v = decodeURIComponent(pair[1]).replace(WPFH._plus, " ");
    if(obj[k]){
      if (angular.isArray(obj[k])) obj[k].push(v);
      else obj[k]= [obj[k],v];
    }
    else obj[k]=v;
  }
  return obj;
};

WPFH.query = WPFH.parseQuery();
WPFH.hashQuery = WPFH.parseQuery(window.location.hash.substring(1));
